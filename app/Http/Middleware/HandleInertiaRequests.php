<?php

namespace App\Http\Middleware;

use App\Services\EquippedItemResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        protected EquippedItemResolver $equipped,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'level' => $request->user()->level,
                    'xp' => $request->user()->xp,
                    'coins' => $request->user()->coins,
                    'streak_count' => $request->user()->streak_count,
                    'streak_at_risk' => $request->user()->isStreakAtRisk(),
                    'equipped' => fn () => $this->equipped->resolveEquipped($request->user()),
                ] : null,
            ],
            'flash' => [
                'auth_message' => fn () => $request->session()->get('auth_message'),
                'game_result' => fn () => $request->session()->get('game_result'),
                'store_result' => fn () => $request->session()->get('store_result'),
                'world_completed' => fn () => $request->session()->get('world_completed'),
                // Read-only: the client clears these via the achievements.acknowledge
                // endpoint once consumed. Resolving a shared prop must not mutate the
                // user, or any Inertia response would silently discard pending toasts.
                'achievements_unlocked' => fn (): array => $request->user()?->pending_achievements ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
