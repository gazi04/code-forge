<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shares streak_at_risk as true when user has not played today', function () {
    $user = User::factory()->create([
        'streak_count' => 5,
        'last_active_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.streak_at_risk', true)
        );
});

it('shares streak_at_risk as false when user was active today', function () {
    $user = User::factory()->create([
        'streak_count' => 5,
        'last_active_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.streak_at_risk', false)
        );
});

it('shares streak_at_risk as false when user has no streak', function () {
    $user = User::factory()->create([
        'streak_count' => 0,
        'last_active_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.streak_at_risk', false)
        );
});

it('shares auth.user as null for unauthenticated requests', function () {
    $this->get(route('student.profile'))
        ->assertRedirect(route('login'));
});
