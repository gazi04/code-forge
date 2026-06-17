<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $items = StoreItem::active()
            ->get()
            ->map(fn ($item) => [
                ...$item->toArray(),
                'image_url' => $item->image ? Storage::disk('public')->url($item->image) : null,
            ]);

        $inventory = UserInventory::where('user_id', $user->id)
            ->with('storeItem')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'store_item_id' => $inv->store_item_id,
                'quantity' => $inv->quantity,
                'acquired_at' => $inv->acquired_at,
                'store_item' => [
                    ...$inv->storeItem->toArray(),
                    'image_url' => $inv->storeItem->image ? Storage::disk('public')->url($inv->storeItem->image) : null,
                ],
            ]);

        $equipped = [
            'title' => $user->preferences['equipped_title'] ?? null,
            'avatar' => $user->preferences['equipped_avatar'] ?? null,
        ];

        return Inertia::render('Student/Store/Index', [
            'items' => $items,
            'inventory' => $inventory,
            'equipped' => $equipped,
            'tab' => $request->query('tab', 'shop'),
        ]);
    }

    public function purchase(StoreItem $item): RedirectResponse
    {
        $userId = Auth::id();

        // All validation and mutation happen inside one transaction against
        // row-locked copies, so concurrent purchases can't both pass the checks
        // (negative coins / oversold stock). `decrement` is an atomic
        // `coins = coins - x`, avoiding the read-modify-write lost-update window.
        $result = DB::transaction(function () use ($userId, $item) {
            $lockedItem = StoreItem::whereKey($item->id)->lockForUpdate()->first();
            $lockedUser = User::whereKey($userId)->lockForUpdate()->first();

            if (! $lockedItem || ! $lockedItem->is_active) {
                return ['error' => 'This item is no longer available.'];
            }

            if ($lockedItem->purchase_type === PurchaseType::Permanent
                && UserInventory::where('user_id', $userId)->where('store_item_id', $lockedItem->id)->exists()) {
                return ['error' => 'You already own this item.'];
            }

            if ($lockedItem->purchase_type === PurchaseType::OneTime
                && $lockedItem->stock_limit !== null
                && $lockedItem->sold_count >= $lockedItem->stock_limit) {
                return ['error' => 'This item is sold out.'];
            }

            if ($lockedUser->coins < $lockedItem->price_coins) {
                return ['error' => 'Not enough coins.'];
            }

            $lockedUser->decrement('coins', $lockedItem->price_coins);
            $lockedItem->increment('sold_count');

            $existing = UserInventory::where('user_id', $userId)
                ->where('store_item_id', $lockedItem->id)
                ->first();

            if ($existing) {
                $existing->increment('quantity');
            } else {
                UserInventory::create([
                    'user_id' => $userId,
                    'store_item_id' => $lockedItem->id,
                    'quantity' => 1,
                    'acquired_at' => now(),
                ]);
            }

            return ['purchased' => $lockedItem->name];
        });

        if (isset($result['error'])) {
            return redirect()->back()->with('store_result', ['error' => $result['error']]);
        }

        return redirect()->route('student.store.index', ['tab' => 'inventory'])
            ->with('store_result', ['purchased' => $result['purchased']]);
    }

    public function activateItem(UserInventory $inventory): RedirectResponse
    {
        $user = Auth::user();
        abort_if($inventory->user_id !== $user->id, 403);

        $item = $inventory->storeItem;

        // Only consumables can be "activated". Guard before any inventory mutation
        // so activating a cosmetic (or unknown type) can't silently destroy it via
        // the delete/decrement fall-through below.
        abort_unless(in_array($item->type, [StoreItemType::StreakFreeze, StoreItemType::XpBoost], true), 422);

        if ($item->type === StoreItemType::StreakFreeze) {
            $user->streak_freezes += (int) ($item->effect_config['quantity'] ?? 1);
        } elseif ($item->type === StoreItemType::XpBoost) {
            $multiplier = (float) ($item->effect_config['multiplier'] ?? 2);
            $lessons = (int) ($item->effect_config['lessons'] ?? 3);

            // Stacking: extend the lesson count and keep the stronger multiplier
            // so activating a weaker boost never downgrades an active stronger one.
            $user->xp_boost_multiplier = $user->xp_boost_lessons_remaining > 0
                ? max((float) $user->xp_boost_multiplier, $multiplier)
                : $multiplier;
            $user->xp_boost_lessons_remaining += $lessons;
        }

        $user->save();

        if ($inventory->quantity <= 1) {
            $inventory->delete();
        } else {
            $inventory->decrement('quantity');
        }

        return redirect()->back()->with('store_result', ['activated' => $item->name]);
    }

    public function equip(UserInventory $inventory): RedirectResponse
    {
        $user = Auth::user();
        abort_if($inventory->user_id !== $user->id, 403);

        $item = $inventory->storeItem;

        // Only cosmetic item types are equippable; reject consumables (e.g.
        // streak_freeze, xp_boost) so they can't create junk equipped_* keys.
        abort_unless(in_array($item->type, [StoreItemType::Title, StoreItemType::Avatar], true), 422);

        $user->preferences = array_merge($user->preferences ?? [], [
            'equipped_'.$item->type->value => $item->id,
        ]);
        $user->save();

        return redirect()->back();
    }

    public function unequip(string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['title', 'avatar']), 422);

        $user = Auth::user();
        $user->preferences = array_merge($user->preferences ?? [], [
            'equipped_'.$type => null,
        ]);
        $user->save();

        return redirect()->back();
    }
}
