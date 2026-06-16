<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesEquippedItems;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    use ResolvesEquippedItems;

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $scope = $request->query('scope', 'weekly');
        $redisKey = $scope === 'all_time' ? 'leaderboard:all_time' : 'leaderboard:weekly';

        [$rawEntries, $userRank, $userScore] = Redis::pipeline(function ($pipe) use ($redisKey, $user): void {
            $pipe->zrevrange($redisKey, 0, 49, ['withscores' => true]);
            $pipe->zrevrank($redisKey, $user->id);
            $pipe->zscore($redisKey, $user->id);
        });

        $ids = array_keys($rawEntries);
        $enrichedUsers = User::whereIn('id', $ids)->get()->keyBy('id');

        $allEquippedIds = $enrichedUsers
            ->flatMap(fn (User $u): array => $this->equippedItemIds($u))
            ->unique()->values()->all();

        $equippedItems = $this->fetchEquippedItems($allEquippedIds);

        $mapEquipped = fn (User $u): array => $this->buildEquipped($u, $equippedItems);

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
            }
        } else {
            $userRank = (int) $userRank;
        }

        return Inertia::render('Student/Leaderboard/Index', [
            'leaders' => $leaders,
            'scope' => $scope,
            'player' => [
                'name' => $user->name,
                'rank' => $userRank !== null ? $userRank + 1 : null,
                'xp' => (int) ($userScore ?? ($scope === 'all_time' ? $user->xp : 0)),
                'level' => $user->level,
                'equipped' => $mapEquipped($user),
            ],
        ]);
    }
}
