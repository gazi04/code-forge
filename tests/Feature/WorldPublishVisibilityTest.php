<?php

use App\Models\Course;
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
