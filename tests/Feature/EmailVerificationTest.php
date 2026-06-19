<?php

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends a verification email when a student registers', function () {
    Notification::fake();

    $this->post('/register/student', [
        'name' => 'verify_me',
        'forename' => 'Vera',
        'lastname' => 'Fyer',
        'birthday' => '2014-05-10',
        'gender' => 'female',
        'email' => 'vera@example.com',
        'password' => 'password-1234',
        'password_confirmation' => 'password-1234',
    ]);

    $user = User::where('email', 'vera@example.com')->firstOrFail();

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('queues the verification notification so registration never blocks on mail', function () {
    expect(new QueuedVerifyEmail)->toBeInstanceOf(ShouldQueue::class);
});

it('redirects an unverified student away from the app to the verification notice', function () {
    $user = User::factory()->unverified()->create(['role' => 'student']);

    $this->actingAs($user)
        ->get('/worlds')
        ->assertRedirect(route('verification.notice'));
});

it('lets a verified student into the app', function () {
    $user = User::factory()->create(['role' => 'student']); // verified by default

    $this->actingAs($user)
        ->get('/worlds')
        ->assertOk();
});

it('marks the user verified when the signed verification link is visited', function () {
    $user = User::factory()->unverified()->create(['role' => 'student']);

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
