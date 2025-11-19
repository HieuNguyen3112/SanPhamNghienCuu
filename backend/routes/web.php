<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SessionAuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;

// AUTH (session/cookie, co CSRF)
Route::middleware(['web'])->group(function () {
    Route::post('/login',  [SessionAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [SessionAuthController::class, 'logout'])->middleware('auth');

    Route::post('/register', [RegistrationController::class, 'register'])->middleware('throttle:login');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');
    Route::get('/csrf-token', fn() => response()->json(['csrf_token' => csrf_token()], 200));

    // Email verification
    Route::get('/email/verify/{id}/{hash}', [RegistrationController::class, 'verify'])
        ->middleware(['auth', 'signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [RegistrationController::class, 'resendVerification'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('verification.send');

    // Profile (yeu cau dang nhap + verify neu can)
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/me', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    });
});