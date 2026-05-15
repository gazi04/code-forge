<?php

use App\Http\Controllers\WorldController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// The "World Map" - Entry point for the student
Route::get('/worlds', [WorldController::class, 'index'])->name('student.worlds.index');
Route::get('/worlds/{world:slug}', [WorldController::class, 'show'])->name('student.worlds.show');
require __DIR__.'/settings.php';
