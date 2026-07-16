<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redis;

#[Signature('app:rebuild-leaderboard')]
#[Description('Rebuilds the all-time leaderboard Redis sorted set from users.xp (the durable source of truth).')]
class RebuildLeaderboard extends Command
{
    public function handle(): int
    {
        Redis::del('leaderboard:all_time');

        $count = 0;

        User::query()
            ->where('is_shadowbanned', false)
            ->where('xp', '>', 0)
            ->select(['id', 'xp'])
            ->chunkById(500, function (Collection $users) use (&$count): void {
                foreach ($users as $user) {
                    Redis::zadd('leaderboard:all_time', $user->xp, $user->id);
                    $count++;
                }
            });

        $this->info(sprintf('Rebuilt all-time leaderboard from %d students.', $count));

        return self::SUCCESS;
    }
}
