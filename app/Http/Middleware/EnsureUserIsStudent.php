<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    /**
     * Restrict the student-facing surface to student accounts. Admins are routed
     * to the Filament panel; any other role is forbidden. Keeps a clean trust
     * boundary so admin sessions can't build leaderboard/profile state on an
     * account that never plays.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->role !== 'student') {
            if ($user?->role === 'admin') {
                return redirect()->route('filament.admin.pages.dashboard');
            }

            abort(403);
        }

        return $next($request);
    }
}
