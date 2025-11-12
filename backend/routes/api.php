<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\ProfileController;

// TOKEN-BASED (Sanctum Bearer)
Route::post('/auth/token/login', [TokenAuthController::class, 'loginAndIssue'])
    ->middleware('throttle:login');

Route::prefix('auth')->group(function () {
    Route::middleware(['auth:sanctum', 'auto.rotate.sanctum'])->group(function () {
        Route::post('/token/issue',   [TokenAuthController::class, 'issue']);   // issue PAT cho user hien tai
        Route::post('/token/revoke',  [TokenAuthController::class, 'revoke']);  // revoke current hoac theo id
        Route::post('/token/rotate',  [TokenAuthController::class, 'rotate']);  // xoay token khi can
        Route::get('/token/ttl',      [TokenAuthController::class, 'ttl']);     // xem minutes_left
        Route::get('/me', [ProfileController::class, 'me']);
    });
});

// VA- demo route bao ve theo role/permission
Route::middleware(['auth:sanctum', 'role:ADMIN'])->get('/admin/ping', fn() => ['ok' => true]);
