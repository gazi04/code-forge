<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\WorldController;
use App\Http\Controllers\Auth\StudentLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function(){
    return "hi";
})->name('home');

Route::get('/login', [StudentLoginController::class, 'show'])->name('login');
Route::post('/login/student', [StudentLoginController::class, 'store'])->name('student.login.submit');

Route::middleware(['auth'])->group(function () {

    Route::get('/worlds', [WorldController::class, 'index'])->name('student.worlds.index');
    Route::get('/worlds/{world:slug}', [WorldController::class, 'show'])->name('student.worlds.show');

    Route::get('/course/{course:slug}', [CourseController::class, 'show'])->name('student.course.show');

    Route::get('/lessons/{lesson:slug}', [LessonController::class, 'show'])->name('student.lessons.show');

    Route::post('/lessons/{lesson:slug}/submit', [LessonController::class, 'submitClaim'])->name('student.lessons.submit');
});

require __DIR__.'/settings.php';
