<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Collection;

class AchievementListBuilder
{
    /**
     * Build the full achievement list for a user, flagging which are unlocked.
     *
     * @return Collection<int, array{id: int, name: string, description: string, image_path: ?string, metric_type: string, threshold: int, unlocked: bool, unlocked_at: mixed}>
     */
    public function buildAchievementList(User $user): Collection
    {
        $earnedAchievements = $user->achievements()->withPivot('unlocked_at')->get()->keyBy('id');

        return Achievement::all()->map(fn (Achievement $achievement): array => [
            'id' => $achievement->id,
            'name' => $achievement->name,
            'description' => $achievement->description,
            'image_path' => $achievement->image_path,
            'metric_type' => $achievement->metric_type,
            'threshold' => $achievement->threshold,
            'unlocked' => $earnedAchievements->has($achievement->id),
            'unlocked_at' => $earnedAchievements->get($achievement->id)?->pivot?->unlocked_at,
        ]);
    }
}
