<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserInventory;
use App\Support\StoreResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreService
{
    /**
     * Everything the store page renders: active items, the user's inventory, and equipped cosmetics.
     *
     * @return array{items: Collection<int, array<string, mixed>>, inventory: Collection<int, array<string, mixed>>, equipped: array{title: mixed, avatar: mixed}}
     */
    public function listStoreData(User $user): array
    {
        $items = StoreItem::query()->active()
            ->get()
            ->map(fn ($item): array => [
                ...$item->toArray(),
                'image_url' => $item->image ? Storage::disk('public')->url($item->image) : null,
            ]);

        return [
            'items' => $items,
            'inventory' => $this->listInventory($user),
            'equipped' => [
                'title' => $user->preferences['equipped_title'] ?? null,
                'avatar' => $user->preferences['equipped_avatar'] ?? null,
            ],
        ];
    }

    /**
     * The user's inventory rows with their store item (incl. resolved image URL).
     * Shared with the profile page.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listInventory(User $user): Collection
    {
        return UserInventory::query()->where('user_id', $user->id)
            ->with('storeItem')
            ->get()
            ->map(fn ($inv): array => [
                'id' => $inv->id,
                'store_item_id' => $inv->store_item_id,
                'quantity' => $inv->quantity,
                'acquired_at' => $inv->acquired_at,
                'store_item' => [
                    ...$inv->storeItem->toArray(),
                    'image_url' => $inv->storeItem->image ? Storage::disk('public')->url($inv->storeItem->image) : null,
                ],
            ]);
    }

    /**
     * Buy an item. All validation and mutation happen inside one transaction against
     * row-locked copies, so concurrent purchases can't both pass the checks
     * (negative coins / oversold stock). `decrement` is an atomic `coins = coins - x`,
     * avoiding the read-modify-write lost-update window.
     */
    public function purchase(int $userId, StoreItem $item): StoreResult
    {
        return DB::transaction(function () use ($userId, $item): StoreResult {
            $lockedItem = StoreItem::query()->whereKey($item->id)->lockForUpdate()->first();
            $lockedUser = User::query()->whereKey($userId)->lockForUpdate()->first();

            if (! $lockedItem || ! $lockedItem->is_active) {
                return StoreResult::error('This item is no longer available.');
            }

            if ($lockedItem->purchase_type === PurchaseType::Permanent
                && UserInventory::query()->where('user_id', $userId)->where('store_item_id', $lockedItem->id)->exists()) {
                return StoreResult::error('You already own this item.');
            }

            if ($lockedItem->purchase_type === PurchaseType::OneTime
                && $lockedItem->stock_limit !== null
                && $lockedItem->sold_count >= $lockedItem->stock_limit) {
                return StoreResult::error('This item is sold out.');
            }

            if ($lockedUser->coins < $lockedItem->price_coins) {
                return StoreResult::error('Not enough coins.');
            }

            $lockedUser->decrement('coins', $lockedItem->price_coins);
            $lockedItem->increment('sold_count');

            $existing = UserInventory::query()->where('user_id', $userId)
                ->where('store_item_id', $lockedItem->id)
                ->first();

            if ($existing) {
                $existing->increment('quantity');
            } else {
                UserInventory::query()->create([
                    'user_id' => $userId,
                    'store_item_id' => $lockedItem->id,
                    'quantity' => 1,
                    'acquired_at' => now(),
                ]);
            }

            return StoreResult::purchased($lockedItem->name);
        });
    }

    /**
     * Consume a consumable inventory item and apply its effect. Re-fetch both rows
     * under a lock inside one transaction so two concurrent activations of the same
     * quantity-1 item can't both apply the effect before either consumes the row
     * (effect duplication). Mutate the locked user copy, not the passed-in instance,
     * to avoid a read-modify-write lost update.
     *
     * Caller is responsible for the ownership + consumable-type guards.
     */
    public function activate(User $user, UserInventory $inventory): StoreResult
    {
        $item = $inventory->storeItem;

        return DB::transaction(function () use ($inventory, $item, $user): StoreResult {
            $lockedInventory = UserInventory::query()->whereKey($inventory->id)->lockForUpdate()->first();
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->first();

            // A concurrent activation may have consumed the row between binding and lock.
            if (! $lockedInventory || $lockedInventory->quantity < 1) {
                return StoreResult::error('This item is no longer available.');
            }

            if ($item->type === StoreItemType::StreakFreeze) {
                $lockedUser->streak_freezes += (int) ($item->effect_config['quantity'] ?? 1);
            } elseif ($item->type === StoreItemType::XpBoost) {
                $multiplier = (float) ($item->effect_config['multiplier'] ?? 2);
                $lessons = (int) ($item->effect_config['lessons'] ?? 3);

                // Stacking: extend the lesson count and keep the stronger multiplier
                // so activating a weaker boost never downgrades an active stronger one.
                $lockedUser->xp_boost_multiplier = $lockedUser->xp_boost_lessons_remaining > 0
                    ? max((float) $lockedUser->xp_boost_multiplier, $multiplier)
                    : $multiplier;
                $lockedUser->xp_boost_lessons_remaining += $lessons;
            }

            $lockedUser->save();

            if ($lockedInventory->quantity <= 1) {
                $lockedInventory->delete();
            } else {
                $lockedInventory->decrement('quantity');
            }

            return StoreResult::activated($item->name);
        });
    }

    /**
     * Equip a cosmetic item. Caller is responsible for the ownership + cosmetic-type guards.
     */
    public function equip(User $user, UserInventory $inventory): void
    {
        $item = $inventory->storeItem;

        $user->preferences = array_merge($user->preferences ?? [], [
            'equipped_'.$item->type->value => $item->id,
        ]);
        $user->save();
    }

    /**
     * Clear an equipped cosmetic slot. Caller is responsible for the type guard.
     */
    public function unequip(User $user, string $type): void
    {
        $user->preferences = array_merge($user->preferences ?? [], [
            'equipped_'.$type => null,
        ]);
        $user->save();
    }
}
