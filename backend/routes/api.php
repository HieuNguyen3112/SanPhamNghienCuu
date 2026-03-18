<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenAuthController;
use App\Http\Controllers\PublicLecturerController;
use App\Http\Controllers\Auth\AuthMeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LecturerProfileController;
use App\Http\Controllers\LecturerParticipationNotificationController;
use App\Http\Controllers\LecturerDeclarationDraftController;
use App\Http\Controllers\LecturerPersonalWorkController;
use App\Http\Controllers\LecturerHoursCalculateController;
use App\Http\Controllers\LecturerPersonalHoursController;
use App\Http\Controllers\LecturerHoursWarningController;
use App\Http\Controllers\LecturerNotificationController;
use App\Http\Controllers\FacultyNotificationController;
use App\Http\Controllers\LecturerResearchWorkSearchController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ResearchActivityController;
use App\Http\Controllers\AdminResearchWorkController;
use App\Http\Controllers\AdminResearchWorkSearchController;
use App\Http\Controllers\AdminLecturerHoursController;
use App\Http\Controllers\AdminLecturerHourWarningController;
use App\Http\Controllers\AdminLecturerAccountController;
use App\Http\Controllers\FacultyLecturerAccountController;
use App\Http\Controllers\AdminOrgStructureController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\FacultyAuditLogController;
use App\Http\Controllers\AdminLecturerReportController;
use App\Http\Controllers\FacultyLecturerReportController;
use App\Http\Controllers\AdminResearchReportController;
use App\Http\Controllers\FacultyResearchReportController;
use App\Http\Controllers\FacultyResearchHoursReportController;
use App\Http\Controllers\AdminResearchHoursReportController;
use App\Http\Controllers\AdminWorkCatalogController;
use App\Http\Controllers\AdminResearchHoursCatalogController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\FacultyResearchWorkApprovalController;
use App\Http\Controllers\FacultyResearchWorkManagementController;
use App\Http\Controllers\FacultyLecturerHourWarningController;
use App\Http\Controllers\FacultyLecturerHoursController;
use App\Http\Controllers\FacultyLecturerHourApprovalController;
use App\Http\Controllers\FacultyOrgStructureController;
use App\Http\Controllers\PublicResearchWorkController;

// TOKEN-BASED (Sanctum Bearer)
Route::prefix('auth')->group(function () {

    // ME: uses Sanctum session auth, no token rotation.
    Route::middleware(['auth:sanctum', 'force.json'])->group(function () {
        Route::get('/me', [AuthMeController::class, 'show']);
    });

    // Token endpoints: SCIENCE_OFFICE only, with rotation.
    Route::middleware(['auth:sanctum', 'auto.rotate.sanctum'])->group(function () {
        Route::get('/tokens', [TokenAuthController::class, 'index']);

        Route::post('/token/issue', [TokenAuthController::class, 'issue'])
            ->middleware('role:SCIENCE_OFFICE');

        Route::post('/token/revoke', [TokenAuthController::class, 'revoke']);
        Route::post('/token/revoke-all', [TokenAuthController::class, 'revokeAll']);

        Route::post('/token/rotate', [TokenAuthController::class, 'rotate'])
            ->middleware('role:SCIENCE_OFFICE');

        Route::get('/token/ttl', [TokenAuthController::class, 'ttl']);
    });
});


// PROFILE (Sanctum + auto rotate)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json'])
    ->prefix('profile')
    ->group(function () {
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

// RESEARCH ACTIVITIES (declarations) - Lecturer only
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('research-activities')
    ->group(function () {
        Route::post('/', [ResearchActivityController::class, 'store']);
        Route::get('/{activity}', [ResearchActivityController::class, 'show']);
        Route::put('/{activity}', [ResearchActivityController::class, 'update']);
        Route::put('/{activity}/members', [ResearchActivityController::class, 'syncMembers']);
        Route::post('/{activity}/members/{member}/reinvite', [ResearchActivityController::class, 'reinviteMember']);
        Route::post('/{activity}/submit', [ResearchActivityController::class, 'submit']);
        Route::put('/{activity}/{detail}', [ResearchActivityController::class, 'upsertDetail']);
        Route::get('/{activity}/evidence-files', [ResearchActivityController::class, 'listEvidenceFiles']);
        Route::post('/{activity}/evidence-files', [ResearchActivityController::class, 'uploadEvidenceFile']);
        Route::delete('/{activity}/evidence-files/{evidence}', [ResearchActivityController::class, 'deleteEvidenceFile']);
        Route::get('/{activity}/evidence-files/{evidence}/preview', [ResearchActivityController::class, 'previewEvidenceFile'])
            ->name('research.activities.evidence.preview');
        Route::get('/{activity}/evidence-files/{evidence}/download', [ResearchActivityController::class, 'downloadEvidenceFile'])
            ->name('research.activities.evidence.download');
        Route::get('/{activity}/evidence-links', [ResearchActivityController::class, 'listEvidenceLinks']);
        Route::post('/{activity}/evidence-links', [ResearchActivityController::class, 'storeEvidenceLink']);
        Route::delete('/{activity}/evidence-links/{link}', [ResearchActivityController::class, 'deleteEvidenceLink']);
    });

// PARTICIPATION CONFIRMATION (Lecturer)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/participation-requests')
    ->group(function () {
        Route::get('/', [LecturerParticipationNotificationController::class, 'index']);
        Route::get('/{requestId}', [LecturerParticipationNotificationController::class, 'show']);
        Route::post('/{requestId}/confirm', [LecturerParticipationNotificationController::class, 'confirm']);
        Route::post('/{requestId}/reject', [LecturerParticipationNotificationController::class, 'reject']);
    });

// LECTURER MY WORKS
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/works/my')
    ->group(function () {
        Route::get('/', [LecturerPersonalWorkController::class, 'index']);
        Route::get('/{activity}', [LecturerPersonalWorkController::class, 'show']);
    });

// LECTURER HOURS CALCULATE
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/hours/calculate')
    ->group(function () {
        Route::get('/', [LecturerHoursCalculateController::class, 'index']);
        Route::get('/{activity}', [LecturerHoursCalculateController::class, 'show']);
        Route::post('/submit', [LecturerHoursCalculateController::class, 'submit']);
        Route::get('/{activity}/evidence', [LecturerHoursCalculateController::class, 'listEvidence']);
        Route::post('/{activity}/evidence', [LecturerHoursCalculateController::class, 'uploadEvidence']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/hours')
    ->group(function () {
        Route::put('/{activity}/proposed-hours', [LecturerHoursCalculateController::class, 'updateProposedHours']);
        Route::post('/submit', [LecturerHoursCalculateController::class, 'submit']);
        Route::delete('/evidence/{evidence}', [LecturerHoursCalculateController::class, 'deleteEvidence']);
        Route::get('/evidence/{evidence}/download', [LecturerHoursCalculateController::class, 'downloadEvidence'])
            ->name('lecturer.hours.evidence.download');
    });

// LECTURER HOURS PERSONAL
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/hours/personal')
    ->group(function () {
        Route::get('/overview', [LecturerPersonalHoursController::class, 'overview']);
        Route::get('/distribution', [LecturerPersonalHoursController::class, 'distribution']);
        Route::get('/batches', [LecturerPersonalHoursController::class, 'batches']);
        Route::get('/batches/{batchId}', [LecturerPersonalHoursController::class, 'batchDetail']);
    });

// LECTURER HOURS WARNINGS
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/hours/warnings')
    ->group(function () {
        Route::get('/', [LecturerHoursWarningController::class, 'index']);
        Route::patch('/{warningId}/seen', [LecturerHoursWarningController::class, 'markSeen']);
        Route::patch('/{warningId}/resolved', [LecturerHoursWarningController::class, 'markResolved']);
    });

// LECTURER NOTIFICATIONS (bell dropdown)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/notifications')
    ->group(function () {
        Route::get('/', [LecturerNotificationController::class, 'index']);
        Route::delete('/delete-read', [LecturerNotificationController::class, 'destroyRead']);
        Route::delete('/{notificationId}', [LecturerNotificationController::class, 'destroy']);
        Route::patch('/read-all', [LecturerNotificationController::class, 'markAllRead']);
        Route::patch('/{notificationId}/read', [LecturerNotificationController::class, 'markRead']);
    });

// FACULTY NOTIFICATIONS (bell dropdown)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/notifications')
    ->group(function () {
        Route::get('/', [FacultyNotificationController::class, 'index']);
        Route::delete('/delete-read', [FacultyNotificationController::class, 'destroyRead']);
        Route::delete('/{notificationId}', [FacultyNotificationController::class, 'destroy']);
        Route::patch('/read-all', [FacultyNotificationController::class, 'markAllRead']);
        Route::patch('/{notificationId}/read', [FacultyNotificationController::class, 'markRead']);
    });

// LOOKUPS (read-only)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json'])
    ->prefix('lookups')
    ->group(function () {
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
        Route::get('/journals', [LookupController::class, 'journals']);
        Route::get('/conferences', [LookupController::class, 'conferences']);
        Route::get('/publishers', [LookupController::class, 'publishers']);
    });

// SCIENCE_OFFICE only demo/ping
Route::middleware(['auth:sanctum', 'role:SCIENCE_OFFICE'])->group(function () {
    Route::get('/admin/ping', fn() => ['ok' => true]);
    // TODO: add API admin management routes.
});

// SCIENCE_OFFICE research works management
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/works')
    ->group(function () {
        Route::get('/lecturers/summary/export/excel', [AdminResearchWorkController::class, 'exportSummaryExcel'])->middleware('audit.export');
        Route::get('/lecturers/summary/export/pdf', [AdminResearchWorkController::class, 'exportSummaryPdf'])->middleware('audit.export');
        Route::get('/lecturers/summary', [AdminResearchWorkController::class, 'lecturerSummary']);
        Route::get('/lecturers/{lecturer}/approved', [AdminResearchWorkController::class, 'approvedByLecturer']);
        Route::get('/activities/{activity}/approved', [AdminResearchWorkController::class, 'approvedDetail']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE|DEPARTMENT_BOARD'])
    ->prefix('admin/works')
    ->group(function () {
        Route::get('/lookups', [AdminResearchWorkSearchController::class, 'lookups']);
        Route::get('/search', [AdminResearchWorkSearchController::class, 'index']);
        Route::get('/attachments/{attachment}/preview', [AdminResearchWorkSearchController::class, 'previewAttachment'])
            ->name('admin.works.attachments.preview');
        Route::get('/attachments/{attachment}/download', [AdminResearchWorkSearchController::class, 'downloadAttachment'])
            ->name('admin.works.attachments.download');
        Route::get('/{activity}', [AdminResearchWorkSearchController::class, 'show']);
    });

// FACULTY (Department Board) works approvals & management
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
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
        Route::get('/export/excel', [FacultyResearchWorkManagementController::class, 'exportSummaryExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [FacultyResearchWorkManagementController::class, 'exportSummaryPdf'])->middleware('audit.export');
        Route::get('/evidence/{evidence}/preview', [FacultyResearchWorkManagementController::class, 'previewEvidence'])
            ->name('faculty.works.evidence.preview');
        Route::get('/evidence/{evidence}/download', [FacultyResearchWorkManagementController::class, 'downloadEvidence'])
            ->name('faculty.works.evidence.download');
    });

// LECTURER DECLARATION DRAFTS (Gateway)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/declarations')
    ->group(function () {
        Route::get('/drafts', [LecturerDeclarationDraftController::class, 'index']);
        Route::post('/projects/preview-hours', [ResearchActivityController::class, 'previewProjectHours']);
    });

// FACULTY HOURS (Department Board)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/hours')
    ->group(function () {
        Route::get('/warnings', [FacultyLecturerHourWarningController::class, 'index']);
        Route::post('/warnings/{lecturerId}/send', [FacultyLecturerHourWarningController::class, 'sendWarning']);
        Route::get('/approvals/lookups', [FacultyLecturerHourApprovalController::class, 'lookups']);
        Route::get('/approvals', [FacultyLecturerHourApprovalController::class, 'index']);
        Route::get('/approvals/{requestId}', [FacultyLecturerHourApprovalController::class, 'show']);
        Route::put('/approvals/{requestId}/approve', [FacultyLecturerHourApprovalController::class, 'approve']);
        Route::put('/approvals/{requestId}/reject', [FacultyLecturerHourApprovalController::class, 'reject']);
        Route::get('/evidence/{evidence}/download', [FacultyLecturerHourApprovalController::class, 'downloadEvidence'])
            ->name('faculty.hours.evidence.download');
        Route::get('/lecturers/summary/export/excel', [FacultyLecturerHoursController::class, 'exportSummaryExcel'])->middleware('audit.export');
        Route::get('/lecturers/summary/export/pdf', [FacultyLecturerHoursController::class, 'exportSummaryPdf'])->middleware('audit.export');
        Route::get('/lecturers/summary', [FacultyLecturerHoursController::class, 'index']);
        Route::get('/lecturers/{lecturer}', [FacultyLecturerHoursController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/reports/lecturers')
    ->group(function () {
        Route::get('/lookups', [FacultyLecturerReportController::class, 'lookups']);
        Route::get('/', [FacultyLecturerReportController::class, 'index']);
        Route::get('/export/excel', [FacultyLecturerReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [FacultyLecturerReportController::class, 'exportPdf'])->middleware('audit.export');
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/reports/research')
    ->group(function () {
        Route::get('/filters', [FacultyResearchReportController::class, 'filters']);
        Route::get('/', [FacultyResearchReportController::class, 'index']);
        Route::get('/export/excel', [FacultyResearchReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [FacultyResearchReportController::class, 'exportPdf'])->middleware('audit.export');
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/org-structure')
    ->group(function () {
        Route::get('/lookups', [FacultyOrgStructureController::class, 'lookups']);
        Route::get('/faculties', [FacultyOrgStructureController::class, 'listFaculties']);
        Route::get('/departments', [FacultyOrgStructureController::class, 'listDepartments']);
        Route::post('/departments', [FacultyOrgStructureController::class, 'storeDepartment']);
        Route::put('/departments/{departmentId}', [FacultyOrgStructureController::class, 'updateDepartment']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/users/lecturer-accounts')
    ->group(function () {
        Route::get('/', [FacultyLecturerAccountController::class, 'index']);
        Route::post('/', [FacultyLecturerAccountController::class, 'store']);
        Route::get('/lookups', [FacultyLecturerAccountController::class, 'lookups']);
        Route::put('/{lecturer}', [FacultyLecturerAccountController::class, 'update']);
        Route::put('/{lecturer}/status', [FacultyLecturerAccountController::class, 'updateStatus']);
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/audit-logs')
    ->group(function () {
        Route::get('/meta', [FacultyAuditLogController::class, 'meta']);
        Route::get('/', [FacultyAuditLogController::class, 'index']);
        Route::get('/{id}', [FacultyAuditLogController::class, 'show']);
    });

// Lecturer search works
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:LECTURER'])
    ->prefix('lecturer/works')
    ->group(function () {
        Route::get('/lookups', [LecturerResearchWorkSearchController::class, 'lookups']);
        Route::get('/search', [LecturerResearchWorkSearchController::class, 'index']);
        Route::get('/attachments/{attachment}/download', [LecturerResearchWorkSearchController::class, 'downloadAttachment'])
            ->name('lecturer.works.attachments.download');
        Route::get('/{activity}', [LecturerResearchWorkSearchController::class, 'show']);
    });

// SCIENCE_OFFICE hours
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/hours')
    ->group(function () {
        Route::get('/lecturers/summary/export/excel', [AdminLecturerHoursController::class, 'exportSummaryExcel'])->middleware('audit.export');
        Route::get('/lecturers/summary/export/pdf', [AdminLecturerHoursController::class, 'exportSummaryPdf'])->middleware('audit.export');
        Route::get('/lecturers/summary', [AdminLecturerHoursController::class, 'index']);
        Route::get('/lecturers/{lecturer}', [AdminLecturerHoursController::class, 'show']);
        Route::get('/warnings', [AdminLecturerHourWarningController::class, 'index']);
        Route::post('/warnings/{lecturerId}/send', [AdminLecturerHourWarningController::class, 'sendWarning']);
    });

// REMOVED: admin/uni-approvals (University approval removed by requirement)

// SCIENCE_OFFICE lecturer accounts
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/lecturer-accounts')
    ->group(function () {
        Route::get('/', [AdminLecturerAccountController::class, 'index']);
        Route::post('/', [AdminLecturerAccountController::class, 'store']);
        Route::get('/lookups', [AdminLecturerAccountController::class, 'lookups']);
        Route::put('/{lecturer}', [AdminLecturerAccountController::class, 'update']);
        Route::put('/{lecturer}/roles', [AdminLecturerAccountController::class, 'updateRoles']);
        Route::put('/{lecturer}/status', [AdminLecturerAccountController::class, 'updateStatus']);
    });

// SCIENCE_OFFICE org structure
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/org-structure')
    ->group(function () {
        Route::get('/faculties', [AdminOrgStructureController::class, 'listFaculties']);
        Route::post('/faculties', [AdminOrgStructureController::class, 'storeFaculty']);
        Route::put('/faculties/{facultyId}', [AdminOrgStructureController::class, 'updateFaculty']);

        Route::get('/departments', [AdminOrgStructureController::class, 'listDepartments']);
        Route::post('/departments', [AdminOrgStructureController::class, 'storeDepartment']);
        Route::put('/departments/{departmentId}', [AdminOrgStructureController::class, 'updateDepartment']);
    });

// SCIENCE_OFFICE work catalog
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
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
        Route::get('/journals/suggestions', [AdminWorkCatalogController::class, 'listJournalSuggestions']);
        Route::post('/journals/suggestions/{id}/approve', [AdminWorkCatalogController::class, 'approveJournalSuggestion']);
        Route::post('/journals/suggestions/{id}/reject', [AdminWorkCatalogController::class, 'rejectJournalSuggestion']);

        Route::get('/publishers', [AdminWorkCatalogController::class, 'listPublishers']);
        Route::post('/publishers', [AdminWorkCatalogController::class, 'storePublisher']);
        Route::put('/publishers/{id}', [AdminWorkCatalogController::class, 'updatePublisher']);
        Route::patch('/publishers/{id}/status', [AdminWorkCatalogController::class, 'updatePublisherStatus']);
        Route::get('/publishers/suggestions', [AdminWorkCatalogController::class, 'listPublisherSuggestions']);
        Route::post('/publishers/suggestions/{id}/approve', [AdminWorkCatalogController::class, 'approvePublisherSuggestion']);
        Route::post('/publishers/suggestions/{id}/reject', [AdminWorkCatalogController::class, 'rejectPublisherSuggestion']);

        Route::get('/conferences', [AdminWorkCatalogController::class, 'listConferences']);
        Route::post('/conferences', [AdminWorkCatalogController::class, 'storeConference']);
        Route::put('/conferences/{id}', [AdminWorkCatalogController::class, 'updateConference']);
        Route::patch('/conferences/{id}/status', [AdminWorkCatalogController::class, 'updateConferenceStatus']);
        Route::get('/conferences/suggestions', [AdminWorkCatalogController::class, 'listConferenceSuggestions']);
        Route::post('/conferences/suggestions/{id}/approve', [AdminWorkCatalogController::class, 'approveConferenceSuggestion']);
        Route::post('/conferences/suggestions/{id}/reject', [AdminWorkCatalogController::class, 'rejectConferenceSuggestion']);

        Route::get('/research-fields', [AdminWorkCatalogController::class, 'listResearchFields']);
        Route::post('/research-fields', [AdminWorkCatalogController::class, 'storeResearchField']);
        Route::put('/research-fields/{id}', [AdminWorkCatalogController::class, 'updateResearchField']);
        Route::patch('/research-fields/{id}/status', [AdminWorkCatalogController::class, 'updateResearchFieldStatus']);
    });

// SCIENCE_OFFICE research-hours catalog
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
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

// SCIENCE_OFFICE backups
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/backups')
    ->group(function () {
        Route::get('/', [AdminBackupController::class, 'index']);
        Route::get('/exports-info', [AdminBackupController::class, 'exportsInfo']);
        Route::get('/doctor', [AdminBackupController::class, 'doctor']);
        Route::post('/refresh', [AdminBackupController::class, 'refresh']);
        Route::post('/run', [AdminBackupController::class, 'run']);
        Route::post('/prune', [AdminBackupController::class, 'prune']);
        Route::post('/forget', [AdminBackupController::class, 'forget']);
        Route::post('/unlock-stale', [AdminBackupController::class, 'unlockStaleLock']);
        Route::get('/runs/{runId}', [AdminBackupController::class, 'runStatus']);
        Route::get('/{snapshotId}/manifest', [AdminBackupController::class, 'downloadManifest']);
        Route::get('/{snapshotId}/database-dump', [AdminBackupController::class, 'downloadDatabaseDump']);
        Route::get('/{snapshotId}/export/metadata', [AdminBackupController::class, 'exportMetadata']);
        Route::get('/{snapshotId}/export/download', [AdminBackupController::class, 'downloadExport']);
        Route::get('/{snapshotId}/export', [AdminBackupController::class, 'downloadExport']);
        Route::post('/{snapshotId}/restore', [AdminBackupController::class, 'restore']);
        Route::get('/{snapshotId}', [AdminBackupController::class, 'show']);
    });

// SCIENCE_OFFICE audit logs
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/audit-logs')
    ->group(function () {
        Route::get('/meta', [AdminAuditLogController::class, 'meta']);
        Route::get('/', [AdminAuditLogController::class, 'index']);
        Route::get('/{id}', [AdminAuditLogController::class, 'show']);
    });

// SCIENCE_OFFICE reports
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/reports/lecturers')
    ->group(function () {
        Route::get('/filters', [AdminLecturerReportController::class, 'filters']);
        Route::get('/', [AdminLecturerReportController::class, 'index']);
        Route::get('/export/excel', [AdminLecturerReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [AdminLecturerReportController::class, 'exportPdf'])->middleware('audit.export');
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/reports/research')
    ->group(function () {
        Route::get('/filters', [AdminResearchReportController::class, 'filters']);
        Route::get('/', [AdminResearchReportController::class, 'index']);
        Route::get('/export/excel', [AdminResearchReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [AdminResearchReportController::class, 'exportPdf'])->middleware('audit.export');
    });

Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:SCIENCE_OFFICE'])
    ->prefix('admin/reports/hour-research')
    ->group(function () {
        Route::get('/filters', [AdminResearchHoursReportController::class, 'filters']);
        Route::get('/', [AdminResearchHoursReportController::class, 'index']);
        Route::get('/export/excel', [AdminResearchHoursReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [AdminResearchHoursReportController::class, 'exportPdf'])->middleware('audit.export');
    });

// FACULTY reports hour-research (Department Board)
Route::middleware(['auth:sanctum', 'auto.rotate.sanctum', 'force.json', 'role:DEPARTMENT_BOARD'])
    ->prefix('faculty/reports/hour-research')
    ->group(function () {
        Route::get('/filters', [FacultyResearchHoursReportController::class, 'filters']);
        Route::get('/', [FacultyResearchHoursReportController::class, 'index']);
        Route::get('/export/excel', [FacultyResearchHoursReportController::class, 'exportExcel'])->middleware('audit.export');
        Route::get('/export/pdf', [FacultyResearchHoursReportController::class, 'exportPdf'])->middleware('audit.export');
    });

Route::middleware(['force.json'])
    ->prefix('public')
    ->group(function () {
        Route::get('/lecturers', [PublicLecturerController::class, 'index']);
        Route::get('/lecturers/{lecturer:code}', [PublicLecturerController::class, 'show']);
    });

Route::middleware(['force.json'])
    ->prefix('public')
    ->group(function () {
        Route::get('/research-works/lookups', [PublicResearchWorkController::class, 'lookups']);
        Route::get('/research-works', [PublicResearchWorkController::class, 'index']);
        Route::get('/research-works/{activityId}', [PublicResearchWorkController::class, 'show']);
        Route::get('/research-works/{activityId}/evidence-files/{evidenceId}/preview', [PublicResearchWorkController::class, 'previewEvidence'])
            ->name('public.research.evidence.preview');
        Route::get('/research-works/{activityId}/evidence-files/{evidenceId}/download', [PublicResearchWorkController::class, 'downloadEvidence'])
            ->name('public.research.evidence.download');
    });
