<?php

namespace App\Http\Controllers;

use App\Models\StoreItem;
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
        $user = Auth::user();

        if (! $item->is_active) {
            return redirect()->back()->with('store_result', ['error' => 'This item is no longer available.']);
        }

        if ($user->coins < $item->price_coins) {
            return redirect()->back()->with('store_result', ['error' => 'Not enough coins.']);
        }

        if ($item->purchase_type === 'permanent') {
            $alreadyOwned = UserInventory::where('user_id', $user->id)
                ->where('store_item_id', $item->id)
                ->exists();

            if ($alreadyOwned) {
                return redirect()->back()->with('store_result', ['error' => 'You already own this item.']);
            }
        }

        if ($item->purchase_type === 'one_time' && $item->sold_count >= $item->stock_limit) {
            return redirect()->back()->with('store_result', ['error' => 'This item is sold out.']);
        }

        DB::transaction(function () use ($user, $item) {
            $user->coins -= $item->price_coins;
            $user->save();

            $item->increment('sold_count');

            $existing = UserInventory::where('user_id', $user->id)
                ->where('store_item_id', $item->id)
                ->first();

            if ($existing) {
                $existing->increment('quantity');
            } else {
                UserInventory::create([
                    'user_id' => $user->id,
                    'store_item_id' => $item->id,
                    'quantity' => 1,
                    'acquired_at' => now(),
                ]);
            }
        });

        return redirect()->route('student.store.index', ['tab' => 'inventory'])
            ->with('store_result', ['purchased' => $item->name]);
    }

    public function activateItem(UserInventory $inventory): RedirectResponse
    {
        $user = Auth::user();
        abort_if($inventory->user_id !== $user->id, 403);

        $item = $inventory->storeItem;

        if ($item->type === 'streak_freeze') {
            $user->streak_freezes += (int) ($item->effect_config['quantity'] ?? 1);
        } elseif ($item->type === 'xp_boost') {
            $user->xp_boost_multiplier = (int) ($item->effect_config['multiplier'] ?? 2);
            $user->xp_boost_lessons_remaining += (int) ($item->effect_config['lessons'] ?? 3);
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
        $user->preferences = array_merge($user->preferences ?? [], [
            'equipped_'.$item->type => $item->id,
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
