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
    $world = World::create(['name' => 'Test World', 'slug' => $worldSlug, 'theme_pack_id' => $theme->id, 'is_published' => true]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Test Course',
        'slug' => $courseSlug,
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => $minLevel,
        'is_published' => true,
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
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

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
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim");

    Event::assertDispatched(ProgressRegistered::class);
});

it('rejects a block claim with an out-of-bounds index and awards nothing', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20, 'coin_reward' => 5]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/999/claim")
        ->assertNotFound();

    expect($user->fresh()->xp)->toBe(0);
    expect(BlockSubmission::where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejects a block claim with a negative index', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/-1/claim")
        ->assertNotFound();

    expect($user->fresh()->xp)->toBe(0);
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

it('rejects a block claim when user level is below the course requirement', function () {
    $user = User::factory()->create(['level' => 1, 'xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20, 'coin_reward' => 5]],
    ], 'gated-world', 'high-course', 'locked-lesson', 5);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim")
        ->assertForbidden();

    expect($user->fresh()->xp)->toBe(0);
    expect(BlockSubmission::where('user_id', $user->id)->exists())->toBeFalse();
});

it('forbids viewing a lesson when user level is below the course requirement', function () {
    $user = User::factory()->create(['level' => 1]);
    ['lesson' => $lesson] = createLessonHierarchy([], 'gated-world', 'high-course', 'locked-lesson', 5);

    $this->actingAs($user)
        ->get("/lessons/{$lesson->slug}")
        ->assertForbidden();
});

it('forbids claiming a block on an unpublished course', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson, 'course' => $course] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => false, 'xp_reward' => 20]],
    ]);
    $course->update(['is_published' => false]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim")
        ->assertForbidden();

    expect($user->fresh()->xp)->toBe(0);
});

// ─── Server-side answer validation ─────────────────────────────────────────────

it('rejects a quiz block claim with a wrong answer and awards nothing', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'answers' => [
                ['text' => 'A', 'is_correct' => false],
                ['text' => 'B', 'is_correct' => true],
            ],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [0]])
        ->assertRedirect()
        ->assertSessionHas('game_result.status', 'incorrect');

    expect($user->fresh()->xp)->toBe(0);
    expect(BlockSubmission::where('user_id', $user->id)->exists())->toBeFalse();
});

it('awards a quiz block claim with the correct answer', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'answers' => [
                ['text' => 'A', 'is_correct' => false],
                ['text' => 'B', 'is_correct' => true],
            ],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [1]])
        ->assertRedirect();

    expect($user->fresh()->xp)->toBe(20);
    expect(BlockSubmission::where('user_id', $user->id)->where('block_index', 0)->exists())->toBeTrue();
});

it('validates a sequence block against the stored order', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'sequence_challenge', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'correct_sequence' => [['value' => 'one'], ['value' => 'two'], ['value' => 'three']],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => ['two', 'one', 'three']])
        ->assertSessionHas('game_result.status', 'incorrect');

    expect($user->fresh()->xp)->toBe(0);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => ['one', 'two', 'three']])
        ->assertRedirect();

    expect($user->fresh()->xp)->toBe(20);
});

it('validates a bughunt block against the correct line fixes', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'bughunt_challenge', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'code_lines' => [
                ['type' => 'clean', 'displayed_text' => 'a = 1'],
                ['type' => 'buggy', 'displayed_text' => 'b = 2', 'correct_text' => 'b = 3', 'decoy_1' => 'x', 'decoy_2' => 'y'],
            ],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [1 => 'x']])
        ->assertSessionHas('game_result.status', 'incorrect');

    expect($user->fresh()->xp)->toBe(0);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [1 => 'b = 3']])
        ->assertRedirect();

    expect($user->fresh()->xp)->toBe(20);
});

it('validates a variable matching block against the stored pairs', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'variable_matching_challenge', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'pairs' => [
                ['left_item' => 'L1', 'right_item' => 'R1'],
                ['left_item' => 'L2', 'right_item' => 'R2'],
            ],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [
            ['left' => 'L1', 'right' => 'R2'],
            ['left' => 'L2', 'right' => 'R1'],
        ]])
        ->assertSessionHas('game_result.status', 'incorrect');

    expect($user->fresh()->xp)->toBe(0);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [
            ['left' => 'L1', 'right' => 'R1'],
            ['left' => 'L2', 'right' => 'R2'],
        ]])
        ->assertRedirect();

    expect($user->fresh()->xp)->toBe(20);
});

it('does not re-validate an already-cleared block', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => [
            'answers' => [
                ['text' => 'A', 'is_correct' => false],
                ['text' => 'B', 'is_correct' => true],
            ],
        ]],
    ]);

    BlockSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'xp_rewarded' => 15,
        'coins_rewarded' => 5,
    ]);

    // A wrong answer still resolves to already_completed (no re-validation).
    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [0]])
        ->assertSessionHas('game_result.status', 'already_completed');
});

// ─── Double-reward gate (1.5) ──────────────────────────────────────────────────

it('awards a block reward only once across repeated claims', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy([
        ['type' => 'quiz', 'data' => [
            'xp_reward' => 20,
            'coin_reward' => 5,
            'answers' => [
                ['text' => 'A', 'is_correct' => false],
                ['text' => 'B', 'is_correct' => true],
            ],
        ]],
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [1]])
        ->assertRedirect();

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/blocks/0/claim", ['answer' => [1]])
        ->assertSessionHas('game_result.status', 'already_completed');

    expect($user->fresh()->xp)->toBe(20);
    expect(BlockSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->where('block_index', 0)
        ->count()
    )->toBe(1);
});

it('awards a lesson reward only once across repeated submits', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = createLessonHierarchy();

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertSessionHas('game_result.status', 'success');

    // XP after the first (genuine) submit — includes any world-completion bonus.
    $xpAfterFirst = $user->fresh()->xp;

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertSessionHas('game_result.status', 'already_completed');

    // Second submit must award nothing and must not insert a duplicate row.
    expect($user->fresh()->xp)->toBe($xpAfterFirst);
    expect(LessonSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->count()
    )->toBe(1);
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
        ->where('lesson_id', $lesson->id)
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

    // submitClaim now authorizes via CoursePolicy::view (the level gate denies → 403)
    // instead of returning a redirect with an error bag.
    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertForbidden();

    expect(LessonSubmission::where('user_id', $user->id)->where('lesson_id', $lesson->id)->exists())->toBeFalse();
});

it('returns already_completed flash without creating a duplicate LessonSubmission', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = createLessonHierarchy();

    LessonSubmission::create([
        'user_id' => $user->id,
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->id,
        'xp_rewarded' => 50,
        'coins_rewarded' => 10,
    ]);

    $this->actingAs($user)
        ->from("/lessons/{$lesson->slug}")
        ->post("/lessons/{$lesson->slug}/submit")
        ->assertRedirect()
        ->assertSessionHas('game_result.status', 'already_completed');

    expect(LessonSubmission::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->count()
    )->toBe(1);
});
