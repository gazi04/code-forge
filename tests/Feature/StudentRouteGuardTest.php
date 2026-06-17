<?php

use App\Models\User;

it('redirects an admin away from a student surface to the admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('student.store.index'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('allows a student to access a student surface', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.store.index'))
        ->assertOk();
});
