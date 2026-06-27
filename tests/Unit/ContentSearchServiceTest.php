<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use App\Services\ContentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function seedSearchHierarchy(int $minLevel = 1): array
{
    $theme = ThemePack::create(['name' => 'Pack', 'identifier' => 'pack-'.uniqid(), 'config' => ['palette' => ['primary' => '#8b5cf6']]]);
    $world = World::create(['name' => 'Python World', 'slug' => 'world-'.uniqid(), 'description' => 'Learn Python', 'theme_pack_id' => $theme->id, 'is_published' => true]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Python Basics',
        'slug' => 'course-'.uniqid(),
        'description' => 'Variables',
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => $minLevel,
        'is_published' => true,
    ]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Python Loops',
        'slug' => 'lesson-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => [],
    ]);

    return compact('world', 'course', 'lesson');
}

it('returns empty buckets for a term under the 2-char minimum', function () {
    $user = User::factory()->create(['role' => 'student']);
    seedSearchHierarchy();

    expect((new ContentSearchService)->search($user, 'p'))
        ->toBe(['worlds' => [], 'courses' => [], 'lessons' => []]);
});

it('matches published worlds, courses and lessons by name', function () {
    $user = User::factory()->create(['role' => 'student', 'level' => 99]);
    seedSearchHierarchy();

    $result = (new ContentSearchService)->search($user, 'Python');

    expect($result['worlds'])->toHaveCount(1)
        ->and($result['courses'])->toHaveCount(1)
        ->and($result['lessons'])->toHaveCount(1);
});

it('treats LIKE wildcards as literals (escaping)', function () {
    $user = User::factory()->create(['role' => 'student']);
    seedSearchHierarchy();

    // 'z%' must match the literal substring "z%", not act as a wildcard that
    // would otherwise return every row.
    $result = (new ContentSearchService)->search($user, 'z%');

    expect($result['worlds'])->toHaveCount(0)
        ->and($result['courses'])->toHaveCount(0);
});

it('tags a course above the student level as locked with a reason', function () {
    $user = User::factory()->create(['role' => 'student', 'level' => 1]);
    seedSearchHierarchy(minLevel: 99);

    $result = (new ContentSearchService)->search($user, 'Python');

    expect($result['courses']->first()['locked'])->toBeTrue()
        ->and($result['courses']->first()['lock_reason'])->not->toBeNull();
});
