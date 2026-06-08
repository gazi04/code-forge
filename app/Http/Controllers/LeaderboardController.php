<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $scope = $request->query('scope', 'weekly');
        $redisKey = $scope === 'all_time' ? 'leaderboard:all_time' : 'leaderboard:weekly';

        $rawEntries = Redis::zrevrange($redisKey, 0, 49, ['withscores' => true]);

        $names = array_keys($rawEntries);
        $enrichedUsers = User::whereIn('name', $names)->get()->keyBy('name');

        $leaders = [];
        $rank = 1;

        foreach ($rawEntries as $name => $score) {
            $dbUser = $enrichedUsers->get($name);
            $leaders[] = [
                'rank' => $rank++,
                'name' => $name,
                'level' => $dbUser?->level ?? 0,
                'xp' => (int) $score,
            ];
        }

        $userRank = Redis::zrevrank($redisKey, $user->name);
        $userScore = Redis::zscore($redisKey, $user->name);

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
            ],
        ]);
    }
}
