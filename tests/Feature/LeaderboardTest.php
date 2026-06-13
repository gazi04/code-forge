<?php

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    try {
        Redis::connection()->ping();
    } catch (Exception $e) {
        $this->markTestSkipped('Redis not available: '.$e->getMessage());
    }

    Redis::del('leaderboard:all_time', 'leaderboard:weekly');
});

// ─── Leaderboard ─────────────────────────────────────────────────────────────

it('returns leaders in descending score order', function () {
    $alice = User::factory()->create(['name' => 'Alice', 'level' => 5]);
    $bob = User::factory()->create(['name' => 'Bob', 'level' => 3]);
    $charlie = User::factory()->create(['name' => 'Charlie', 'level' => 1]);

    Redis::zadd('leaderboard:weekly', 500, 'Alice');
    Redis::zadd('leaderboard:weekly', 300, 'Bob');
    Redis::zadd('leaderboard:weekly', 100, 'Charlie');

    $this->actingAs($alice)
        ->get(route('student.leaderboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Leaderboard/Index')
            ->where('leaders.0.name', 'Alice')
            ->where('leaders.0.rank', 1)
            ->where('leaders.0.xp', 500)
            ->where('leaders.1.name', 'Bob')
            ->where('leaders.2.name', 'Charlie')
        );
});

it('enriches leaders with level from the database', function () {
    $user = User::factory()->create(['name' => 'LevelUser', 'level' => 7]);

    Redis::zadd('leaderboard:weekly', 200, 'LevelUser');

    $this->actingAs($user)
        ->get(route('student.leaderboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('leaders.0.level', 7)
        );
});

it('includes the authenticated user rank and score in the player prop', function () {
    $user = User::factory()->create(['name' => 'Player1', 'level' => 2]);

    Redis::zadd('leaderboard:weekly', 150, 'Player1');

    $this->actingAs($user)
        ->get(route('student.leaderboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('player.name', 'Player1')
            ->where('player.xp', 150)
            ->where('player.rank', 1)
        );
});

it('returns all_time leaderboard when scope query param is all_time', function () {
    $user = User::factory()->create(['name' => 'TimeUser', 'level' => 1]);

    Redis::zadd('leaderboard:all_time', 999, 'TimeUser');

    $this->actingAs($user)
        ->get(route('student.leaderboard', ['scope' => 'all_time']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scope', 'all_time')
            ->where('leaders.0.name', 'TimeUser')
            ->where('leaders.0.xp', 999)
        );
});
