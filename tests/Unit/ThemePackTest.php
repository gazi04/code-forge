<?php

use App\Models\ThemePack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── helpers ─────────────────────────────────────────────────────────────────

function baseConfig(array $uiOverrides = [], array $bgOverrides = [], array $audioOverrides = []): array
{
    return [
        'palette' => [
            'primary' => '#8b5cf6',
            'secondary' => '#0f172a',
            'accent' => '#10b981',
            'background' => '#09090b',
            'surface' => '#18181b',
            'text' => '#f8fafc',
        ],
        'background' => array_merge(['style' => 'solid', 'value' => '#09090b'], $bgOverrides),
        'ui' => array_merge([
            'card_style' => 'glassy',
            'border_radius' => 'lg',
            'font_style' => 'monospace',
            'course_layout' => 'grid',
            'map_layout' => 'linear',
        ], $uiOverrides),
        'audio' => array_merge([
            'background_music_url' => null,
            'level_up_sfx_url' => null,
            'boss_intro_sfx_url' => null,
        ], $audioOverrides),
    ];
}

function makeFullThemePack(array $overrides = []): ThemePack
{
    return ThemePack::create(array_merge([
        'name' => 'Test Theme',
        'identifier' => 'theme_test_'.uniqid(),
        'config' => baseConfig(),
    ], $overrides));
}

// ─── ThemePack model ──────────────────────────────────────────────────────────

it('casts config to array on retrieval', function () {
    $theme = makeFullThemePack();

    expect(ThemePack::find($theme->id)->config)->toBeArray();
});

it('stores all palette color keys', function () {
    $theme = makeFullThemePack();

    expect($theme->fresh()->config['palette'])
        ->toHaveKeys(['primary', 'secondary', 'accent', 'background', 'surface', 'text']);
});

it('stores all ui keys', function () {
    $theme = makeFullThemePack();

    expect($theme->fresh()->config['ui'])
        ->toHaveKeys(['card_style', 'border_radius', 'font_style', 'map_layout']);
});

it('stores background style and value keys', function () {
    $theme = makeFullThemePack();

    expect($theme->fresh()->config['background'])
        ->toHaveKeys(['style', 'value']);
});

it('is valid when all audio URLs are null', function () {
    $theme = makeFullThemePack();

    expect($theme->fresh()->config['audio'])->toMatchArray([
        'background_music_url' => null,
        'level_up_sfx_url' => null,
        'boss_intro_sfx_url' => null,
    ]);
});

// ─── Enum value coverage ──────────────────────────────────────────────────────

it('stores each background.style value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(bgOverrides: ['style' => $value]),
    ]);

    expect($theme->fresh()->config['background']['style'])->toBe($value);
})->with(['solid', 'gradient', 'pattern', 'image']);

it('stores each ui.card_style value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(uiOverrides: ['card_style' => $value]),
    ]);

    expect($theme->fresh()->config['ui']['card_style'])->toBe($value);
})->with(['flat', 'bordered', 'glassy', 'embossed', 'pixel']);

it('stores each ui.border_radius value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(uiOverrides: ['border_radius' => $value]),
    ]);

    expect($theme->fresh()->config['ui']['border_radius'])->toBe($value);
})->with(['none', 'sm', 'md', 'lg', 'full']);

it('stores each ui.font_style value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(uiOverrides: ['font_style' => $value]),
    ]);

    expect($theme->fresh()->config['ui']['font_style'])->toBe($value);
})->with(['default', 'monospace', 'rounded', 'medieval', 'futuristic']);

it('stores each ui.course_layout value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(uiOverrides: ['course_layout' => $value]),
    ]);

    expect($theme->fresh()->config['ui']['course_layout'])->toBe($value);
})->with(['grid', 'carousel', 'asymmetrical', 'cinematic']);

it('stores each ui.map_layout value', function (string $value) {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(uiOverrides: ['map_layout' => $value]),
    ]);

    expect($theme->fresh()->config['ui']['map_layout'])->toBe($value);
})->with(['linear', 'branching', 'road']);

it('stores non-null audio URLs correctly', function () {
    $theme = ThemePack::create([
        'name' => 'T',
        'identifier' => 'theme_'.uniqid(),
        'config' => baseConfig(audioOverrides: [
            'background_music_url' => 'https://cdn.example.com/music.mp3',
            'level_up_sfx_url' => 'https://cdn.example.com/levelup.mp3',
            'boss_intro_sfx_url' => 'https://cdn.example.com/boss.mp3',
        ]),
    ]);

    expect($theme->fresh()->config['audio'])->toMatchArray([
        'background_music_url' => 'https://cdn.example.com/music.mp3',
        'level_up_sfx_url' => 'https://cdn.example.com/levelup.mp3',
        'boss_intro_sfx_url' => 'https://cdn.example.com/boss.mp3',
    ]);
});
