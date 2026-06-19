<?php

use App\Models\Course;
use App\Models\ThemePack;
use App\Models\World;
use Illuminate\Support\Str;

function makePrereqWorld(): World
{
    $theme = ThemePack::create([
        'name' => 'Prereq Theme',
        'identifier' => 'theme_prereq_'.uniqid(),
        'config' => [],
    ]);

    return World::create([
        'name' => 'Prereq World',
        'slug' => 'prereq-world-'.uniqid(),
        'theme_pack_id' => $theme->id,
        'is_published' => true,
    ]);
}

function makePrereqCourse(string $name, ?int $prerequisiteId = null): Course
{
    $world = makePrereqWorld();

    return Course::create([
        'world_id' => $world->id,
        'name' => $name,
        'slug' => Str::slug($name).'-'.uniqid(),
        'age_tier' => 'explorer',
        'difficulty' => 1,
        'min_level_requirement' => 1,
        'estimated_duration' => 30,
        'is_published' => true,
        'prerequisite_course_id' => $prerequisiteId,
    ]);
}

it('casts min_level_requirement to an integer', function () {
    $course = makePrereqCourse('Casting Course');

    expect($course->fresh()->min_level_requirement)->toBeInt();
});

it('returns the transitive dependent chain, excluding self and unrelated courses', function () {
    $a = makePrereqCourse('Course A');
    $b = makePrereqCourse('Course B', $a->id);   // B requires A
    $c = makePrereqCourse('Course C', $b->id);   // C requires B (→ A)
    $d = makePrereqCourse('Course D');           // unrelated

    $dependents = $a->transitiveDependentIds();

    expect($dependents)->toContain($b->id)
        ->toContain($c->id)
        ->not->toContain($d->id)
        ->not->toContain($a->id);
});

it('terminates on a pre-existing prerequisite cycle', function () {
    $a = makePrereqCourse('Cycle A');
    $b = makePrereqCourse('Cycle B', $a->id);
    $a->update(['prerequisite_course_id' => $b->id]); // A↔B cycle

    expect($a->transitiveDependentIds())->toContain($b->id);
});
