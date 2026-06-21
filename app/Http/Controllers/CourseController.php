<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Services\CourseProgressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected CourseProgressService $courseProgress) {}

    public function show($slug)
    {
        $course = $this->courseProgress->findForDetail($slug);

        $this->authorize('view', $course);

        return Inertia::render('Student/CourseDetail', [
            'course' => $course,
            'world' => new WorldResource($course->world),
            'lessons' => $course->lessons,
            ...$this->courseProgress->getCourseProgress(Auth::user(), $course),
        ]);
    }
}
