<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

#[Signature('app:reset-weekly-leaderboard')]
#[Description('Resets the weekly leaderboard Redis sorted set every Monday at midnight.')]
class ResetWeeklyLeaderboard extends Command
{
    public function handle(): void
    {
        Redis::del('leaderboard:weekly');

        $this->info('Weekly leaderboard reset successfully.');
    }
}
