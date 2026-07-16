<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockSubmission;
use App\Models\LessonSubmission;
use App\Models\User;
use App\Models\UserWorldCompletion;
use Illuminate\Support\Collection;

class ProfilePageService
{
    public function __construct(
        protected ProgressionService $progressionService,
        protected StoreService $storeService,
        protected EquippedItemResolver $equippedItems,
        protected AchievementListBuilder $achievementList,
    ) {}

    /**
     * Everything the authenticated profile page renders.
     *
     * @return array<string, mixed>
     */
    public function getProfilePageData(User $user): array
    {
        $prefs = $user->preferences ?? [];

        return [
            'hero' => $this->hero($user),
            'ledger' => $this->recentLedger($user),
            'achievements' => $this->achievementList->buildAchievementList($user),
            'inventory' => $this->storeService->listInventory($user),
            'equipped' => [
                'title' => $prefs['equipped_title'] ?? null,
                'avatar' => $prefs['equipped_avatar'] ?? null,
            ],
            'preferences' => array_merge([
                'background_audio' => true,
                'sound_effects' => true,
                'accessibility_mode' => false,
                'public_profile' => true,
            ], $prefs),
            'certificates' => $this->certificates($user),
        ];
    }

    /**
     * The hero banner block. `$public` hides coins and the share URL for the
     * publicly-viewable profile; the private dashboard gets both.
     *
     * @return array<string, mixed>
     */
    public function hero(User $user, bool $public = false): array
    {
        return [
            'name' => $user->name,
            'level' => $user->level,
            'xp' => $user->xp,
            'xp_for_current_level' => $this->progressionService->getXpRequiredForLevel($user->level),
            'xp_for_next_level' => $this->progressionService->getXpRequiredForLevel($user->level + 1),
            'coins' => $public ? null : $user->coins,
            'streak_count' => $user->streak_count,
            'equipped' => $this->equippedItems->resolveEquipped($user),
            ...($public ? [] : ['public_url' => route('public.profile.show', $user)]),
        ];
    }

    /**
     * The 10 most recent lesson + block reward entries, merged and sorted.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentLedger(User $user): Collection
    {
        $lessonEntries = LessonSubmission::query()->where('user_id', $user->id)
            ->with('lesson:id,name')->latest()
            ->limit(10)
            ->get()
            ->map(fn (LessonSubmission $submission): array => [
                'type' => 'lesson',
                'label' => $submission->lesson?->name ?? 'Lesson #'.$submission->lesson_id,
                'xp' => $submission->xp_rewarded,
                'coins' => $submission->coins_rewarded,
                'completed_at' => $submission->created_at,
            ]);

        $blockEntries = BlockSubmission::query()->where('user_id', $user->id)->latest()
            ->limit(10)
            ->get()
            ->map(fn (BlockSubmission $submission): array => [
                'type' => 'block',
                'label' => $submission->block_title ?? 'Block #'.$submission->block_index,
                'xp' => $submission->xp_rewarded,
                'coins' => $submission->coins_rewarded,
                'completed_at' => $submission->created_at,
            ]);

        return $lessonEntries->concat($blockEntries)
            ->sortByDesc('completed_at')
            ->take(10)
            ->values();
    }

    /**
     * The user's earned world-completion certificates, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function certificates(User $user): Collection
    {
        return $user->worldCompletions()
            ->with('world.themePack')
            ->latest('completed_at')
            ->get()
            ->map(fn (UserWorldCompletion $c): array => [
                'world_name' => $c->world->name,
                'world_slug' => $c->world->slug,
                'primary_color' => $c->world->themePack?->config['palette']['primary'] ?? '#8b5cf6',
                'completed_at' => $c->completed_at,
                'xp_bonus' => $c->xp_bonus_awarded,
                'coins_bonus' => $c->coins_bonus_awarded,
            ]);
    }
}
