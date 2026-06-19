<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentRegisterController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Student/Register');
    }

    public function store(StudentRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            ...$validated,
            'role' => 'student',
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/worlds');
    }
}
