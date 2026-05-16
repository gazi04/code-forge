<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\Course;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function show($slug)
    {
        $course = Course::where('slug', $slug)
            ->with([
                'world.themePack',
                'lessons' => function ($query) {
                    $query->orderBy('order_column', 'asc');
                }
            ])
            ->firstOrFail();

        return Inertia::render('Student/CourseDetail', [
            'course' => $course,
            'world' => new WorldResource($course->world),
            'lessons' => $course->lessons,
        ]);
    }
}
