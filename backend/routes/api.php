<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\Auth\AuthMeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LecturerProfileController;
use App\Http\Controllers\LecturerParticipationNotificationController;
use App\Http\Controllers\LecturerPersonalWorkController;
use App\Http\Controllers\LecturerHoursCalculateController;
use App\Http\Controllers\LecturerPersonalHoursController;
use App\Http\Controllers\LecturerHoursWarningController;
use App\Http\Controllers\LecturerResearchWorkSearchController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ResearchActivityController;
use App\Http\Controllers\AdminResearchWorkController;
use App\Http\Controllers\AdminResearchWorkSearchController;
use App\Http\Controllers\AdminUniversityApprovalController;
use App\Http\Controllers\AdminLecturerHoursController;
use App\Http\Controllers\AdminLecturerHourApprovalController;
use App\Http\Controllers\AdminLecturerHourWarningController;
use App\Http\Controllers\AdminLecturerAccountController;
use App\Http\Controllers\FacultyLecturerAccountController;
use App\Http\Controllers\AdminOrgStructureController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminLecturerReportController;
use App\Http\Controllers\AdminResearchReportController;
use App\Http\Controllers\AdminResearchHoursReportController;
use App\Http\Controllers\AdminWorkCatalogController;
use App\Http\Controllers\AdminResearchHoursCatalogController;
use App\Http\Controllers\FacultyResearchWorkApprovalController;
use App\Http\Controllers\FacultyResearchWorkManagementController;
use App\Http\Controllers\FacultyLecturerHourWarningController;
use App\Http\Controllers\FacultyLecturerHoursController;
use App\Http\Controllers\FacultyLecturerHourApprovalController;
use App\Http\Controllers\FacultyOrgStructureController;

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

// PARTICIPATION CONFIRMATION (Lecturer)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/participation-requests')
    ->group(function () {
        Route::get('/', [LecturerParticipationNotificationController::class, 'index']);
        Route::get('/{requestId}', [LecturerParticipationNotificationController::class, 'show']);
        Route::post('/{requestId}/confirm', [LecturerParticipationNotificationController::class, 'confirm']);
        Route::post('/{requestId}/reject', [LecturerParticipationNotificationController::class, 'reject']);
    });

// LECTURER MY WORKS
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/works/my')
    ->group(function () {
        Route::get('/', [LecturerPersonalWorkController::class, 'index']);
        Route::get('/{activity}', [LecturerPersonalWorkController::class, 'show']);
    });

// LECTURER HOURS CALCULATE
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/hours/calculate')
    ->group(function () {
        Route::get('/', [LecturerHoursCalculateController::class, 'index']);
        Route::get('/{activity}', [LecturerHoursCalculateController::class, 'show']);
        Route::post('/submit', [LecturerHoursCalculateController::class, 'submit']);
    });

// LECTURER HOURS PERSONAL
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/hours/personal')
    ->group(function () {
        Route::get('/overview', [LecturerPersonalHoursController::class, 'overview']);
        Route::get('/distribution', [LecturerPersonalHoursController::class, 'distribution']);
        Route::get('/batches', [LecturerPersonalHoursController::class, 'batches']);
        Route::get('/batches/{batchId}', [LecturerPersonalHoursController::class, 'batchDetail']);
    });

// LECTURER HOURS WARNINGS
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/hours/warnings')
    ->group(function () {
        Route::get('/', [LecturerHoursWarningController::class, 'index']);
        Route::patch('/{warningId}/seen', [LecturerHoursWarningController::class, 'markSeen']);
        Route::patch('/{warningId}/resolved', [LecturerHoursWarningController::class, 'markResolved']);
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

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DL|QL|ADMIN'])
    ->prefix('admin/works')
    ->group(function () {
        Route::get('/lookups', [AdminResearchWorkSearchController::class, 'lookups']);
        Route::get('/search', [AdminResearchWorkSearchController::class, 'index']);
        Route::get('/attachments/{attachment}/download', [AdminResearchWorkSearchController::class, 'downloadAttachment'])
            ->name('admin.works.attachments.download');
        Route::get('/{activity}', [AdminResearchWorkSearchController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DL'])
    ->prefix('faculty/works')
    ->group(function () {
        Route::get('/approvals/lookups', [FacultyResearchWorkApprovalController::class, 'lookups']);
        Route::get('/approvals', [FacultyResearchWorkApprovalController::class, 'index']);
        Route::get('/approvals/{activity}', [FacultyResearchWorkApprovalController::class, 'show']);
        Route::put('/approvals/{activity}/approve', [FacultyResearchWorkApprovalController::class, 'approve']);
        Route::put('/approvals/{activity}/reject', [FacultyResearchWorkApprovalController::class, 'reject']);
        Route::get('/lookups', [FacultyResearchWorkManagementController::class, 'lookups']);
        Route::get('/lecturers/summary', [FacultyResearchWorkManagementController::class, 'lecturerSummary']);
        Route::get('/lecturers/{lecturer}/works', [FacultyResearchWorkManagementController::class, 'lecturerWorks']);
        Route::get('/activities/{activity}/approved', [FacultyResearchWorkManagementController::class, 'approvedDetail']);
        Route::get('/export/excel', [FacultyResearchWorkManagementController::class, 'exportSummaryExcel']);
        Route::get('/export/pdf', [FacultyResearchWorkManagementController::class, 'exportSummaryPdf']);
        Route::get('/evidence/{evidence}/download', [FacultyResearchWorkManagementController::class, 'downloadEvidence'])
            ->name('faculty.works.evidence.download');
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DL'])
    ->prefix('faculty/hours')
    ->group(function () {
        Route::get('/warnings', [FacultyLecturerHourWarningController::class, 'index']);
        Route::post('/warnings/{lecturerId}/send', [FacultyLecturerHourWarningController::class, 'sendWarning']);
        Route::get('/approvals/lookups', [FacultyLecturerHourApprovalController::class, 'lookups']);
        Route::get('/approvals', [FacultyLecturerHourApprovalController::class, 'index']);
        Route::get('/approvals/{requestId}', [FacultyLecturerHourApprovalController::class, 'show']);
        Route::put('/approvals/{requestId}/approve', [FacultyLecturerHourApprovalController::class, 'approve']);
        Route::put('/approvals/{requestId}/reject', [FacultyLecturerHourApprovalController::class, 'reject']);
        Route::get('/lecturers/summary/export/excel', [FacultyLecturerHoursController::class, 'exportSummaryExcel']);
        Route::get('/lecturers/summary/export/pdf', [FacultyLecturerHoursController::class, 'exportSummaryPdf']);
        Route::get('/lecturers/summary', [FacultyLecturerHoursController::class, 'index']);
        Route::get('/lecturers/{lecturer}', [FacultyLecturerHoursController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DL'])
    ->prefix('faculty/org-structure')
    ->group(function () {
        Route::get('/lookups', [FacultyOrgStructureController::class, 'lookups']);
        Route::get('/faculties', [FacultyOrgStructureController::class, 'listFaculties']);
        Route::get('/departments', [FacultyOrgStructureController::class, 'listDepartments']);
        Route::post('/departments', [FacultyOrgStructureController::class, 'storeDepartment']);
        Route::put('/departments/{departmentId}', [FacultyOrgStructureController::class, 'updateDepartment']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DL'])
    ->prefix('faculty/users/lecturer-accounts')
    ->group(function () {
        Route::get('/', [FacultyLecturerAccountController::class, 'index']);
        Route::get('/lookups', [FacultyLecturerAccountController::class, 'lookups']);
        Route::put('/{lecturer}', [FacultyLecturerAccountController::class, 'update']);
        Route::put('/{lecturer}/roles', [FacultyLecturerAccountController::class, 'updateRoles']);
        Route::put('/{lecturer}/status', [FacultyLecturerAccountController::class, 'updateStatus']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:GV'])
    ->prefix('lecturer/works')
    ->group(function () {
        Route::get('/lookups', [LecturerResearchWorkSearchController::class, 'lookups']);
        Route::get('/search', [LecturerResearchWorkSearchController::class, 'index']);
        Route::get('/attachments/{attachment}/download', [LecturerResearchWorkSearchController::class, 'downloadAttachment'])
            ->name('lecturer.works.attachments.download');
        Route::get('/{activity}', [LecturerResearchWorkSearchController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/hours')
    ->group(function () {
        Route::get('/lecturers/summary/export/excel', [AdminLecturerHoursController::class, 'exportSummaryExcel']);
        Route::get('/lecturers/summary/export/pdf', [AdminLecturerHoursController::class, 'exportSummaryPdf']);
        Route::get('/lecturers/summary', [AdminLecturerHoursController::class, 'index']);
        Route::get('/lecturers/{lecturer}', [AdminLecturerHoursController::class, 'show']);
        Route::get('/approvals', [AdminLecturerHourApprovalController::class, 'index']);
        Route::get('/approvals/{requestId}', [AdminLecturerHourApprovalController::class, 'show']);
        Route::put('/approvals/{requestId}/approve', [AdminLecturerHourApprovalController::class, 'approve']);
        Route::put('/approvals/{requestId}/reject', [AdminLecturerHourApprovalController::class, 'reject']);
        Route::get('/warnings', [AdminLecturerHourWarningController::class, 'index']);
        Route::post('/warnings/{lecturerId}/send', [AdminLecturerHourWarningController::class, 'sendWarning']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/uni-approvals')
    ->group(function () {
        Route::get('/', [AdminUniversityApprovalController::class, 'index']);
        Route::get('/{activity}', [AdminUniversityApprovalController::class, 'show']);
        Route::put('/{activity}/finalize', [AdminUniversityApprovalController::class, 'finalize']);
        Route::put('/{activity}/reject', [AdminUniversityApprovalController::class, 'reject']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/lecturer-accounts')
    ->group(function () {
        Route::get('/', [AdminLecturerAccountController::class, 'index']);
        Route::get('/lookups', [AdminLecturerAccountController::class, 'lookups']);
        Route::put('/{lecturer}', [AdminLecturerAccountController::class, 'update']);
        Route::put('/{lecturer}/roles', [AdminLecturerAccountController::class, 'updateRoles']);
        Route::put('/{lecturer}/status', [AdminLecturerAccountController::class, 'updateStatus']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/org-structure')
    ->group(function () {
        Route::get('/faculties', [AdminOrgStructureController::class, 'listFaculties']);
        Route::post('/faculties', [AdminOrgStructureController::class, 'storeFaculty']);
        Route::put('/faculties/{facultyId}', [AdminOrgStructureController::class, 'updateFaculty']);

        Route::get('/departments', [AdminOrgStructureController::class, 'listDepartments']);
        Route::post('/departments', [AdminOrgStructureController::class, 'storeDepartment']);
        Route::put('/departments/{departmentId}', [AdminOrgStructureController::class, 'updateDepartment']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/work-catalog')
    ->group(function () {
        Route::get('/work-types', [AdminWorkCatalogController::class, 'listWorkTypes']);
        Route::post('/work-types', [AdminWorkCatalogController::class, 'storeWorkType']);
        Route::put('/work-types/{id}', [AdminWorkCatalogController::class, 'updateWorkType']);
        Route::patch('/work-types/{id}/status', [AdminWorkCatalogController::class, 'updateWorkTypeStatus']);

        Route::get('/work-levels', [AdminWorkCatalogController::class, 'listWorkLevels']);
        Route::post('/work-levels', [AdminWorkCatalogController::class, 'storeWorkLevel']);
        Route::put('/work-levels/{id}', [AdminWorkCatalogController::class, 'updateWorkLevel']);
        Route::patch('/work-levels/{id}/status', [AdminWorkCatalogController::class, 'updateWorkLevelStatus']);

        Route::get('/journals', [AdminWorkCatalogController::class, 'listJournals']);
        Route::post('/journals', [AdminWorkCatalogController::class, 'storeJournal']);
        Route::put('/journals/{id}', [AdminWorkCatalogController::class, 'updateJournal']);
        Route::patch('/journals/{id}/status', [AdminWorkCatalogController::class, 'updateJournalStatus']);
        Route::post('/journals/{journalId}/rankings', [AdminWorkCatalogController::class, 'storeJournalRanking']);

        Route::get('/conferences', [AdminWorkCatalogController::class, 'listConferences']);
        Route::post('/conferences', [AdminWorkCatalogController::class, 'storeConference']);
        Route::put('/conferences/{id}', [AdminWorkCatalogController::class, 'updateConference']);
        Route::patch('/conferences/{id}/status', [AdminWorkCatalogController::class, 'updateConferenceStatus']);

        Route::get('/research-fields', [AdminWorkCatalogController::class, 'listResearchFields']);
        Route::post('/research-fields', [AdminWorkCatalogController::class, 'storeResearchField']);
        Route::put('/research-fields/{id}', [AdminWorkCatalogController::class, 'updateResearchField']);
        Route::patch('/research-fields/{id}/status', [AdminWorkCatalogController::class, 'updateResearchFieldStatus']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN|QL'])
    ->prefix('admin/research-hours')
    ->group(function () {
        Route::get('/meta', [AdminResearchHoursCatalogController::class, 'meta']);

        Route::get('/hour-rules', [AdminResearchHoursCatalogController::class, 'listHourRules']);
        Route::post('/hour-rules', [AdminResearchHoursCatalogController::class, 'storeHourRule']);
        Route::put('/hour-rules/{id}', [AdminResearchHoursCatalogController::class, 'updateHourRule']);
        Route::patch('/hour-rules/{id}/status', [AdminResearchHoursCatalogController::class, 'updateHourRuleStatus']);

        Route::get('/workload-quotas', [AdminResearchHoursCatalogController::class, 'listWorkloadQuotas']);
        Route::post('/workload-quotas', [AdminResearchHoursCatalogController::class, 'storeWorkloadQuota']);
        Route::put('/workload-quotas/{id}', [AdminResearchHoursCatalogController::class, 'updateWorkloadQuota']);

        Route::get('/academic-years', [AdminResearchHoursCatalogController::class, 'listAcademicYears']);
        Route::post('/academic-years', [AdminResearchHoursCatalogController::class, 'storeAcademicYear']);
        Route::put('/academic-years/{id}', [AdminResearchHoursCatalogController::class, 'updateAcademicYear']);
        Route::patch('/academic-years/{id}/apply', [AdminResearchHoursCatalogController::class, 'applyAcademicYear']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN'])
    ->prefix('admin/audit-logs')
    ->group(function () {
        Route::get('/meta', [AdminAuditLogController::class, 'meta']);
        Route::get('/', [AdminAuditLogController::class, 'index']);
        Route::get('/{id}', [AdminAuditLogController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN'])
    ->prefix('admin/reports/lecturers')
    ->group(function () {
        Route::get('/filters', [AdminLecturerReportController::class, 'filters']);
        Route::get('/', [AdminLecturerReportController::class, 'index']);
        Route::get('/export/excel', [AdminLecturerReportController::class, 'exportExcel']);
        Route::get('/export/pdf', [AdminLecturerReportController::class, 'exportPdf']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN'])
    ->prefix('admin/reports/research')
    ->group(function () {
        Route::get('/filters', [AdminResearchReportController::class, 'filters']);
        Route::get('/', [AdminResearchReportController::class, 'index']);
        Route::get('/export/excel', [AdminResearchReportController::class, 'exportExcel']);
        Route::get('/export/pdf', [AdminResearchReportController::class, 'exportPdf']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:ADMIN'])
    ->prefix('admin/reports/hour-research')
    ->group(function () {
        Route::get('/filters', [AdminResearchHoursReportController::class, 'filters']);
        Route::get('/', [AdminResearchHoursReportController::class, 'index']);
        Route::get('/export/excel', [AdminResearchHoursReportController::class, 'exportExcel']);
        Route::get('/export/pdf', [AdminResearchHoursReportController::class, 'exportPdf']);
    });

// Placeholder nhóm cho role DL (ban chủ nhiệm/khoa)
Route::middleware(['auth:sanctum', 'role:DL'])->prefix('dl')->group(function () {
    // TODO: thêm route dành riêng DL
});

// Placeholder nhóm cho role QL/QLKH (SCIENCE_OFFICE)
Route::middleware(['auth:sanctum', 'role:QL'])->prefix('ql')->group(function () {
    // TODO: thêm route dành riêng QL/QLKH
});
