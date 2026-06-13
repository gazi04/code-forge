<?php

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
        ActivityLog::create([
            'log_name' => 'progression',
            'description' => "Student advanced from Level {$event->oldLevel} to Level {$event->newLevel} and claimed +{$coinBonus} bonus coins.",
            'subject_id' => $user->id,
            'subject_type' => get_class($user),
            'event' => 'level_up',
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
            'properties' => [
                'old_level' => $event->oldLevel,
                'new_level' => $event->newLevel,
                'bonus_coins' => $coinBonus,
            ],
        ]);
    }
}
