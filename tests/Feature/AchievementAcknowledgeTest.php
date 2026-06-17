<?php

use App\Models\User;

$pending = [['id' => 1, 'name' => 'First Steps', 'description' => 'Did a thing', 'image_path' => null]];

it('does not clear pending achievements while resolving shared props on a page load', function () use ($pending) {
    $user = User::factory()->create(['pending_achievements' => $pending]);

    $this->actingAs($user)
        ->get(route('student.world.index'))
        ->assertOk();

    // The shared prop is read-only now — a normal page load must not consume them.
    expect($user->fresh()->pending_achievements)->toBe($pending);
});

it('clears pending achievements via the acknowledge endpoint', function () use ($pending) {
    $user = User::factory()->create(['pending_achievements' => $pending]);

    $this->actingAs($user)
        ->from(route('student.world.index'))
        ->post(route('student.achievements.acknowledge'), ['ids' => [1]])
        ->assertRedirect();

    expect($user->fresh()->pending_achievements)->toBeNull();
});

it('clears only the acknowledged ids and preserves newer pending achievements', function () {
    $first = ['id' => 1, 'name' => 'First Steps', 'description' => 'Did a thing', 'image_path' => null];
    $second = ['id' => 2, 'name' => 'Second Wind', 'description' => 'Did another thing', 'image_path' => null];
    $user = User::factory()->create(['pending_achievements' => [$first, $second]]);

    // The client only saw id 1; id 2 arrived after the prop was built.
    $this->actingAs($user)
        ->from(route('student.world.index'))
        ->post(route('student.achievements.acknowledge'), ['ids' => [1]])
        ->assertRedirect();

    expect($user->fresh()->pending_achievements)->toBe([$second]);
});

it('requires authentication to acknowledge achievements', function () {
    $this->post(route('student.achievements.acknowledge'), ['ids' => [1]])
        ->assertRedirect(route('login'));
});
