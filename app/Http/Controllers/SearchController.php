<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\World;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();

        if ($query->length() < 2) {
            return response()->json(['worlds' => [], 'courses' => [], 'lessons' => []]);
        }

        $worlds = World::where('is_published', true)
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%"))
            ->with('themePack')
            ->limit(5)
            ->get()
            ->map(fn (World $w): array => [
                'type' => 'world',
                'name' => $w->name,
                'slug' => $w->slug,
                'description' => $w->description,
                'primary_color' => $w->themePack?->config['palette']['primary'] ?? '#8b5cf6',
            ]);

        $courses = Course::where('is_published', true)
            ->whereHas('world', fn ($q) => $q->where('is_published', true))
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%"))
            ->with('world')
            ->limit(5)
            ->get()
            ->map(fn (Course $c): array => [
                'type' => 'course',
                'name' => $c->name,
                'slug' => $c->slug,
                'world_name' => $c->world->name,
                'world_slug' => $c->world->slug,
                'age_tier' => $c->age_tier,
                'difficulty' => $c->difficulty,
            ]);

        $lessons = Lesson::where('name', 'like', "%{$query}%")
            ->whereHas('course', fn ($q) => $q->where('is_published', true)
                ->whereHas('world', fn ($q) => $q->where('is_published', true)))
            ->with('course.world')
            ->limit(5)
            ->get()
            ->map(fn (Lesson $l): array => [
                'type' => 'lesson',
                'name' => $l->name,
                'slug' => $l->slug,
                'course_name' => $l->course->name,
                'course_slug' => $l->course->slug,
                'world_name' => $l->course->world->name,
                'is_boss' => $l->is_boss,
                'xp_reward' => $l->xp_reward,
            ]);

        return response()->json([
            'worlds' => $worlds,
            'courses' => $courses,
            'lessons' => $lessons,
        ]);
    }
}
