<?php

namespace App\Listeners;

use App\Events\WorldCompleted;
use App\Models\UserWorldCompletion;
use App\Services\ProgressionService;

class HandleWorldCompletion
{
    private const BONUS_XP = 500;

    private const BONUS_COINS = 250;

    public function __construct(private readonly ProgressionService $progressionService) {}

    public function handle(WorldCompleted $event): void
    {
        $user = $event->user;
        $world = $event->world;

        $completion = UserWorldCompletion::firstOrCreate(
            ['user_id' => $user->id, 'world_id' => $world->id],
            ['completed_at' => now(), 'xp_bonus_awarded' => 0, 'coins_bonus_awarded' => 0],
        );

        if (! $completion->wasRecentlyCreated) {
            return;
        }

        $result = $this->progressionService->processVictory($user, self::BONUS_XP, self::BONUS_COINS);

        $completion->update([
            'xp_bonus_awarded' => $result['total_xp_earned'],
            'coins_bonus_awarded' => $result['coins_earned'],
        ]);

        session()->flash('world_completed', [
            'world_id' => $world->id,
            'world_slug' => $world->slug,
            'world_name' => $world->name,
            'bonus_xp' => $result['total_xp_earned'],
            'bonus_coins' => $result['coins_earned'],
        ]);
    }
}
