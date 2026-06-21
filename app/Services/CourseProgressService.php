<?php

namespace App\Services;

use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\LessonSubmission;
use App\Models\User;

class CourseProgressService
{
    /**
     * Load a course for its detail page with the world theme and lesson metadata
     * (never the heavy `blocks` JSON — hundreds of KB and it leaks answer keys).
     */
    public function findForDetail(string $slug): Course
    {
        return Course::where('slug', $slug)
            ->with([
                'world.themePack',
                'lessons' => function ($query): void {
                    $query->select([
                        'id', 'course_id', 'name', 'slug', 'xp_reward',
                        'coin_reward', 'estimated_duration', 'is_boss', 'sort_order',
                    ])->orderBy('sort_order', 'asc');
                },
            ])
            ->firstOrFail();
    }

    /**
     * The user's progress through a loaded course: which lessons are done and which
     * lesson to resume (the most recent partially-attempted, not-yet-completed one).
     *
     * @return array{resume_lesson_slug: ?string, completed_lesson_slugs: list<string>}
     */
    public function getCourseProgress(User $user, Course $course): array
    {
        $lessonIds = $course->lessons->pluck('id');

        $completedIds = LessonSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id');

        $resumeLessonId = BlockSubmission::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotIn('lesson_id', $completedIds)
            ->latest()
            ->value('lesson_id');

        $resumeLessonSlug = $resumeLessonId
            ? $course->lessons->find($resumeLessonId)?->slug
            : null;

        return [
            'resume_lesson_slug' => $resumeLessonSlug,
            'completed_lesson_slugs' => $course->lessons->whereIn('id', $completedIds)->pluck('slug')->toArray(),
        ];
    }
}
