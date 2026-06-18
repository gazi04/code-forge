<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\World;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->string('q')->trim();

        if ($query->length() < 2) {
            return response()->json(['worlds' => [], 'courses' => [], 'lessons' => []]);
        }

        $escape = '\\';
        $term = '%'.addcslashes((string) $query, '%_\\').'%';

        $worlds = World::where('is_published', true)
            ->where(fn ($q) => $q->whereRaw('name LIKE ? ESCAPE ?', [$term, $escape])
                ->orWhereRaw('description LIKE ? ESCAPE ?', [$term, $escape]))
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
            ->where(fn ($q) => $q->whereRaw('name LIKE ? ESCAPE ?', [$term, $escape])
                ->orWhereRaw('description LIKE ? ESCAPE ?', [$term, $escape]))
            ->with('world', 'prerequisite')
            ->limit(5)
            ->get()
            ->map(function (Course $c) use ($user): array {
                $gate = Gate::forUser($user)->inspect('view', $c);

                return [
                    'type' => 'course',
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'world_name' => $c->world->name,
                    'world_slug' => $c->world->slug,
                    'age_tier' => $c->age_tier,
                    'difficulty' => $c->difficulty,
                    'locked' => ! $gate->allowed(),
                    'lock_reason' => $gate->allowed() ? null : $gate->message(),
                ];
            });

        $lessons = Lesson::whereRaw('name LIKE ? ESCAPE ?', [$term, $escape])
            ->whereHas('course', fn ($q) => $q->where('is_published', true)
                ->whereHas('world', fn ($q) => $q->where('is_published', true)))
            ->with('course.world', 'course.prerequisite')
            ->limit(5)
            ->get()
            ->map(function (Lesson $l) use ($user): array {
                $gate = Gate::forUser($user)->inspect('view', $l->course);

                return [
                    'type' => 'lesson',
                    'name' => $l->name,
                    'slug' => $l->slug,
                    'course_name' => $l->course->name,
                    'course_slug' => $l->course->slug,
                    'world_name' => $l->course->world->name,
                    'is_boss' => $l->is_boss,
                    'xp_reward' => $l->xp_reward,
                    'locked' => ! $gate->allowed(),
                    'lock_reason' => $gate->allowed() ? null : $gate->message(),
                ];
            });

        return response()->json([
            'worlds' => $worlds,
            'courses' => $courses,
            'lessons' => $lessons,
        ]);
    }
}
