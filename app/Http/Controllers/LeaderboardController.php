<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaderboardRequest;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(protected LeaderboardService $leaderboard) {}

    public function index(LeaderboardRequest $request): Response
    {
        $scope = $request->validated('scope', 'weekly');

        return Inertia::render(
            'Student/Leaderboard/Index',
            $this->leaderboard->topLeaders(Auth::user(), $scope),
        );
    }
}
