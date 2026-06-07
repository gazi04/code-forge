<?php

namespace App\Listeners;

use App\Events\ProgressRegistered;
use App\Models\Achievement;
use App\Models\BlockSubmission;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;

class EvaluateAchievements implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle(ProgressRegistered $event): void
    {
        $user = $event->user;

        $earnedIds = $user->achievements()->pluck('achievement_id');

        $pending = Achievement::whereNotIn('id', $earnedIds)->get();

        if ($pending->isEmpty()) {
            return;
        }

        $grouped = $pending->groupBy('metric_type');

        $newlyUnlocked = collect();

        foreach ($grouped as $metricType => $achievements) {
            $metricValue = $this->resolveMetric($user, $metricType, $achievements);

            foreach ($achievements as $achievement) {
                $value = is_array($metricValue)
                    ? ($metricValue[$achievement->target_id] ?? 0)
                    : $metricValue;

                if ($value >= $achievement->threshold) {
                    $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
                    $newlyUnlocked->push($achievement);
                }
            }
        }

        if ($newlyUnlocked->isNotEmpty()) {
            $payload = $newlyUnlocked->map(fn (Achievement $a): array => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'image_path' => $a->image_path,
            ])->all();

            $user->update([
                'pending_achievements' => array_merge($user->pending_achievements ?? [], $payload),
            ]);
        }
    }

    /**
     * Resolve the current metric value(s) for the user.
     * Returns a scalar for simple metrics, or a keyed array for target-based metrics.
     */
    private function resolveMetric(mixed $user, string $metricType, Collection $achievements): mixed
    {
        return match ($metricType) {
            'total_xp_earned' => $user->xp,
            'level_reached' => $user->level,
            'daily_streak_count' => $user->streak_count,
            'total_coins_earned' => $user->coins,

            'total_lessons_completed' => LessonSubmission::where('user_id', $user->id)->count(),

            'specific_course_completed' => $this->resolveSpecificCourseCompleted($user, $achievements),

            'total_blocks_completed' => BlockSubmission::where('user_id', $user->id)->count(),

            'specific_block_type_completed' => $this->resolveSpecificBlockTypeCompleted($user, $achievements),

            default => 0,
        };
    }

    /**
     * Returns a keyed array of [course_id => boolean (0 or 1)] indicating full course completion.
     *
     * @return array<string, int>
     */
    private function resolveSpecificCourseCompleted(mixed $user, Collection $achievements): array
    {
        $result = [];

        foreach ($achievements as $achievement) {
            $courseId = $achievement->target_id;
            if (! $courseId) {
                continue;
            }

            $totalLessons = Lesson::where('course_id', $courseId)->count();

            if ($totalLessons === 0) {
                $result[$courseId] = 0;

                continue;
            }

            $lessonSlugs = Lesson::where('course_id', $courseId)->pluck('slug');
            $completedCount = LessonSubmission::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonSlugs)
                ->count();

            $result[$courseId] = $completedCount >= $totalLessons ? 1 : 0;
        }

        return $result;
    }

    /**
     * Returns a keyed array of [block_type => count] for completed block types.
     *
     * @return array<string, int>
     */
    private function resolveSpecificBlockTypeCompleted(mixed $user, Collection $achievements): array
    {
        $result = [];
        $targetTypes = $achievements->pluck('target_id')->filter()->unique();

        foreach ($targetTypes as $blockType) {
            // Cross-reference block_submissions with the lesson JSON payload to count by block type
            $count = BlockSubmission::where('user_id', $user->id)
                ->join('lessons', 'block_submissions.lesson_id', '=', 'lessons.id')
                ->whereRaw(
                    "json_extract(lessons.blocks, '$[' || block_submissions.block_index || '].type') = ?",
                    [$blockType]
                )
                ->count();

            $result[$blockType] = $count;
        }

        return $result;
    }
}
