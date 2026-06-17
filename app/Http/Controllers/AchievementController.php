<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchievementController extends Controller
{
    /**
     * Clear the achievement toasts the client has actually displayed. Called by
     * the toast component with the ids it consumed, so the shared Inertia prop
     * never has to mutate the user during prop resolution.
     *
     * Only the acknowledged ids are removed — re-reading under a row lock — so an
     * achievement queued between the client rendering its toasts and POSTing this
     * acknowledgement is preserved instead of being silently discarded.
     */
    public function acknowledge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $acknowledgedIds = $validated['ids'];

        DB::transaction(function () use ($request, $acknowledgedIds): void {
            /** @var User $user */
            $user = User::whereKey($request->user()->id)->lockForUpdate()->first();

            $pending = $user->pending_achievements ?? [];

            $remaining = array_values(array_filter(
                $pending,
                fn (array $achievement): bool => ! in_array($achievement['id'], $acknowledgedIds, true),
            ));

            $user->update(['pending_achievements' => $remaining === [] ? null : $remaining]);
        });

        return back();
    }
}
