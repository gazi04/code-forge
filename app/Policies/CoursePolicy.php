<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): Response
    {
        // 1. Enforce that only students can access student views
        if ($user->role !== 'student') {
            return Response::deny('Administrators cannot participate in student quests.');
        }

        // 2. Reject unpublished courses
        if (! $course->is_published) {
            return Response::deny('This course is not available yet.');
        }

        // 3. Reject courses whose parent world is still a draft (a published
        // course inside an unpublished world must not leak via direct slug access).
        if (! $course->world?->is_published) {
            return Response::deny('This course is not available yet.');
        }

        // 4. Enforce your Horizontal Global Power Level Gating rule!
        if ($user->level < $course->min_level_requirement) {
            return Response::deny("🔒 This world is restricted! Requires Adventure Level {$course->min_level_requirement}.");
        }

        // 5. Enforce the configured course prerequisite: the student must have
        // completed every lesson of the prerequisite course before this one opens.
        if ($course->prerequisite_course_id) {
            $prereqLessonIds = Lesson::where('course_id', $course->prerequisite_course_id)->pluck('id');

            if ($prereqLessonIds->isNotEmpty()) {
                $completedCount = LessonSubmission::whereIn('lesson_id', $prereqLessonIds)
                    ->where('user_id', $user->id)
                    ->count();

                if ($completedCount < $prereqLessonIds->count()) {
                    return Response::deny("Finish {$course->prerequisite->name} first.");
                }
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }
}
