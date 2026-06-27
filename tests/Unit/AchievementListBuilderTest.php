<?php

use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementListBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists every achievement as locked when the user has earned none', function () {
    Achievement::create(['name' => 'First Steps', 'description' => 'Start', 'metric_type' => 'total_lessons_completed', 'threshold' => 1]);
    Achievement::create(['name' => 'Marathon', 'description' => '50 lessons', 'metric_type' => 'total_lessons_completed', 'threshold' => 50]);

    $user = User::factory()->create();

    $list = (new AchievementListBuilder)->buildAchievementList($user);

    expect($list)->toHaveCount(2)
        ->and($list->pluck('unlocked')->all())->toBe([false, false]);
});

it('flags earned achievements as unlocked with an unlocked_at timestamp', function () {
    $earned = Achievement::create(['name' => 'First Steps', 'description' => 'Start', 'metric_type' => 'total_lessons_completed', 'threshold' => 1]);
    Achievement::create(['name' => 'Marathon', 'description' => '50 lessons', 'metric_type' => 'total_lessons_completed', 'threshold' => 50]);

    $user = User::factory()->create();
    $user->achievements()->attach($earned->id, ['unlocked_at' => now()]);

    $list = (new AchievementListBuilder)->buildAchievementList($user);
    $earnedRow = $list->firstWhere('id', $earned->id);

    expect($earnedRow['unlocked'])->toBeTrue()
        ->and($earnedRow['unlocked_at'])->not->toBeNull();
});
