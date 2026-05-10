<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('worlds')]
class World extends Model
{
    protected $casts = [
        'accent_colors' => 'array',
        'is_published' => 'boolean',
    ];

    public function themePack(): BelongsTo
    {
        return $this->belongsTo(ThemePack::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class)->orderBy('order');
    }
}
