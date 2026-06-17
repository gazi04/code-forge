<?php

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserInventory;

// ─── helpers ─────────────────────────────────────────────────────────────────

function makeItem(array $overrides = []): StoreItem
{
    return StoreItem::create(array_merge([
        'name' => 'Test Item',
        'type' => 'streak_freeze',
        'purchase_type' => 'consumable',
        'price_coins' => 100,
        'effect_config' => ['quantity' => 1],
        'is_active' => true,
    ], $overrides));
}

// ─── Purchase ────────────────────────────────────────────────────────────────

it('deducts coins and creates inventory on a successful purchase', function () {
    $user = User::factory()->create(['coins' => 200]);
    $item = makeItem(['price_coins' => 100]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect();

    expect($user->fresh()->coins)->toBe(100);
    expect(UserInventory::where('user_id', $user->id)->where('store_item_id', $item->id)->exists())->toBeTrue();
    expect($item->fresh()->sold_count)->toBe(1);
});

it('rejects purchase when user has insufficient coins', function () {
    $user = User::factory()->create(['coins' => 50]);
    $item = makeItem(['price_coins' => 100]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect()
        ->assertSessionHas('store_result.error');

    expect($user->fresh()->coins)->toBe(50);
    expect(UserInventory::where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejects purchase when the item is not active', function () {
    $user = User::factory()->create(['coins' => 500]);
    $item = makeItem(['is_active' => false]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect()
        ->assertSessionHas('store_result.error');
});

it('rejects purchasing a permanent item that is already owned', function () {
    $user = User::factory()->create(['coins' => 500]);
    $item = makeItem(['type' => 'title', 'purchase_type' => 'permanent', 'price_coins' => 200, 'effect_config' => null]);

    UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect()
        ->assertSessionHas('store_result.error');

    expect(UserInventory::where('user_id', $user->id)->where('store_item_id', $item->id)->count())->toBe(1);
});

it('rejects purchase when a one_time item is sold out', function () {
    $user = User::factory()->create(['coins' => 500]);
    $item = makeItem(['purchase_type' => 'one_time', 'stock_limit' => 1, 'sold_count' => 1]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect()
        ->assertSessionHas('store_result.error');
});

it('increments quantity on a repeat consumable purchase', function () {
    $user = User::factory()->create(['coins' => 500]);
    $item = makeItem(['purchase_type' => 'consumable', 'price_coins' => 100]);

    UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 2,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item));

    expect(UserInventory::where('user_id', $user->id)->where('store_item_id', $item->id)->value('quantity'))->toBe(3);
});

it('never drives coins negative across repeated purchases at the balance boundary', function () {
    $user = User::factory()->create(['coins' => 100]);
    $item = makeItem(['purchase_type' => 'consumable', 'price_coins' => 100]);

    // First purchase spends the exact balance.
    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item));

    expect($user->fresh()->coins)->toBe(0);

    // Second purchase must be rejected and leave state untouched (no negative coins).
    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertSessionHas('store_result.error');

    expect($user->fresh()->coins)->toBe(0);
    expect($item->fresh()->sold_count)->toBe(1);
    expect(UserInventory::where('user_id', $user->id)->where('store_item_id', $item->id)->value('quantity'))->toBe(1);
});

it('allows purchasing a one_time item with no stock limit', function () {
    $user = User::factory()->create(['coins' => 500]);
    $item = makeItem(['purchase_type' => 'one_time', 'stock_limit' => null, 'price_coins' => 100]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.store.purchase', $item))
        ->assertRedirect();

    expect($user->fresh()->coins)->toBe(400);
    expect(UserInventory::where('user_id', $user->id)->where('store_item_id', $item->id)->exists())->toBeTrue();
});

// ─── Activate consumable ──────────────────────────────────────────────────────

it('activates a streak_freeze item and increments streak_freezes', function () {
    $user = User::factory()->create(['streak_freezes' => 0]);
    $item = makeItem(['type' => 'streak_freeze', 'effect_config' => ['quantity' => 1]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    expect($user->fresh()->streak_freezes)->toBe(1);
    expect(UserInventory::find($inventory->id))->toBeNull();
});

it('activates an xp_boost item and sets the boost fields', function () {
    $user = User::factory()->create(['xp_boost_multiplier' => 1, 'xp_boost_lessons_remaining' => 0]);
    $item = makeItem(['type' => 'xp_boost', 'effect_config' => ['multiplier' => 2, 'lessons' => 5]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    expect($user->fresh()->xp_boost_multiplier)->toBe(2.0)
        ->and($user->fresh()->xp_boost_lessons_remaining)->toBe(5);
    expect(UserInventory::find($inventory->id))->toBeNull();
});

it('preserves a fractional xp_boost multiplier instead of truncating to an integer', function () {
    $user = User::factory()->create(['xp_boost_multiplier' => 1, 'xp_boost_lessons_remaining' => 0]);
    $item = makeItem(['type' => 'xp_boost', 'effect_config' => ['multiplier' => 1.5, 'lessons' => 3]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    expect($user->fresh()->xp_boost_multiplier)->toBe(1.5)
        ->and($user->fresh()->xp_boost_lessons_remaining)->toBe(3);
});

it('extends lessons and keeps the stronger multiplier when stacking xp_boosts', function () {
    $user = User::factory()->create(['xp_boost_multiplier' => 2.0, 'xp_boost_lessons_remaining' => 2]);
    $item = makeItem(['type' => 'xp_boost', 'effect_config' => ['multiplier' => 1.5, 'lessons' => 3]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    // Weaker boost must not downgrade the active 2.0×; lessons extend 2 + 3 = 5.
    expect($user->fresh()->xp_boost_multiplier)->toBe(2.0)
        ->and($user->fresh()->xp_boost_lessons_remaining)->toBe(5);
});

it('decrements inventory quantity instead of deleting when quantity is above 1', function () {
    $user = User::factory()->create(['streak_freezes' => 0]);
    $item = makeItem(['type' => 'streak_freeze', 'effect_config' => ['quantity' => 1]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 3,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    expect(UserInventory::find($inventory->id)->quantity)->toBe(2);
});

it('returns 403 when activating another user inventory item', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $item = makeItem();
    $inventory = UserInventory::create([
        'user_id' => $owner->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($attacker)
        ->post(route('student.inventory.activate', $inventory))
        ->assertStatus(403);
});

it('rejects activating a cosmetic item and leaves the inventory row intact', function () {
    $user = User::factory()->create();
    $item = makeItem(['type' => 'title', 'purchase_type' => 'permanent', 'effect_config' => null]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('student.inventory.activate', $inventory))
        ->assertStatus(422);

    // The item must NOT be consumed by the delete/decrement fall-through.
    expect(UserInventory::whereKey($inventory->id)->exists())->toBeTrue();
});

// ─── Equip / Unequip ─────────────────────────────────────────────────────────

it('equips a title item into user preferences', function () {
    $user = User::factory()->create();
    $item = makeItem(['type' => 'title', 'purchase_type' => 'permanent', 'effect_config' => null]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.equip', $inventory));

    expect($user->fresh()->preferences['equipped_title'])->toBe($item->id);
});

it('rejects equipping a non-cosmetic item and writes no junk preference', function () {
    $user = User::factory()->create();
    $item = makeItem(['type' => 'streak_freeze']);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.equip', $inventory))
        ->assertStatus(422);

    expect($user->fresh()->preferences['equipped_streak_freeze'] ?? null)->toBeNull();
});

it('unequips a title type from user preferences', function () {
    $user = User::factory()->create(['preferences' => ['equipped_title' => 99]]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->delete(route('student.inventory.unequip', 'title'));

    expect($user->fresh()->preferences['equipped_title'])->toBeNull();
});

it('returns 422 when unequipping an invalid type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('student.inventory.unequip', 'xp_boost'))
        ->assertStatus(422);
});

it('returns 403 when equipping another user inventory item', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $item = makeItem(['type' => 'title', 'purchase_type' => 'permanent', 'effect_config' => null]);
    $inventory = UserInventory::create([
        'user_id' => $owner->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($attacker)
        ->post(route('student.inventory.equip', $inventory))
        ->assertStatus(403);
});

// ─── Enum casts ──────────────────────────────────────────────────────────────

it('casts type and purchase_type to backed enums', function () {
    $item = makeItem(['type' => 'xp_boost', 'purchase_type' => 'one_time', 'stock_limit' => 5]);

    $fresh = StoreItem::find($item->id);

    expect($fresh->type)->toBe(StoreItemType::XpBoost);
    expect($fresh->purchase_type)->toBe(PurchaseType::OneTime);
    // Backed enums still serialize to plain strings for the frontend payload.
    expect($fresh->toArray()['type'])->toBe('xp_boost');
});
