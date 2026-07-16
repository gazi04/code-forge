<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StoreItemType;
use App\Models\StoreItem;
use App\Models\UserInventory;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $store,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Student/Store/Index', [
            ...$this->store->listStoreData(Auth::user()),
            'tab' => $request->query('tab', 'shop'),
        ]);
    }

    public function purchase(StoreItem $item): RedirectResponse
    {
        $result = $this->store->purchase(Auth::id(), $item);

        if (! $result->success) {
            return back()->with('store_result', $result->toArray());
        }

        return to_route('student.store.index', ['tab' => 'inventory'])
            ->with('store_result', $result->toArray());
    }

    public function activateItem(UserInventory $inventory): RedirectResponse
    {
        $user = Auth::user();
        abort_if($inventory->user_id !== $user->id, 403);

        // Only consumables can be "activated". Guard before any inventory mutation
        // so activating a cosmetic (or unknown type) can't silently destroy it via
        // the delete/decrement fall-through inside the service.
        abort_unless(in_array($inventory->storeItem->type, [StoreItemType::StreakFreeze, StoreItemType::XpBoost], true), 422);

        $result = $this->store->activate($user, $inventory);

        return back()->with('store_result', $result->toArray());
    }

    public function equip(UserInventory $inventory): RedirectResponse
    {
        $user = Auth::user();
        abort_if($inventory->user_id !== $user->id, 403);

        // Only cosmetic item types are equippable; reject consumables (e.g.
        // streak_freeze, xp_boost) so they can't create junk equipped_* keys.
        abort_unless(in_array($inventory->storeItem->type, [StoreItemType::Title, StoreItemType::Avatar], true), 422);

        $this->store->equip($user, $inventory);

        return back();
    }

    public function unequip(string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['title', 'avatar'], true), 422);

        $this->store->unequip(Auth::user(), $type);

        return back();
    }
}
