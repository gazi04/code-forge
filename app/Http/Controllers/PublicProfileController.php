<?php

namespace App\Http\Controllers;

use App\Concerns\BuildsAchievementList;
use App\Models\User;
use App\Services\ProfilePageService;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    use BuildsAchievementList;

    public function __construct(protected ProfilePageService $profilePage) {}

    public function show(User $user): Response
    {
        abort_unless($user->role === 'student', 404);
        abort_if($user->is_shadowbanned, 404);
        abort_unless($user->preferences['public_profile'] ?? true, 404);

        return Inertia::render('Student/Profile/Public', [
            'hero' => $this->profilePage->hero($user, public: true),
            'achievements' => $this->buildAchievementList($user),
            'certificates' => $this->profilePage->certificates($user),
        ]);
    }
}
