<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ThemePack;
use App\Models\World;
use Illuminate\Database\Seeder;

class WorldSeeder extends Seeder
{
    /**
     * Create the four learning worlds, each bound to an existing theme pack.
     */
    public function run(): void
    {
        $worlds = [
            [
                'name' => 'Math for Programmers',
                'slug' => 'math-for-programmers',
                'description' => 'Sharpen the numbers, logic, and patterns that every line of code is secretly built on.',
                'theme_identifier' => 'theme_cyberpunk_grid',
                'is_published' => true,
            ],
            [
                'name' => 'Computers in Cloud',
                'slug' => 'computers-in-cloud',
                'description' => 'Float above the data centers and discover how servers, networks, and the cloud keep the world online.',
                'theme_identifier' => 'theme_ocean_deep',
                'is_published' => true,
            ],
            [
                'name' => 'Data Engineering for Dummies',
                'slug' => 'data-engineering-for-dummies',
                'description' => 'Collect, clean, and move data through pipelines like a true data wrangler.',
                'theme_identifier' => 'theme_retro_pixel',
                'is_published' => true,
            ],
            [
                'name' => 'The mythic world of AI',
                'slug' => 'the-mythic-world-of-ai',
                'description' => 'Enter the enchanted realm where machines learn to think, predict, and create.',
                'theme_identifier' => 'theme_enchanted_forest',
                'is_published' => true,
            ],
        ];

        foreach ($worlds as $world) {
            $themePack = ThemePack::query()->where('identifier', $world['theme_identifier'])->first();

            if ($themePack === null) {
                $this->command->warn(sprintf('Theme pack [%s] not found, skipping world [%s]. Run ThemePackSeeder first.', $world['theme_identifier'], $world['name']));

                continue;
            }

            World::query()->firstOrCreate(['slug' => $world['slug']], [
                'name' => $world['name'],
                'description' => $world['description'],
                'theme_pack_id' => $themePack->id,
                'is_published' => $world['is_published'],
            ]);
        }
    }
}
