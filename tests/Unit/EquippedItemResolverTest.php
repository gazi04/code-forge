<?php

use App\Models\StoreItem;
use App\Models\User;
use App\Services\EquippedItemResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeStoreItem(array $overrides = []): StoreItem
{
    return StoreItem::create(array_merge([
        'name' => 'Test Item',
        'type' => 'title',
        'purchase_type' => 'permanent',
        'price_coins' => 100,
        'is_active' => true,
    ], $overrides));
}

it('returns null title and avatar when nothing is equipped', function () {
    $user = User::factory()->create(['preferences' => []]);

    expect((new EquippedItemResolver)->resolveEquipped($user))
        ->toBe(['title' => null, 'avatar' => null]);
});

it('filters null ids out of equippedItemIds', function () {
    $title = makeStoreItem(['type' => 'title']);
    $user = User::factory()->create(['preferences' => ['equipped_title' => $title->id, 'equipped_avatar' => null]]);

    expect((new EquippedItemResolver)->equippedItemIds($user))->toBe([$title->id]);
});

it('returns an empty collection when fetching no ids', function () {
    expect((new EquippedItemResolver)->fetchEquippedItems([]))->toBeEmpty();
});

it('builds the equipped payload for a title and an avatar', function () {
    $title = makeStoreItem(['type' => 'title', 'name' => 'Champion', 'display_config' => ['color' => '#ff0000']]);
    $avatar = makeStoreItem(['type' => 'avatar', 'name' => 'Robot', 'image' => 'avatars/robot.png']);

    $user = User::factory()->create(['preferences' => [
        'equipped_title' => $title->id,
        'equipped_avatar' => $avatar->id,
    ]]);

    $payload = (new EquippedItemResolver)->resolveEquipped($user);

    expect($payload['title'])->toMatchArray(['id' => $title->id, 'name' => 'Champion', 'color' => '#ff0000'])
        ->and($payload['avatar']['id'])->toBe($avatar->id)
        ->and($payload['avatar']['name'])->toBe('Robot')
        ->and($payload['avatar']['image_url'])->toContain('avatars/robot.png');
});

it('builds equipped from a pre-fetched batch collection', function () {
    $title = makeStoreItem(['type' => 'title', 'name' => 'Sage', 'display_config' => ['color' => '#00ff00']]);
    $user = User::factory()->create(['preferences' => ['equipped_title' => $title->id]]);

    $resolver = new EquippedItemResolver;
    $items = $resolver->fetchEquippedItems($resolver->equippedItemIds($user));

    $payload = $resolver->buildEquipped($user, $items);

    expect($payload['title']['name'])->toBe('Sage')
        ->and($payload['avatar'])->toBeNull();
});
