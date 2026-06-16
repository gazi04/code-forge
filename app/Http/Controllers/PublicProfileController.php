<?php

namespace App\Http\Controllers;

use App\Concerns\BuildsAchievementList;
use App\Concerns\ResolvesEquippedItems;
use App\Models\User;
use App\Models\UserWorldCompletion;
use App\Services\ProgressionService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    use BuildsAchievementList, ResolvesEquippedItems;

    public function __construct(protected ProgressionService $progressionService) {}

    public function show(User $user): Response
    {
        abort_unless($user->role === 'student', 404);
        abort_if($user->is_shadowbanned, 404);
        abort_unless($user->preferences['public_profile'] ?? true, 404);

        return Inertia::render('Student/Profile/Public', [
            'hero' => $this->buildHero($user),
            'achievements' => $this->buildAchievementList($user),
            'certificates' => $this->buildCertificates($user),
        ]);
    }

    /**
     * @return array{name: string, level: int, xp: int, xp_for_current_level: int, xp_for_next_level: int, coins: null, streak_count: int, equipped: array{title: array<string, mixed>|null, avatar: array<string, mixed>|null}}
     */
    private function buildHero(User $user): array
    {
        return [
            'name' => $user->name,
            'level' => $user->level,
            'xp' => $user->xp,
            'xp_for_current_level' => $this->progressionService->getXpRequiredForLevel($user->level),
            'xp_for_next_level' => $this->progressionService->getXpRequiredForLevel($user->level + 1),
            'coins' => null,
            'streak_count' => $user->streak_count,
            'equipped' => $this->resolveEquipped($user),
        ];
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
