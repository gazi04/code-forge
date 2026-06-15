<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Clear the authenticated user's pending achievement toasts. Called by the
     * client once it has consumed them, so the shared Inertia prop never has to
     * mutate the user during prop resolution.
     */
    public function acknowledge(Request $request): RedirectResponse
    {
        $request->user()->update(['pending_achievements' => null]);

        return back();
    }
}
