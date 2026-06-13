<?php

namespace App\Http\Controllers;

use App\Events\ProgressRegistered;
use App\Events\WorldCompleted;
use App\Http\Resources\LessonResource;
use App\Models\BlockSubmission;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LessonController extends Controller
{
    protected ProgressionService $progressionService;

    public function __construct(ProgressionService $progressionService)
    {
        $this->progressionService = $progressionService;
    }

    public function show(Lesson $lesson)
    {
        $user = Auth::user();
        $lesson->load('course.world.themePack');
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
            ->where('lesson_id', $lesson->slug)
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

        if ($user->level < $lesson->course->min_level_requirement) {
            return back()->withErrors([
                'error' => "Your current level ({$user->level}) is insufficient to unlock this sector.",
            ]);
        }

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

        $alreadySubmitted = LessonSubmission::where('user_id', $user->id)
            ->where('lesson_id', $lesson->slug)
            ->exists();

        if ($alreadySubmitted) {
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

        $result = $this->progressionService->processVictory(
            $user,
            $lesson->xp_reward,
            $lesson->coin_reward
        );

        LessonSubmission::create([
            'user_id' => $user->id,
            'course_id' => $lesson->course_id,
            'lesson_id' => $lesson->slug,
            'xp_rewarded' => $result['total_xp_earned'],
            'coins_rewarded' => $result['coins_earned'],
        ]);

        ProgressRegistered::dispatch($user);

        $this->checkWorldCompletion($user, $lesson);

        // 5. Flash the result payload to the session for Svelte to intercept
        return back()->with('game_result', $result);
    }

    private function checkWorldCompletion(User $user, Lesson $lesson): void
    {
        $lesson->loadMissing('course.world');
        $world = $lesson->course->world;

        if ($user->worldCompletions()->where('world_id', $world->id)->exists()) {
            return;
        }

        $lessonSlugs = Lesson::whereHas('course', fn ($q) => $q->where('world_id', $world->id))
            ->pluck('slug');

        if ($lessonSlugs->isEmpty()) {
            return;
        }

        $completedCount = LessonSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonSlugs)
            ->count();

        if ($completedCount >= $lessonSlugs->count()) {
            WorldCompleted::dispatch($user, $world);
        }
    }

    public function submitBlockClaim(Request $request, Lesson $lesson, int $blockIndex)
    {
        $user = Auth::user();

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

        // 2. Dynamic Rewards: Extract how much this specific block is worth.
        // If your JSON blocks have an explicit 'xp_reward' set, use it. Otherwise, give a standard micro-reward.
        $blockData = $blocks[$blockIndex]['data'] ?? [];

        $xpReward = $blockData['xp_reward'] ?? 15; // 15 XP default for mini-tasks
        $coinReward = $blockData['coin_reward'] ?? 5; // 5 Coins default
        $blockTitle = $blockData['game_title'] ?? null;

        // 3. Engine Processing: Run the math
        $result = $this->progressionService->processVictory(
            $user,
            $xpReward,
            $coinReward
        );

        // 4. Ledger Record: Save it so they can't farm it
        BlockSubmission::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'block_index' => $blockIndex,
            'block_title' => $blockTitle,
            'xp_rewarded' => $result['total_xp_earned'],
            'coins_rewarded' => $result['coins_earned'],
        ]);

        ProgressRegistered::dispatch($user);

        // 5. Intercept & Celebrate:
        // Flashing this data means if this 15 XP pushes them over the edge,
        // your layout will pause the lesson, fire confetti, and show the Level Up modal!
        return back()->with('game_result', $result);
    }
}
