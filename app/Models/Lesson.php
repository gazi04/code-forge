<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

#[Table('lessons')]
#[Fillable(['course_id', 'name', 'slug', 'xp_reward', 'coin_reward', 'estimated_duration', 'is_boss', 'blocks'])]
class Lesson extends Model implements Sortable
{
    use SortableTrait;

    protected $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    protected $casts = [
        'is_boss' => 'boolean',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'estimated_duration' => 'integer',
        'blocks' => 'array',
        'sort_order' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
