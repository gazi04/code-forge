<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\UserLeveledUp;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ProgressionService
{
    public function getXpRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        // Fast, highly-controlled pacing for the first 10 levels
        $earlyLevels = [
            1 => 0,
            2 => 100,
            3 => 250,
            4 => 450,
            5 => 700,
            6 => 1000,
            7 => 1350,
            8 => 1750,
            9 => 2200,
            10 => 2700,
        ];

        if (array_key_exists($level, $earlyLevels)) {
            return $earlyLevels[$level];
        }

        // Polynomial steep scaling for Level 11+
        // Formula: (Level ^ 1.5) * 100
        return (int) round($level ** 1.5 * 100);
    }

    /**
     * 2. The Game Loop Processor
     * Calculates multipliers, injects loot, checks for level ups, and secures it in a transaction.
     */
    public function processVictory(User $user, int $baseXp, int $baseCoins): array
    {
        return DB::transaction(function () use ($user, $baseXp, $baseCoins): array {
            // Re-fetch the row under a lock and run every read/mutation against this
            // copy. Eloquent's save() writes dirty columns from the in-memory value,
            // so processing the passed-in (possibly stale) instance lets two
            // concurrent claims for the same user clobber each other's XP/coins
            // (lost update). lockForUpdate serializes them on MySQL/Postgres (no-op
            // on SQLite). The committed state is synced back onto $user at the end so
            // callers and downstream events see the fresh values.
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->first() ?? $user;

            $today = Date::today();
            $lastActive = $lockedUser->last_active_at ? Date::parse($lockedUser->last_active_at)->startOfDay() : null;

            // --- A. Streak & Daily Login Logic ---
            if (! $lastActive || $lastActive->isBefore($today)) {
                if ($lastActive && $lastActive->isYesterday()) {
                    // Standard consecutive day
                    $lockedUser->streak_count++;
                } elseif ($lastActive && $lastActive->diffInDays($today) > 1) {
                    // The user skipped at least one day. A freeze covers exactly one
                    // missed day, so the streak is only saved when they hold enough
                    // freezes for every missed day in the gap.
                    $daysSince = (int) round($lastActive->diffInDays($today));
                    $daysMissed = $daysSince - 1;

                    if ($lockedUser->streak_freezes >= $daysMissed) {
                        // Fully covered: spend one freeze per missed day, preserve the
                        // streak, and let today extend it by one (frozen days don't count).
                        $lockedUser->streak_freezes -= $daysMissed;
                        $lockedUser->streak_count++;
                    } else {
                        // Not enough freezes: the streak lapsed.
                        $lockedUser->streak_count = 1;

                        // Rested XP catch-up only when the streak actually broke for 3+ days
                        // (a freeze-covered gap is not an absence to compensate for).
                        if ($daysSince >= 3) {
                            $lockedUser->rested_xp_balance += 200;
                        }
                    }
                } else {
                    // First time ever playing
                    $lockedUser->streak_count = 1;
                }

                // Update their last active timestamp to right now
                $lockedUser->last_active_at = now();
            }

            // --- B. Experience Multipliers ---
            $multiplier = 1.0;
            if ($lockedUser->streak_count >= 7) {
                $multiplier = 1.15; // 15% bonus for a week-long streak
            } elseif ($lockedUser->streak_count >= 3) {
                $multiplier = 1.05; // 5% bonus for a 3-day streak
            }

            $earnedXp = (int) round($baseXp * $multiplier);

            // Drain the Rested XP pool (grants a 50% bonus on top of their earnings until the pool is empty)
            if ($lockedUser->rested_xp_balance > 0) {
                $restedBonus = (int) round($earnedXp * 0.5);

                if ($lockedUser->rested_xp_balance >= $restedBonus) {
                    $earnedXp += $restedBonus;
                    $lockedUser->rested_xp_balance -= $restedBonus;
                } else {
                    $earnedXp += $lockedUser->rested_xp_balance;
                    $lockedUser->rested_xp_balance = 0;
                }
            }

            // Apply active XP boost before awarding
            if ($lockedUser->xp_boost_lessons_remaining > 0) {
                $earnedXp = (int) round($earnedXp * $lockedUser->xp_boost_multiplier);
                $lockedUser->xp_boost_lessons_remaining--;
                if ($lockedUser->xp_boost_lessons_remaining === 0) {
                    $lockedUser->xp_boost_multiplier = 1;
                }
            }

            // --- C. Award Loot ---
            $lockedUser->xp += $earnedXp;
            $lockedUser->coins += $baseCoins; // We keep coins flat and predictable
            $lockedUser->total_coins_earned += $baseCoins;

            // --- D. Level Up Engine ---
            $leveledUp = false;
            $startingLevel = $lockedUser->level;

            // We use a while loop just in case they earn massive XP and jump two levels at once
            while ($lockedUser->xp >= $this->getXpRequiredForLevel($lockedUser->level + 1)) {
                $lockedUser->level++;
                $leveledUp = true;
            }

            $lockedUser->save();

            // Sync the committed state back onto the caller's instance so downstream
            // consumers (e.g. ProgressRegistered -> EvaluateAchievements, submitClaim's
            // level/refresh flow) read the fresh values, not the stale pre-call ones.
            $user->setRawAttributes($lockedUser->getAttributes());
            $user->syncOriginal();

            if ($leveledUp) {
                event(new UserLeveledUp($user, $startingLevel, $user->level));
            }

            if (! $user->is_shadowbanned) {
                // Defer the leaderboard writes until the outermost DB transaction
                // commits — the controller callers wrap processVictory in an outer
                // transaction plus the submission write. If that rolls back, the XP
                // is reverted in the DB and Redis must not keep the increment.
                DB::afterCommit(function () use ($user, $earnedXp): void {
                    Redis::zincrby('leaderboard:all_time', $earnedXp, $user->id);
                    Redis::zincrby('leaderboard:weekly', $earnedXp, $user->id);
                });
            }

            // Return the payload formatted perfectly for Svelte
            return [
                'status' => 'success',
                'base_xp' => $baseXp,
                'total_xp_earned' => $earnedXp,
                'coins_earned' => $baseCoins,
                'leveled_up' => $leveledUp,
                'new_level' => $lockedUser->level,
                'streak_count' => $lockedUser->streak_count,
            ];
        });
    }
}
