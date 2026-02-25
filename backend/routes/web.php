<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SessionAuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;

Route::get('/', fn() => response()->json(['ok' => true], 200));

// AUTH (session/cookie, co CSRF)
Route::middleware(['web'])->group(function () {
    // Cong khai cho dang nhap/dang ky
    Route::post('/login',  [SessionAuthController::class, 'login'])->middleware('throttle:login');
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

    // Dang xuat
    Route::post('/logout', [SessionAuthController::class, 'logout'])->middleware('auth');

    // Profile: /me tra JSON ke ca loi (auth/verified/role) de tranh redirect/HTML
    Route::middleware(['force.json', 'auth', 'verified', 'role:LECTURER|DEPARTMENT_BOARD|SCIENCE_OFFICE|GV|DL|QL|ADMIN'])->group(function () {
        Route::get('/me', [ProfileController::class, 'me']);
    });

    Route::middleware(['auth', 'verified', 'role:LECTURER|DEPARTMENT_BOARD|SCIENCE_OFFICE|GV|DL|QL|ADMIN'])->group(function () {
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        // Nhom placeholder cho cac module web rieng theo role (them sau)
        Route::middleware('role:SCIENCE_OFFICE|QL|ADMIN')->group(function () {
            // TODO: them route quan tri web (neu can) - giu trong de sap xep middleware
        });
    });
});
