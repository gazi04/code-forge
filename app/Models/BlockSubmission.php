<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('block_submissions')]
#[Fillable(['user_id', 'lesson_id', 'block_index', 'xp_rewarded', 'coins_rewarded'])]
class BlockSubmission extends Model
{
    //
}
