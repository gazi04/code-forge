<?php

namespace Database\Seeders;

use App\Models\StoreItem;
use Illuminate\Database\Seeder;

class StoreItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Code Wizard',
                'description' => 'A title for those who have mastered the arcane arts of code.',
                'type' => 'title',
                'purchase_type' => 'permanent',
                'price_coins' => 200,
                'icon' => '🧙',
                'is_active' => true,
            ],
            [
                'name' => 'Bug Slayer',
                'description' => 'Earned by those who defeat bugs without mercy.',
                'type' => 'title',
                'purchase_type' => 'permanent',
                'price_coins' => 150,
                'icon' => '⚔️',
                'is_active' => true,
            ],
            [
                'name' => 'Pixel Hero',
                'description' => 'A pixel art hero avatar for your profile.',
                'type' => 'avatar',
                'purchase_type' => 'permanent',
                'price_coins' => 250,
                'icon' => '🦸',
                'is_active' => true,
            ],
            [
                'name' => 'Streak Shield',
                'description' => 'Protects your streak for one missed day.',
                'type' => 'streak_freeze',
                'purchase_type' => 'consumable',
                'price_coins' => 100,
                'icon' => '🛡️',
                'effect_config' => ['quantity' => 1],
                'is_active' => true,
            ],
            [
                'name' => 'XP Surge',
                'description' => 'Double your XP for the next 3 lessons.',
                'type' => 'xp_boost',
                'purchase_type' => 'consumable',
                'price_coins' => 250,
                'icon' => '⚡',
                'effect_config' => ['multiplier' => 2, 'lessons' => 3],
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            StoreItem::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
