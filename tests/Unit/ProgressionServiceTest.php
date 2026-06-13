<?php

use App\Events\UserLeveledUp;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── getXpRequiredForLevel ────────────────────────────────────────────────────

it('returns 0 for level 1', function () {
    expect((new ProgressionService)->getXpRequiredForLevel(1))->toBe(0);
});

it('returns 0 for level 0', function () {
    expect((new ProgressionService)->getXpRequiredForLevel(0))->toBe(0);
});

it('returns correct early-level thresholds', function () {
    $service = new ProgressionService;

    expect($service->getXpRequiredForLevel(2))->toBe(100)
        ->and($service->getXpRequiredForLevel(5))->toBe(700)
        ->and($service->getXpRequiredForLevel(10))->toBe(2700);
});

it('uses polynomial formula for levels above 10', function () {
    $service = new ProgressionService;

    expect($service->getXpRequiredForLevel(11))->toBe((int) round(pow(11, 1.5) * 100))
        ->and($service->getXpRequiredForLevel(12))->toBe((int) round(pow(12, 1.5) * 100));
});

// ─── processVictory — streak logic ──────────────────────────────────────────

it('sets streak to 1 on first ever login', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => null, 'streak_count' => 0]);
    $result = (new ProgressionService)->processVictory($user, 10, 5);

    expect($user->fresh()->streak_count)->toBe(1)
        ->and($result['streak_count'])->toBe(1);
});

it('does not increment streak when already active today', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now(), 'streak_count' => 3]);
    (new ProgressionService)->processVictory($user, 10, 5);

    expect($user->fresh()->streak_count)->toBe(3);
});

it('increments streak on consecutive day', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now()->subDay(), 'streak_count' => 4]);
    (new ProgressionService)->processVictory($user, 10, 5);

    expect($user->fresh()->streak_count)->toBe(5);
});

it('resets streak to 1 on 2-day gap with no freeze', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now()->subDays(2),
        'streak_count' => 5,
        'streak_freezes' => 0,
    ]);
    (new ProgressionService)->processVictory($user, 10, 5);

    expect($user->fresh()->streak_count)->toBe(1)
        ->and($user->fresh()->streak_freezes)->toBe(0);
});

it('consumes a freeze and preserves streak on 2-day gap', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now()->subDays(2),
        'streak_count' => 5,
        'streak_freezes' => 2,
    ]);
    (new ProgressionService)->processVictory($user, 10, 5);

    expect($user->fresh()->streak_count)->toBe(6)
        ->and($user->fresh()->streak_freezes)->toBe(1);
});

it('adds 200 rested XP on a 3+ day gap', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now()->subDays(4),
        'rested_xp_balance' => 0,
        'streak_freezes' => 0,
    ]);
    // Use 0 base XP so rested drain = 0 and we see the raw 200 accumulation
    (new ProgressionService)->processVictory($user, 0, 0);

    expect($user->fresh()->rested_xp_balance)->toBe(200);
});

it('does not add rested XP on a 2-day gap', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now()->subDays(2),
        'rested_xp_balance' => 0,
        'streak_freezes' => 0,
    ]);
    (new ProgressionService)->processVictory($user, 0, 0);

    expect($user->fresh()->rested_xp_balance)->toBe(0);
});

// ─── processVictory — XP multipliers ─────────────────────────────────────────

it('applies no streak bonus below 3-day streak', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now(), 'streak_count' => 2]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    expect($result['total_xp_earned'])->toBe(100);
});

it('applies 5% bonus at 3-day streak', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now(), 'streak_count' => 3]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    expect($result['total_xp_earned'])->toBe(105);
});

it('applies 15% bonus at 7-day streak', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now(), 'streak_count' => 7]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    expect($result['total_xp_earned'])->toBe(115);
});

// ─── processVictory — rested XP ──────────────────────────────────────────────

it('fully drains rested XP pool and adds 50% bonus', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now(),
        'streak_count' => 0,
        'rested_xp_balance' => 50,
    ]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    // restedBonus = round(100 * 0.5) = 50; pool has exactly 50 → full drain
    expect($result['total_xp_earned'])->toBe(150)
        ->and($user->fresh()->rested_xp_balance)->toBe(0);
});

it('partially drains rested XP when pool is smaller than the bonus', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now(),
        'streak_count' => 0,
        'rested_xp_balance' => 30,
    ]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    // restedBonus = 50, but pool only has 30 → earnedXp = 100 + 30 = 130
    expect($result['total_xp_earned'])->toBe(130)
        ->and($user->fresh()->rested_xp_balance)->toBe(0);
});

// ─── processVictory — XP boost ───────────────────────────────────────────────

it('applies xp boost multiplier and decrements remaining count', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now(),
        'xp_boost_multiplier' => 2,
        'xp_boost_lessons_remaining' => 3,
    ]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    expect($result['total_xp_earned'])->toBe(200)
        ->and($user->fresh()->xp_boost_lessons_remaining)->toBe(2)
        ->and($user->fresh()->xp_boost_multiplier)->toBe(2);
});

it('resets xp boost when last lesson is consumed', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create([
        'last_active_at' => now(),
        'xp_boost_multiplier' => 2,
        'xp_boost_lessons_remaining' => 1,
    ]);
    (new ProgressionService)->processVictory($user, 100, 0);

    expect($user->fresh()->xp_boost_lessons_remaining)->toBe(0)
        ->and($user->fresh()->xp_boost_multiplier)->toBe(1);
});

// ─── processVictory — level engine ───────────────────────────────────────────

it('levels up when XP crosses the threshold', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
    Event::fake([UserLeveledUp::class]);

    $user = User::factory()->create(['last_active_at' => now(), 'level' => 1, 'xp' => 0]);
    $result = (new ProgressionService)->processVictory($user, 100, 0);

    expect($user->fresh()->level)->toBe(2)
        ->and($result['leveled_up'])->toBeTrue();
    Event::assertDispatched(UserLeveledUp::class);
});

it('jumps multiple levels in a single processVictory call', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
    Event::fake([UserLeveledUp::class]);

    // Thresholds: L2=100, L3=250, L4=450, L5=700 → 450 XP lands on level 4
    $user = User::factory()->create(['last_active_at' => now(), 'level' => 1, 'xp' => 0]);
    (new ProgressionService)->processVictory($user, 450, 0);

    expect($user->fresh()->level)->toBe(4);
});

it('does not level up when XP stays below the next threshold', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);
    Event::fake([UserLeveledUp::class]);

    $user = User::factory()->create(['last_active_at' => now(), 'level' => 1, 'xp' => 0]);
    $result = (new ProgressionService)->processVictory($user, 50, 0);

    expect($user->fresh()->level)->toBe(1)
        ->and($result['leveled_up'])->toBeFalse();
    Event::assertNotDispatched(UserLeveledUp::class);
});

it('returns the expected result shape', function () {
    Redis::shouldReceive('zincrby')->andReturn(0);

    $user = User::factory()->create(['last_active_at' => now(), 'streak_count' => 0]);
    $result = (new ProgressionService)->processVictory($user, 50, 10);

    expect($result)->toHaveKeys(['status', 'base_xp', 'total_xp_earned', 'coins_earned', 'leveled_up', 'new_level', 'streak_count'])
        ->and($result['base_xp'])->toBe(50)
        ->and($result['coins_earned'])->toBe(10);
});

// ─── processVictory — shadowban ───────────────────────────────────────────────

it('shadowbanned user does not update Redis leaderboard', function () {
    Redis::shouldReceive('zincrby')->never();

    $user = User::factory()->create(['last_active_at' => now()]);
    DB::table('users')->where('id', $user->id)->update(['is_shadowbanned' => true]);
    $user->refresh();

    (new ProgressionService)->processVictory($user, 100, 0);
});
