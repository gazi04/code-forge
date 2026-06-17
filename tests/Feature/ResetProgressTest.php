<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\StoreItem;
use App\Models\User;
use App\Models\UserInventory;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Redis::shouldReceive('zrem')->andReturn(0)->byDefault();
});

it('nulls out equipped_title and equipped_avatar when resetting progress', function () {
    $item = StoreItem::create([
        'name' => 'Cool Title',
        'type' => 'title',
        'purchase_type' => 'permanent',
        'price_coins' => 50,
        'is_active' => true,
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'preferences' => [
            'equipped_title' => $item->id,
            'equipped_avatar' => $item->id,
            'background_audio' => true,
            'sound_effects' => false,
            'accessibility_mode' => true,
        ],
    ]);

    UserInventory::create(['user_id' => $student->id, 'store_item_id' => $item->id, 'quantity' => 1, 'acquired_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');

    $student->refresh();

    expect($student->preferences['equipped_title'])->toBeNull();
    expect($student->preferences['equipped_avatar'])->toBeNull();
});

it('preserves audio and accessibility preferences when resetting progress', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'preferences' => [
            'equipped_title' => 999,
            'equipped_avatar' => 999,
            'background_audio' => false,
            'sound_effects' => true,
            'accessibility_mode' => true,
        ],
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');

    $student->refresh();

    expect($student->preferences['background_audio'])->toBeFalse();
    expect($student->preferences['sound_effects'])->toBeTrue();
    expect($student->preferences['accessibility_mode'])->toBeTrue();
});

it('clears inventory and resets stats when resetting progress', function () {
    $item = StoreItem::create([
        'name' => 'Item',
        'type' => 'streak_freeze',
        'purchase_type' => 'consumable',
        'price_coins' => 10,
        'is_active' => true,
    ]);

    $student = User::factory()->create(['role' => 'student', 'level' => 5, 'xp' => 500, 'coins' => 200]);
    UserInventory::create(['user_id' => $student->id, 'store_item_id' => $item->id, 'quantity' => 1, 'acquired_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');

    $student->refresh();

    expect($student->level)->toBe(1)
        ->and($student->xp)->toBe(0)
        ->and($student->coins)->toBe(0)
        ->and(UserInventory::where('user_id', $student->id)->count())->toBe(0);
});

it('resets xp_boost_multiplier and xp_boost_lessons_remaining when resetting progress', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'xp_boost_multiplier' => 1.5,
        'xp_boost_lessons_remaining' => 5,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');

    $student->refresh();

    expect((float) $student->xp_boost_multiplier)->toBe(1.0)
        ->and($student->xp_boost_lessons_remaining)->toBe(0);
});

it('resets streak, rested xp and pending achievements when resetting progress', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'streak_count' => 12,
        'streak_freezes' => 3,
        'rested_xp_balance' => 400,
        'pending_achievements' => [['name' => 'First Steps']],
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');

    $student->refresh();

    expect($student->streak_count)->toBe(0)
        ->and($student->streak_freezes)->toBe(0)
        ->and($student->rested_xp_balance)->toBe(0)
        ->and($student->pending_achievements)->toBeNull();
});

it('removes the student from both Redis leaderboards when resetting progress', function () {
    $student = User::factory()->create(['role' => 'student']);

    Redis::shouldReceive('zrem')->once()->with('leaderboard:all_time', $student->id)->andReturn(1);
    Redis::shouldReceive('zrem')->once()->with('leaderboard:weekly', $student->id)->andReturn(1);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $student->getRouteKey()])
        ->callAction('resetProgress');
});
