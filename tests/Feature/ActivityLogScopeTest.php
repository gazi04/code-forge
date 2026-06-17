<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('does not log high-churn gamification field changes', function () {
    $user = User::factory()->create();
    $before = Activity::count();

    $user->update(['xp' => $user->xp + 100, 'coins' => $user->coins + 50]);

    expect(Activity::count())->toBe($before);
});

it('logs admin-meaningful identity field changes', function () {
    $user = User::factory()->create();
    $before = Activity::count();

    $user->update(['name' => 'Renamed Student']);

    expect(Activity::count())->toBe($before + 1);
});
