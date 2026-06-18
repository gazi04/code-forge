<?php

namespace App\Http\Controllers;

use App\Concerns\BuildsAchievementList;
use App\Concerns\ResolvesEquippedItems;
use App\Http\Requests\UpdateProfileSettingsRequest;
use App\Models\BlockSubmission;
use App\Models\LessonSubmission;
use App\Models\UserInventory;
use App\Models\UserWorldCompletion;
use App\Services\ProgressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use BuildsAchievementList, ResolvesEquippedItems;

    public function __construct(protected ProgressionService $progressionService) {}

    public function show(): Response
    {
        $user = Auth::user();

        $xpForCurrentLevel = $this->progressionService->getXpRequiredForLevel($user->level);
        $xpForNextLevel = $this->progressionService->getXpRequiredForLevel($user->level + 1);

        $lessonEntries = LessonSubmission::where('user_id', $user->id)
            ->with('lesson:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (LessonSubmission $submission): array => [
                'type' => 'lesson',
                'label' => $submission->lesson?->name ?? "Lesson #{$submission->lesson_id}",
                'xp' => $submission->xp_rewarded,
                'coins' => $submission->coins_rewarded,
                'completed_at' => $submission->created_at,
            ]);

        $blockEntries = BlockSubmission::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (BlockSubmission $submission): array => [
                'type' => 'block',
                'label' => $submission->block_title ?? "Block #{$submission->block_index}",
                'xp' => $submission->xp_rewarded,
                'coins' => $submission->coins_rewarded,
                'completed_at' => $submission->created_at,
            ]);

        $ledger = $lessonEntries->concat($blockEntries)
            ->sortByDesc('completed_at')
            ->take(10)
            ->values();

        $prefs = $user->preferences ?? [];

        return Inertia::render('Student/Profile/Index', [
            'hero' => [
                'name' => $user->name,
                'public_url' => route('public.profile.show', $user),
                'level' => $user->level,
                'xp' => $user->xp,
                'xp_for_current_level' => $xpForCurrentLevel,
                'xp_for_next_level' => $xpForNextLevel,
                'coins' => $user->coins,
                'streak_count' => $user->streak_count,
                'equipped' => $this->resolveEquipped($user),
            ],
            'ledger' => $ledger,
            'achievements' => $this->buildAchievementList($user),
            'inventory' => UserInventory::where('user_id', $user->id)
                ->with('storeItem')
                ->get()
                ->map(fn (UserInventory $inv): array => [
                    'id' => $inv->id,
                    'store_item_id' => $inv->store_item_id,
                    'quantity' => $inv->quantity,
                    'store_item' => [
                        ...$inv->storeItem->toArray(),
                        'image_url' => $inv->storeItem->image ? Storage::disk('public')->url($inv->storeItem->image) : null,
                    ],
                ]),
            'equipped' => [
                'title' => $prefs['equipped_title'] ?? null,
                'avatar' => $prefs['equipped_avatar'] ?? null,
            ],
            'preferences' => array_merge([
                'background_audio' => true,
                'sound_effects' => true,
                'accessibility_mode' => false,
                'public_profile' => true,
            ], $user->preferences ?? []),
            'certificates' => $user->worldCompletions()
                ->with('world.themePack')
                ->orderByDesc('completed_at')
                ->get()
                ->map(fn (UserWorldCompletion $c): array => [
                    'world_name' => $c->world->name,
                    'world_slug' => $c->world->slug,
                    'primary_color' => $c->world->themePack?->config['palette']['primary'] ?? '#8b5cf6',
                    'completed_at' => $c->completed_at,
                    'xp_bonus' => $c->xp_bonus_awarded,
                    'coins_bonus' => $c->coins_bonus_awarded,
                ]),
        ]);
    }

    public function updateSettings(UpdateProfileSettingsRequest $request): RedirectResponse
    {
        $prefs = array_merge(Auth::user()->preferences ?? [], $request->validated());
        Auth::user()->update(['preferences' => $prefs]);

        return back();
    }
}
