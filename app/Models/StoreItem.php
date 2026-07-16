<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'type', 'purchase_type', 'price_coins', 'icon', 'image', 'stock_limit', 'sold_count', 'effect_config', 'display_config', 'is_active'])]
class StoreItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => StoreItemType::class,
            'purchase_type' => PurchaseType::class,
            'effect_config' => 'array',
            'display_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function userInventory(): HasMany
    {
        return $this->hasMany(UserInventory::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_inventory')
            ->withPivot(['quantity', 'acquired_at']);
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
