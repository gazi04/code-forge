<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

it('rebuilds the all-time leaderboard from non-shadowbanned users with xp', function () {
    $a = User::factory()->create(['xp' => 300]);
    $b = User::factory()->create(['xp' => 150]);
    User::factory()->create(['xp' => 0]); // no score → excluded
    $banned = User::factory()->create(['xp' => 999]);
    DB::table('users')->where('id', $banned->id)->update(['is_shadowbanned' => true]);

    Redis::shouldReceive('del')->once()->with('leaderboard:all_time');
    Redis::shouldReceive('zadd')->once()->with('leaderboard:all_time', 300, $a->id);
    Redis::shouldReceive('zadd')->once()->with('leaderboard:all_time', 150, $b->id);
    // Strict mock: any zadd for the zero-xp or shadowbanned user would fail the test.

    $this->artisan('app:rebuild-leaderboard')->assertSuccessful();
});
