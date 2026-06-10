<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\LessonSubmission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    use AuthorizesRequests;

    public function show($slug)
    {
        $course = Course::where('slug', $slug)
            ->with([
                'world.themePack',
                'lessons' => function ($query): void {
                    $query->orderBy('sort_order', 'asc');
                },
            ])
            ->firstOrFail();

        $this->authorize('view', $course);

        $user = Auth::user();
        $lessonIds = $course->lessons->pluck('id');
        $lessonSlugs = $course->lessons->pluck('slug');

        $completedIds = LessonSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonSlugs)
            ->pluck('lesson_id')
            ->pipe(fn ($slugs) => $course->lessons->whereIn('slug', $slugs)->pluck('id'));

        $resumeLessonId = BlockSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotIn('lesson_id', $completedIds)
            ->latest()
            ->value('lesson_id');

        $resumeLessonSlug = $resumeLessonId
            ? $course->lessons->find($resumeLessonId)?->slug
            : null;

        $completedSlugs = $course->lessons->whereIn('id', $completedIds)->pluck('slug')->toArray();

        return Inertia::render('Student/CourseDetail', [
            'course' => $course,
            'world' => new WorldResource($course->world),
            'lessons' => $course->lessons,
            'resume_lesson_slug' => $resumeLessonSlug,
            'completed_lesson_slugs' => $completedSlugs,
        ]);
    }
}
