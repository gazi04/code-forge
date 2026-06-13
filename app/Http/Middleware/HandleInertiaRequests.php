<?php

namespace App\Http\Middleware;

use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
                    'equipped' => $this->resolveEquipped($request->user()),
                ] : null,
            ],
            'flash' => [
                'auth_message' => fn () => $request->session()->get('auth_message'),
                'game_result' => fn () => $request->session()->get('game_result'),
                'store_result' => fn () => $request->session()->get('store_result'),
                'world_completed' => fn () => $request->session()->get('world_completed'),
                'achievements_unlocked' => function () use ($request): array {
                    $user = $request->user();
                    if (! $user || empty($user->pending_achievements)) {
                        return [];
                    }
                    $pending = $user->pending_achievements;
                    $user->update(['pending_achievements' => null]);

                    return $pending;
                },
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /** @return array{title: array<string, mixed>|null, avatar: array<string, mixed>|null} */
    private function resolveEquipped(User $user): array
    {
        $prefs = $user->preferences ?? [];
        $titleId = $prefs['equipped_title'] ?? null;
        $avatarId = $prefs['equipped_avatar'] ?? null;
        $ids = array_filter([$titleId, $avatarId]);

        $items = $ids
            ? StoreItem::whereIn('id', $ids)->select(['id', 'name', 'type', 'image', 'display_config'])->get()->keyBy('id')
            : collect();

        $title = $titleId && $items->has($titleId) ? $items->get($titleId) : null;
        $avatar = $avatarId && $items->has($avatarId) ? $items->get($avatarId) : null;

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
