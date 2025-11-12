<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SessionAuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\ProfileController;

// AUTH (session/cookie, có CSRF)
Route::middleware(['web'])->group(function () {
    // Fortify đã có POST /login, POST /logout, POST /register nếu bạn muốn dùng trực tiếp.
    // Ở đây ta phơi bày endpoints tự quản (có thể dùng thay thế Fortify login)
    Route::post('/login',  [SessionAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [SessionAuthController::class, 'logout'])->middleware('auth');

    Route::post('/register', [RegistrationController::class, 'register'])->middleware('throttle:login');

    // Email verification
    Route::get('/email/verify/{id}/{hash}', [RegistrationController::class, 'verify'])
        ->middleware(['auth', 'signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [RegistrationController::class, 'resendVerification'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('verification.send');

    // Profile (yêu cầu đăng nhập + verify nếu cần)
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/me', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    });
});
