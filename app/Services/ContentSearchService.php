<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ContentSearchService
{
    /**
     * Search published worlds/courses/lessons by name (and description for
     * worlds/courses), capped at 5 each. Course/lesson results carry the viewer's
     * locked state via gate inspection. Terms under 2 chars return empty buckets.
     *
     * @return array{worlds: Collection<int, array<string, mixed>>|array{}, courses: Collection<int, array<string, mixed>>|array{}, lessons: Collection<int, array<string, mixed>>|array{}}
     */
    public function search(User $user, string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return ['worlds' => [], 'courses' => [], 'lessons' => []];
        }

        $escape = '\\';
        $like = '%'.addcslashes($term, '%_\\').'%';

        $worlds = World::where('is_published', true)
            ->where(fn ($q) => $q->whereRaw('name LIKE ? ESCAPE ?', [$like, $escape])
                ->orWhereRaw('description LIKE ? ESCAPE ?', [$like, $escape]))
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
            ->where(fn ($q) => $q->whereRaw('name LIKE ? ESCAPE ?', [$like, $escape])
                ->orWhereRaw('description LIKE ? ESCAPE ?', [$like, $escape]))
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

        $lessons = Lesson::whereRaw('name LIKE ? ESCAPE ?', [$like, $escape])
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

        return [
            'worlds' => $worlds,
            'courses' => $courses,
            'lessons' => $lessons,
        ];
    }
}
