<?php

namespace App\Http\Controllers;

use App\Events\ProgressRegistered;
use App\Events\WorldCompleted;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonProgressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LessonController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected LessonProgressService $lessonProgress,
    ) {}

    public function show(Lesson $lesson)
    {
        $user = Auth::user();
        $lesson->load('course.world.themePack');
        $course = $lesson->course;

        $this->authorize('view', $course);

        return Inertia::render('Student/LessonView', [
            'lesson' => new LessonResource($lesson),
            'theme' => $course->world->themePack,
            'course_slug' => $course->slug,
            ...$this->lessonProgress->getLessonViewData($user, $lesson),
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

        $result = $this->lessonProgress->submitLesson($user, $lesson);

        if ($result->status === 'requirements_not_met') {
            return back()->withErrors([
                'error' => 'You must complete all mandatory encounters before advancing.',
            ]);
        }

        if ($result->status === 'already_completed') {
            return back()->with('game_result', $result->gameResult);
        }

        ProgressRegistered::dispatch($user, 'lesson');

        if ($world = $this->lessonProgress->findWorldToComplete($user, $lesson)) {
            WorldCompleted::dispatch($user, $world);
        }

        // The synchronous world-completion bonus (HandleWorldCompletion) may award XP
        // after the lesson result was computed, crossing a level boundary the lesson
        // reward alone didn't. Re-read the final level so a bonus-driven level-up still
        // fires the level-up modal/confetti (which the layout keys off game_result).
        $user->refresh();
        $gameResult = $result->gameResult;
        $gameResult['leveled_up'] = $user->level > $result->levelBefore;
        $gameResult['new_level'] = $user->level;

        // Flash the result payload to the session for Svelte to intercept
        return back()->with('game_result', $gameResult);
    }

    public function submitBlockClaim(Request $request, Lesson $lesson, int $blockIndex)
    {
        $user = Auth::user();

        $this->authorize('view', $lesson->course);

        $result = $this->lessonProgress->claimBlock($user, $lesson, $blockIndex, $request->input('answer'));

        if ($result->status === 'out_of_bounds') {
            abort(404);
        }

        if ($result->status === 'success') {
            ProgressRegistered::dispatch($user, 'block');
        }

        // Flashing this data means if this reward pushes them over the edge, the
        // layout will pause the lesson, fire confetti, and show the Level Up modal.
        return back()->with('game_result', $result->gameResult);
    }
}
