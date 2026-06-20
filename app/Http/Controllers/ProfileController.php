<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileSettingsRequest;
use App\Services\ProfilePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(protected ProfilePageService $profilePage) {}

    public function show(): Response
    {
        return Inertia::render(
            'Student/Profile/Index',
            $this->profilePage->getProfilePageData(Auth::user()),
        );
    }

    public function updateSettings(UpdateProfileSettingsRequest $request): RedirectResponse
    {
        $prefs = array_merge(Auth::user()->preferences ?? [], $request->validated());
        Auth::user()->update(['preferences' => $prefs]);

        return back();
    }
}
