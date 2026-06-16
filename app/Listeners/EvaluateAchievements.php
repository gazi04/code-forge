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

        // Skip metric groups the triggering event cannot affect. Scalar XP/coin/level/streak
        // metrics change on any processVictory (both sources award XP+coins and run streak
        // logic), so they stay relevant to both. A null source evaluates everything.
        $relevant = match ($event->source) {
            'block' => ['total_xp_earned', 'level_reached', 'daily_streak_count',
                'total_coins_earned', 'total_blocks_completed',
                'specific_block_type_completed'],
            'lesson' => ['total_xp_earned', 'level_reached', 'daily_streak_count',
                'total_coins_earned', 'total_lessons_completed',
                'specific_course_completed'],
            default => null,
        };

        if ($relevant !== null) {
            // Note: $grouped is an Eloquent collection, whose only() filters by model key.
            // Filter on the metric_type group key instead.
            $grouped = $grouped->filter(fn ($achievements, string $metricType): bool => in_array($metricType, $relevant, true));
        }

        $newlyUnlocked = collect();

        foreach ($grouped as $metricType => $achievements) {
            $metricValue = $this->resolveMetric($user, $metricType, $achievements);

            foreach ($achievements as $achievement) {
                $value = is_array($metricValue)
                    ? ($metricValue[$achievement->target_id] ?? 0)
                    : $metricValue;

                if ($value >= $achievement->threshold) {
                    // Idempotent: the pivot PK (user_id, achievement_id) blocks duplicates;
                    // syncWithoutDetaching avoids throwing if a concurrent evaluation already inserted it.
                    $user->achievements()->syncWithoutDetaching([$achievement->id => ['unlocked_at' => now()]]);
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
            'total_coins_earned' => $user->total_coins_earned,

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
        $courseIds = $achievements->pluck('target_id')->filter()->unique();

        if ($courseIds->isEmpty()) {
            return [];
        }

        // Two grouped queries for all target courses at once (portable selectRaw + groupBy),
        // instead of three queries per achievement.
        $totals = Lesson::whereIn('course_id', $courseIds)
            ->selectRaw('course_id, COUNT(*) as c')
            ->groupBy('course_id')
            ->pluck('c', 'course_id');

        $completed = LessonSubmission::query()
            ->where('lesson_submissions.user_id', $user->id)
            ->join('lessons', 'lessons.id', '=', 'lesson_submissions.lesson_id')
            ->whereIn('lessons.course_id', $courseIds)
            ->selectRaw('lessons.course_id as course_id, COUNT(*) as c')
            ->groupBy('lessons.course_id')
            ->pluck('c', 'course_id');

        $result = [];
        foreach ($courseIds as $courseId) {
            $total = (int) ($totals[$courseId] ?? 0);
            $result[$courseId] = $total > 0 && (int) ($completed[$courseId] ?? 0) >= $total ? 1 : 0;
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
        $targetTypes = $achievements->pluck('target_id')->filter()->unique();

        if ($targetTypes->isEmpty()) {
            return [];
        }

        $submissions = BlockSubmission::where('user_id', $user->id)->get(['lesson_id', 'block_index']);

        if ($submissions->isEmpty()) {
            return $targetTypes->mapWithKeys(fn ($type): array => [$type => 0])->all();
        }

        // Resolve each submission's block type from the lesson's JSON payload in PHP,
        // so counting is driver-agnostic (no SQLite-only json_extract path expression).
        $lessons = Lesson::whereIn('id', $submissions->pluck('lesson_id')->unique())
            ->get(['id', 'blocks'])
            ->keyBy('id');

        $counts = [];
        foreach ($submissions as $submission) {
            $blocks = $lessons->get($submission->lesson_id)?->blocks ?? [];
            $type = $blocks[$submission->block_index]['type'] ?? null;

            if ($type !== null) {
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }

        return $targetTypes->mapWithKeys(fn ($type): array => [$type => $counts[$type] ?? 0])->all();
    }
}
