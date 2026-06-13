<?php

use App\Events\WorldCompleted;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\UserWorldCompletion;
use App\Models\World;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

// ─── helpers ─────────────────────────────────────────────────────────────────

function createWorldWithOneLesson(string $suffix = ''): array
{
    $theme = ThemePack::create([
        'name' => 'Theme '.$suffix,
        'identifier' => 'theme_wc_'.$suffix.'_'.uniqid(),
        'config' => [],
    ]);
    $world = World::create([
        'name' => 'Test World '.$suffix,
        'slug' => 'test-world-'.$suffix.'-'.uniqid(),
        'theme_pack_id' => $theme->id,
    ]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Course '.$suffix,
        'slug' => 'course-'.$suffix.'-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
    ]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Lesson '.$suffix,
        'slug' => 'lesson-'.$suffix.'-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => [],
    ]);

    return compact('world', 'course', 'lesson');
}

beforeEach(function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
});

// ─── Event dispatch ───────────────────────────────────────────────────────────

it('dispatches WorldCompleted event when last lesson in world is submitted', function () {
    Event::fake([WorldCompleted::class]);

    $user = User::factory()->create();
    ['world' => $world, 'lesson' => $lesson] = createWorldWithOneLesson('dispatch');

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect();

    Event::assertDispatched(WorldCompleted::class, function (WorldCompleted $event) use ($user, $world) {
        return $event->user->id === $user->id && $event->world->id === $world->id;
    });
});

it('does not dispatch WorldCompleted when world has remaining uncompleted lessons', function () {
    Event::fake([WorldCompleted::class]);

    $user = User::factory()->create();
    ['world' => $world, 'course' => $course, 'lesson' => $lesson1] = createWorldWithOneLesson('multi');

    // Second lesson in same course/world
    $lesson2 = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Lesson 2 Multi',
        'slug' => 'lesson-2-multi-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => [],
    ]);

    // Only submit the first lesson
    $this->actingAs($user)
        ->from("/lessons/{$lesson1->slug}")
        ->post("/lessons/{$lesson1->slug}/submit")
        ->assertRedirect();

    Event::assertNotDispatched(WorldCompleted::class);
});

// ─── DB persistence ───────────────────────────────────────────────────────────

it('creates a user_world_completions record on world completion', function () {
    $user = User::factory()->create();
    ['world' => $world, 'lesson' => $lesson] = createWorldWithOneLesson('persist');

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit");

    expect(
        UserWorldCompletion::where('user_id', $user->id)
            ->where('world_id', $world->id)
            ->exists()
    )->toBeTrue();
});

it('does not duplicate the completion record or re-award bonus on repeated triggers', function () {
    $user = User::factory()->create();
    ['world' => $world, 'lesson' => $lesson] = createWorldWithOneLesson('dedup');

    // Submit once — creates completion record
    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit");

    $xpAfterFirst = $user->fresh()->xp;

    // Try to trigger the completion again manually
    WorldCompleted::dispatch($user, $world);

    expect(
        UserWorldCompletion::where('user_id', $user->id)->where('world_id', $world->id)->count()
    )->toBe(1);

    expect($user->fresh()->xp)->toBe($xpAfterFirst);
});

// ─── Flash data ───────────────────────────────────────────────────────────────

it('flashes world_completed session data after world completion', function () {
    $user = User::factory()->create();
    ['world' => $world, 'lesson' => $lesson] = createWorldWithOneLesson('flash');

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHas('world_completed.world_id', $world->id)
        ->assertSessionHas('world_completed.world_name', $world->name)
        ->assertSessionHas('world_completed.world_slug', $world->slug);
});

// ─── Certificate download ─────────────────────────────────────────────────────

it('returns 403 if user has not completed the world', function () {
    $user = User::factory()->create();
    ['world' => $world] = createWorldWithOneLesson('cert-403');

    $this->actingAs($user)
        ->get("/worlds/{$world->slug}/certificate")
        ->assertForbidden();
});

it('returns a PDF download for a completed world', function () {
    $user = User::factory()->create();
    ['world' => $world] = createWorldWithOneLesson('cert-ok');

    UserWorldCompletion::create([
        'user_id' => $user->id,
        'world_id' => $world->id,
        'completed_at' => now(),
        'xp_bonus_awarded' => 500,
        'coins_bonus_awarded' => 250,
    ]);

    $response = $this->actingAs($user)
        ->get("/worlds/{$world->slug}/certificate");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

// ─── Bonus XP awarded ─────────────────────────────────────────────────────────

it('awards bonus XP and coins when world is first completed', function () {
    $user = User::factory()->create(['xp' => 0, 'coins' => 0]);
    ['lesson' => $lesson] = createWorldWithOneLesson('bonus');

    $xpBefore = $user->xp;

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit");

    // Lesson XP (50) + world bonus (500) should both be applied
    expect($user->fresh()->xp)->toBeGreaterThan($xpBefore + 50);
});
