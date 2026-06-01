<?php

namespace App\Http\Controllers;

use App\Http\Resources\LessonResource;
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
        $lesson->load('course.world.themePack');
        $course = $lesson->course;

        $previousLesson = Lesson::where('course_id', $course->id)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        $nextLesson = Lesson::where('course_id', $course->id)
            ->where('sort_order', '>', $lesson->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        return Inertia::render('Student/LessonView', [
            'lesson' => new LessonResource($lesson),
            'theme' => $course->world->themePack,
            'course_slug' => $course->slug,
            'previous_lesson_slug' => $previousLesson?->slug,
            'next_lesson_slug' => $nextLesson?->slug,
        ]);
    }

    public function submitClaim(Request $request, Lesson $lesson)
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Gating Check: Ensure the user's level is high enough for this sector
        if ($user->level < $lesson->course->min_level_requirement) {
            return back()->withErrors([
                'error' => "Your current level ({$user->level}) is insufficient to unlock this sector.",
            ]);
        }

        // 2. Anti-Cheat: Prevent harvesting duplicate XP for the same lesson
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

        // 3. Process the math and rewards via the engine
        $result = $this->progressionService->processVictory(
            $user,
            $lesson->xp_reward,
            $lesson->coin_reward
        );

        // 4. Record the ledger entry so they can't farm it again
        LessonSubmission::create([
            'user_id' => $user->id,
            'course_id' => $lesson->course_id,
            'lesson_id' => $lesson->slug,
            'xp_rewarded' => $result['total_xp_earned'],
            'coins_rewarded' => $result['coins_earned'],
        ]);

        // 5. Flash the result payload to the session for Svelte to intercept
        return back()->with('game_result', $result);
    }
}
