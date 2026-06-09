<?php

namespace App\Http\Controllers;

use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
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

        $allEquippedIds = $enrichedUsers->flatMap(function (User $u): array {
            $prefs = $u->preferences ?? [];

            return array_filter([
                $prefs['equipped_title'] ?? null,
                $prefs['equipped_avatar'] ?? null,
            ]);
        })->unique()->values()->all();

        $equippedItems = $allEquippedIds
            ? StoreItem::whereIn('id', $allEquippedIds)->select(['id', 'name', 'type', 'image', 'display_config'])->get()->keyBy('id')
            : collect();

        $mapEquipped = fn (User $u): array => $this->buildEquipped($u, $equippedItems);

        $leaders = [];
        $rank = 1;

        foreach ($rawEntries as $name => $score) {
            $dbUser = $enrichedUsers->get($name);
            $leaders[] = [
                'rank' => $rank++,
                'name' => $name,
                'level' => $dbUser?->level ?? 0,
                'xp' => (int) $score,
                'equipped' => $dbUser ? $mapEquipped($dbUser) : ['title' => null, 'avatar' => null],
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
                'equipped' => $mapEquipped($user),
            ],
        ]);
    }

    /** @return array{title: array<string, mixed>|null, avatar: array<string, mixed>|null} */
    private function buildEquipped(User $user, Collection $equippedItems): array
    {
        $prefs = $user->preferences ?? [];
        $titleId = $prefs['equipped_title'] ?? null;
        $avatarId = $prefs['equipped_avatar'] ?? null;

        $title = $titleId && $equippedItems->has($titleId) ? $equippedItems->get($titleId) : null;
        $avatar = $avatarId && $equippedItems->has($avatarId) ? $equippedItems->get($avatarId) : null;

        return [
            'title' => $title ? [
                'id' => $title->id,
                'name' => $title->name,
                'color' => $title->display_config['color'] ?? null,
            ] : null,
            'avatar' => $avatar ? [
                'id' => $avatar->id,
                'name' => $avatar->name,
                'image_url' => $avatar->image ? Storage::url($avatar->image) : null,
            ] : null,
        ];
    }
}
