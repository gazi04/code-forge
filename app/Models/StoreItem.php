<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'type', 'purchase_type', 'price_coins', 'icon', 'image', 'stock_limit', 'sold_count', 'effect_config', 'display_config', 'is_active'])]
class StoreItem extends Model
{
    protected function casts(): array
    {
        return [
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
