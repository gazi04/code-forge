<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public ?string $source = null) {}
}
