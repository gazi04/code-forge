<?php

use App\Models\Achievement;
use App\Models\User;

it('renders the public profile for a visible student', function () {
    $student = User::factory()->create([
        'preferences' => ['public_profile' => true],
        'level' => 4,
        'xp' => 350,
        'streak_count' => 6,
    ]);

    $this->get("/u/{$student->name}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Student/Profile/Public')
            ->where('hero.name', $student->name)
            ->where('hero.level', 4)
            ->where('hero.xp', 350)
            ->where('hero.streak_count', 6)
            ->has('achievements')
            ->has('certificates'));
});

it('does not expose coins on the public profile', function () {
    $student = User::factory()->create(['coins' => 999]);

    $this->get("/u/{$student->name}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hero.coins', null));
});

it('treats a missing public_profile preference as public by default', function () {
    $student = User::factory()->create(['preferences' => []]);

    $this->get("/u/{$student->name}")->assertOk();
});

it('returns 404 when the student disabled their public profile', function () {
    $student = User::factory()->create([
        'preferences' => ['public_profile' => false],
    ]);

    $this->get("/u/{$student->name}")->assertNotFound();
});

it('returns 404 for shadowbanned students', function () {
    $student = User::factory()->create([
        'is_shadowbanned' => true,
        'preferences' => ['public_profile' => true],
    ]);

    $this->get("/u/{$student->name}")->assertNotFound();
});

it('returns 404 for admin users', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->get("/u/{$admin->name}")->assertNotFound();
});

it('returns 404 for a non-existent username', function () {
    $this->get('/u/this-user-does-not-exist-ever')->assertNotFound();
});

it('marks earned achievements as unlocked and the rest as locked', function () {
    $student = User::factory()->create();

    $earned = Achievement::create([
        'name' => 'First Steps',
        'description' => 'Complete your first lesson',
        'metric_type' => 'lessons_completed',
        'threshold' => 1,
    ]);
    Achievement::create([
        'name' => 'Marathon',
        'description' => 'Complete 50 lessons',
        'metric_type' => 'lessons_completed',
        'threshold' => 50,
    ]);

    $student->achievements()->attach($earned->id, ['unlocked_at' => now()]);

    $this->get("/u/{$student->name}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('achievements', 2)
            ->where('achievements.0.unlocked', true)
            ->where('achievements.1.unlocked', false));
});

it('allows a student to toggle public profile visibility', function () {
    $student = User::factory()->create([
        'preferences' => [
            'background_audio' => true,
            'sound_effects' => true,
            'accessibility_mode' => false,
            'public_profile' => true,
        ],
    ]);

    $this->actingAs($student)->put('/profile/settings', [
        'background_audio' => true,
        'sound_effects' => true,
        'accessibility_mode' => false,
        'public_profile' => false,
    ])->assertRedirect();

    expect($student->refresh()->preferences['public_profile'])->toBeFalse();

    $this->get("/u/{$student->name}")->assertNotFound();
});
