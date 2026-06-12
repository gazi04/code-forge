<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserWorldCompletion;
use App\Services\ProgressionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(protected ProgressionService $progressionService) {}

    public function show(User $user): Response
    {
        abort_unless($user->role === 'student', 404);
        abort_if($user->is_shadowbanned, 404);
        abort_unless($user->preferences['public_profile'] ?? true, 404);

        return Inertia::render('Student/Profile/Public', [
            'hero' => $this->buildHero($user),
            'achievements' => $this->buildAchievements($user),
            'certificates' => $this->buildCertificates($user),
        ]);
    }

    /**
     * @return array{name: string, level: int, xp: int, xp_for_current_level: int, xp_for_next_level: int, coins: null, streak_count: int, equipped: array{title: ?array{id: int, name: string, color: ?string}, avatar: ?array{id: int, name: string, image_url: ?string}}}
     */
    private function buildHero(User $user): array
    {
        $prefs = $user->preferences ?? [];
        $titleId = $prefs['equipped_title'] ?? null;
        $avatarId = $prefs['equipped_avatar'] ?? null;
        $ids = array_filter([$titleId, $avatarId]);

        $equippedItems = $ids
            ? StoreItem::whereIn('id', $ids)->select(['id', 'name', 'type', 'image', 'display_config'])->get()->keyBy('id')
            : collect();

        $titleItem = $titleId ? $equippedItems->get($titleId) : null;
        $avatarItem = $avatarId ? $equippedItems->get($avatarId) : null;

        return [
            'name' => $user->name,
            'level' => $user->level,
            'xp' => $user->xp,
            'xp_for_current_level' => $this->progressionService->getXpRequiredForLevel($user->level),
            'xp_for_next_level' => $this->progressionService->getXpRequiredForLevel($user->level + 1),
            'coins' => null,
            'streak_count' => $user->streak_count,
            'equipped' => [
                'title' => $titleItem ? [
                    'id' => $titleItem->id,
                    'name' => $titleItem->name,
                    'color' => $titleItem->display_config['color'] ?? null,
                ] : null,
                'avatar' => $avatarItem ? [
                    'id' => $avatarItem->id,
                    'name' => $avatarItem->name,
                    'image_url' => $avatarItem->image ? Storage::url($avatarItem->image) : null,
                ] : null,
            ],
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, description: string, image_path: ?string, metric_type: string, threshold: int, unlocked: bool, unlocked_at: mixed}>
     */
    private function buildAchievements(User $user): Collection
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

    /**
     * @return Collection<int, array{world_name: string, world_slug: string, primary_color: string, completed_at: mixed, xp_bonus: int, coins_bonus: int}>
     */
    private function buildCertificates(User $user): Collection
    {
        return $user->worldCompletions()
            ->with('world.themePack')
            ->orderByDesc('completed_at')
            ->get()
            ->map(fn (UserWorldCompletion $completion): array => [
                'world_name' => $completion->world->name,
                'world_slug' => $completion->world->slug,
                'primary_color' => $completion->world->themePack?->config['palette']['primary'] ?? '#8b5cf6',
                'completed_at' => $completion->completed_at,
                'xp_bonus' => $completion->xp_bonus_awarded,
                'coins_bonus' => $completion->coins_bonus_awarded,
            ]);
    }
}
