<?php

use App\Events\ProgressRegistered;
use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

// ─── helpers ─────────────────────────────────────────────────────────────────

function createLessonHierarchy(array $blocks = [], string $worldSlug = 'test-world', string $courseSlug = 'test-course', string $lessonSlug = 'test-lesson', int $minLevel = 1): array
{
    $theme = ThemePack::create(['name' => 'Test Theme', 'identifier' => 'theme_test_'.uniqid(), 'config' => []]);
    $world = World::create(['name' => 'Test World', 'slug' => $worldSlug, 'theme_pack_id' => $theme->id]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Test Course',
        'slug' => $courseSlug,
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => $minLevel,
    ]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Test Lesson',
        'slug' => $lessonSlug,
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => $blocks,
    ]);

    return compact('theme', 'world', 'course', 'lesson');
}

beforeEach(function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
});

// ─── Block claim ─────────────────────────────────────────────────────────────

it('creates a BlockSubmission and awards XP on a valid block claim', function () {
    $user = User::factory()->create(['xp' => 0, 'coins' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim")
        ->assertRedirect();

    expect(BlockSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->where('block_index', 0)
        ->exists()
    )->toBeTrue();

    expect($user->fresh()->xp)->toBe(20);
});

it('returns already_completed flash and skips duplicate BlockSubmission', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy();

    BlockSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'xp_rewarded' => 15,
        'coins_rewarded' => 5,
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim")
        ->assertRedirect()
        ->assertSessionHas('game_result.status', 'already_completed');

    expect(BlockSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->where('block_index', 0)
        ->count()
    )->toBe(1);
});

it('dispatches ProgressRegistered event on block claim', function () {
    Event::fake([ProgressRegistered::class]);

    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy();

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim");

    Event::assertDispatched(ProgressRegistered::class);
});

it('uses default 15 XP reward when block has no explicit xp_reward', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'text_content', 'data' => ['is_required' => false]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim");

    expect($user->fresh()->xp)->toBe(15);
});

// ─── Lesson submission ────────────────────────────────────────────────────────

it('creates a LessonSubmission when all required blocks are cleared', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => true, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

    BlockSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'xp_rewarded' => 20,
        'coins_rewarded' => 5,
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHas('game_result.status', 'success');

    expect(LessonSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->slug)
        ->exists()
    )->toBeTrue();
});

it('rejects lesson submission when a required block has not been cleared', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => true, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    expect(LessonSubmission::where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejects lesson submission when user level is below the course requirement', function () {
    $user = User::factory()->create(['level' => 1]);
    ['lesson' => $lesson] = createLessonHierarchy([], 'gated-world', 'high-course', 'locked-lesson', 5);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHasErrors('error');
});

it('returns already_completed flash without creating a duplicate LessonSubmission', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy();

    LessonSubmission::create([
        'user_id' => $user->id,
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->slug,
        'xp_rewarded' => 50,
        'coins_rewarded' => 10,
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHas('game_result.status', 'already_completed');

    expect(LessonSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->slug)
        ->count()
    )->toBe(1);
});
