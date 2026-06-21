<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserWorldCompletion;
use App\Models\World;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class CertificateService
{
    /**
     * The user's completion record for a world, or null if not yet earned.
     */
    public function completionFor(User $user, World $world): ?UserWorldCompletion
    {
        return $user->worldCompletions()->where('world_id', $world->id)->first();
    }

    /**
     * Render (or serve from cache) the world-completion certificate PDF bytes.
     *
     * The certificate is deterministic per (user, world) — a completion is a
     * one-time event with a fixed completed_at — so the rendered PDF is cached and
     * served from cache, avoiding a fresh DomPDF render (and abuse vector) on every
     * request. The route is also throttled.
     */
    public function renderFor(User $user, World $world, UserWorldCompletion $completion): string
    {
        $world->load('themePack', 'courses');
        $primaryColor = $world->themePack?->config['palette']['primary'] ?? '#8b5cf6';

        return Cache::remember(
            "certificate-pdf:{$user->id}:{$world->id}",
            now()->addDay(),
            fn (): string => Pdf::loadView('certificates.world', [
                'user' => $user,
                'world' => $world,
                'courses' => $world->courses,
                'completedAt' => $completion->completed_at,
                'primaryColor' => $primaryColor,
            ])->setPaper('a4', 'landscape')->output(),
        );
    }
}
