<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'agent_ada',
        'forename' => 'Ada',
        'lastname' => 'Lovelace',
        'birthday' => '2014-05-10',
        'gender' => 'female',
        'email' => 'ada@example.com',
        'password' => 'password-1234',
        'password_confirmation' => 'password-1234',
    ], $overrides);
}

it('renders the registration page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Student/Register'));
});

it('registers a student, logs them in, and redirects to worlds', function () {
    $response = $this->post('/register/student', validRegistrationPayload());

    $response->assertRedirect('/worlds');
    $this->assertAuthenticated();

    $user = User::where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('student')
        ->and($user->name)->toBe('agent_ada')
        ->and($user->forename)->toBe('Ada')
        ->and($user->lastname)->toBe('Lovelace')
        ->and($user->gender)->toBe('female')
        ->and($user->birthday->format('Y-m-d'))->toBe('2014-05-10');
});

it('rejects a duplicate username', function () {
    User::factory()->create(['name' => 'agent_ada']);

    $this->post('/register/student', validRegistrationPayload())
        ->assertSessionHasErrors('name');

    $this->assertGuest();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/register/student', validRegistrationPayload())
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects a password confirmation mismatch', function () {
    $this->post('/register/student', validRegistrationPayload([
        'password_confirmation' => 'different-1234',
    ]))->assertSessionHasErrors('password');

    $this->assertGuest();
});

it('rejects a gender outside the allowed set', function () {
    $this->post('/register/student', validRegistrationPayload([
        'gender' => 'other',
    ]))->assertSessionHasErrors('gender');

    $this->assertGuest();
});

it('rejects a birthday that is not in the past', function () {
    $this->post('/register/student', validRegistrationPayload([
        'birthday' => now()->addDay()->format('Y-m-d'),
    ]))->assertSessionHasErrors('birthday');

    $this->assertGuest();
});

it('rejects missing required fields', function () {
    $this->post('/register/student', [])
        ->assertSessionHasErrors(['name', 'forename', 'lastname', 'birthday', 'gender', 'email', 'password']);

    $this->assertGuest();
});
