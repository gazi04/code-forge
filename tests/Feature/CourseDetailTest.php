<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Inertia\Testing\AssertableInertia as Assert;

function makeCourseWithLesson(): array
{
    $theme = ThemePack::create([
        'name' => 'Theme',
        'identifier' => 'theme_cd_'.uniqid(),
        'config' => [],
    ]);

    $world = World::create([
        'name' => 'World',
        'slug' => 'world-cd-'.uniqid(),
        'theme_pack_id' => $theme->id,
        'is_published' => true,
    ]);

    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Course',
        'slug' => 'course-cd-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
        'is_published' => true,
    ]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Intro Lesson',
        'slug' => 'intro-lesson-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 5,
        'is_boss' => false,
        'blocks' => [
            ['type' => 'quiz', 'data' => ['question' => 'x?', 'options' => [['text' => 'a', 'is_correct' => true]]]],
        ],
    ]);

    return [$course, $lesson];
}

it('ships lesson metadata but never the blocks JSON on the course page', function () {
    [$course, $lesson] = makeCourseWithLesson();

    $this->actingAs(User::factory()->create())
        ->get("/course/{$course->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('lessons', 1)
            ->where('lessons.0.slug', $lesson->slug)
            ->where('lessons.0.name', $lesson->name)
            ->missing('lessons.0.blocks'));
});
