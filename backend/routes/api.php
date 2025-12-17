<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LecturerProfileController;

// TOKEN-BASED (Sanctum Bearer)
Route::prefix('auth')->group(function () {
    Route::middleware(['auth:sanctum', 'auto.rotate.sanctum'])->group(function () {
        Route::get('/tokens',        [TokenAuthController::class, 'index']);   // list PATs
        // Chỉ ADMIN/QL được phát hành/luân chuyển PAT để tích hợp
        Route::post('/token/issue',  [TokenAuthController::class, 'issue'])->middleware('role:ADMIN|QL');   // issue PAT cho user hien tai
        Route::post('/token/revoke', [TokenAuthController::class, 'revoke']);  // revoke current hoac theo id
        Route::post('/token/revoke-all', [TokenAuthController::class, 'revokeAll']); // revoke all PATs
        Route::post('/token/rotate', [TokenAuthController::class, 'rotate'])->middleware('role:ADMIN|QL');  // xoay token khi can
        Route::get('/token/ttl',     [TokenAuthController::class, 'ttl']);     // xem minutes_left
        Route::get('/me', [ProfileController::class, 'me']);
    });
});

// PROFILE (Sanctum + auto rotate)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum'])->prefix('profile')->group(function () {
    Route::get('/me', [LecturerProfileController::class, 'me']);
    Route::put('/contact', [LecturerProfileController::class, 'updateContact']);
});

// ADMIN only demo/ping
Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
    Route::get('/admin/ping', fn() => ['ok' => true]);
    // TODO: thêm route quản trị API
});

// Placeholder nhóm cho role DL (ban chủ nhiệm/khoa)
Route::middleware(['auth:sanctum', 'role:DL'])->prefix('dl')->group(function () {
    // TODO: thêm route dành riêng DL
});

// Placeholder nhóm cho role QL/QLKH (SCIENCE_OFFICE)
Route::middleware(['auth:sanctum', 'role:QL'])->prefix('ql')->group(function () {
    // TODO: thêm route dành riêng QL/QLKH
});
