<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\ProfileController;

// TOKEN-BASED (Sanctum Bearer)
Route::prefix('auth')->group(function () {
    Route::middleware(['auth:sanctum', 'auto.rotate.sanctum'])->group(function () {
        Route::get('/tokens',        [TokenAuthController::class, 'index']);   // list PATs
        Route::post('/token/issue',  [TokenAuthController::class, 'issue']);   // issue PAT cho user hien tai
        Route::post('/token/revoke', [TokenAuthController::class, 'revoke']);  // revoke current hoac theo id
        Route::post('/token/revoke-all', [TokenAuthController::class, 'revokeAll']); // revoke all PATs
        Route::post('/token/rotate', [TokenAuthController::class, 'rotate']);  // xoay token khi can
        Route::get('/token/ttl',     [TokenAuthController::class, 'ttl']);     // xem minutes_left
        Route::get('/me', [ProfileController::class, 'me']);
    });
});

// VA- demo route bao ve theo role/permission
Route::middleware(['auth:sanctum', 'role:ADMIN'])->get('/admin/ping', fn() => ['ok' => true]);