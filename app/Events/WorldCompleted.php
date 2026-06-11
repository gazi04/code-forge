<?php

namespace App\Events;

use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorldCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly World $world,
    ) {}
}
