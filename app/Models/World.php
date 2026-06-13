<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

#[Table('worlds')]
#[Fillable(['name', 'slug', 'description', 'theme_pack_id', 'is_published'])]
class World extends Model implements Sortable
{
    use SortableTrait;

    protected $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function themePack(): BelongsTo
    {
        return $this->belongsTo(ThemePack::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
