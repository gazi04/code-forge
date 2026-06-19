<?php

use App\Enums\StoreItemType;
use App\Filament\Resources\StoreItems\Pages\CreateStoreItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('does not error when selecting a consumable type on the live form', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateStoreItem::class)
        ->fillForm(['type' => StoreItemType::XpBoost->value])
        ->assertOk();
});

it('shows the consumable effect field for consumable types', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateStoreItem::class)
        ->fillForm(['type' => StoreItemType::StreakFreeze->value])
        ->assertFormFieldVisible('effect_config')
        ->fillForm(['type' => StoreItemType::XpBoost->value])
        ->assertFormFieldVisible('effect_config');
});

it('hides the consumable effect field for cosmetic types', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateStoreItem::class)
        ->fillForm(['type' => StoreItemType::Avatar->value])
        ->assertFormFieldHidden('effect_config');
});

it('shows the title color field only for the title type', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateStoreItem::class)
        ->fillForm(['type' => StoreItemType::Title->value])
        ->assertFormFieldVisible('display_config.color')
        ->fillForm(['type' => StoreItemType::Avatar->value])
        ->assertFormFieldHidden('display_config.color');
});
