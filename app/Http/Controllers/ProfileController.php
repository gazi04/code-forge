<?php

namespace App\Http\Controllers;

use App\Models\BlockSubmission;
use App\Models\LessonSubmission;
use App\Services\ProgressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(protected ProgressionService $progressionService) {}

    public function show(): Response
    {
        $user = Auth::user();

        $xpForCurrentLevel = $this->progressionService->getXpRequiredForLevel($user->level);
        $xpForNextLevel = $this->progressionService->getXpRequiredForLevel($user->level + 1);

        $lessonEntries = LessonSubmission::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (LessonSubmission $submission): array => [
                'type' => 'lesson',
                'label' => $submission->lesson_id,
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

        return Inertia::render('Student/Profile/Index', [
            'hero' => [
                'name' => $user->name,
                'level' => $user->level,
                'xp' => $user->xp,
                'xp_for_current_level' => $xpForCurrentLevel,
                'xp_for_next_level' => $xpForNextLevel,
                'coins' => $user->coins,
                'streak_count' => $user->streak_count,
            ],
            'ledger' => $ledger,
            'preferences' => $user->preferences ?? [
                'background_audio' => true,
                'sound_effects' => true,
                'accessibility_mode' => false,
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'background_audio' => ['required', 'boolean'],
            'sound_effects' => ['required', 'boolean'],
            'accessibility_mode' => ['required', 'boolean'],
        ]);

        Auth::user()->update(['preferences' => $validated]);

        return back();
    }
}
