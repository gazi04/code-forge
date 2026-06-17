<?php

use App\Events\ProgressRegistered;
use App\Listeners\EvaluateAchievements;
use App\Models\Achievement;
use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\DB;

function makeLessonForAchievements(array $blocks): Lesson
{
    $theme = ThemePack::create(['name' => 'T', 'identifier' => 'theme_'.uniqid(), 'config' => []]);
    $world = World::create(['name' => 'W', 'slug' => 'w-'.uniqid(), 'theme_pack_id' => $theme->id]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'C',
        'slug' => 'c-'.uniqid(),
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
    ]);

    return Lesson::create([
        'course_id' => $course->id,
        'name' => 'L',
        'slug' => 'l-'.uniqid(),
        'xp_reward' => 10,
        'coin_reward' => 5,
        'estimated_duration' => 5,
        'blocks' => $blocks,
    ]);
}

it('counts completed block types from the lesson JSON without driver-specific SQL', function () {
    $user = User::factory()->create();
    $lesson = makeLessonForAchievements([
        ['type' => 'quiz', 'data' => []],
        ['type' => 'code_challenge', 'data' => []],
    ]);

    // Student cleared only the quiz block (index 0).
    BlockSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'xp_rewarded' => 10,
        'coins_rewarded' => 5,
    ]);

    $quizAch = Achievement::create(['name' => 'Quizzer', 'metric_type' => 'specific_block_type_completed', 'target_id' => 'quiz', 'threshold' => 1]);
    $codeAch = Achievement::create(['name' => 'Coder', 'metric_type' => 'specific_block_type_completed', 'target_id' => 'code_challenge', 'threshold' => 1]);

    (new EvaluateAchievements)->handle(new ProgressRegistered($user));

    $earned = $user->fresh()->achievements()->pluck('achievement_id');
    expect($earned)->toContain($quizAch->id)
        ->and($earned)->not->toContain($codeAch->id);

    $pendingIds = collect($user->fresh()->pending_achievements)->pluck('id');
    expect($pendingIds)->toContain($quizAch->id);
});

it('does not throw or duplicate when evaluation runs twice', function () {
    $user = User::factory()->create(['xp' => 500]);
    $ach = Achievement::create(['name' => 'XP Hoarder', 'metric_type' => 'total_xp_earned', 'target_id' => null, 'threshold' => 100]);

    $listener = new EvaluateAchievements;
    $listener->handle(new ProgressRegistered($user));
    $listener->handle(new ProgressRegistered($user->fresh()));

    expect(DB::table('achievement_user')
        ->where('user_id', $user->id)
        ->where('achievement_id', $ach->id)
        ->count()
    )->toBe(1);
});

it('unlocks a simple total_xp_earned achievement when the threshold is met', function () {
    $user = User::factory()->create(['xp' => 250]);
    $ach = Achievement::create(['name' => 'Initiate', 'metric_type' => 'total_xp_earned', 'target_id' => null, 'threshold' => 200]);

    (new EvaluateAchievements)->handle(new ProgressRegistered($user));

    expect($user->fresh()->achievements()->pluck('achievement_id'))->toContain($ach->id);
});

it('does not evaluate lesson/course metrics on a block-source event', function () {
    $user = User::factory()->create();
    $lesson = makeLessonForAchievements([['type' => 'quiz', 'data' => []]]);
    $courseId = $lesson->course_id;

    // User has fully completed the single-lesson course.
    LessonSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'course_id' => $courseId,
        'xp_rewarded' => 10,
        'coins_rewarded' => 5,
    ]);

    $courseAch = Achievement::create(['name' => 'Course Clear', 'metric_type' => 'specific_course_completed', 'target_id' => (string) $courseId, 'threshold' => 1]);
    $lessonsAch = Achievement::create(['name' => 'One Lesson', 'metric_type' => 'total_lessons_completed', 'target_id' => null, 'threshold' => 1]);

    (new EvaluateAchievements)->handle(new ProgressRegistered($user, 'block'));

    $earned = $user->fresh()->achievements()->pluck('achievement_id');
    expect($earned)->not->toContain($courseAch->id)
        ->and($earned)->not->toContain($lessonsAch->id);
});

it('does not evaluate block-type metrics on a lesson-source event', function () {
    $user = User::factory()->create();
    $lesson = makeLessonForAchievements([['type' => 'quiz', 'data' => []]]);

    BlockSubmission::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'xp_rewarded' => 10,
        'coins_rewarded' => 5,
    ]);

    $quizAch = Achievement::create(['name' => 'Quizzer', 'metric_type' => 'specific_block_type_completed', 'target_id' => 'quiz', 'threshold' => 1]);

    (new EvaluateAchievements)->handle(new ProgressRegistered($user, 'lesson'));

    expect($user->fresh()->achievements()->pluck('achievement_id'))->not->toContain($quizAch->id);
});

it('unlocks specific_course_completed for multiple courses in one lesson-source evaluation', function () {
    $user = User::factory()->create();
    $lessonA = makeLessonForAchievements([['type' => 'quiz', 'data' => []]]);
    $lessonB = makeLessonForAchievements([['type' => 'quiz', 'data' => []]]);

    foreach ([$lessonA, $lessonB] as $lesson) {
        LessonSubmission::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'xp_rewarded' => 10,
            'coins_rewarded' => 5,
        ]);
    }

    $achA = Achievement::create(['name' => 'Clear A', 'metric_type' => 'specific_course_completed', 'target_id' => (string) $lessonA->course_id, 'threshold' => 1]);
    $achB = Achievement::create(['name' => 'Clear B', 'metric_type' => 'specific_course_completed', 'target_id' => (string) $lessonB->course_id, 'threshold' => 1]);

    (new EvaluateAchievements)->handle(new ProgressRegistered($user, 'lesson'));

    $earned = $user->fresh()->achievements()->pluck('achievement_id');
    expect($earned)->toContain($achA->id)
        ->and($earned)->toContain($achB->id);
});
