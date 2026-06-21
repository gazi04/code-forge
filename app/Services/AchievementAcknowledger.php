<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementAcknowledger
{
    /**
     * Clear the acknowledged achievement toasts from a user's pending queue.
     *
     * Only the acknowledged ids are removed — re-reading under a row lock — so an
     * achievement queued between the client rendering its toasts and POSTing this
     * acknowledgement is preserved instead of being silently discarded.
     *
     * @param  array<int, int>  $acknowledgedIds
     */
    public function acknowledge(User $user, array $acknowledgedIds): void
    {
        DB::transaction(function () use ($user, $acknowledgedIds): void {
            /** @var User $lockedUser */
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

            $pending = $lockedUser->pending_achievements ?? [];

            $remaining = array_values(array_filter(
                $pending,
                fn (array $achievement): bool => ! in_array($achievement['id'], $acknowledgedIds, true),
            ));

            $lockedUser->update(['pending_achievements' => $remaining === [] ? null : $remaining]);
        });
    }
}
