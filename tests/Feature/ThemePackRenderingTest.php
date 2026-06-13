<?php

use App\Http\Resources\WorldResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\ThemePack;
use App\Models\User;
use App\Models\World;
use Inertia\Testing\AssertableInertia as Assert;

// ─── helpers ─────────────────────────────────────────────────────────────────

function makeTheme(array $paletteOverrides = []): ThemePack
{
    return ThemePack::create([
        'name' => 'Render Theme',
        'identifier' => 'theme_render_'.uniqid(),
        'config' => [
            'palette' => array_merge([
                'primary' => '#8b5cf6',
                'secondary' => '#0f172a',
                'accent' => '#10b981',
                'background' => '#09090b',
                'surface' => '#18181b',
                'text' => '#f8fafc',
            ], $paletteOverrides),
            'background' => ['style' => 'solid', 'value' => '#09090b'],
            'ui' => [
                'card_style' => 'glassy',
                'border_radius' => 'lg',
                'font_style' => 'monospace',
                'course_layout' => 'grid',
                'map_layout' => 'linear',
            ],
            'audio' => [
                'background_music_url' => null,
                'level_up_sfx_url' => null,
                'boss_intro_sfx_url' => null,
            ],
        ],
    ]);
}

function makeThemeLessonStack(ThemePack $theme, string $suffix = ''): array
{
    $world = World::create([
        'name' => 'Theme World '.$suffix,
        'slug' => 'theme-world-'.$suffix,
        'theme_pack_id' => $theme->id,
    ]);
    $course = Course::create([
        'world_id' => $world->id,
        'name' => 'Theme Course',
        'slug' => 'theme-course-'.$suffix,
        'age_tier' => 'junior',
        'difficulty' => 1,
        'estimated_duration' => 30,
        'min_level_requirement' => 1,
    ]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'name' => 'Theme Lesson',
        'slug' => 'theme-lesson-'.$suffix,
        'xp_reward' => 50,
        'coin_reward' => 10,
        'estimated_duration' => 10,
        'blocks' => [],
    ]);

    return compact('world', 'course', 'lesson');
}

// ─── Lesson page ─────────────────────────────────────────────────────────────

it('lesson page delivers palette primary color via theme.config', function () {
    $theme = makeTheme(['primary' => '#ff6600']);
    ['lesson' => $lesson] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/lessons/{$lesson->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('theme.config.palette.primary', '#ff6600')
        );
});

it('lesson page delivers ui.map_layout via theme.config', function () {
    $theme = makeTheme();
    ['lesson' => $lesson] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/lessons/{$lesson->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('theme.config.ui.map_layout', 'linear')
        );
});

it('lesson page loads without error when all audio URLs are null', function () {
    $theme = makeTheme();
    ['lesson' => $lesson] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/lessons/{$lesson->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('theme.config.audio')
        );
});

// ─── Course page ──────────────────────────────────────────────────────────────

it('course page delivers palette primary color via world.theme.config', function () {
    $theme = makeTheme(['primary' => '#cc00ff']);
    ['course' => $course] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create(['level' => 1]);

    $this->actingAs($user)
        ->get("/course/{$course->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('world.data.theme.config.palette.primary', '#cc00ff')
        );
});

it('course page delivers ui.map_layout via world.theme.config', function () {
    $theme = makeTheme();
    ['course' => $course] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create(['level' => 1]);

    $this->actingAs($user)
        ->get("/course/{$course->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('world.data.theme.config.ui.map_layout', 'linear')
        );
});

// ─── map_layout enum coverage ────────────────────────────────────────────────

it('course page delivers each map_layout value via world.data.theme.config', function (string $mapLayout) {
    $theme = ThemePack::create([
        'name' => 'ML Theme',
        'identifier' => 'theme_ml_'.uniqid(),
        'config' => [
            'palette' => ['primary' => '#8b5cf6', 'secondary' => '#0f172a', 'accent' => '#10b981', 'background' => '#09090b', 'surface' => '#18181b', 'text' => '#f8fafc'],
            'background' => ['style' => 'solid', 'value' => '#09090b'],
            'ui' => ['card_style' => 'flat', 'border_radius' => 'md', 'font_style' => 'default', 'course_layout' => 'grid', 'map_layout' => $mapLayout],
            'audio' => ['background_music_url' => null, 'level_up_sfx_url' => null, 'boss_intro_sfx_url' => null],
        ],
    ]);
    ['course' => $course] = makeThemeLessonStack($theme, uniqid());
    $user = User::factory()->create(['level' => 1]);

    $this->actingAs($user)
        ->get("/course/{$course->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('world.data.theme.config.ui.map_layout', $mapLayout)
        );
})->with(['linear', 'branching', 'road']);

// ─── WorldResource direct serialization ──────────────────────────────────────

it('WorldResource serializes theme config with palette and ui keys', function () {
    $theme = makeTheme(['primary' => '#abcdef']);
    ['world' => $world] = makeThemeLessonStack($theme, uniqid());
    $world->load('themePack');

    $output = (new WorldResource($world))->toArray(request());

    expect($output['theme']['config']['palette']['primary'])->toBe('#abcdef')
        ->and($output['theme']['config']['ui']['map_layout'])->toBe('linear');
});
