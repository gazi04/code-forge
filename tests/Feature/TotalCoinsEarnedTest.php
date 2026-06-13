<?php

use App\Events\ProgressRegistered;
use App\Listeners\EvaluateAchievements;
use App\Models\Achievement;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    try {
        Redis::connection()->ping();
    } catch (Exception $e) {
        $this->markTestSkipped('Redis not available: '.$e->getMessage());
    }

    Redis::del('leaderboard:all_time', 'leaderboard:weekly');
});

it('increments total_coins_earned alongside coins in processVictory', function () {
    $user = User::factory()->create(['coins' => 0, 'total_coins_earned' => 0]);

    app(ProgressionService::class)->processVictory($user, baseXp: 10, baseCoins: 50);

    $user->refresh();

    expect($user->coins)->toBe(50)
        ->and($user->total_coins_earned)->toBe(50);
});

it('does not decrement total_coins_earned when coins are spent', function () {
    $user = User::factory()->create(['coins' => 100, 'total_coins_earned' => 100]);

    $user->decrement('coins', 90);
    $user->refresh();

    expect($user->coins)->toBe(10)
        ->and($user->total_coins_earned)->toBe(100);
});

it('unlocks total_coins_earned achievement based on earned total not spendable balance', function () {
    $user = User::factory()->create(['coins' => 0, 'total_coins_earned' => 0]);

    $achievement = Achievement::factory()->create([
        'name' => 'Coin Collector',
        'metric_type' => 'total_coins_earned',
        'threshold' => 100,
        'target_id' => null,
    ]);

    // Earn 100 coins then spend 90 — balance is 10 but earned total is 100
    app(ProgressionService::class)->processVictory($user, baseXp: 0, baseCoins: 100);
    $user->refresh();
    $user->decrement('coins', 90);
    $user->refresh();

    expect($user->coins)->toBe(10)
        ->and($user->total_coins_earned)->toBe(100);

    $listener = new EvaluateAchievements;
    $listener->handle(new ProgressRegistered($user));

    $user->refresh();
    expect($user->achievements()->where('achievement_id', $achievement->id)->exists())->toBeTrue();
});
