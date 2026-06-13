<?php

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('returns false when streak_count is 0', function () {
    $user = new User(['streak_count' => 0, 'last_active_at' => now()->subDay()]);

    expect($user->isStreakAtRisk())->toBeFalse();
});

it('returns false when last_active_at is null', function () {
    $user = new User(['streak_count' => 5, 'last_active_at' => null]);

    expect($user->isStreakAtRisk())->toBeFalse();
});

it('returns false when user was active today', function () {
    $user = new User(['streak_count' => 5, 'last_active_at' => now()]);

    expect($user->isStreakAtRisk())->toBeFalse();
});

it('returns false when user was active at start of today', function () {
    $user = new User(['streak_count' => 5, 'last_active_at' => today()->startOfDay()]);

    expect($user->isStreakAtRisk())->toBeFalse();
});

it('returns true when user was last active yesterday', function () {
    $user = new User(['streak_count' => 5, 'last_active_at' => now()->subDay()]);

    expect($user->isStreakAtRisk())->toBeTrue();
});

it('returns true when user was last active several days ago', function () {
    $user = new User(['streak_count' => 3, 'last_active_at' => now()->subDays(3)]);

    expect($user->isStreakAtRisk())->toBeTrue();
});
