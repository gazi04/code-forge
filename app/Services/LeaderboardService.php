<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;

class LeaderboardService
{
    public function __construct(
        protected EquippedItemResolver $equippedItems,
    ) {}

    /**
     * Top 50 ranked players for the scope plus the viewer's own rank/score, with
     * equipped cosmetics resolved in a single batch fetch across all shown users.
     *
     * @param  'all_time'|'weekly'  $scope
     * @return array{leaders: list<array<string, mixed>>, scope: string, player: array<string, mixed>}
     */
    public function topLeaders(User $user, string $scope): array
    {
        $redisKey = $scope === 'all_time' ? 'leaderboard:all_time' : 'leaderboard:weekly';

        [$rawEntries, $userRank, $userScore, $totalRanked] = Redis::pipeline(function ($pipe) use ($redisKey, $user): void {
            $pipe->zrevrange($redisKey, 0, 49, ['withscores' => true]);
            $pipe->zrevrank($redisKey, $user->id);
            $pipe->zscore($redisKey, $user->id);
            $pipe->zcard($redisKey);
        });

        $ids = array_keys($rawEntries);
        $enrichedUsers = User::whereIn('id', $ids)->get()->keyBy('id');

        $allEquippedIds = $enrichedUsers
            ->flatMap(fn (User $u): array => $this->equippedItems->equippedItemIds($u))
            ->unique()->values()->all();

        $equippedItems = $this->equippedItems->fetchEquippedItems($allEquippedIds);

        $mapEquipped = fn (User $u): array => $this->equippedItems->buildEquipped($u, $equippedItems);

        $leaders = [];
        $rank = 1;

        foreach ($rawEntries as $id => $score) {
            $dbUser = $enrichedUsers->get($id);

            if (! $dbUser) {
                continue;
            }

            $leaders[] = [
                'rank' => $rank++,
                'name' => $dbUser->name,
                'level' => $dbUser->level,
                'xp' => (int) $score,
                'equipped' => $mapEquipped($dbUser),
            ];
        }

        if ($userRank === null) {
            if ($scope === 'all_time') {
                $userRank = User::where('xp', '>', $user->xp)->where('is_shadowbanned', false)->count();
            } else {
                $userRank = (int) $totalRanked; // weekly: ranked just below everyone with a weekly score
            }
        } else {
            $userRank = (int) $userRank;
        }

        return [
            'leaders' => $leaders,
            'scope' => $scope,
            'player' => [
                'name' => $user->name,
                'rank' => $userRank !== null ? $userRank + 1 : null,
                'xp' => (int) ($userScore ?? ($scope === 'all_time' ? $user->xp : 0)),
                'level' => $user->level,
                'equipped' => $mapEquipped($user),
            ],
        ];
    }
}
