<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\Course;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

        return Inertia::render('Student/CourseDetail', [
            'course' => $course,
            'world' => new WorldResource($course->world),
            'lessons' => $course->lessons,
        ]);
    }
}
