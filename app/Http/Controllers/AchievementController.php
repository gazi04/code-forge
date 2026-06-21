<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcknowledgeAchievementRequest;
use App\Services\AchievementAcknowledger;
use Illuminate\Http\RedirectResponse;

class AchievementController extends Controller
{
    public function __construct(protected AchievementAcknowledger $acknowledger) {}

    /**
     * Clear the achievement toasts the client has actually displayed. Called by
     * the toast component with the ids it consumed, so the shared Inertia prop
     * never has to mutate the user during prop resolution.
     */
    public function acknowledge(AcknowledgeAchievementRequest $request): RedirectResponse
    {
        $this->acknowledger->acknowledge($request->user(), $request->validated('ids'));

        return back();
    }
}
