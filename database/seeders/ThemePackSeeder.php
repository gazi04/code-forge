<?php

namespace Database\Seeders;

use App\Models\ThemePack;
use Illuminate\Database\Seeder;

class ThemePackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Cyberpunk Grid',
                'identifier' => 'theme_cyberpunk_grid',
                'config' => [
                    'palette' => [
                        'primary' => '#c084fc',
                        'secondary' => '#0f172a',
                        'accent' => '#22d3ee',
                        'background' => '#09090f',
                        'surface' => '#1a0a2e',
                        'text' => '#e2e8f0',
                    ],
                    'background' => [
                        'style' => 'solid',
                        'value' => '#09090f',
                    ],
                    'ui' => [
                        'card_style' => 'glassy',
                        'border_radius' => 'sm',
                        'font_style' => 'monospace',
                        'course_layout' => 'grid',
                        'map_layout' => 'linear',
                    ],
                    'sprites' => [],
                    'audio' => [
                        'background_music_url' => null,
                        'level_up_sfx_url' => null,
                        'boss_intro_sfx_url' => null,
                    ],
                ],
            ],
            [
                'name' => 'Enchanted Forest',
                'identifier' => 'theme_enchanted_forest',
                'config' => [
                    'palette' => [
                        'primary' => '#16a34a',
                        'secondary' => '#1a2e1a',
                        'accent' => '#fbbf24',
                        'background' => '#0d1a0f',
                        'surface' => '#1a2e1f',
                        'text' => '#fef9c3',
                    ],
                    'background' => [
                        'style' => 'gradient',
                        'value' => 'linear-gradient(160deg, #0d1a0f 0%, #1a3320 60%, #0f2a1a 100%)',
                    ],
                    'ui' => [
                        'card_style' => 'embossed',
                        'border_radius' => 'lg',
                        'font_style' => 'medieval',
                        'course_layout' => 'asymmetrical',
                        'map_layout' => 'branching',
                    ],
                    'sprites' => [],
                    'audio' => [
                        'background_music_url' => null,
                        'level_up_sfx_url' => null,
                        'boss_intro_sfx_url' => null,
                    ],
                ],
            ],
            [
                'name' => 'Retro Pixel',
                'identifier' => 'theme_retro_pixel',
                'config' => [
                    'palette' => [
                        'primary' => '#ef4444',
                        'secondary' => '#1c1917',
                        'accent' => '#facc15',
                        'background' => '#0c0a09',
                        'surface' => '#1c1917',
                        'text' => '#f5f5f4',
                    ],
                    'background' => [
                        'style' => 'solid',
                        'value' => '#0c0a09',
                    ],
                    'ui' => [
                        'card_style' => 'pixel',
                        'border_radius' => 'none',
                        'font_style' => 'monospace',
                        'course_layout' => 'grid',
                        'map_layout' => 'road',
                    ],
                    'sprites' => [],
                    'audio' => [
                        'background_music_url' => null,
                        'level_up_sfx_url' => null,
                        'boss_intro_sfx_url' => null,
                    ],
                ],
            ],
            [
                'name' => 'Ocean Deep',
                'identifier' => 'theme_ocean_deep',
                'config' => [
                    'palette' => [
                        'primary' => '#06b6d4',
                        'secondary' => '#0c1a2e',
                        'accent' => '#34d399',
                        'background' => '#06111f',
                        'surface' => '#0c2040',
                        'text' => '#e0f2fe',
                    ],
                    'background' => [
                        'style' => 'gradient',
                        'value' => 'linear-gradient(180deg, #06111f 0%, #0c2040 50%, #071a30 100%)',
                    ],
                    'ui' => [
                        'card_style' => 'bordered',
                        'border_radius' => 'lg',
                        'font_style' => 'rounded',
                        'course_layout' => 'carousel',
                        'map_layout' => 'linear',
                    ],
                    'sprites' => [],
                    'audio' => [
                        'background_music_url' => null,
                        'level_up_sfx_url' => null,
                        'boss_intro_sfx_url' => null,
                    ],
                ],
            ],
            [
                'name' => 'Solar Flare',
                'identifier' => 'theme_solar_flare',
                'config' => [
                    'palette' => [
                        'primary' => '#f97316',
                        'secondary' => '#1c0a00',
                        'accent' => '#fbbf24',
                        'background' => '#0f0800',
                        'surface' => '#1f1000',
                        'text' => '#fef3c7',
                    ],
                    'background' => [
                        'style' => 'gradient',
                        'value' => 'radial-gradient(ellipse at top, #3d1a00 0%, #0f0800 70%)',
                    ],
                    'ui' => [
                        'card_style' => 'flat',
                        'border_radius' => 'md',
                        'font_style' => 'futuristic',
                        'course_layout' => 'cinematic',
                        'map_layout' => 'linear',
                    ],
                    'sprites' => [],
                    'audio' => [
                        'background_music_url' => null,
                        'level_up_sfx_url' => null,
                        'boss_intro_sfx_url' => null,
                    ],
                ],
            ],
        ];

        foreach ($packs as $pack) {
            ThemePack::firstOrCreate(
                ['identifier' => $pack['identifier']],
                $pack,
            );
        }
    }
}
