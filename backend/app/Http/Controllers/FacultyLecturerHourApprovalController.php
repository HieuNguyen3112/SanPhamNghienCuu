<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyLecturerHourApprovalListRequest;
use App\Http\Requests\Faculty\FacultyLecturerHourApprovalRejectRequest;
use App\Support\AuditLogger;
use App\Support\AcademicYearResolver;
use App\Support\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class FacultyLecturerHourApprovalController extends Controller
{
    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageId = $this->resolveHoursStageId();

        $academicYears = DB::table('academic_years')
            ->select(['id', 'code', 'start_date', 'end_date', 'is_active'])
            ->orderByDesc('start_date')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'start_date' => (string) $row->start_date,
                'end_date' => (string) $row->end_date,
                'is_active' => (bool) $row->is_active,
            ])
            ->all();

        $defaultAcademicYear = $stageId
            ? $this->resolveAcademicYearForApprovals($scope['faculty_id'], $stageId, null, 'pending')
            : AcademicYearResolver::current();

        return response()->json([
            'data' => [
                'faculties' => [
                    [
                        'id' => $scope['faculty_id'],
                        'name' => $scope['faculty_name'],
                    ],
                ],
                'statuses' => [
                    ['code' => 'all', 'label' => 'Tất cả'],
                    ['code' => 'pending', 'label' => 'Chờ khoa duyệt giờ'],
                    ['code' => 'partially_approved', 'label' => 'Đã duyệt một phần'],
                    ['code' => 'approved', 'label' => 'Đã duyệt giờ'],
                    ['code' => 'need_revision', 'label' => 'Cần chỉnh sửa'],
                    ['code' => 'rejected', 'label' => 'Khoa từ chối giờ'],
                ],
                'academic_years' => $academicYears,
                'current_academic_year_id' => $defaultAcademicYear ? (int) $defaultAcademicYear->id : null,
            ],
        ], Response::HTTP_OK);
    }

    public function index(FacultyLecturerHourApprovalListRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $requestedFacultyId = $validated['faculty_id'] ?? null;
        if ($requestedFacultyId && (int) $requestedFacultyId !== $scope['faculty_id']) {
            return response()->json(['message' => 'faculty scope mismatch'], Response::HTTP_FORBIDDEN);
        }

        $stageId = $this->resolveHoursStageId();
        $includeAllAcademicYears = (bool) ($validated['include_all_years'] ?? false);

        $academicYear = null;
        if (! $includeAllAcademicYears) {
            $academicYear = $stageId
                ? $this->resolveAcademicYearForApprovals(
                    $scope['faculty_id'],
                    $stageId,
                    $validated['academic_year_id'] ?? null,
                    $validated['status'] ?? null
                )
                : AcademicYearResolver::current();
            if (! $academicYear) {
                return response()->json(['message' => 'academic year not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        $validated['academic_year_id'] = $academicYear ? (int) $academicYear->id : null;

        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        if (! $stageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'items' => [],
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                ],
            ], Response::HTTP_OK);
        }

        $revisionLikePattern = '%"decision_mode":"revision"%';
        $pendingCountExpression = "SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END)";
        $approvedCountExpression = "SUM(CASE WHEN aa.status = 'approved' THEN 1 ELSE 0 END)";
        $needRevisionCountExpression = "SUM(CASE WHEN aa.status = 'rejected' AND aa.note LIKE '{$revisionLikePattern}' THEN 1 ELSE 0 END)";
        $hardRejectedCountExpression = "SUM(CASE WHEN aa.status = 'rejected' AND (aa.note IS NULL OR aa.note NOT LIKE '{$revisionLikePattern}') THEN 1 ELSE 0 END)";

        $statusCase = "CASE
            WHEN {$pendingCountExpression} > 0 THEN 'pending'

            WHEN {$approvedCountExpression} > 0
                AND {$pendingCountExpression} = 0
                AND {$hardRejectedCountExpression} = 0
                AND {$needRevisionCountExpression} = 0
            THEN 'approved'

            WHEN {$hardRejectedCountExpression} > 0
                AND {$pendingCountExpression} = 0
                AND {$approvedCountExpression} = 0
                AND {$needRevisionCountExpression} = 0
            THEN 'rejected'

            WHEN {$needRevisionCountExpression} > 0
                AND {$pendingCountExpression} = 0
                AND {$approvedCountExpression} = 0
                AND {$hardRejectedCountExpression} = 0
            THEN 'need_revision'

            ELSE 'partially_approved'
        END";

        $query = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('research_activity_members as ram', function ($join) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->on('ram.lecturer_id', '=', 'l.id');
            })
            ->where('aa.stage_id', $stageId)
            ->where('f.id', $scope['faculty_id'])
            ->when(! $includeAllAcademicYears && ! empty($validated['academic_year_id']), function ($q) use ($validated) {
                $q->where('ra.academic_year_id', (int) $validated['academic_year_id']);
            })
            ->when(! empty($validated['from_date']), function ($q) use ($validated) {
                $q->whereDate('aa.created_at', '>=', $validated['from_date']);
            })
            ->when(! empty($validated['to_date']), function ($q) use ($validated) {
                $q->whereDate('aa.created_at', '<=', $validated['to_date']);
            })
            ->when(! empty($validated['keyword']), function ($q) use ($validated) {
                $keyword = trim($validated['keyword']);
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('l.full_name', 'like', '%' . $keyword . '%')
                        ->orWhere('l.code', 'like', '%' . $keyword . '%');
                });
            })
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                DB::raw('COUNT(DISTINCT ra.id) as works_count'),
                DB::raw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as total_hours_requested'),
                DB::raw('MAX(aa.created_at) as submitted_at'),
                DB::raw($statusCase . ' as status_code'),
            ])
            ->groupBy('l.id', 'l.code', 'l.full_name', 'f.id', 'f.name');

        $statusFilter = $validated['status'] ?? null;
        if ($statusFilter && $statusFilter !== 'all') {
            $query->havingRaw($statusCase . ' = ?', [$statusFilter]);
        }

        $paginator = $query
            ->orderByDesc(DB::raw('MAX(aa.created_at)'))
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            $statusCode = $row->status_code;
            return [
                'request_id' => (int) $row->lecturer_id,
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
                'faculty_name' => $row->faculty_name,
                'works_count' => (int) $row->works_count,
                'activity_count' => (int) $row->works_count,
                'total_hours_requested' => (float) $row->total_hours_requested,
                'total_hours' => (float) $row->total_hours_requested,
                'submitted_at' => $row->submitted_at,
                'status_code' => $statusCode,
                'status_label' => $this->statusLabel($statusCode),
                'status' => $statusCode,
                'partial_approved' => $statusCode === 'partially_approved',
            ];
        })->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'academic_year' => [
                    'id' => $academicYear ? (int) $academicYear->id : null,
                    'code' => $academicYear ? (string) $academicYear->code : null,
                    'start_date' => $academicYear ? (string) $academicYear->start_date : null,
                    'end_date' => $academicYear ? (string) $academicYear->end_date : null,
                    'include_all_years' => $includeAllAcademicYears,
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $requestId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $academicYearId = $this->resolveRequestedAcademicYearId($request);

        $stageId = $this->resolveHoursStageId();
        if (! $stageId) {
            return response()->json(['message' => 'hours approval stage not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lecturer = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('l.id', $requestId)
            ->where('f.id', $scope['faculty_id'])
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
            ])
            ->first();

        if (! $lecturer) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        $items = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->join('research_activity_members as ram', function ($join) use ($requestId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $requestId);
            })
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('aa.stage_id', $stageId)
            ->where('ra.owner_lecturer_id', $requestId)
            ->when($academicYearId !== null, function ($query) use ($academicYearId) {
                $query->where('ra.academic_year_id', $academicYearId);
            })
            ->orderByDesc('aa.created_at')
            ->select([
                'ra.id as activity_id',
                'ra.title as activity_title',
                'ra.kind_id',
                'ra.type_id',
                'ra.total_hours_calc',
                'ra.quantity',
                'ak.name as activity_kind_name',
                'mr.name as member_role_name',
                'mr.code as member_role_code',
                'ram.contribution_share',
                'ram.hours_assigned as hours_converted',
                'aa.status as approval_status',
                'aa.note as approval_note',
                'aa.created_at as submitted_at',
            ])
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        $displayStatuses = $items
            ->map(fn($item) => $this->normalizeApprovalStatusForDisplay($item->approval_status ?? null, $item->approval_note ?? null))
            ->all();

        $statusCode = $this->aggregateStatus($displayStatuses);
        $submittedAt = $items->max('submitted_at');
        $activityIds = $items->pluck('activity_id')->map(fn($id) => (int) $id)->unique()->values()->all();
        $evidenceByActivity = $this->fetchEvidenceByActivityIds($activityIds);
        $noteFromFaculty = null;
        $noteFromFacultyReasonCode = null;
        $noteFromFacultyReasonDetail = null;
        if (in_array($statusCode, ['rejected', 'need_revision', 'partially_approved'], true)) {
            $latestHandled = $items->first(function ($item) {
                $status = $this->normalizeApprovalStatusForDisplay(
                    $item->approval_status ?? null,
                    $item->approval_note ?? null
                );

                return in_array($status, ['rejected', 'need_revision'], true);
            });
            $latestRejectMeta = $this->resolveRejectMeta($latestHandled?->approval_note);
            $noteFromFaculty = $latestRejectMeta['reason_text'];
            $noteFromFacultyReasonCode = $latestRejectMeta['reason_code'];
            $noteFromFacultyReasonDetail = $latestRejectMeta['reason_detail'];
        }

        $totalHours = (float) $items->sum(function ($row) {
            return (float) ($row->hours_converted ?? 0);
        });

        $detailItems = $items->map(function ($row) use ($evidenceByActivity) {
            $activityId = (int) $row->activity_id;
            $approvalStatus = $this->normalizeApprovalStatusForDisplay(
                $row->approval_status ?? null,
                $row->approval_note ?? null
            );
            $rejectMeta = in_array($approvalStatus, ['rejected', 'need_revision'], true)
                ? $this->resolveRejectMeta($row->approval_note)
                : null;

            return [
                'activity_id' => $activityId,
                'activity_title' => $row->activity_title,
                'activity_kind_name' => $row->activity_kind_name,
                'member_role_name' => $row->member_role_name,
                'hours_converted' => $row->hours_converted !== null ? (float) $row->hours_converted : 0.0,
                'approval_status' => $approvalStatus,
                'rejection_reason' => $rejectMeta['reason_text'] ?? null,
                'rejection_reason_code' => $rejectMeta['reason_code'] ?? null,
                'rejection_reason_detail' => $rejectMeta['reason_detail'] ?? null,
                'evidence_files' => $evidenceByActivity[$activityId] ?? [],
            ];
        })->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'request_id' => (int) $requestId,
                'lecturer_id' => (int) $lecturer->lecturer_id,
                'lecturer_code' => $lecturer->lecturer_code,
                'lecturer_full_name' => $lecturer->lecturer_full_name,
                'faculty_id' => $lecturer->faculty_id ? (int) $lecturer->faculty_id : null,
                'faculty_name' => $lecturer->faculty_name,
                'submitted_at' => $submittedAt,
                'status' => $statusCode,
                'status_label' => $this->statusLabel($statusCode),
                'partial_approved' => $statusCode === 'partially_approved',
                'note_from_lecturer' => null,
                'note_from_faculty' => $noteFromFaculty,
                'note_from_faculty_reason_code' => $noteFromFacultyReasonCode,
                'note_from_faculty_reason_detail' => $noteFromFacultyReasonDetail,
                'activity_count' => $items->count(),
                'total_hours_requested' => $totalHours,
                'total_hours_valid' => $totalHours,
                'total_hours' => $totalHours,
                'items' => $detailItems,
            ],
        ], Response::HTTP_OK);
    }

    public function approve(Request $request, int $requestId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageId = $this->resolveHoursStageId();
        if (! $stageId) {
            return response()->json(['message' => 'hours approval stage not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->lecturerInScope($requestId, $scope['faculty_id'])) {
            return response()->json(['message' => 'request not in scope'], Response::HTTP_FORBIDDEN);
        }

        $selectedActivityIds = $this->extractSelectedActivityIds($request);
        if ($selectedActivityIds === null) {
            return response()->json([
                'message' => 'activity_ids is invalid',
                'code' => 'INVALID_ACTIVITY_IDS',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingRows = $this->pendingRowsForRequest($stageId, $requestId, $selectedActivityIds);
        if (! empty($selectedActivityIds)) {
            $pendingActivityIds = $pendingRows
                ->pluck('activity_id')
                ->map(fn($id) => (int) $id)
                ->all();
            $invalidActivityIds = array_values(array_diff($selectedActivityIds, $pendingActivityIds));
            if (! empty($invalidActivityIds)) {
                return response()->json([
                    'message' => 'some selected activities are not pending',
                    'code' => 'INVALID_SELECTED_ACTIVITY_IDS',
                    'invalid_activity_ids' => $invalidActivityIds,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $pendingIds = $pendingRows
            ->pluck('approval_id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($pendingIds)) {
            $message = empty($selectedActivityIds)
                ? 'request is not pending'
                : 'selected activities are not pending';
            return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updatedAt = now();
        DB::transaction(function () use ($pendingIds, $request, $requestId, $stageId, $pendingRows, $updatedAt) {
            DB::table('activity_approvals')
                ->whereIn('id', $pendingIds)
                ->update([
                    'status' => 'approved',
                    'decided_by_user_id' => $request->user()?->id,
                    'decided_at' => $updatedAt,
                    'note' => null,
                    'updated_at' => $updatedAt,
                ]);

            $academicYearIds = $pendingRows
                ->pluck('academic_year_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            foreach ($academicYearIds as $academicYearId) {
                $this->syncLecturerYearlyHours($requestId, $academicYearId, $stageId, $updatedAt);
            }
        });

        $approvedActivityIds = $pendingRows
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $context = $this->buildHoursApprovalContext($stageId, $requestId);
        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'HOURS_APPROVED',
            'action_label' => 'Duyệt giờ NCKH',
            'severity' => 'important',
            'result_status' => 'success',
            'target_type' => 'hours_request',
            'target_id' => $requestId,
            'target_display' => $context['lecturer_name']
                ? 'Yêu cầu duyệt giờ: ' . $context['lecturer_name']
                : 'Yêu cầu duyệt giờ',
            'faculty_id' => $context['faculty_id'],
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'lecturer_id' => $requestId,
                'lecturer_code' => $context['lecturer_code'],
                'academic_year_code' => $context['academic_year_code'],
                'works_count' => $context['works_count'],
                'total_hours' => $context['total_hours'],
                'approved_activity_ids' => $approvedActivityIds,
                'approved_count' => count($approvedActivityIds),
            ],
        ], $request->user());

        $approvedHours = 0.0;
        if (! empty($approvedActivityIds)) {
            $approvedHours = (float) DB::table('research_activity_members')
                ->whereIn('activity_id', $approvedActivityIds)
                ->where('lecturer_id', $requestId)
                ->sum(DB::raw('COALESCE(hours_assigned, 0)'));
        }

        WorkflowNotification::notifyLecturer(
            $requestId,
            WorkflowNotification::makePayload(
                'hours_approved',
                'Giờ NCKH đã được duyệt',
                $approvedHours > 0
                    ? ('Công trình của bạn đã được duyệt giờ: ' . round($approvedHours, 2) . ' giờ.')
                    : 'Yêu cầu duyệt giờ NCKH của bạn đã được khoa phê duyệt.',
                '/hours/personal',
                [
                    'lecturer_id' => (int) $requestId,
                    'approved_activity_ids' => $approvedActivityIds,
                    'hours_value' => round($approvedHours, 2),
                    'academic_year' => $context['academic_year_code'] ?? null,
                ]
            )
        );

        $this->recordHoursHistory(
            $requestId,
            'approve',
            (int) ($request->user()?->id ?? 0),
            json_encode([
                'activity_ids' => $approvedActivityIds,
                'count' => count($approvedActivityIds),
                'academic_year' => $context['academic_year_code'] ?? null,
            ], JSON_UNESCAPED_UNICODE)
        );

        return $this->show($request, $requestId);
    }

    public function reject(FacultyLecturerHourApprovalRejectRequest $request, int $requestId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageId = $this->resolveHoursStageId();
        if (! $stageId) {
            return response()->json(['message' => 'hours approval stage not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->lecturerInScope($requestId, $scope['faculty_id'])) {
            return response()->json(['message' => 'request not in scope'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $canonicalReasonCode = $this->canonicalReasonCode($validated['reason_code'] ?? null);
        $reasonDetail = isset($validated['reason_detail'])
            ? trim((string) $validated['reason_detail'])
            : '';
        $reasonDetail = $reasonDetail !== '' ? $reasonDetail : null;

        $decisionMode = strtolower((string) ($validated['decision_mode'] ?? 'reject'));
        $isRevisionMode = $decisionMode === 'revision';

        $selectedActivityIds = collect($validated['activity_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $pendingRows = $this->pendingRowsForRequest($stageId, $requestId, $selectedActivityIds);
        if (! empty($selectedActivityIds)) {
            $pendingActivityIds = $pendingRows
                ->pluck('activity_id')
                ->map(fn($id) => (int) $id)
                ->all();
            $invalidActivityIds = array_values(array_diff($selectedActivityIds, $pendingActivityIds));
            if (! empty($invalidActivityIds)) {
                return response()->json([
                    'message' => 'some selected activities are not pending',
                    'code' => 'INVALID_SELECTED_ACTIVITY_IDS',
                    'invalid_activity_ids' => $invalidActivityIds,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $pendingIds = $pendingRows
            ->pluck('approval_id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($pendingIds)) {
            $message = empty($selectedActivityIds)
                ? 'request is not pending'
                : 'selected activities are not pending';
            return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = json_encode([
            'reason_code' => $canonicalReasonCode,
            'reason_detail' => $reasonDetail,
            'decision_mode' => $isRevisionMode ? 'revision' : 'reject',
        ], JSON_UNESCAPED_UNICODE);

        $updatedAt = now();
        DB::table('activity_approvals')
            ->whereIn('id', $pendingIds)
            ->update([
                'status' => 'rejected',
                'decided_by_user_id' => $request->user()?->id,
                'decided_at' => $updatedAt,
                'note' => $note,
                'updated_at' => $updatedAt,
            ]);

        $academicYearIds = $pendingRows
            ->pluck('academic_year_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        foreach ($academicYearIds as $academicYearId) {
            $this->syncLecturerYearlyHours($requestId, $academicYearId, $stageId, $updatedAt);
        }

        $rejectedActivityIds = $pendingRows
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $context = $this->buildHoursApprovalContext($stageId, $requestId);
        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => $isRevisionMode ? 'HOURS_NEED_REVISION' : 'HOURS_REJECTED',
            'action_label' => $isRevisionMode ? 'Yêu cầu chỉnh sửa hồ sơ giờ NCKH' : 'Từ chối duyệt giờ NCKH',
            'severity' => 'important',
            'result_status' => 'success',
            'target_type' => 'hours_request',
            'target_id' => $requestId,
            'target_display' => $context['lecturer_name']
                ? 'Yêu cầu duyệt giờ: ' . $context['lecturer_name']
                : 'Yêu cầu duyệt giờ',
            'faculty_id' => $context['faculty_id'],
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'lecturer_id' => $requestId,
                'lecturer_code' => $context['lecturer_code'],
                'academic_year_code' => $context['academic_year_code'],
                'works_count' => $context['works_count'],
                'total_hours' => $context['total_hours'],
                'reason_code' => $canonicalReasonCode,
                'reason_detail' => $reasonDetail,
                'decision_mode' => $isRevisionMode ? 'revision' : 'reject',
                'rejected_activity_ids' => $rejectedActivityIds,
                'rejected_count' => count($rejectedActivityIds),
            ],
        ], $request->user());

        $rejectionReason = $reasonDetail
            ?? $this->reasonCodeLabel($canonicalReasonCode)
            ?? '';

        WorkflowNotification::notifyLecturer(
            $requestId,
            WorkflowNotification::makePayload(
                $isRevisionMode ? 'hours_need_revision' : 'hours_rejected',
                $isRevisionMode ? 'Giờ NCKH cần chỉnh sửa' : 'Giờ NCKH bị từ chối',
                $isRevisionMode
                    ? ($rejectionReason !== ''
                        ? ('Khoa yêu cầu bạn chỉnh sửa hồ sơ giờ NCKH và gửi lại. Góp ý: ' . $rejectionReason . '.')
                        : 'Khoa yêu cầu bạn chỉnh sửa hồ sơ giờ NCKH và gửi lại.')
                    : ($rejectionReason !== ''
                        ? ('Khoa đã từ chối yêu cầu duyệt giờ NCKH. Lý do: ' . $rejectionReason . '.')
                        : 'Khoa đã từ chối yêu cầu duyệt giờ NCKH của bạn.'),
                '/hours/calculate',
                [
                    'lecturer_id' => (int) $requestId,
                    'rejected_activity_ids' => $rejectedActivityIds,
                    'reason_code' => $canonicalReasonCode,
                    'reason_detail' => $reasonDetail,
                    'decision_mode' => $isRevisionMode ? 'revision' : 'reject',
                    'academic_year' => $context['academic_year_code'] ?? null,
                ]
            )
        );

        $this->recordHoursHistory(
            $requestId,
            $isRevisionMode ? 'revision' : 'reject',
            (int) ($request->user()?->id ?? 0),
            json_encode([
                'reason_code' => $canonicalReasonCode,
                'reason_detail' => $reasonDetail,
                'decision_mode' => $isRevisionMode ? 'revision' : 'reject',
                'activity_ids' => $rejectedActivityIds,
                'count' => count($rejectedActivityIds),
            ], JSON_UNESCAPED_UNICODE)
        );

        return $this->show($request, $requestId);
    }

    private function resolveFacultyScope(Request $request): ?array
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;
        if (! $lecturer || ! $lecturer->department_id) {
            return null;
        }

        $faculty = DB::table('departments as d')
            ->join('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('d.id', $lecturer->department_id)
            ->select(['f.id', 'f.name'])
            ->first();

        if (! $faculty) {
            return null;
        }

        return [
            'faculty_id' => (int) $faculty->id,
            'faculty_name' => $faculty->name,
        ];
    }

    private function lecturerInScope(int $lecturerId, int $facultyId): bool
    {
        return DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('l.id', $lecturerId)
            ->where('d.faculty_id', $facultyId)
            ->exists();
    }

    private function resolveHoursStageId(): ?int
    {
        $id = DB::table('approval_stages')->where('code', 'hours')->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveRequestedAcademicYearId(Request $request): ?int
    {
        if (! $request->query->has('academic_year_id')) {
            return null;
        }

        $raw = trim((string) $request->query('academic_year_id'));
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        $matchedId = DB::table('academic_years')
            ->where('code', $raw)
            ->value('id');

        return $matchedId ? (int) $matchedId : null;
    }

    private function extractSelectedActivityIds(Request $request): ?array
    {
        $validator = Validator::make($request->all(), [
            'activity_ids' => ['nullable', 'array', 'min:1'],
            'activity_ids.*' => ['integer', 'distinct'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        return collect($validator->validated()['activity_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function pendingRowsForRequest(int $stageId, int $lecturerId, array $activityIds = [])
    {
        $query = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->where('aa.stage_id', $stageId)
            ->where('ra.owner_lecturer_id', $lecturerId)
            ->where('aa.status', 'pending');

        if (! empty($activityIds)) {
            $query->whereIn('ra.id', $activityIds);
        }

        return $query->select([
            'aa.id as approval_id',
            'ra.id as activity_id',
            'ra.academic_year_id',
        ])->get();
    }

    private function resolveAcademicYearForApprovals(
        int $facultyId,
        int $stageId,
        ?int $requestedAcademicYearId,
        ?string $status
    ): ?object {
        if ($requestedAcademicYearId) {
            return AcademicYearResolver::resolve($requestedAcademicYearId);
        }

        $normalizedStatus = strtolower(trim((string) ($status ?? '')));
        $preferredStatus = in_array($normalizedStatus, ['pending', 'approved', 'rejected'], true)
            ? $normalizedStatus
            : 'pending';

        $yearId = $this->findAcademicYearIdByFacultyApprovals($facultyId, $stageId, $preferredStatus);
        if (! $yearId) {
            $yearId = $this->findAcademicYearIdByFacultyApprovals($facultyId, $stageId, null);
        }

        if ($yearId) {
            $resolved = AcademicYearResolver::resolve($yearId);
            if ($resolved) {
                return $resolved;
            }
        }

        return AcademicYearResolver::current();
    }

    private function findAcademicYearIdByFacultyApprovals(int $facultyId, int $stageId, ?string $status): ?int
    {
        $query = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('aa.stage_id', $stageId)
            ->where('d.faculty_id', $facultyId)
            ->whereNotNull('ra.academic_year_id')
            ->orderByDesc('ay.is_active')
            ->orderByDesc('ay.start_date');

        if ($status) {
            $query->where('aa.status', $status);
        }

        $value = $query->value('ra.academic_year_id');
        return $value ? (int) $value : null;
    }

    private function buildHoursApprovalContext(int $stageId, int $lecturerId): array
    {
        $lecturer = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('l.id', $lecturerId)
            ->select([
                'l.code as lecturer_code',
                'l.full_name as lecturer_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
            ])
            ->first();

        $summary = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('aa.stage_id', $stageId)
            ->where('ra.owner_lecturer_id', $lecturerId)
            ->selectRaw('COUNT(DISTINCT ra.id) as works_count')
            ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as total_hours')
            ->selectRaw('MAX(ay.code) as academic_year_code')
            ->first();

        return [
            'lecturer_code' => $lecturer?->lecturer_code,
            'lecturer_name' => $lecturer?->lecturer_name,
            'faculty_id' => $lecturer?->faculty_id ? (int) $lecturer->faculty_id : null,
            'faculty_name' => $lecturer?->faculty_name,
            'works_count' => $summary?->works_count ? (int) $summary->works_count : 0,
            'total_hours' => $summary?->total_hours ? (float) $summary->total_hours : 0.0,
            'academic_year_code' => $summary?->academic_year_code,
        ];
    }

    private function syncLecturerYearlyHours(int $lecturerId, int $academicYearId, int $hoursStageId, $updatedAt): void
    {
        $approvedHours = (float) DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where('aa.stage_id', $hoursStageId)
            ->where('aa.status', 'approved')
            ->where('ra.owner_lecturer_id', $lecturerId)
            ->where('ra.academic_year_id', $academicYearId)
            ->sum(DB::raw('COALESCE(ram.hours_assigned, 0)'));

        $existingId = DB::table('lecturer_yearly_hours')
            ->where('lecturer_id', $lecturerId)
            ->where('academic_year_id', $academicYearId)
            ->value('id');

        if ($existingId) {
            DB::table('lecturer_yearly_hours')
                ->where('id', (int) $existingId)
                ->update([
                    'hours_total' => $approvedHours,
                    'updated_at' => $updatedAt,
                ]);

            return;
        }

        DB::table('lecturer_yearly_hours')->insert([
            'lecturer_id' => $lecturerId,
            'academic_year_id' => $academicYearId,
            'hours_total' => $approvedHours,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }

    public function downloadEvidence(Request $request, int $evidence)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageId = $this->resolveHoursStageId();
        if (! $stageId) {
            return response()->json([
                'message' => 'hours approval stage not configured',
                'code' => 'HOURS_STAGE_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ef.activity_id', '=', 'ra.id')
            ->join('activity_approvals as aa', function ($join) use ($stageId) {
                $join->on('aa.activity_id', '=', 'ra.id')
                    ->where('aa.stage_id', '=', $stageId);
            })
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ef.id', $evidence)
            ->where('d.faculty_id', $scope['faculty_id'])
            ->select([
                'ef.id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
            ])
            ->first();

        if (! $file) {
            return response()->json(['message' => 'evidence not found'], Response::HTTP_NOT_FOUND);
        }

        return \App\Support\StorageDownload::stream(
            $file->disk ?: 'local',
            $file->path,
            $file->original_name ?: ('evidence-' . $file->id),
            ['Content-Type' => $file->mime_type ?: 'application/octet-stream']
        );
    }

    private function resolveRejectNote(?string $note): ?string
    {
        $meta = $this->resolveRejectMeta($note);
        return $meta['reason_text'];
    }

    private function resolveRejectMeta(?string $note): array
    {
        if (! $note || trim($note) === '') {
            return [
                'reason_code' => null,
                'reason_detail' => null,
                'decision_mode' => null,
                'reason_text' => null,
            ];
        }

        $decoded = json_decode($note, true);
        if (! is_array($decoded)) {
            $raw = trim($note);
            return [
                'reason_code' => null,
                'reason_detail' => $raw !== '' ? $raw : null,
                'decision_mode' => null,
                'reason_text' => $raw !== '' ? $raw : null,
            ];
        }

        $reasonCode = $this->canonicalReasonCode($decoded['reason_code'] ?? null);
        $reasonDetail = isset($decoded['reason_detail']) ? trim((string) $decoded['reason_detail']) : '';
        $decisionMode = strtolower(trim((string) ($decoded['decision_mode'] ?? '')));
        if (! in_array($decisionMode, ['revision', 'reject'], true)) {
            $decisionMode = null;
        }

        $reasonText = $reasonDetail !== ''
            ? $reasonDetail
            : $this->reasonCodeLabel($reasonCode);

        return [
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail !== '' ? $reasonDetail : null,
            'decision_mode' => $decisionMode,
            'reason_text' => $reasonText,
        ];
    }

    private function canonicalReasonCode($reasonCode): ?string
    {
        $normalized = strtoupper(trim((string) $reasonCode));
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'INVALID_EVIDENCE', 'MISSING_EVIDENCE' => 'INVALID_EVIDENCE',
            'INVALID_HOURS', 'HOURS_NOT_REASONABLE' => 'INVALID_HOURS',
            'INVALID_ACTIVITY', 'OTHER' => 'INVALID_ACTIVITY',
            'NOT_ELIGIBLE', 'WORK_NOT_ELIGIBLE' => 'NOT_ELIGIBLE',
            default => $normalized,
        };
    }

    private function reasonCodeLabel(?string $reasonCode): ?string
    {
        return match ($reasonCode) {
            'INVALID_EVIDENCE' => 'Minh chứng không hợp lệ hoặc còn thiếu',
            'INVALID_HOURS' => 'Giờ quy đổi chưa hợp lý',
            'INVALID_ACTIVITY' => 'Hoạt động không hợp lệ',
            'NOT_ELIGIBLE' => 'Không đủ điều kiện xét duyệt giờ',
            default => null,
        };
    }

    private function normalizeApprovalStatusForDisplay(?string $status, ?string $note): string
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized !== 'rejected') {
            return $normalized !== '' ? $normalized : 'pending';
        }

        return $this->isRevisionDecision($note)
            ? 'need_revision'
            : 'rejected';
    }

    private function isRevisionDecision(?string $note): bool
    {
        if (! $note || trim($note) === '') {
            return false;
        }

        $decoded = json_decode($note, true);
        if (is_array($decoded)) {
            $decisionMode = strtolower(trim((string) ($decoded['decision_mode'] ?? '')));
            if ($decisionMode === 'revision') {
                return true;
            }
        }

        return str_contains($note, '"decision_mode":"revision"');
    }

    private function recordHoursHistory(int $lecturerId, string $action, int $performedBy, ?string $reason = null): void
    {
        DB::table('hours_history')->insert([
            'lecturer_id' => $lecturerId,
            'action' => $action,
            'performed_by' => $performedBy > 0 ? $performedBy : null,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function fetchEvidenceByActivityIds(array $activityIds): array
    {
        if (empty($activityIds)) {
            return [];
        }

        $rows = DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->whereIn('ef.activity_id', $activityIds)
            ->orderByDesc('ef.uploaded_at')
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.file_type_id',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.uploaded_at',
                'eft.name as file_type_name',
            ])
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $activityId = (int) $row->activity_id;
            $grouped[$activityId] ??= [];
            $grouped[$activityId][] = [
                'id' => (int) $row->id,
                'activity_id' => $activityId,
                'file_type_id' => (int) $row->file_type_id,
                'file_type_name' => $row->file_type_name,
                'original_name' => $row->original_name,
                'mime_type' => $row->mime_type,
                'size_bytes' => (int) $row->size_bytes,
                'uploaded_at' => $row->uploaded_at,
                'download_url' => route('faculty.hours.evidence.download', ['evidence' => (int) $row->id]),
            ];
        }

        return $grouped;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ khoa duyệt giờ',
            'partially_approved' => 'Đã duyệt một phần',
            'approved' => 'Đã duyệt giờ',
            'need_revision' => 'Cần chỉnh sửa',
            'rejected' => 'Khoa từ chối giờ',
            default => $status,
        };
    }

    private function aggregateStatus(array $statuses): string
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            fn($status) => strtolower(trim((string) $status)),
            $statuses
        ))));

        if (empty($normalized)) {
            return 'pending';
        }

        if (in_array('pending', $normalized, true)) {
            return 'pending';
        }

        if (count($normalized) === 1) {
            return $normalized[0];
        }

        return 'partially_approved';
    }
}
