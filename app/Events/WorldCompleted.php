<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorldCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly World $world,
    ) {}
}
