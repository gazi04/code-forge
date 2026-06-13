<?php

namespace App\Services;

use App\Events\UserLeveledUp;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        return (int) round(pow($level, 1.5) * 100);
    }

    /**
     * 2. The Game Loop Processor
     * Calculates multipliers, injects loot, checks for level ups, and secures it in a transaction.
     */
    public function processVictory(User $user, int $baseXp, int $baseCoins): array
    {
        return DB::transaction(function () use ($user, $baseXp, $baseCoins) {
            $today = Carbon::today();
            $lastActive = $user->last_active_at ? Carbon::parse($user->last_active_at)->startOfDay() : null;

            // --- A. Streak & Daily Login Logic ---
            if (! $lastActive || $lastActive->isBefore($today)) {
                if ($lastActive && $lastActive->isYesterday()) {
                    // Standard consecutive day
                    $user->streak_count++;
                } elseif ($lastActive && $lastActive->diffInDays($today) > 1) {
                    // The user broke their streak!
                    if ($user->streak_freezes > 0) {
                        // Mechanic: Consume a freeze to save the streak
                        $user->streak_freezes--;
                        $user->streak_count++;
                    } else {
                        // Reset to day 1
                        $user->streak_count = 1;
                    }

                    // Mechanic: Rested XP catch-up pool if they were gone for 3+ days
                    if ($lastActive->diffInDays($today) >= 3) {
                        $user->rested_xp_balance += 200;
                    }
                } else {
                    // First time ever playing
                    $user->streak_count = 1;
                }

                // Update their last active timestamp to right now
                $user->last_active_at = now();
            }

            // --- B. Experience Multipliers ---
            $multiplier = 1.0;
            if ($user->streak_count >= 7) {
                $multiplier = 1.15; // 15% bonus for a week-long streak
            } elseif ($user->streak_count >= 3) {
                $multiplier = 1.05; // 5% bonus for a 3-day streak
            }

            $earnedXp = (int) round($baseXp * $multiplier);

            // Drain the Rested XP pool (grants a 50% bonus on top of their earnings until the pool is empty)
            if ($user->rested_xp_balance > 0) {
                $restedBonus = (int) round($earnedXp * 0.5);

                if ($user->rested_xp_balance >= $restedBonus) {
                    $earnedXp += $restedBonus;
                    $user->rested_xp_balance -= $restedBonus;
                } else {
                    $earnedXp += $user->rested_xp_balance;
                    $user->rested_xp_balance = 0;
                }
            }

            // Apply active XP boost before awarding
            if ($user->xp_boost_lessons_remaining > 0) {
                $earnedXp = (int) ($earnedXp * $user->xp_boost_multiplier);
                $user->xp_boost_lessons_remaining--;
                if ($user->xp_boost_lessons_remaining === 0) {
                    $user->xp_boost_multiplier = 1;
                }
            }

            // --- C. Award Loot ---
            $user->xp += $earnedXp;
            $user->coins += $baseCoins; // We keep coins flat and predictable
            $user->total_coins_earned += $baseCoins;

            // --- D. Level Up Engine ---
            $leveledUp = false;
            $startingLevel = $user->level;

            // We use a while loop just in case they earn massive XP and jump two levels at once
            while ($user->xp >= $this->getXpRequiredForLevel($user->level + 1)) {
                $user->level++;
                $leveledUp = true;
            }

            $user->save();

            if ($leveledUp) {
                event(new UserLeveledUp($user, $startingLevel, $user->level));
            }

            if (! $user->is_shadowbanned) {
                Redis::zincrby('leaderboard:all_time', $earnedXp, $user->name);
                Redis::zincrby('leaderboard:weekly', $earnedXp, $user->name);
            }

            // Return the payload formatted perfectly for Svelte
            return [
                'status' => 'success',
                'base_xp' => $baseXp,
                'total_xp_earned' => $earnedXp,
                'coins_earned' => $baseCoins,
                'leveled_up' => $leveledUp,
                'new_level' => $user->level,
                'streak_count' => $user->streak_count,
            ];
        });
    }
}
