<?php

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserInventory;
use Inertia\Testing\AssertableInertia as Assert;

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

it('applies a quantity-1 consumable effect only once', function () {
    $user = User::factory()->create(['streak_freezes' => 0]);
    $item = makeItem(['type' => 'streak_freeze', 'effect_config' => ['quantity' => 1]]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    // First activation applies the effect and consumes the row.
    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory));

    expect($user->fresh()->streak_freezes)->toBe(1);
    expect(UserInventory::find($inventory->id))->toBeNull();

    // A second activation of the now-consumed row must not re-apply the effect.
    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.activate', $inventory))
        ->assertStatus(404);

    expect($user->fresh()->streak_freezes)->toBe(1);
});

// ─── Index serialization ─────────────────────────────────────────────────────

it('never ships store item internals to the client', function () {
    $user = User::factory()->create();
    makeItem([
        'name' => 'Frost Charm',
        'type' => 'streak_freeze',
        'purchase_type' => 'consumable',
        'price_coins' => 250,
        'effect_config' => ['quantity' => 3],
        'display_config' => ['color' => '#ff0000'],
        'stock_limit' => 40,
        'sold_count' => 11,
    ]);

    $this->actingAs($user)
        ->get(route('student.store.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            // Economy internals must not reach the browser.
            ->missing('items.0.effect_config')
            ->missing('items.0.display_config')
            ->missing('items.0.is_active')
            ->missing('items.0.stock_limit')
            ->missing('items.0.sold_count')
            // What the UI actually renders must survive the whitelist.
            ->where('items.0.name', 'Frost Charm')
            ->where('items.0.type', 'streak_freeze')
            ->where('items.0.purchase_type', 'consumable')
            ->where('items.0.price_coins', 250)
            ->has('items.0.id')
            ->has('items.0.image_url')
        );
});

it('derives stock_remaining for a limited item instead of exposing the raw counts', function () {
    $user = User::factory()->create();
    makeItem([
        'type' => 'title',
        'purchase_type' => 'one_time',
        'stock_limit' => 5,
        'sold_count' => 2,
        'effect_config' => null,
    ]);

    $this->actingAs($user)
        ->get(route('student.store.index'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.stock_remaining', 3));
});

it('reports no stock_remaining for an unlimited item', function () {
    $user = User::factory()->create();
    makeItem(['purchase_type' => 'consumable', 'stock_limit' => null]);

    $this->actingAs($user)
        ->get(route('student.store.index'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.stock_remaining', null));
});

it('clamps stock_remaining at zero for an oversold item', function () {
    $user = User::factory()->create();
    makeItem([
        'type' => 'title',
        'purchase_type' => 'one_time',
        'stock_limit' => 5,
        'sold_count' => 7,
        'effect_config' => null,
    ]);

    $this->actingAs($user)
        ->get(route('student.store.index'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.stock_remaining', 0));
});

it('applies the same whitelist to inventory items as to shop items', function () {
    $user = User::factory()->create();
    $item = makeItem(['effect_config' => ['quantity' => 2], 'stock_limit' => 9, 'sold_count' => 4]);
    UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.store.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inventory', 1)
            ->missing('inventory.0.store_item.effect_config')
            ->missing('inventory.0.store_item.stock_limit')
            ->missing('inventory.0.store_item.sold_count')
            ->missing('inventory.0.store_item.is_active')
            ->has('inventory.0.store_item.type')
            ->has('inventory.0.store_item.image_url')
            // The surrounding inventory keys the card reads must stay put.
            ->has('inventory.0.id')
            ->has('inventory.0.store_item_id')
            ->where('inventory.0.quantity', 1)
        );
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

it('preserves unrelated preference keys when equipping', function () {
    $user = User::factory()->create([
        'preferences' => [
            'background_audio' => false,
            'accessibility_mode' => true,
            'equipped_title' => 99,
        ],
    ]);
    $item = makeItem(['type' => 'avatar', 'purchase_type' => 'permanent', 'effect_config' => null]);
    $inventory = UserInventory::create([
        'user_id' => $user->id,
        'store_item_id' => $item->id,
        'quantity' => 1,
        'acquired_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->post(route('student.inventory.equip', $inventory));

    $prefs = $user->fresh()->preferences;

    expect($prefs['equipped_avatar'])->toBe($item->id)
        ->and($prefs['equipped_title'])->toBe(99)
        ->and($prefs['background_audio'])->toBeFalse()
        ->and($prefs['accessibility_mode'])->toBeTrue();
});

it('preserves unrelated preference keys when unequipping', function () {
    $user = User::factory()->create([
        'preferences' => [
            'background_audio' => false,
            'equipped_title' => 99,
            'equipped_avatar' => 42,
        ],
    ]);

    $this->actingAs($user)
        ->from(route('student.store.index'))
        ->delete(route('student.inventory.unequip', 'title'));

    $prefs = $user->fresh()->preferences;

    expect($prefs['equipped_title'])->toBeNull()
        ->and($prefs['equipped_avatar'])->toBe(42)
        ->and($prefs['background_audio'])->toBeFalse();
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
