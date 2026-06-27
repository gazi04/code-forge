<?php

use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use App\Services\BlockValidator;
use App\Services\LessonProgressService;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── helpers ─────────────────────────────────────────────────────────────────

function makeLessonHierarchy(array $blocks = []): array
{
    $theme = ThemePack::create(['name' => 'Test Theme', 'identifier' => 'theme_'.uniqid(), 'config' => []]);
    $world = World::create(['name' => 'Test World', 'slug' => 'world-'.uniqid(), 'theme_pack_id' => $theme->id, 'is_published' => true]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Test Course',
        'slug' => 'course-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
        'is_published' => true,
    ]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Test Lesson',
        'slug' => 'lesson-'.uniqid(),
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => $blocks,
    ]);

    return compact('theme', 'world', 'course', 'lesson');
}

function lessonProgressService(): LessonProgressService
{
    return new LessonProgressService(new ProgressionService, new BlockValidator);
}

beforeEach(function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
});

// ─── claimBlock — anti-cheat ─────────────────────────────────────────────────

it('rejects a forged quiz answer without writing a BlockSubmission', function () {
    $user = User::factory()->create(['xp' => 0]);
    ['lesson' => $lesson] = makeLessonHierarchy([
        ['type' => 'quiz', 'data' => ['answers' => [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false],
        ]]],
    ]);

    $result = lessonProgressService()->claimBlock($user, $lesson, 0, [1]);

    expect($result->status)->toBe('incorrect')
        ->and(BlockSubmission::where('user_id', $user->id)->count())->toBe(0)
        ->and($user->fresh()->xp)->toBe(0);
});

it('awards XP and writes a BlockSubmission on a correct claim', function () {
    $user = User::factory()->create(['xp' => 0, 'last_active_at' => now()]);
    ['lesson' => $lesson] = makeLessonHierarchy([
        ['type' => 'quiz', 'data' => ['xp_reward' => 20, 'coin_reward' => 5, 'answers' => [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false],
        ]]],
    ]);

    $result = lessonProgressService()->claimBlock($user, $lesson, 0, [0]);

    expect($result->status)->toBe('success')
        ->and(BlockSubmission::where('user_id', $user->id)->where('block_index', 0)->exists())->toBeTrue()
        ->and($user->fresh()->xp)->toBe(20);
});

it('does not double-reward a block already claimed', function () {
    $user = User::factory()->create(['xp' => 0, 'last_active_at' => now()]);
    ['lesson' => $lesson] = makeLessonHierarchy([
        ['type' => 'quiz', 'data' => ['xp_reward' => 20, 'answers' => [
            ['text' => 'A', 'is_correct' => true],
        ]]],
    ]);

    $service = lessonProgressService();
    $service->claimBlock($user, $lesson, 0, [0]);
    $xpAfterFirst = $user->fresh()->xp;

    $result = $service->claimBlock($user, $lesson, 0, [0]);

    expect($result->status)->toBe('already_completed')
        ->and(BlockSubmission::where('user_id', $user->id)->where('block_index', 0)->count())->toBe(1)
        ->and($user->fresh()->xp)->toBe($xpAfterFirst);
});

it('returns out_of_bounds for an index past the block list', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = makeLessonHierarchy([
        ['type' => 'quiz', 'data' => ['answers' => [['text' => 'A', 'is_correct' => true]]]],
    ]);

    expect(lessonProgressService()->claimBlock($user, $lesson, 5, [0])->status)->toBe('out_of_bounds');
});

// ─── submitLesson — gates ────────────────────────────────────────────────────

it('blocks lesson completion when a required block is uncleared', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson] = makeLessonHierarchy([
        ['type' => 'quiz', 'data' => ['is_required' => true, 'answers' => [['text' => 'A', 'is_correct' => true]]]],
    ]);

    $result = lessonProgressService()->submitLesson($user, $lesson);

    expect($result->status)->toBe('requirements_not_met')
        ->and(LessonSubmission::where('user_id', $user->id)->count())->toBe(0);
});

it('completes a lesson with no required blocks and refuses a second reward', function () {
    $user = User::factory()->create(['xp' => 0, 'last_active_at' => now()]);
    ['lesson' => $lesson] = makeLessonHierarchy();

    $service = lessonProgressService();
    $first = $service->submitLesson($user, $lesson);
    $second = $service->submitLesson($user, $lesson);

    expect($first->status)->toBe('success')
        ->and($second->status)->toBe('already_completed')
        ->and(LessonSubmission::where('user_id', $user->id)->count())->toBe(1);
});

// ─── findWorldToComplete ─────────────────────────────────────────────────────

it('returns null until every lesson in the world is submitted, then the World', function () {
    $user = User::factory()->create();
    ['lesson' => $lesson, 'world' => $world] = makeLessonHierarchy();

    expect(lessonProgressService()->findWorldToComplete($user, $lesson))->toBeNull();

    LessonSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'course_id' => $lesson->course_id,
        'xp_rewarded' => 0,
        'coins_rewarded' => 0,
    ]);

    $found = lessonProgressService()->findWorldToComplete($user, $lesson);
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($world->id);
});
