<?php

namespace App\Http\Controllers;

use App\Events\ProgressRegistered;
use App\Events\WorldCompleted;
use App\Http\Resources\LessonResource;
use App\Models\BlockSubmission;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\User;
use App\Services\BlockValidator;
use App\Services\ProgressionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LessonController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProgressionService $progressionService,
        protected BlockValidator $blockValidator,
    ) {}

    public function show(Lesson $lesson)
    {
        $user = Auth::user();
        $lesson->load('course.world.themePack');
        $course = $lesson->course;

        $this->authorize('view', $course);

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

        return Inertia::render('Student/LessonView', [
            'lesson' => new LessonResource($lesson),
            'theme' => $course->world->themePack,
            'course_slug' => $course->slug,
            'previous_lesson_slug' => $previousLesson?->slug,
            'next_lesson_slug' => $nextLesson?->slug,
            'cleared_block_indices' => $clearedBlockIndices,
            'is_completed' => $isCompleted,
        ]);
    }

    public function submitClaim(Request $request, Lesson $lesson)
    {
        /** @var User $user */
        $user = Auth::user();

        // Same gate as show()/submitBlockClaim(): role + publish + world publish
        // + level + prerequisite via CoursePolicy. Closes the unpublished-course
        // reward path on the highest-value (lesson-completion) endpoint.
        $this->authorize('view', $lesson->course);

        $requiredBlockIndices = collect($lesson->blocks ?? [])
            ->filter(fn (array $block): bool => ($block['data']['is_required'] ?? false) === true)
            ->keys();

        if ($requiredBlockIndices->isNotEmpty()) {
            $clearedBlockIndices = BlockSubmission::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->pluck('block_index');

            if ($requiredBlockIndices->diff($clearedBlockIndices)->isNotEmpty()) {
                return back()->withErrors([
                    'error' => 'You must complete all mandatory encounters before advancing.',
                ]);
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
            return back()->with([
                'game_result' => [
                    'status' => 'already_completed',
                    'base_xp' => 0,
                    'total_xp_earned' => 0,
                    'coins_earned' => 0,
                    'leveled_up' => false,
                    'new_level' => $user->level,
                    'streak_count' => $user->streak_count,
                ],
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

        ProgressRegistered::dispatch($user, 'lesson');

        $this->checkWorldCompletion($user, $lesson);

        // The synchronous world-completion bonus (HandleWorldCompletion) may award XP
        // after $result was computed, crossing a level boundary the lesson reward alone
        // didn't. Re-read the final level so a bonus-driven level-up still fires the
        // level-up modal/confetti (which the layout keys off game_result).
        $user->refresh();
        $result['leveled_up'] = $user->level > $levelBefore;
        $result['new_level'] = $user->level;

        // Flash the result payload to the session for Svelte to intercept
        return back()->with('game_result', $result);
    }

    private function checkWorldCompletion(User $user, Lesson $lesson): void
    {
        $lesson->loadMissing('course.world');
        $world = $lesson->course->world;

        if ($user->worldCompletions()->where('world_id', $world->id)->exists()) {
            return;
        }

        $lessonIds = Lesson::whereHas('course', fn ($q) => $q->where('world_id', $world->id))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return;
        }

        $completedCount = LessonSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        if ($completedCount >= $lessonIds->count()) {
            WorldCompleted::dispatch($user, $world);
        }
    }

    public function submitBlockClaim(Request $request, Lesson $lesson, int $blockIndex)
    {
        $user = Auth::user();

        $this->authorize('view', $lesson->course);

        $blocks = $lesson->blocks ?? [];

        if ($blockIndex < 0 || $blockIndex >= count($blocks)) {
            abort(404);
        }

        // 1. Anti-Cheat: Did they already get the reward for this specific quiz/challenge?
        $alreadySubmitted = BlockSubmission::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('block_index', $blockIndex)
            ->exists();

        if ($alreadySubmitted) {
            // Return silently with an already_completed status so the frontend knows to unlock the next step without giving double XP
            return back()->with([
                'game_result' => [
                    'status' => 'already_completed',
                    'leveled_up' => false,
                ],
            ]);
        }

        // 2. Anti-Cheat: Verify the submitted answer server-side before awarding.
        // Answer keys are stripped from the client payload, so correctness can only
        // be confirmed here against the authoritative block data.
        if (! $this->blockValidator->isCorrect($blocks[$blockIndex], $request->input('answer'))) {
            return back()->with('game_result', [
                'status' => 'incorrect',
                'leveled_up' => false,
            ]);
        }

        // 3. Dynamic Rewards: Extract how much this specific block is worth.
        // If your JSON blocks have an explicit 'xp_reward' set, use it. Otherwise, give a standard micro-reward.
        $blockData = $blocks[$blockIndex]['data'] ?? [];

        $xpReward = $blockData['xp_reward'] ?? 15; // 15 XP default for mini-tasks
        $coinReward = $blockData['coin_reward'] ?? 5; // 5 Coins default
        $blockTitle = $blockData['game_title'] ?? null;

        // 4. Atomic anti-double-reward gate: the unique (user_id, lesson_id, block_index)
        // index is the source of truth. `createOrFirst` returns the existing row on a
        // concurrent insert, so only the genuine first claim awards XP/coins.
        $submission = BlockSubmission::createOrFirst(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id, 'block_index' => $blockIndex],
            ['block_title' => $blockTitle, 'xp_rewarded' => 0, 'coins_rewarded' => 0],
        );

        if (! $submission->wasRecentlyCreated) {
            return back()->with([
                'game_result' => [
                    'status' => 'already_completed',
                    'leveled_up' => false,
                ],
            ]);
        }

        // 5. Engine Processing: run the math and persist the rewards on the gate row.
        $result = DB::transaction(function () use ($user, $xpReward, $coinReward, $submission) {
            $result = $this->progressionService->processVictory(
                $user,
                $xpReward,
                $coinReward
            );

            $submission->update([
                'xp_rewarded' => $result['total_xp_earned'],
                'coins_rewarded' => $result['coins_earned'],
            ]);

            return $result;
        });

        ProgressRegistered::dispatch($user, 'block');

        // 6. Intercept & Celebrate:
        // Flashing this data means if this 15 XP pushes them over the edge,
        // your layout will pause the lesson, fire confetti, and show the Level Up modal!
        return back()->with('game_result', $result);
    }
}
