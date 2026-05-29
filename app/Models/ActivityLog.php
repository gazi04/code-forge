<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('activity_log')]
#[Fillable(['log_name', 'description', 'subject', 'causer', 'attribute_changes', 'properties'])]
class ActivityLog extends Model
{
    protected $casts = [
        'attribute_changes' => 'array',
        'properties' => 'array',
    ];
}
