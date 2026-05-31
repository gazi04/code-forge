<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLeveledUp
{
    use Dispatchable, SerializesModels;

    public User $user;
    public int $oldLevel;
    public int $newLevel;

    public function __construct(User $user, int $oldLevel, int $newLevel)
    {
        $this->user = $user;
        $this->oldLevel = $oldLevel;
        $this->newLevel = $newLevel;
    }
}
