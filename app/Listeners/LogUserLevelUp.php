<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLeveledUp;
use App\Models\ActivityLog;

class LogUserLevelUp
{
    public function handle(UserLeveledUp $event): void
    {
        $user = $event->user;

        // 1. Award a milestone bonus (+50 coins per level)
        $coinBonus = 50;
        $user->increment('coins', $coinBonus);
        $user->increment('total_coins_earned', $coinBonus);

        // 2. Log it directly into your custom audit schema
        ActivityLog::query()->create([
            'log_name' => 'progression',
            'description' => sprintf('Student advanced from Level %d to Level %d and claimed +%d bonus coins.', $event->oldLevel, $event->newLevel, $coinBonus),
            'subject_id' => $user->id,
            'subject_type' => $user::class,
            'event' => 'level_up',
            'causer_id' => $user->id,
            'causer_type' => $user::class,
            'properties' => [
                'old_level' => $event->oldLevel,
                'new_level' => $event->newLevel,
                'bonus_coins' => $coinBonus,
            ],
        ]);
    }
}
