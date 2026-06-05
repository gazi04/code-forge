<?php

use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorldController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'hi';
})->name('home');

Route::get('/login', [StudentLoginController::class, 'show'])->name('login');
Route::post('/login/student', [StudentLoginController::class, 'store'])->name('student.login.submit');

Route::middleware(['auth'])->name('student.')->group(function (): void {

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');

    Route::name('world.')->group(function (): void {
        Route::get('/worlds', [WorldController::class, 'index'])
            ->name('index');

        Route::get('/worlds/{world:slug}', [WorldController::class, 'show'])
            ->name('show');
    });

    Route::get('/course/{course:slug}', [CourseController::class, 'show'])
        ->name('course.show');

    Route::name('lessons.')->group(function (): void {
        Route::get('/lessons/{lesson:slug}', [LessonController::class, 'show'])
            ->name('show');

        Route::post('/lessons/{lesson:slug}/submit', [LessonController::class, 'submitClaim'])
            ->name('submit');

        Route::post('/lessons/{lesson:slug}/blocks/{blockIndex}/claim', [LessonController::class, 'submitBlockClaim'])
            ->name('block.claim');
    });

});

require __DIR__.'/settings.php';
