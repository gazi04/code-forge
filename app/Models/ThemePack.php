<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('theme_packs')]
#[Fillable(['name', 'identifier', 'config'])]
class ThemePack extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'config' stores the background style, color palette,
        // UI element styling, and character sprites.
        'config' => 'array',
    ];

    /**
     * Get the worlds that use this theme pack.
     */
    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }
}
