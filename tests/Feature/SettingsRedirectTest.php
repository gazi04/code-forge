<?php

use App\Models\User;

it('redirects an authenticated user from settings to the profile page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings')
        ->assertRedirect(route('profile.edit'));
});

it('redirects a guest visiting settings to login', function () {
    $this->get('/settings')
        ->assertRedirect(route('login'));
});

it('returns 405 for non-GET requests to settings', function () {
    $this->actingAs(User::factory()->create())
        ->post('/settings')
        ->assertMethodNotAllowed();
});
