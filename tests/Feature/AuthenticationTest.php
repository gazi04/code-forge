<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\Hash;

// ─── helpers ─────────────────────────────────────────────────────────────────

function makeCourseAuth(int $minLevel = 1): array
{
    $theme = ThemePack::create(['name' => 'Auth Theme', 'identifier' => 'theme_auth_'.uniqid(), 'config' => []]);
    $world = World::create(['name' => 'Auth World', 'slug' => 'auth-world-'.uniqid(), 'theme_pack_id' => $theme->id]);
    $course = Course::create(['world_id' => $world->id, 'name' => 'Auth Course', 'slug' => 'auth-course-'.uniqid(), 'age_tier' => 'junior', 'difficulty' => 1, 'estimated_duration' => 30, 'min_level_requirement' => $minLevel]);
    $lesson = Lesson::create(['course_id' => $course->id, 'name' => 'Auth Lesson', 'slug' => 'auth-lesson-'.uniqid(), 'xp_reward' => 10, 'coin_reward' => 5, 'estimated_duration' => 5, 'blocks' => []]);

    return compact('theme', 'world', 'course', 'lesson');
}

// ─── Unauthenticated access redirects to login ────────────────────────────────

it('guest is redirected to login when accessing leaderboard', function () {
    $this->get(route('student.leaderboard'))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when accessing store', function () {
    $this->get(route('student.store.index'))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when accessing worlds index', function () {
    $this->get(route('student.world.index'))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when accessing a world page', function () {
    ['world' => $world] = makeCourseAuth();

    $this->get(route('student.world.show', $world->slug))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when accessing a course page', function () {
    ['course' => $course] = makeCourseAuth();

    $this->get(route('student.course.show', $course->slug))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when accessing a lesson page', function () {
    ['lesson' => $lesson] = makeCourseAuth();

    $this->get(route('student.lessons.show', $lesson->slug))
        ->assertRedirect(route('login'));
});

it('guest is redirected to login when submitting a lesson', function () {
    ['lesson' => $lesson] = makeCourseAuth();

    $this->post(route('student.lessons.submit', $lesson->slug))
        ->assertRedirect(route('login'));
});

// ─── Student login flow ───────────────────────────────────────────────────────

it('student logs in with valid credentials and is redirected to worlds', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('student.login.submit'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/worlds');

    $this->assertAuthenticatedAs($user);
});

it('login fails with wrong password and returns an email error', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'password' => Hash::make('correct-password'),
    ]);

    $this->post(route('student.login.submit'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect()
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('admin login via student form is rejected with an error message', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('student.login.submit'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect()
        ->assertSessionHasErrors(['email' => 'Admins must log in through the admin portal.']);

    $this->assertGuest();
});

// ─── CoursePolicy ─────────────────────────────────────────────────────────────

it('admin is forbidden from viewing a course page', function () {
    ['course' => $course] = makeCourseAuth(minLevel: 1);
    $admin = User::factory()->create(['role' => 'admin', 'level' => 99]);

    $this->actingAs($admin)
        ->get(route('student.course.show', $course->slug))
        ->assertForbidden();
});

it('student below the level requirement is forbidden from viewing a course', function () {
    ['course' => $course] = makeCourseAuth(minLevel: 5);
    $student = User::factory()->create(['role' => 'student', 'level' => 1]);

    $this->actingAs($student)
        ->get(route('student.course.show', $course->slug))
        ->assertForbidden();
});

it('student exactly at the level requirement can view a course', function () {
    ['course' => $course] = makeCourseAuth(minLevel: 5);
    $student = User::factory()->create(['role' => 'student', 'level' => 5]);

    $this->actingAs($student)
        ->get(route('student.course.show', $course->slug))
        ->assertOk();
});

it('student above the level requirement can view a course', function () {
    ['course' => $course] = makeCourseAuth(minLevel: 5);
    $student = User::factory()->create(['role' => 'student', 'level' => 10]);

    $this->actingAs($student)
        ->get(route('student.course.show', $course->slug))
        ->assertOk();
});
