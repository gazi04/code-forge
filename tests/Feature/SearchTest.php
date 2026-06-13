<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'student']);

    $this->themePack = ThemePack::create([
        'name' => 'Test Pack',
        'identifier' => 'test-pack-'.uniqid(),
        'config' => ['palette' => ['primary' => '#8b5cf6']],
    ]);

    $this->world = World::create([
        'name' => 'Python World',
        'slug' => 'python-world-'.uniqid(),
        'description' => 'Learn Python programming',
        'theme_pack_id' => $this->themePack->id,
        'is_published' => true,
    ]);

    $this->course = Course::create([
        'world_id' => $this->world->id,
        'name' => 'Python Basics',
        'slug' => 'python-basics-'.uniqid(),
        'description' => 'Variables and loops',
        'age_tier' => 'explorer',
        'difficulty' => 1,
        'min_level_requirement' => 1,
        'estimated_duration' => 30,
        'is_published' => true,
    ]);

    $this->lesson = Lesson::create([
        'course_id' => $this->course->id,
        'name' => 'Variables Intro',
        'slug' => 'variables-intro-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'is_boss' => false,
        'blocks' => [],
    ]);
});

it('returns published worlds matching the query', function () {
    $this->actingAs($this->student)
        ->getJson('/search?q=Python')
        ->assertOk()
        ->assertJsonPath('worlds.0.name', 'Python World');
});

it('excludes unpublished worlds from results', function () {
    $this->world->update(['is_published' => false]);

    $this->actingAs($this->student)
        ->getJson('/search?q=Python')
        ->assertOk()
        ->assertJsonCount(0, 'worlds');
});

it('returns published courses matching the query', function () {
    $this->actingAs($this->student)
        ->getJson('/search?q=Python')
        ->assertOk()
        ->assertJsonPath('courses.0.name', 'Python Basics');
});

it('excludes courses from unpublished worlds', function () {
    $this->world->update(['is_published' => false]);

    $this->actingAs($this->student)
        ->getJson('/search?q=Python')
        ->assertOk()
        ->assertJsonCount(0, 'courses');
});

it('returns lessons matching the query', function () {
    $this->actingAs($this->student)
        ->getJson('/search?q=Variables')
        ->assertOk()
        ->assertJsonPath('lessons.0.name', 'Variables Intro');
});

it('returns empty results for a query shorter than 2 characters', function () {
    $this->actingAs($this->student)
        ->getJson('/search?q=P')
        ->assertOk()
        ->assertJson(['worlds' => [], 'courses' => [], 'lessons' => []]);
});

it('returns empty results for a blank query', function () {
    $this->actingAs($this->student)
        ->getJson('/search?q=')
        ->assertOk()
        ->assertJson(['worlds' => [], 'courses' => [], 'lessons' => []]);
});

it('redirects unauthenticated users', function () {
    $this->getJson('/search?q=Python')
        ->assertUnauthorized();
});
