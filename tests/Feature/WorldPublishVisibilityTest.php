<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Inertia\Testing\AssertableInertia as Assert;

function makeWorld(bool $published, string $suffix): World
{
    $theme = ThemePack::create([
        'name' => 'Theme '.$suffix,
        'identifier' => 'theme_pub_'.$suffix.'_'.uniqid(),
        'config' => [],
    ]);

    return World::create([
        'name' => 'World '.$suffix,
        'slug' => 'world-'.$suffix.'-'.uniqid(),
        'theme_pack_id' => $theme->id,
        'is_published' => $published,
    ]);
}

function makeCourse(World $world, bool $published, string $suffix): Course
{
    return Course::create([
        'world_id' => $world->id,
        'name' => 'Course '.$suffix,
        'slug' => 'course-'.$suffix.'-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
        'is_published' => $published,
    ]);
}

it('hides unpublished worlds from the world map', function () {
    $published = makeWorld(true, 'pub');
    $unpublished = makeWorld(false, 'draft');

    $this->actingAs(User::factory()->create())
        ->get('/worlds')
        ->assertInertia(fn (Assert $page) => $page
            ->has('worlds.data', 1)
            ->where('worlds.data.0.slug', $published->slug));
});

it('orders the world map by sort_order', function () {
    $first = makeWorld(true, 'a');
    $second = makeWorld(true, 'b');
    $third = makeWorld(true, 'c');

    // Assign sort_order out of insertion order.
    $first->forceFill(['sort_order' => 3])->save();
    $second->forceFill(['sort_order' => 1])->save();
    $third->forceFill(['sort_order' => 2])->save();

    $this->actingAs(User::factory()->create())
        ->get('/worlds')
        ->assertInertia(fn (Assert $page) => $page
            ->has('worlds.data', 3)
            ->where('worlds.data.0.slug', $second->slug)
            ->where('worlds.data.1.slug', $third->slug)
            ->where('worlds.data.2.slug', $first->slug));
});

it('shows only published courses on the world detail page', function () {
    $world = makeWorld(true, 'detail');
    $live = makeCourse($world, true, 'live');
    makeCourse($world, false, 'draft');

    $this->actingAs(User::factory()->create())
        ->get("/worlds/{$world->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('world.data.courses', 1)
            ->where('world.data.courses.0.slug', $live->slug));
});

it('denies access to an unpublished course accessed directly by slug', function () {
    $world = makeWorld(true, 'pub2');
    $draft = makeCourse($world, false, 'draft2');

    $this->actingAs(User::factory()->create())
        ->get("/course/{$draft->slug}")
        ->assertForbidden();
});

it('returns 404 for an unpublished world accessed directly', function () {
    $world = makeWorld(false, 'hidden');

    $this->actingAs(User::factory()->create())
        ->get("/worlds/{$world->slug}")
        ->assertNotFound();
});

it('denies a published course whose parent world is unpublished', function () {
    $draftWorld = makeWorld(false, 'draftworld');
    $course = makeCourse($draftWorld, true, 'live-in-draft');

    $this->actingAs(User::factory()->create(['role' => 'student', 'level' => 9]))
        ->get("/course/{$course->slug}")
        ->assertForbidden();
});

it('denies a course with an uncompleted prerequisite, then allows it once complete', function () {
    $world = makeWorld(true, 'prereqworld');
    $prereq = makeCourse($world, true, 'prereq');
    $advanced = Course::create([
        'world_id' => $world->id,
        'name' => 'Advanced',
        'slug' => 'advanced-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
        'is_published' => true,
        'prerequisite_course_id' => $prereq->id,
    ]);
    $prereqLesson = Lesson::create([
        'course_id' => $prereq->id,
        'name' => 'Prereq Lesson',
        'slug' => 'prereq-lesson-'.uniqid(),
        'xp_reward' => 10,
        'coin_reward' => 5,
        'estimated_duration' => 5,
        'blocks' => [],
    ]);

    $user = User::factory()->create(['role' => 'student', 'level' => 5]);

    $this->actingAs($user)
        ->get("/course/{$advanced->slug}")
        ->assertForbidden();

    LessonSubmission::create([
        'user_id' => $user->id,
        'course_id' => $prereq->id,
        'lesson_id' => $prereqLesson->id,
        'xp_rewarded' => 0,
        'coins_rewarded' => 0,
    ]);

    $this->actingAs($user)
        ->get("/course/{$advanced->slug}")
        ->assertOk();
});
