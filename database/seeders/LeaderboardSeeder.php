<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis;

class LeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        Redis::del('leaderboard:all_time', 'leaderboard:weekly');

        $tiers = [
            ['xp' => 8500, 'level' => 12, 'coins' => 420],
            ['xp' => 7200, 'level' => 11, 'coins' => 380],
            ['xp' => 6100, 'level' => 10, 'coins' => 310],
            ['xp' => 4800, 'level' => 9,  'coins' => 260],
            ['xp' => 4100, 'level' => 8,  'coins' => 220],
            ['xp' => 3600, 'level' => 8,  'coins' => 195],
            ['xp' => 3000, 'level' => 7,  'coins' => 170],
            ['xp' => 2700, 'level' => 7,  'coins' => 155],
            ['xp' => 2400, 'level' => 6,  'coins' => 140],
            ['xp' => 2100, 'level' => 6,  'coins' => 125],
            ['xp' => 1800, 'level' => 5,  'coins' => 110],
            ['xp' => 1500, 'level' => 5,  'coins' => 95],
            ['xp' => 1200, 'level' => 4,  'coins' => 80],
            ['xp' => 900,  'level' => 4,  'coins' => 65],
            ['xp' => 700,  'level' => 3,  'coins' => 50],
            ['xp' => 500,  'level' => 3,  'coins' => 40],
            ['xp' => 350,  'level' => 2,  'coins' => 30],
            ['xp' => 200,  'level' => 2,  'coins' => 20],
            ['xp' => 100,  'level' => 1,  'coins' => 10],
            ['xp' => 50,   'level' => 1,  'coins' => 5],
        ];

        foreach ($tiers as $tier) {
            $user = User::factory()->create([
                'role' => 'student',
                'xp' => $tier['xp'],
                'level' => $tier['level'],
                'coins' => $tier['coins'],
            ]);

            $weeklyXp = (int) round($tier['xp'] * (rand(20, 80) / 100));

            Redis::zadd('leaderboard:all_time', $tier['xp'], $user->name);
            Redis::zadd('leaderboard:weekly', $weeklyXp, $user->name);
        }

        $this->command->info('Leaderboard seeded with '.count($tiers).' students.');
    }
}
