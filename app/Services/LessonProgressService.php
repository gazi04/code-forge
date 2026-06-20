<?php

namespace App\Services;

use App\Models\BlockSubmission;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\User;
use App\Models\World;
use App\Support\BlockResult;
use App\Support\LessonResult;
use Illuminate\Support\Facades\DB;

class LessonProgressService
{
    public function __construct(
        protected ProgressionService $progressionService,
        protected BlockValidator $blockValidator,
    ) {}

    /**
     * Lookups backing the lesson view: cleared blocks, sibling navigation, completion state.
     *
     * @return array{cleared_block_indices: list<int>, previous_lesson_slug: ?string, next_lesson_slug: ?string, is_completed: bool}
     */
    public function getLessonViewData(User $user, Lesson $lesson): array
    {
        $course = $lesson->course;

        $clearedBlockIndices = BlockSubmission::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->pluck('block_index')
            ->toArray();

        $previousLesson = Lesson::where('course_id', $course->id)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        $nextLesson = Lesson::where('course_id', $course->id)
            ->where('sort_order', '>', $lesson->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        $isCompleted = LessonSubmission::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->exists();

        return [
            'cleared_block_indices' => $clearedBlockIndices,
            'previous_lesson_slug' => $previousLesson?->slug,
            'next_lesson_slug' => $nextLesson?->slug,
            'is_completed' => $isCompleted,
        ];
    }

    /**
     * Award a lesson completion. Enforces the required-block gate and the atomic
     * anti-double-reward unique index. Event dispatch and world-completion are
     * orchestrated by the caller.
     */
    public function submitLesson(User $user, Lesson $lesson): LessonResult
    {
        $requiredBlockIndices = collect($lesson->blocks ?? [])
            ->filter(fn (array $block): bool => ($block['data']['is_required'] ?? false) === true)
            ->keys();

        if ($requiredBlockIndices->isNotEmpty()) {
            $clearedBlockIndices = BlockSubmission::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->pluck('block_index');

            if ($requiredBlockIndices->diff($clearedBlockIndices)->isNotEmpty()) {
                return new LessonResult('requirements_not_met');
            }
        }

        // Atomic anti-double-reward gate: the unique (user_id, lesson_id) index is the
        // single source of truth. `createOrFirst` inserts the row in its own transaction
        // and, on a concurrent unique-constraint violation, returns the existing row —
        // so only the request that actually inserts proceeds to award XP.
        $submission = LessonSubmission::createOrFirst(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['course_id' => $lesson->course_id, 'xp_rewarded' => 0, 'coins_rewarded' => 0],
        );

        if (! $submission->wasRecentlyCreated) {
            return new LessonResult('already_completed', [
                'status' => 'already_completed',
                'base_xp' => 0,
                'total_xp_earned' => 0,
                'coins_earned' => 0,
                'leveled_up' => false,
                'new_level' => $user->level,
                'streak_count' => $user->streak_count,
            ]);
        }

        $levelBefore = $user->level;

        $result = DB::transaction(function () use ($user, $lesson, $submission) {
            $result = $this->progressionService->processVictory(
                $user,
                $lesson->xp_reward,
                $lesson->coin_reward
            );

            $submission->update([
                'xp_rewarded' => $result['total_xp_earned'],
                'coins_rewarded' => $result['coins_earned'],
            ]);

            return $result;
        });

        return new LessonResult('success', $result, $levelBefore);
    }

    /**
     * Returns the World this submission just finished (and that the user has not
     * already completed), or null. Pure query — the caller dispatches the event.
     */
    public function findWorldToComplete(User $user, Lesson $lesson): ?World
    {
        $lesson->loadMissing('course.world');
        $world = $lesson->course->world;

        if ($user->worldCompletions()->where('world_id', $world->id)->exists()) {
            return null;
        }

        $lessonIds = Lesson::whereHas('course', fn ($q) => $q->where('world_id', $world->id))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return null;
        }

        $completedCount = LessonSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        return $completedCount >= $lessonIds->count() ? $world : null;
    }

    /**
     * Validate and award a single block claim. Returns a typed status; reward
     * dispatch is left to the caller.
     */
    public function claimBlock(User $user, Lesson $lesson, int $blockIndex, mixed $answer): BlockResult
    {
        $blocks = $lesson->blocks ?? [];

        if ($blockIndex < 0 || $blockIndex >= count($blocks)) {
            return new BlockResult('out_of_bounds');
        }

        // 1. Anti-Cheat: Did they already get the reward for this specific quiz/challenge?
        $alreadySubmitted = BlockSubmission::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('block_index', $blockIndex)
            ->exists();

        if ($alreadySubmitted) {
            // Return silently with an already_completed status so the frontend knows to unlock the next step without giving double XP
            return new BlockResult('already_completed', [
                'status' => 'already_completed',
                'leveled_up' => false,
            ]);
        }

        // 2. Anti-Cheat: Verify the submitted answer server-side before awarding.
        // Answer keys are stripped from the client payload, so correctness can only
        // be confirmed here against the authoritative block data.
        if (! $this->blockValidator->isCorrect($blocks[$blockIndex], $answer)) {
            return new BlockResult('incorrect', [
                'status' => 'incorrect',
                'leveled_up' => false,
            ]);
        }

        // 3. Dynamic Rewards: Extract how much this specific block is worth.
        $reward = $this->resolveBlockReward($blocks[$blockIndex]);

        // 4. Atomic anti-double-reward gate: the unique (user_id, lesson_id, block_index)
        // index is the source of truth. `createOrFirst` returns the existing row on a
        // concurrent insert, so only the genuine first claim awards XP/coins.
        $submission = BlockSubmission::createOrFirst(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id, 'block_index' => $blockIndex],
            ['block_title' => $reward['title'], 'xp_rewarded' => 0, 'coins_rewarded' => 0],
        );

        if (! $submission->wasRecentlyCreated) {
            return new BlockResult('already_completed', [
                'status' => 'already_completed',
                'leveled_up' => false,
            ]);
        }

        // 5. Engine Processing: run the math and persist the rewards on the gate row.
        $result = DB::transaction(function () use ($user, $reward, $submission) {
            $result = $this->progressionService->processVictory(
                $user,
                $reward['xp'],
                $reward['coins']
            );

            $submission->update([
                'xp_rewarded' => $result['total_xp_earned'],
                'coins_rewarded' => $result['coins_earned'],
            ]);

            return $result;
        });

        return new BlockResult('success', $result);
    }

    /**
     * Reward defaults for a block: explicit JSON values, else a standard micro-reward.
     *
     * @param  array<string, mixed>  $block
     * @return array{xp: int, coins: int, title: ?string}
     */
    private function resolveBlockReward(array $block): array
    {
        $data = $block['data'] ?? [];

        return [
            'xp' => $data['xp_reward'] ?? 15,    // 15 XP default for mini-tasks
            'coins' => $data['coin_reward'] ?? 5, // 5 Coins default
            'title' => $data['game_title'] ?? null,
        ];
    }
}
