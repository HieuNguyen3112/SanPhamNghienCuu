<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\Auth\AuthMeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LecturerProfileController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ResearchActivityController;
use App\Http\Controllers\AdminResearchWorkController;
use App\Http\Controllers\AdminUniversityApprovalController;

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
        Route::get('/me', [AuthMeController::class, 'show'])->middleware('force.json');
    });
});

// PROFILE (Sanctum + auto rotate)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json'])->prefix('profile')->group(function () {
    Route::get('/', [LecturerProfileController::class, 'me']);
    Route::get('/me', [LecturerProfileController::class, 'me']);
    Route::get('/overview', [LecturerProfileController::class, 'me']);
    Route::get('/lecturers/{lecturer}', [LecturerProfileController::class, 'showLecturer']);
    Route::put('/contact', [LecturerProfileController::class, 'updateContact']);
    Route::put('/academic-titles', [LecturerProfileController::class, 'updateAcademicTitles']);
    Route::put('/research-areas', [LecturerProfileController::class, 'updateResearchAreas']);
    Route::put('/languages', [LecturerProfileController::class, 'syncLanguages']);
    Route::get('/educations', [LecturerProfileController::class, 'trainingHistories']);
    Route::post('/educations', [LecturerProfileController::class, 'storeTrainingHistory']);
    Route::put('/educations', [LecturerProfileController::class, 'syncTrainingHistories']);
    Route::put('/educations/{id}', [LecturerProfileController::class, 'updateTrainingHistory']);
    Route::delete('/educations/{id}', [LecturerProfileController::class, 'deleteTrainingHistory']);
    Route::get('/work-histories', [LecturerProfileController::class, 'workHistories']);
    Route::post('/work-histories', [LecturerProfileController::class, 'storeWorkHistory']);
    Route::put('/work-histories', [LecturerProfileController::class, 'syncWorkHistories']);
    Route::put('/work-histories/{id}', [LecturerProfileController::class, 'updateWorkHistory']);
    Route::delete('/work-histories/{id}', [LecturerProfileController::class, 'deleteWorkHistory']);
});

// RESEARCH ACTIVITIES (declarations)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json'])->prefix('research-activities')->group(function () {
    Route::post('/', [ResearchActivityController::class, 'store']);
    Route::get('/{activity}', [ResearchActivityController::class, 'show']);
    Route::put('/{activity}', [ResearchActivityController::class, 'update']);
    Route::put('/{activity}/members', [ResearchActivityController::class, 'syncMembers']);
    Route::post('/{activity}/submit', [ResearchActivityController::class, 'submit']);
    Route::put('/{activity}/{detail}', [ResearchActivityController::class, 'upsertDetail']);
    Route::get('/{activity}/evidence-files', [ResearchActivityController::class, 'listEvidenceFiles']);
});

// LOOKUPS (read-only)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json'])->prefix('lookups')->group(function () {
    Route::get('/degrees', [LookupController::class, 'degrees']);
    Route::get('/academic-ranks', [LookupController::class, 'academicRanks']);
    Route::get('/faculties', [LookupController::class, 'faculties']);
    Route::get('/departments', [LookupController::class, 'departments']);
    Route::get('/academic-years', [LookupController::class, 'academicYears']);
    Route::get('/activity-kinds', [LookupController::class, 'activityKinds']);
    Route::get('/activity-types', [LookupController::class, 'activityTypes']);
    Route::get('/member-roles', [LookupController::class, 'memberRoles']);
    Route::get('/evidence-file-types', [LookupController::class, 'evidenceFileTypes']);
    Route::get('/activity-statuses', [LookupController::class, 'activityStatuses']);
    Route::get('/lecturers', [LookupController::class, 'lecturers']);
});

// ADMIN only demo/ping
Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
    Route::get('/admin/ping', fn() => ['ok' => true]);
    // TODO: thêm route quản trị API
});

// ADMIN/QL research works management
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/works')
    ->group(function () {
        Route::get('/lecturers/summary/export/excel', [AdminResearchWorkController::class, 'exportSummaryExcel']);
        Route::get('/lecturers/summary/export/pdf', [AdminResearchWorkController::class, 'exportSummaryPdf']);
        Route::get('/lecturers/summary', [AdminResearchWorkController::class, 'lecturerSummary']);
        Route::get('/lecturers/{lecturer}/approved', [AdminResearchWorkController::class, 'approvedByLecturer']);
        Route::get('/activities/{activity}/approved', [AdminResearchWorkController::class, 'approvedDetail']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/uni-approvals')
    ->group(function () {
        Route::get('/', [AdminUniversityApprovalController::class, 'index']);
        Route::get('/{activity}', [AdminUniversityApprovalController::class, 'show']);
        Route::put('/{activity}/finalize', [AdminUniversityApprovalController::class, 'finalize']);
        Route::put('/{activity}/reject', [AdminUniversityApprovalController::class, 'reject']);
    });

// Placeholder nhóm cho role DL (ban chủ nhiệm/khoa)
Route::middleware(['auth:sanctum', 'role:DL'])->prefix('dl')->group(function () {
    // TODO: thêm route dành riêng DL
});

// Placeholder nhóm cho role QL/QLKH (SCIENCE_OFFICE)
Route::middleware(['auth:sanctum', 'role:QL'])->prefix('ql')->group(function () {
    // TODO: thêm route dành riêng QL/QLKH
});
