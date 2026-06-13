<?php

use App\Filament\Widgets\LessonFunnelWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\StorePurchaseDistributionChartWidget;
use App\Filament\Widgets\XpVelocityChartWidget;
use App\Models\BlockSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSubmission;
use App\Models\StoreItem;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\UserInventory;
use App\Models\World;

// ─── helpers ─────────────────────────────────────────────────────────────────

function createDashboardLesson(): Lesson
{
    $theme = ThemePack::create(['name' => 'Dash Theme', 'identifier' => 'theme_dash_'.uniqid(), 'config' => []]);
    $world = World::create(['name' => 'Dash World', 'slug' => 'dash-world', 'theme_pack_id' => $theme->id]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Dash Course',
        'slug' => 'dash-course',
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
    ]);

    return Lesson::create([
        'course_id' => $course->id,
        'name' => 'Dash Lesson',
        'slug' => 'dash-lesson',
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => [],
    ]);
}

function createDashboardStoreItem(string $name): StoreItem
{
    return StoreItem::create([
        'name' => $name,
        'type' => 'streak_freeze',
        'purchase_type' => 'consumable',
        'price_coins' => 100,
        'effect_config' => ['quantity' => 1],
        'is_active' => true,
    ]);
}

/** Call a protected widget method (getStats/getData) without rendering. */
function callWidgetMethod(object $widget, string $method): mixed
{
    return (fn () => $this->{$method}())->call($widget);
}

// ─── OverviewStatsWidget ─────────────────────────────────────────────────────

it('counts daily and weekly active students in the overview stats', function () {
    User::factory()->create(['role' => 'student', 'last_active_at' => now()]);
    User::factory()->create(['role' => 'student', 'last_active_at' => now()->subDays(3)]);
    User::factory()->create(['role' => 'student', 'last_active_at' => now()->subDays(10)]);
    User::factory()->create(['role' => 'admin', 'last_active_at' => now()]);

    $stats = callWidgetMethod(new OverviewStatsWidget, 'getStats');

    expect($stats[0]->getValue())->toBe('3')   // Total Students
        ->and($stats[1]->getValue())->toBe('1') // Active Today
        ->and($stats[2]->getValue())->toBe('2'); // Active This Week
});

// ─── LessonFunnelWidget ──────────────────────────────────────────────────────

it('computes lesson starts and completions in the funnel query', function () {
    $lesson = createDashboardLesson();
    $users = User::factory()->count(3)->create(['role' => 'student']);

    foreach ($users as $user) {
        BlockSubmission::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'block_index' => 0,
            'block_title' => 'Intro',
            'xp_rewarded' => 5,
            'coins_rewarded' => 1,
        ]);
    }

    LessonSubmission::create([
        'user_id' => $users[0]->id,
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->slug,
        'xp_rewarded' => 50,
        'coins_rewarded' => 10,
    ]);

    $row = LessonFunnelWidget::getFunnelQuery()->firstOrFail();

    expect($row->starts_count)->toBe(3)
        ->and($row->completions_count)->toBe(1);
});

// ─── XpVelocityChartWidget ───────────────────────────────────────────────────

it('charts average xp per active student per day', function () {
    $lesson = createDashboardLesson();
    [$alice, $bob] = User::factory()->count(2)->create(['role' => 'student']);

    LessonSubmission::create([
        'user_id' => $alice->id,
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->slug,
        'xp_rewarded' => 100,
        'coins_rewarded' => 10,
    ]);
    BlockSubmission::create([
        'user_id' => $alice->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'block_title' => 'Intro',
        'xp_rewarded' => 20,
        'coins_rewarded' => 1,
    ]);
    BlockSubmission::create([
        'user_id' => $bob->id,
        'lesson_id' => $lesson->id,
        'block_index' => 0,
        'block_title' => 'Intro',
        'xp_rewarded' => 40,
        'coins_rewarded' => 1,
    ]);

    $data = callWidgetMethod(new XpVelocityChartWidget, 'getData');
    $todayPoint = end($data['datasets'][0]['data']);

    // (100 + 20 + 40) XP across 2 active students
    expect($todayPoint)->toBe(80.0)
        ->and($data['labels'])->toHaveCount(30);
});

// ─── StorePurchaseDistributionChartWidget ────────────────────────────────────

it('charts purchase counts grouped by store item', function () {
    $user = User::factory()->create(['role' => 'student']);
    $sword = createDashboardStoreItem('Pixel Sword');
    $shield = createDashboardStoreItem('Pixel Shield');

    UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $sword->id,
        'quantity' => 2,
        'acquired_at' => now(),
    ]);
    UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $shield->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $data = callWidgetMethod(new StorePurchaseDistributionChartWidget, 'getData');

    expect($data['labels'])->toBe(['Pixel Sword', 'Pixel Shield'])
        ->and($data['datasets'][0]['data'])->toBe([2, 1]);
});
