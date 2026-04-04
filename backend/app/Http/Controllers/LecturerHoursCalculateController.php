<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerHoursCalculateIndexRequest;
use App\Http\Requests\Lecturer\LecturerHoursCalculateSubmitRequest;
use App\Services\Evidence\ResearchEvidenceStorageService;
use App\Services\Hours\HoursCalculationService;
use App\Services\Hours\HoursRecomputeService;
use App\Services\Hours\HoursRuleResolver;
use App\Support\StorageDownload;
use App\Support\AcademicYearResolver;
use App\Support\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class LecturerHoursCalculateController extends Controller
{
    private const EVIDENCE_REQUIRED = true;
    private HoursRuleResolver $hoursRuleResolver;
    private HoursRecomputeService $hoursRecomputeService;
    private HoursCalculationService $hoursCalculationService;

    public function __construct(
        HoursRuleResolver $hoursRuleResolver,
        HoursRecomputeService $hoursRecomputeService,
        HoursCalculationService $hoursCalculationService
    ) {
        $this->hoursRuleResolver = $hoursRuleResolver;
        $this->hoursRecomputeService = $hoursRecomputeService;
        $this->hoursCalculationService = $hoursCalculationService;
    }

    public function index(LecturerHoursCalculateIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $approvedStatusId = $this->resolveStatusId('approved');
        if (! $hoursStageId || ! $approvedStatusId) {
            return response()->json([
                'message' => 'Chưa cấu hình bước duyệt giờ hoặc trạng thái đã duyệt.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $filters = $request->validated();
        $includeAllAcademicYears = (bool) ($filters['include_all_years'] ?? false);

        $selectedAcademicYear = null;
        if (! $includeAllAcademicYears) {
            $selectedAcademicYear = $this->resolveAcademicYearForCalculate(
                (int) $lecturer->id,
                $approvedStatusId,
                $filters['academic_year_id'] ?? null
            );
            if (! $selectedAcademicYear) {
                return response()->json(['message' => 'Không tìm thấy năm học.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $selectedAcademicYearId = $selectedAcademicYear ? (int) $selectedAcademicYear->id : null;
        $filters['academic_year_id'] = $selectedAcademicYearId;

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $query = $this->baseQuery($lecturer->id, $hoursStageId, $approvedStatusId);
        $this->applyFilters($query, $filters);
        $missingEvidenceSummary = $this->buildMissingEvidenceSummary(
            (int) $lecturer->id,
            $hoursStageId,
            $approvedStatusId,
            $filters
        );

        $query->orderByDesc('ra.updated_at');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $currentPageActivityIds = collect($paginator->items())
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($currentPageActivityIds !== []) {
            $this->hoursRecomputeService->recomputeActivities($currentPageActivityIds, now(), false);
        }

        $items = collect($paginator->items())->map(fn($row) => $this->mapListItem($row))->all();
        $approvedCount = $this->approvedCount((int) $lecturer->id, $approvedStatusId, $selectedAcademicYearId);
        $responseAcademicYear = $selectedAcademicYear ?: AcademicYearResolver::current();

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
                'summary' => [
                    'approved_count' => $approvedCount,
                    'missing_evidence_count' => $missingEvidenceSummary['missing_evidence_count'],
                    'missing_evidence_hours_total' => $missingEvidenceSummary['missing_evidence_hours_total'],
                ],
                'academic_year' => $responseAcademicYear
                    ? [
                        'id' => (int) $responseAcademicYear->id,
                        'code' => (string) $responseAcademicYear->code,
                        'start_date' => (string) $responseAcademicYear->start_date,
                        'end_date' => (string) $responseAcademicYear->end_date,
                        'is_filter_applied' => $selectedAcademicYear !== null,
                        'include_all_years' => $includeAllAcademicYears,
                    ]
                    : null,
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activityId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $approvedStatusId = $this->resolveStatusId('approved');
        if (! $hoursStageId || ! $approvedStatusId) {
            return response()->json([
                'message' => 'Chưa cấu hình bước duyệt giờ hoặc trạng thái đã duyệt.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->hoursRecomputeService->recomputeActivity((int) $activityId, now(), false);

        $row = $this->detailQuery($lecturer->id, $hoursStageId, $approvedStatusId)
            ->where('ra.id', $activityId)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Không tìm thấy công trình.'], Response::HTTP_NOT_FOUND);
        }

        $hoursMeta = $this->resolveHoursMeta(
            $row->hours_approval_status ?? null,
            $row->hours_approval_note ?? null
        );
        $hoursValues = $this->resolveHoursValues(
            (int) $row->kind_id,
            $row->type_id ? (int) $row->type_id : null,
            $row->academic_year_id ? (int) $row->academic_year_id : null,
            $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
            $row->total_hours_calc !== null ? (float) $row->total_hours_calc : null,
            $row->quantity !== null ? (int) $row->quantity : null,
            $row->contribution_share !== null ? (float) $row->contribution_share : null,
            $row->member_role_code ? (string) $row->member_role_code : null,
            $row->member_count !== null ? (int) $row->member_count : 1,
            $row->principal_count !== null ? (int) $row->principal_count : 0,
            (int) $row->activity_id,
            $row->title ? (string) $row->title : null,
            $row->kind_code ? (string) $row->kind_code : null,
            $row->kind_name ? (string) $row->kind_name : null,
            $row->type_code ? (string) $row->type_code : null,
            $row->type_name ? (string) $row->type_name : null,
            $row->academic_year_code ? (string) $row->academic_year_code : null,
            $row->hours_claimed_before !== null ? (float) $row->hours_claimed_before : null,
            $row->start_date ? (string) $row->start_date : null,
            $row->end_date ? (string) $row->end_date : null
        );

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'activity_id' => (int) $row->activity_id,
                'title' => $row->title,
                'kind_name' => $this->mapKindName($row->kind_code ?? null, $row->kind_name ?? null),
                'academic_year_code' => $row->academic_year_code,
                'publication_or_unit' => $this->resolvePublicationOrUnit($row),
                'member_role_name' => $this->mapRoleName($row->member_role_code ?? null, $row->member_role_name ?? null),
                'contribution_share' => $row->contribution_share !== null ? (float) $row->contribution_share : null,
                'rule_summary' => $hoursValues['rule_summary'],
                'conversion_rule_present' => $hoursValues['conversion_rule_present'],
                'calculated_hours' => $hoursValues['calculated_hours'],
                'proposed_hours' => $hoursValues['proposed_hours'],
                'effective_hours_display' => $hoursValues['effective_hours_display'],
                'hours_for_lecturer' => $hoursValues['effective_hours_display'],
                'total_hours_activity' => $hoursValues['total_hours_activity'],
                'member_hours' => $hoursValues['member_hours'],
                'formula_explanation' => $hoursValues['formula_explanation'],
                'can_edit_proposed_hours' => $hoursValues['can_edit_proposed_hours'],
                'evidence_required' => self::EVIDENCE_REQUIRED,
                'deadline_flags' => [
                    'submission_deadline_enforced' => false,
                    'approval_deadline_enforced' => false,
                ],
                'activity_status_code' => $row->activity_status_code,
                'hours_request_state' => $hoursMeta['state'],
                'hours_rejection_reason' => $hoursMeta['rejection_reason'],
                'hours_rejection_reason_code' => $hoursMeta['rejection_reason_code'],
                'hours_rejection_reason_detail' => $hoursMeta['rejection_reason_detail'],
                'next_action_code' => $hoursMeta['next_action_code'],
                'next_action_text' => $hoursMeta['next_action_text'],
                'evidence_files' => $this->fetchEvidenceFiles((int) $row->activity_id),
            ],
        ], Response::HTTP_OK);
    }

    public function submit(LecturerHoursCalculateSubmitRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $approvedStatusId = $this->resolveStatusId('approved');
        if (! $hoursStageId || ! $approvedStatusId) {
            return response()->json([
                'message' => 'Chưa cấu hình bước duyệt giờ hoặc trạng thái đã duyệt.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activityIds = array_values(array_unique($request->validated()['activity_ids']));
        $eligibleIds = $this->eligibleActivityIds($lecturer->id, $approvedStatusId, $activityIds);
        $ineligibleIds = array_values(array_diff($activityIds, $eligibleIds));

        if (! empty($ineligibleIds)) {
            return response()->json([
                'message' => 'Một số công trình chưa đủ điều kiện. Chỉ công trình đã được duyệt nội dung mới được gửi duyệt giờ.',
                'code' => 'WORK_NOT_FACULTY_APPROVED',
                'invalid_activity_ids' => $ineligibleIds,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (empty($eligibleIds)) {
            return response()->json([
                'message' => 'Không có công trình đủ điều kiện gửi duyệt giờ.',
                'code' => 'NO_ELIGIBLE_WORKS',
                'data' => [
                    'submitted_count' => 0,
                    'skipped_count' => count($activityIds),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->hoursRecomputeService->recomputeActivities($eligibleIds, now(), true);

        $rows = $this->baseQuery((int) $lecturer->id, $hoursStageId, $approvedStatusId)
            ->whereIn('ra.id', $eligibleIds)
            ->get()
            ->keyBy('activity_id');

        $evidenceCountByActivity = $this->validEvidenceCountByActivity($eligibleIds);

        $missingHours = [];
        $missingEvidence = [];
        $pendingActivities = [];
        $approvedActivities = [];
        $finalRejectedActivities = [];

        foreach ($eligibleIds as $activityId) {
            $row = $rows->get($activityId);
            if (! $row) {
                $missingHours[] = [
                    'activity_id' => (int) $activityId,
                    'reason' => 'ACTIVITY_CONTEXT_NOT_FOUND',
                ];
                continue;
            }

            $hoursMeta = $this->resolveHoursMeta(
                $row->hours_approval_status ?? null,
                $row->hours_approval_note ?? null
            );

            if ($hoursMeta['state'] === 'hours_pending_faculty') {
                $pendingActivities[] = (int) $activityId;
                continue;
            }

            if ($hoursMeta['state'] === 'hours_approved') {
                $approvedActivities[] = (int) $activityId;
                continue;
            }

            if ($hoursMeta['state'] === 'hours_rejected') {
                $finalRejectedActivities[] = (int) $activityId;
                continue;
            }

            $hoursValues = $this->resolveHoursValues(
                (int) $row->kind_id,
                $row->type_id ? (int) $row->type_id : null,
                $row->academic_year_id ? (int) $row->academic_year_id : null,
                $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
                $row->total_hours_calc !== null ? (float) $row->total_hours_calc : null,
                $row->quantity !== null ? (int) $row->quantity : null,
                $row->contribution_share !== null ? (float) $row->contribution_share : null,
                $row->member_role_code ? (string) $row->member_role_code : null,
                $row->member_count !== null ? (int) $row->member_count : 1,
                $row->principal_count !== null ? (int) $row->principal_count : 0,
                (int) $row->activity_id,
                $row->title ? (string) $row->title : null,
                $row->kind_code ? (string) $row->kind_code : null,
                $row->kind_name ? (string) $row->kind_name : null,
                $row->type_code ? (string) $row->type_code : null,
                $row->type_name ? (string) $row->type_name : null,
                $row->academic_year_code ? (string) $row->academic_year_code : null,
                $row->hours_claimed_before !== null ? (float) $row->hours_claimed_before : null,
                $row->start_date ? (string) $row->start_date : null,
                $row->end_date ? (string) $row->end_date : null
            );

            if ($hoursValues['effective_hours_display'] === null) {
                $missingHours[] = [
                    'activity_id' => (int) $activityId,
                    'conversion_rule_present' => (bool) $hoursValues['conversion_rule_present'],
                    'rule_summary' => (string) $hoursValues['rule_summary'],
                ];
                continue;
            }

            $evidenceCount = (int) ($evidenceCountByActivity[$activityId] ?? 0);
            if ($evidenceCount === 0) {
                $missingEvidence[] = (int) $activityId;
            }
        }

        if (! empty($pendingActivities)) {
            return response()->json([
                'message' => 'Một số công trình đang chờ khoa duyệt giờ, không thể gửi lại.',
                'code' => 'HOURS_ALREADY_PENDING',
                'invalid_activity_ids' => array_values($pendingActivities),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! empty($approvedActivities)) {
            return response()->json([
                'message' => 'Một số công trình đã duyệt giờ nên không cần gửi lại.',
                'code' => 'HOURS_ALREADY_APPROVED',
                'invalid_activity_ids' => array_values($approvedActivities),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! empty($finalRejectedActivities)) {
            return response()->json([
                'message' => 'Một số công trình đã bị từ chối hẳn, không thể gửi lại.',
                'code' => 'HOURS_FINAL_REJECTED',
                'invalid_activity_ids' => array_values($finalRejectedActivities),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! empty($missingHours)) {
            return response()->json([
                'message' => 'Một số công trình chưa có dữ liệu tính giờ tự động theo quy tắc.',
                'code' => 'HOURS_VALUE_REQUIRED',
                'invalid_items' => $missingHours,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! empty($missingEvidence)) {
            return response()->json([
                'message' => 'Mỗi công trình phải có ít nhất một minh chứng PDF trước khi gửi duyệt giờ.',
                'code' => 'EVIDENCE_REQUIRED',
                'invalid_activity_ids' => $missingEvidence,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $submittedIds = [];
        $skipped = [];

        DB::transaction(function () use ($eligibleIds, $hoursStageId, $now, &$submittedIds, &$skipped, $lecturer) {
            $existing = DB::table('activity_approvals')
                ->where('stage_id', $hoursStageId)
                ->whereIn('activity_id', $eligibleIds)
                ->get()
                ->keyBy('activity_id');

            $rowsToInsert = [];
            $approvalIdsToResetPending = [];

            foreach ($eligibleIds as $activityId) {
                $row = $existing[$activityId] ?? null;
                if (! $row) {
                    $rowsToInsert[] = [
                        'activity_id' => $activityId,
                        'stage_id' => $hoursStageId,
                        'status' => 'pending',
                        'decided_by_user_id' => null,
                        'decided_at' => null,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $submittedIds[] = $activityId;
                    continue;
                }

                if ($row->status === 'rejected' && $this->isNeedRevisionNote($row->note)) {
                    $approvalIdsToResetPending[] = (int) $row->id;
                    $submittedIds[] = $activityId;
                    continue;
                }

                $skipped[] = [
                    'activity_id' => (int) $activityId,
                    'status' => (string) $row->status,
                ];
            }

            if (! empty($rowsToInsert)) {
                DB::table('activity_approvals')->insert($rowsToInsert);
            }

            if (! empty($approvalIdsToResetPending)) {
                DB::table('activity_approvals')
                    ->whereIn('id', $approvalIdsToResetPending)
                    ->update([
                        'status' => 'pending',
                        'decided_by_user_id' => null,
                        'decided_at' => null,
                        'note' => null,
                        'updated_at' => $now,
                    ]);
            }

            $this->upsertMemberHoursApprovals(
                $submittedIds,
                (int) $lecturer->id,
                $hoursStageId,
                'pending',
                null,
                null,
                null,
                $now
            );
        });

        if (empty($submittedIds)) {
            return response()->json([
                'message' => 'Không có công trình hợp lệ để gửi duyệt giờ.',
                'code' => 'NO_SUBMITTABLE_WORKS',
                'data' => [
                    'submitted_count' => 0,
                    'skipped_count' => count($skipped),
                    'skipped' => $skipped,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! empty($submittedIds)) {
            $academicYearCode = DB::table('research_activities as ra')
                ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
                ->whereIn('ra.id', $submittedIds)
                ->whereNotNull('ay.code')
                ->value('ay.code');

            $lecturerName = trim((string) ($lecturer->full_name ?? 'Giảng viên'));
            WorkflowNotification::notifyFacultyBoardByActivityId(
                (int) $submittedIds[0],
                WorkflowNotification::makePayload(
                    'hours_submitted_to_faculty',
                    'Có yêu cầu duyệt giờ mới',
                    $lecturerName . ' đã gửi duyệt giờ NCKH cho ' . count($submittedIds) . ' công trình' .
                        ($academicYearCode ? (' (' . $academicYearCode . ').') : '.'),
                    '/hours/facapprovals?lecturer_id=' . (int) $lecturer->id,
                    [
                        'lecturer_id' => (int) $lecturer->id,
                        'activity_ids' => array_values(array_map('intval', $submittedIds)),
                        'academic_year' => $academicYearCode,
                    ]
                ),
                (int) ($request->user()?->id ?? 0)
            );

            $this->recordHoursHistory(
                (int) $lecturer->id,
                'submit',
                (int) ($request->user()?->id ?? 0),
                json_encode([
                    'activity_ids' => array_values(array_map('intval', $submittedIds)),
                    'count' => count($submittedIds),
                    'academic_year' => $academicYearCode,
                ], JSON_UNESCAPED_UNICODE)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'submitted',
            'data' => [
                'submitted_count' => count($submittedIds),
                'skipped_count' => count($skipped),
                'submitted_activity_ids' => array_values($submittedIds),
                'skipped' => $skipped,
            ],
        ], Response::HTTP_OK);
    }

    public function updateProposedHours(Request $request, int $activityId)
    {
        return response()->json([
            'message' => 'Hệ thống chỉ hỗ trợ tính giờ tự động theo quy tắc đã cấu hình.',
            'code' => 'AUTO_CALC_ONLY',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function listEvidence(Request $request, int $activityId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $approvedStatusId = $this->resolveStatusId('approved');
        if (! $approvedStatusId) {
            return response()->json([
                'message' => 'Chưa cấu hình trạng thái đã duyệt.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = $this->resolveAccessibleApprovedActivity((int) $lecturer->id, $activityId, $approvedStatusId);
        if (! $activity) {
            return response()->json([
                'message' => 'Không tìm thấy công trình.',
                'code' => 'WORK_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $this->fetchEvidenceFiles($activityId),
        ], Response::HTTP_OK);
    }

    public function uploadEvidence(Request $request, int $activityId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $approvedStatusId = $this->resolveStatusId('approved');
        if (! $hoursStageId || ! $approvedStatusId) {
            return response()->json([
                'message' => 'Chưa cấu hình bước duyệt giờ hoặc trạng thái đã duyệt.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = $this->resolveAccessibleApprovedActivity((int) $lecturer->id, $activityId, $approvedStatusId);
        if (! $activity) {
            return response()->json([
                'message' => 'Không tìm thấy công trình.',
                'code' => 'WORK_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        $hoursStatus = $this->resolveHoursApprovalStatus($activityId, $hoursStageId, (int) $lecturer->id);
        if ($hoursStatus === 'approved') {
            return response()->json([
                'message' => 'Đã duyệt giờ, không thể cập nhật minh chứng.',
                'code' => 'EVIDENCE_LOCKED_BY_APPROVED_HOURS',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'file' => ['required', 'file', 'max:10240', 'mimes:pdf'],
                'file_type_id' => ['required', 'integer', 'exists:evidence_file_types,id'],
            ],
            [
                'file.required' => 'Vui lòng chọn tệp minh chứng.',
                'file.file' => 'Tệp minh chứng không hợp lệ.',
                'file.max' => 'Dung lượng tệp minh chứng không được vượt quá 10MB.',
                'file.mimes' => 'Minh chứng phải là tệp PDF.',
                'file_type_id.required' => 'Vui lòng chọn loại minh chứng.',
                'file_type_id.integer' => 'Loại minh chứng không hợp lệ.',
                'file_type_id.exists' => 'Loại minh chứng không tồn tại.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'code' => 'VALIDATION_FAILED',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        $contentSha256 = hash_file('sha256', $file->getRealPath());
        // Giữ chống trùng trong cùng công trình, nhưng cho phép cùng một PDF dùng cho công trình khác.
        $scopedSha256 = hash('sha256', $activityId . '|' . $contentSha256);

        $existingByHash = DB::table('evidence_files')
            ->where('activity_id', $activityId)
            ->whereIn('sha256', [$contentSha256, $scopedSha256])
            ->first();
        if ($existingByHash) {
            return response()->json([
                'success' => true,
                'message' => 'Minh chứng đã tồn tại.',
                'data' => $this->mapEvidenceRow($existingByHash),
            ], Response::HTTP_OK);
        }

        $disk = (string) config('filesystems.default', 'local');
        if (! config("filesystems.disks.{$disk}")) {
            $disk = 'local';
        }

        $originalName = $file->getClientOriginalName() ?: ('evidence-' . Str::uuid() . '.bin');
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName = Str::slug($nameWithoutExt);
        if ($safeName === '') {
            $safeName = 'evidence';
        }

        $filename = Str::uuid()->toString() . '-' . $safeName . ($ext !== '' ? '.' . $ext : '');
        $directory = 'evidence/hours/' . now()->format('Y/m') . '/activity-' . $activityId;
        $storedPath = $file->storeAs($directory, $filename, $disk);

        if (! $storedPath) {
            return response()->json([
                'message' => 'Không thể lưu tệp minh chứng.',
                'code' => 'EVIDENCE_STORE_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $now = now();
        $id = DB::table('evidence_files')->insertGetId([
            'activity_id' => $activityId,
            'file_type_id' => (int) $request->input('file_type_id'),
            'disk' => $disk,
            'path' => $storedPath,
            'original_name' => $originalName,
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
            'size_bytes' => (int) $file->getSize(),
            'sha256' => $scopedSha256,
            'uploaded_by_user_id' => (int) $request->user()->id,
            'uploaded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $saved = DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.id', $id)
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.file_type_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.sha256',
                'ef.uploaded_by_user_id',
                'ef.uploaded_at',
                'ef.created_at',
                'ef.updated_at',
                'eft.name as file_type_name',
            ])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Đã tải minh chứng lên.',
            'data' => $saved ? $this->mapEvidenceRow($saved) : null,
        ], Response::HTTP_CREATED);
    }

    public function deleteEvidence(Request $request, int $evidenceId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        if (! $hoursStageId) {
            return response()->json([
                'message' => 'Chưa cấu hình bước duyệt giờ.',
                'code' => 'HOURS_WORKFLOW_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $evidence = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ef.activity_id', '=', 'ra.id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturer) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', (int) $lecturer->id);
            })
            ->where('ef.id', $evidenceId)
            ->where(function ($query) use ($lecturer) {
                $query->where('ra.owner_lecturer_id', (int) $lecturer->id)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
            ])
            ->first();

        if (! $evidence) {
            return response()->json([
                'message' => 'Không tìm thấy minh chứng.',
                'code' => 'EVIDENCE_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        $hoursStatus = $this->resolveHoursApprovalStatus((int) $evidence->activity_id, $hoursStageId, (int) $lecturer->id);
        if ($hoursStatus === 'approved') {
            return response()->json([
                'message' => 'Đã duyệt giờ, không thể cập nhật minh chứng.',
                'code' => 'EVIDENCE_LOCKED_BY_APPROVED_HOURS',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $disk = (string) ($evidence->disk ?: 'local');

        try {
            if (strtolower(trim($disk)) === ResearchEvidenceStorageService::RCLONE_DISK) {
                /** @var ResearchEvidenceStorageService $storage */
                $storage = app(ResearchEvidenceStorageService::class);
                $storage->deleteFromRclone((string) $evidence->path);
            } elseif (config("filesystems.disks.{$disk}") && Storage::disk($disk)->exists($evidence->path)) {
                Storage::disk($disk)->delete($evidence->path);
            }
        } catch (RuntimeException $exception) {
            Log::error('lecturer.hours.evidence_delete_failed', [
                'evidence_id' => (int) $evidenceId,
                'activity_id' => (int) $evidence->activity_id,
                'disk' => $disk,
                'path' => (string) $evidence->path,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể xóa minh chứng trên kho lưu trữ.',
                'code' => 'EVIDENCE_DELETE_STORAGE_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::table('evidence_files')->where('id', $evidenceId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa minh chứng.',
            'data' => [
                'evidence_id' => (int) $evidenceId,
                'activity_id' => (int) $evidence->activity_id,
            ],
        ], Response::HTTP_OK);
    }

    public function downloadEvidence(Request $request, int $evidenceId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $file = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ef.activity_id', '=', 'ra.id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturer) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', (int) $lecturer->id);
            })
            ->where('ef.id', $evidenceId)
            ->where(function ($query) use ($lecturer) {
                $query->where('ra.owner_lecturer_id', (int) $lecturer->id)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->select([
                'ef.id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
            ])
            ->first();

        if (! $file) {
            return response()->json(['message' => 'Không tìm thấy minh chứng.'], Response::HTTP_NOT_FOUND);
        }

        $disk = $file->disk ?: 'local';
        $path = $file->path;
        $filename = $file->original_name ?: ('evidence-' . $file->id);

        return StorageDownload::stream($disk, $path, $filename, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ]);
    }

    private function resolveLecturer(Request $request)
    {
        return $request->user()?->lecturer;
    }

    private function resolveStageId(string $code): ?int
    {
        $query = DB::table('approval_stages');
        $id = $query->where('code', $code)->value('id');
        if ($id) {
            return (int) $id;
        }

        $codeAliases = match ($code) {
            'hours' => ['hours', 'hours_approval', 'duyet_gio', 'xet_duyet_gio'],
            default => [$code],
        };

        $id = DB::table('approval_stages')
            ->whereIn('code', $codeAliases)
            ->value('id');
        if ($id) {
            return (int) $id;
        }

        $nameAliases = match ($code) {
            'hours' => ['Hours Approval', 'Duyệt giờ', 'Xét duyệt giờ', 'Duyệt giờ NCKH'],
            default => [],
        };

        if ($nameAliases !== []) {
            $id = DB::table('approval_stages')
                ->whereIn('name', $nameAliases)
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return $id ? (int) $id : null;
    }

    private function resolveStatusId(string $code): ?int
    {
        $id = DB::table('activity_statuses')->where('code', $code)->value('id');
        if ($id) {
            return (int) $id;
        }

        $codeAliases = match ($code) {
            'approved' => ['approved', 'da_duyet', 'khoa_duyet'],
            default => [$code],
        };

        $id = DB::table('activity_statuses')
            ->whereIn('code', $codeAliases)
            ->value('id');
        if ($id) {
            return (int) $id;
        }

        $nameAliases = match ($code) {
            'approved' => ['Đã duyệt', 'Khoa duyệt', 'Approved'],
            default => [],
        };

        if ($nameAliases !== []) {
            $id = DB::table('activity_statuses')
                ->whereIn('name', $nameAliases)
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function baseQuery(int $lecturerId, int $hoursStageId, int $approvedStatusId)
    {
        $evidenceCountSubQuery = $this->validEvidenceBaseQuery()
            ->selectRaw('activity_id, COUNT(*) as evidence_count')
            ->groupBy('activity_id');

        $memberStatsSubQuery = DB::table('research_activity_members as ram_stats')
            ->join('research_activities as ra_stats', 'ram_stats.activity_id', '=', 'ra_stats.id')
            ->leftJoin('member_roles as mr_stats', 'ram_stats.member_role_id', '=', 'mr_stats.id')
            ->selectRaw("
                ram_stats.activity_id,
                SUM(CASE
                    WHEN ram_stats.confirmation_status = 'accepted'
                        OR ram_stats.lecturer_id = ra_stats.owner_lecturer_id
                    THEN 1 ELSE 0
                END) as member_count,
                SUM(CASE
                    WHEN (
                        ram_stats.confirmation_status = 'accepted'
                        OR ram_stats.lecturer_id = ra_stats.owner_lecturer_id
                    ) AND mr_stats.code IN ('principal', 'chief_editor')
                    THEN 1 ELSE 0
                END) as principal_count
            ")
            ->groupBy('ram_stats.activity_id');

        return DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoinSub($evidenceCountSubQuery, 'efc', function ($join) {
                $join->on('efc.activity_id', '=', 'ra.id');
            })
            ->leftJoinSub($memberStatsSubQuery, 'rms', function ($join) {
                $join->on('rms.activity_id', '=', 'ra.id');
            })
            ->leftJoin('activity_member_approvals as ama_hours', function ($join) use ($hoursStageId, $lecturerId) {
                $join->on('ama_hours.activity_id', '=', 'ra.id')
                    ->where('ama_hours.stage_id', '=', $hoursStageId)
                    ->where('ama_hours.lecturer_id', '=', $lecturerId);
            })
            ->leftJoin('activity_approvals as aa_hours', function ($join) use ($hoursStageId) {
                $join->on('aa_hours.activity_id', '=', 'ra.id')
                    ->where('aa_hours.stage_id', '=', $hoursStageId);
            })
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->where('ra.status_id', $approvedStatusId)
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.total_hours_calc',
                'ra.quantity',
                'ra.kind_id',
                'ra.type_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'at.code as type_code',
                'at.name as type_name',
                'mr.name as member_role_name',
                'mr.code as member_role_code',
                'ram.hours_assigned',
                'ram.hours_claimed_before',
                'ram.contribution_share',
                'ast.code as activity_status_code',
                DB::raw('COALESCE(ama_hours.status, aa_hours.status) as hours_approval_status'),
                DB::raw('COALESCE(ama_hours.note, aa_hours.note) as hours_approval_note'),
                DB::raw('COALESCE(efc.evidence_count, 0) as evidence_count'),
                DB::raw('COALESCE(rms.member_count, 1) as member_count'),
                DB::raw('COALESCE(rms.principal_count, 0) as principal_count'),
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
                'ra.start_date',
                'ra.end_date',
                'ra.updated_at',
            ]);
    }

    private function detailQuery(int $lecturerId, int $hoursStageId, int $approvedStatusId)
    {
        return $this->baseQuery($lecturerId, $hoursStageId, $approvedStatusId)
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->addSelect([
                'pd.journal_name',
                'bd.publisher',
                'prd.project_code',
                'cd.conference_name',
                'cd.location',
            ]);
    }

    private function applyFilters($query, array $filters): void
    {
        $this->applyScopeFilters($query, $filters);
        $this->applyHoursStatusFilter($query, $filters['status'] ?? null);
        $this->applyMissingEvidenceOnlyFilter($query, (bool) ($filters['missing_evidence_only'] ?? false));
    }

    private function applyScopeFilters($query, array $filters): void
    {
        if (! empty($filters['academic_year_id'])) {
            $query->where('ra.academic_year_id', (int) $filters['academic_year_id']);
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhere('ra.activity_code', 'like', $keyword);
            });
        }
    }

    private function applyHoursStatusFilter($query, ?string $status): void
    {
        if (! $status || $status === 'all') {
            return;
        }

        $normalized = strtolower(trim($status));
        $revisionMarker = '%"decision_mode":"revision"%';
        $statusExpr = 'COALESCE(ama_hours.status, aa_hours.status)';
        $noteExpr = 'COALESCE(ama_hours.note, aa_hours.note)';
        if (in_array($normalized, ['not_submitted', 'hours_not_submitted'], true)) {
            $query->whereRaw("{$statusExpr} IS NULL");
        } elseif (in_array($normalized, ['pending', 'hours_pending_faculty'], true)) {
            $query->whereRaw("{$statusExpr} = 'pending'");
        } elseif (in_array($normalized, ['approved', 'hours_approved'], true)) {
            $query->whereRaw("{$statusExpr} = 'approved'");
        } elseif (in_array($normalized, ['need_revision', 'hours_need_revision'], true)) {
            $query->whereRaw("{$statusExpr} = 'rejected'")
                ->whereRaw("{$noteExpr} LIKE ?", [$revisionMarker]);
        } elseif (in_array($normalized, ['rejected', 'hours_rejected'], true)) {
            $query->whereRaw("{$statusExpr} = 'rejected'")
                ->where(function ($sub) use ($revisionMarker) {
                    $sub->whereRaw('COALESCE(ama_hours.note, aa_hours.note) IS NULL')
                        ->orWhereRaw('COALESCE(ama_hours.note, aa_hours.note) NOT LIKE ?', [$revisionMarker]);
                });
        }
    }

    private function applyMissingEvidenceOnlyFilter($query, bool $missingEvidenceOnly): void
    {
        if (! $missingEvidenceOnly) {
            return;
        }

        $query
            ->whereRaw('COALESCE(ama_hours.status, aa_hours.status) IS NULL')
            ->whereRaw('COALESCE(efc.evidence_count, 0) = 0');
    }

    private function buildMissingEvidenceSummary(
        int $lecturerId,
        int $hoursStageId,
        int $approvedStatusId,
        array $filters
    ): array {
        $query = $this->baseQuery($lecturerId, $hoursStageId, $approvedStatusId);
        $this->applyScopeFilters($query, $filters);
        $this->applyHoursStatusFilter($query, 'hours_not_submitted');
        $this->applyMissingEvidenceOnlyFilter($query, true);

        $items = $query
            ->orderByDesc('ra.updated_at')
            ->get()
            ->map(fn($row) => $this->mapListItem($row));

        return [
            'missing_evidence_count' => $items->count(),
            'missing_evidence_hours_total' => round(
                (float) $items->sum(fn(array $item) => (float) ($item['effective_hours_display'] ?? 0)),
                2
            ),
        ];
    }

    private function validEvidenceBaseQuery(string $alias = 'evidence_files')
    {
        $table = $alias === 'evidence_files'
            ? 'evidence_files'
            : 'evidence_files as ' . $alias;

        return DB::table($table)
            ->where($alias . '.disk', '<>', ResearchEvidenceStorageService::LINK_DISK);
    }

    private function validEvidenceCountByActivity(array $activityIds)
    {
        if (empty($activityIds)) {
            return collect();
        }

        return $this->validEvidenceBaseQuery()
            ->selectRaw('activity_id, COUNT(*) as total')
            ->whereIn('activity_id', $activityIds)
            ->groupBy('activity_id')
            ->pluck('total', 'activity_id');
    }

    private function approvedCount(int $lecturerId, int $approvedStatusId, ?int $academicYearId = null): int
    {
        $query = DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->where('ra.status_id', $approvedStatusId)
            ->distinct('ra.id');

        if ($academicYearId) {
            $query->where('ra.academic_year_id', $academicYearId);
        }

        return (int) $query->count('ra.id');
    }

    private function eligibleActivityIds(int $lecturerId, int $approvedStatusId, array $activityIds): array
    {
        if (empty($activityIds)) {
            return [];
        }

        return DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->where('ra.status_id', $approvedStatusId)
            ->whereIn('ra.id', $activityIds)
            ->distinct()
            ->pluck('ra.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function resolveAcademicYearForCalculate(
        int $lecturerId,
        int $_approvedStatusId,
        ?int $requestedAcademicYearId
    ): ?object {
        if ($requestedAcademicYearId) {
            return AcademicYearResolver::resolve($requestedAcademicYearId);
        }

        $currentAcademicYear = AcademicYearResolver::current();
        if ($currentAcademicYear) {
            return $currentAcademicYear;
        }

        $yearIdWithData = DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->orderByDesc('ay.is_active')
            ->orderByDesc('ay.start_date')
            ->value('ra.academic_year_id');

        if ($yearIdWithData) {
            $resolved = AcademicYearResolver::resolve((int) $yearIdWithData);
            if ($resolved) {
                return $resolved;
            }
        }

        return AcademicYearResolver::current();
    }

    private function mapListItem(object $row): array
    {
        $hoursMeta = $this->resolveHoursMeta(
            $row->hours_approval_status ?? null,
            $row->hours_approval_note ?? null
        );
        $evidenceCount = (int) ($row->evidence_count ?? 0);
        $hoursValues = $this->resolveHoursValues(
            (int) $row->kind_id,
            $row->type_id ? (int) $row->type_id : null,
            $row->academic_year_id ? (int) $row->academic_year_id : null,
            $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
            $row->total_hours_calc !== null ? (float) $row->total_hours_calc : null,
            $row->quantity !== null ? (int) $row->quantity : null,
            $row->contribution_share !== null ? (float) $row->contribution_share : null,
            $row->member_role_code ? (string) $row->member_role_code : null,
            $row->member_count !== null ? (int) $row->member_count : 1,
            $row->principal_count !== null ? (int) $row->principal_count : 0,
            (int) $row->activity_id,
            $row->title ? (string) $row->title : null,
            $row->kind_code ? (string) $row->kind_code : null,
            $row->kind_name ? (string) $row->kind_name : null,
            $row->type_code ? (string) $row->type_code : null,
            $row->type_name ? (string) $row->type_name : null,
            $row->academic_year_code ? (string) $row->academic_year_code : null,
            $row->hours_claimed_before !== null ? (float) $row->hours_claimed_before : null,
            $row->start_date ? (string) $row->start_date : null,
            $row->end_date ? (string) $row->end_date : null
        );

        return [
            'activity_id' => (int) $row->activity_id,
            'activity_code' => $row->activity_code,
            'academic_year_code' => $row->academic_year_code,
            'title' => $row->title,
            'kind_name' => $this->mapKindName($row->kind_code ?? null, $row->kind_name ?? null),
            'member_role_name' => $this->mapRoleName($row->member_role_code ?? null, $row->member_role_name ?? null),
            'hours_assigned' => $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
            'rule_summary' => $hoursValues['rule_summary'],
            'conversion_rule_present' => $hoursValues['conversion_rule_present'],
            'calculated_hours' => $hoursValues['calculated_hours'],
            'proposed_hours' => $hoursValues['proposed_hours'],
            'effective_hours_display' => $hoursValues['effective_hours_display'],
            'total_hours_activity' => $hoursValues['total_hours_activity'],
            'member_hours' => $hoursValues['member_hours'],
            'formula_explanation' => $hoursValues['formula_explanation'],
            'can_edit_proposed_hours' => $hoursValues['can_edit_proposed_hours'],
            'evidence_required' => self::EVIDENCE_REQUIRED,
            'deadline_flags' => [
                'submission_deadline_enforced' => false,
                'approval_deadline_enforced' => false,
            ],
            'activity_status_code' => $row->activity_status_code,
            'hours_request_state' => $hoursMeta['state'],
            'hours_rejection_reason' => $hoursMeta['rejection_reason'],
            'hours_rejection_reason_code' => $hoursMeta['rejection_reason_code'],
            'hours_rejection_reason_detail' => $hoursMeta['rejection_reason_detail'],
            'next_action_code' => $hoursMeta['next_action_code'],
            'next_action_text' => $hoursMeta['next_action_text'],
            'evidence_count' => $evidenceCount,
            'valid_evidence_count' => $evidenceCount,
            'has_valid_evidence' => $evidenceCount > 0,
            'can_submit_hours' => $this->canSubmitHours(
                $hoursMeta['state'],
                $hoursValues['effective_hours_display'],
                $evidenceCount
            ),
        ];
    }

    private function canSubmitHours(string $hoursRequestState, ?float $effectiveHoursDisplay, int $evidenceCount): bool
    {
        return in_array($hoursRequestState, ['hours_not_submitted', 'hours_need_revision'], true)
            && $effectiveHoursDisplay !== null
            && $evidenceCount > 0;
    }

    private function resolveHoursMeta(?string $hoursStatus, ?string $note): array
    {
        $normalized = $hoursStatus ? strtolower(trim($hoursStatus)) : null;
        $rejectMeta = $this->resolveRejectionMeta($note);

        if (! $normalized) {
            return [
                'state' => 'hours_not_submitted',
                'rejection_reason' => null,
                'rejection_reason_code' => null,
                'rejection_reason_detail' => null,
                'next_action_code' => 'submit_hours',
                'next_action_text' => 'Tải tối thiểu 1 minh chứng PDF và bấm Gửi duyệt giờ',
            ];
        }

        if ($normalized === 'approved') {
            return [
                'state' => 'hours_approved',
                'rejection_reason' => null,
                'rejection_reason_code' => null,
                'rejection_reason_detail' => null,
                'next_action_code' => 'none',
                'next_action_text' => 'Đã duyệt giờ',
            ];
        }

        if ($normalized === 'rejected') {
            if ($this->isNeedRevisionNote($note)) {
                return [
                    'state' => 'hours_need_revision',
                    'rejection_reason' => $rejectMeta['reason_text'],
                    'rejection_reason_code' => $rejectMeta['reason_code'],
                    'rejection_reason_detail' => $rejectMeta['reason_detail'],
                    'next_action_code' => 'resubmit_hours',
                    'next_action_text' => 'Cần chỉnh sửa và gửi lại theo góp ý của khoa',
                ];
            }

            return [
                'state' => 'hours_rejected',
                'rejection_reason' => $rejectMeta['reason_text'],
                'rejection_reason_code' => $rejectMeta['reason_code'],
                'rejection_reason_detail' => $rejectMeta['reason_detail'],
                'next_action_code' => 'none',
                'next_action_text' => 'Khoa đã từ chối hồ sơ giờ',
            ];
        }

        return [
            'state' => 'hours_pending_faculty',
            'rejection_reason' => null,
            'rejection_reason_code' => null,
            'rejection_reason_detail' => null,
            'next_action_code' => 'wait_faculty',
            'next_action_text' => 'Chờ khoa duyệt giờ',
        ];
    }

    private function resolveRejectionReason(?string $note): ?string
    {
        $meta = $this->resolveRejectionMeta($note);
        return $meta['reason_text'];
    }

    private function resolveRejectionMeta(?string $note): array
    {
        if (! $note || trim($note) === '') {
            return [
                'reason_code' => null,
                'reason_detail' => null,
                'reason_text' => null,
            ];
        }

        $decoded = json_decode($note, true);
        if (! is_array($decoded)) {
            $raw = trim($note);
            return [
                'reason_code' => null,
                'reason_detail' => $raw !== '' ? $raw : null,
                'reason_text' => $raw !== '' ? $raw : null,
            ];
        }

        $reasonCode = $this->canonicalReasonCode($decoded['reason_code'] ?? null);
        $reasonDetail = isset($decoded['reason_detail']) ? trim((string) $decoded['reason_detail']) : '';
        $reasonText = $reasonDetail !== ''
            ? $reasonDetail
            : $this->reasonCodeLabel($reasonCode);

        return [
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail !== '' ? $reasonDetail : null,
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

    private function isNeedRevisionNote(?string $note): bool
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

    private function resolvePublicationOrUnit(object $row): string
    {
        $candidates = [
            $row->journal_name ?? null,
            $row->publisher ?? null,
            $row->project_code ?? null,
            $row->conference_name ?? null,
            $row->location ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return '-';
    }

    private function mapKindName(?string $code, ?string $fallback): ?string
    {
        if ($fallback !== null && trim($fallback) !== '') {
            return $fallback;
        }

        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'paper' => 'Bài báo / Báo cáo khoa học',
            'book' => 'Sách/Giáo trình',
            'project' => 'Đề tài KH&CN',
            'conference' => 'Hội nghị/Hội thảo',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
    }

    private function mapRoleName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'principal' => 'Chủ nhiệm',
            'secretary' => 'Thư ký',
            'member' => 'Thành viên',
            'coauthor' => 'Đồng tác giả',
            'corresponding_author' => 'Tác giả chính',
            'chief_editor' => 'Chủ biên',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
    }

    private function resolveHoursValues(
        int $kindId,
        ?int $typeId,
        ?int $academicYearId,
        ?float $hoursAssigned,
        ?float $totalHoursCalc = null,
        ?int $quantity = null,
        ?float $contributionShare = null,
        ?string $memberRoleCode = null,
        ?int $memberCount = null,
        ?int $principalCount = null,
        ?int $activityId = null,
        ?string $activityTitle = null,
        ?string $kindCode = null,
        ?string $kindName = null,
        ?string $typeCode = null,
        ?string $typeName = null,
        ?string $academicYearCode = null,
        ?float $hoursClaimedBefore = null,
        ?string $activityStartDate = null,
        ?string $activityEndDate = null
    ): array {
        $resolvedTypeId = $this->resolveRuleTypeId(
            $kindId,
            $typeId,
            $activityId,
            $activityTitle,
            $kindCode,
            $typeCode
        );

        $rule = $this->hoursRuleResolver->resolveForActivity($kindId, $resolvedTypeId, $academicYearId);
        $rulePresent = $rule !== null;
        $previewMembers = $this->buildPreviewMembers(
            $memberRoleCode,
            $memberCount,
            $principalCount,
            $hoursClaimedBefore
        );

        $calculation = $this->hoursCalculationService->calculate(
            $rule,
            $quantity,
            $previewMembers,
            [
                'kind_code' => $kindCode,
                'start_date' => $activityStartDate,
                'end_date' => $activityEndDate,
                'executed_at' => now(),
            ]
        );

        $calculatedMember = $calculation['members'][0] ?? null;

        $totalHoursActivity = $totalHoursCalc ?? ($calculation['total_hours_activity'] ?? null);
        $memberHours = $hoursAssigned;

        if ($memberHours === null && is_array($calculatedMember)) {
            $memberHours = isset($calculatedMember['hours_assigned']) ? (float) $calculatedMember['hours_assigned'] : null;
        }
        if ($memberHours === null && $totalHoursActivity !== null && $contributionShare !== null) {
            $memberHours = round($totalHoursActivity * $contributionShare, 2);
        }

        $memberSharePercent = null;
        if ($totalHoursActivity !== null && $totalHoursActivity > 0 && $memberHours !== null) {
            $memberSharePercent = round(($memberHours / $totalHoursActivity) * 100, 2);
        } elseif ($contributionShare !== null) {
            $memberSharePercent = round($contributionShare * 100, 2);
        }

        $formula = $calculation['formula'] ?? [];
        $progressMultiplier = isset($formula['progress_multiplier'])
            ? (float) $formula['progress_multiplier']
            : 1.0;
        $progressPercent = round($progressMultiplier * 100, 2);
        $ruleName = $rulePresent
            ? $this->buildRuleName($rule, (string) ($rule->distribution_strategy ?? 'unknown'))
            : 'Chưa có quy tắc quy đổi';

        $formulaExplanation = [
            'rule_name' => $ruleName,
            'distribution_strategy' => $formula['distribution_strategy'] ?? ($rule?->distribution_strategy ?? null),
            'base_hours' => $formula['base_hours'] ?? null,
            'modifiers' => $this->buildFormulaModifiers($formula),
            'total_hours_activity' => $totalHoursActivity,
            'member_hours' => $memberHours,
            'member_calculated_hours' => is_array($calculatedMember)
                ? ($calculatedMember['calculated_hours'] ?? null)
                : null,
            'hours_claimed_before' => is_array($calculatedMember)
                ? ($calculatedMember['hours_claimed_before'] ?? ($hoursClaimedBefore ?? 0.0))
                : ($hoursClaimedBefore ?? 0.0),
            'hours_to_add' => is_array($calculatedMember)
                ? ($calculatedMember['hours_to_add'] ?? $memberHours)
                : $memberHours,
            'progress_multiplier' => $progressMultiplier,
            'progress_percent' => $progressPercent,
            'member_share_percent' => $memberSharePercent,
            'member_role_code' => $memberRoleCode,
            'contribution_share' => $contributionShare,
            'progress' => $progressPercent,
            'role' => $memberRoleCode,
            'claimed_before' => is_array($calculatedMember)
                ? ($calculatedMember['hours_claimed_before'] ?? ($hoursClaimedBefore ?? 0.0))
                : ($hoursClaimedBefore ?? 0.0),
            'final_hours' => $memberHours,
            'explainability' => [
                'base_hours' => $formula['base_hours'] ?? null,
                'progress' => $progressPercent,
                'role' => $memberRoleCode,
                'claimed_before' => is_array($calculatedMember)
                    ? ($calculatedMember['hours_claimed_before'] ?? ($hoursClaimedBefore ?? 0.0))
                    : ($hoursClaimedBefore ?? 0.0),
                'final_hours' => $memberHours,
            ],
        ];

        $calculatedHours = $rulePresent
            ? (is_array($calculatedMember)
                ? (isset($calculatedMember['calculated_hours']) ? (float) $calculatedMember['calculated_hours'] : $memberHours)
                : $memberHours)
            : null;
        $proposedHours = null;
        $effectiveHours = $rulePresent ? $memberHours : null;
        $ruleSummary = $rulePresent
            ? $this->hoursRuleResolver->formatRuleSummary($rule)
            : $this->buildMissingRuleSummary(
                $kindId,
                $resolvedTypeId,
                $academicYearId,
                $kindCode,
                $kindName,
                $typeCode,
                $typeName,
                $academicYearCode
            );

        return [
            'rule_summary' => $ruleSummary,
            'conversion_rule_present' => $rulePresent,
            'calculated_hours' => $calculatedHours,
            'proposed_hours' => $proposedHours,
            'effective_hours_display' => $effectiveHours,
            'total_hours_activity' => $totalHoursActivity,
            'member_hours' => $memberHours,
            'formula_explanation' => $formulaExplanation,
            'can_edit_proposed_hours' => false,
        ];
    }

    private function buildFormulaModifiers(array $formula): array
    {
        $modifiers = [];

        $push = static function (string $name, $value) use (&$modifiers): void {
            if ($value === null || $value === '') {
                return;
            }

            if (is_bool($value)) {
                $value = $value ? 'Có' : 'Không';
            }

            if (is_float($value)) {
                $value = round($value, 4);
            }

            $modifiers[] = [
                'name' => $name,
                'value' => $value,
            ];
        };

        $progressReason = isset($formula['progress_reason']) ? (string) $formula['progress_reason'] : '';
        if ($progressReason !== '') {
            $push('Nguồn tiến độ', $this->progressReasonLabel($progressReason));
        }

        $progressWindow = $formula['progress_window'] ?? null;
        if (is_array($progressWindow)) {
            $start = isset($progressWindow['start_date']) ? (string) $progressWindow['start_date'] : '';
            $end = isset($progressWindow['end_date']) ? (string) $progressWindow['end_date'] : '';
            if ($start !== '' || $end !== '') {
                $push('Mốc thời gian', trim(($start !== '' ? $start : '?') . ' -> ' . ($end !== '' ? $end : '?')));
            }
        }

        $push('Số lần quy đổi', $formula['effective_occurrences'] ?? null);
        $push('Giới hạn số lần trong năm', $formula['max_occurrences_per_year'] ?? null);
        $push('Giờ chủ nhiệm áp dụng', $formula['leader_hours_total'] ?? null);
        $push('Quỹ giờ thành viên', $formula['member_pool_total'] ?? null);
        $push('Quỹ giờ thành viên áp dụng', $formula['member_pool_applied_total'] ?? null);
        $push('Số chủ nhiệm/chủ biên', $formula['principal_count'] ?? null);
        $push('Số thành viên', $formula['non_principal_count'] ?? null);
        $push('Tỷ lệ chủ nhiệm/chủ biên', $formula['principal_fraction'] ?? null);
        $push('Tỷ lệ nhóm thành viên', $formula['others_fraction_total'] ?? null);

        return $modifiers;
    }

    private function progressReasonLabel(string $reason): string
    {
        return match ($reason) {
            'progress_disabled' => 'Không áp dụng tiến độ',
            'missing_dates_fallback_full_hours' => 'Thiếu mốc thời gian, dùng 100%',
            'start_date_in_future' => 'Đề tài chưa bắt đầu',
            'already_finished' => 'Đề tài đã kết thúc',
            'invalid_duration_fallback_full_hours' => 'Mốc thời gian không hợp lệ, dùng 100%',
            'time_based_progress' => 'Tính theo thời gian hệ thống',
            default => $reason,
        };
    }

    private function buildPreviewMembers(
        ?string $memberRoleCode,
        ?int $memberCount,
        ?int $principalCount,
        ?float $hoursClaimedBefore
    ): array {
        $normalizedMemberCount = max(1, (int) ($memberCount ?? 1));
        $normalizedPrincipalCount = max(0, min($normalizedMemberCount, (int) ($principalCount ?? 0)));

        $normalizedRoleCode = strtolower(trim((string) ($memberRoleCode ?? '')));
        if ($normalizedRoleCode === '') {
            $normalizedRoleCode = $normalizedPrincipalCount > 0 ? 'principal' : 'member';
        }

        $currentIsPrincipal = in_array($normalizedRoleCode, ['principal', 'chief_editor'], true);
        $remainingPrincipals = max(0, $normalizedPrincipalCount - ($currentIsPrincipal ? 1 : 0));
        $remainingMembers = max(0, ($normalizedMemberCount - 1) - $remainingPrincipals);

        $previewMembers = [
            (object) [
                'id' => 1,
                'lecturer_id' => 1,
                'member_role_code' => $normalizedRoleCode,
                'owner_lecturer_id' => 1,
                'hours_claimed_before' => $hoursClaimedBefore ?? 0.0,
            ],
        ];

        $cursor = 2;
        for ($i = 0; $i < $remainingPrincipals; $i++) {
            $previewMembers[] = (object) [
                'id' => $cursor,
                'lecturer_id' => $cursor,
                'member_role_code' => 'principal',
                'owner_lecturer_id' => 1,
                'hours_claimed_before' => 0.0,
            ];
            $cursor++;
        }

        for ($i = 0; $i < $remainingMembers; $i++) {
            $previewMembers[] = (object) [
                'id' => $cursor,
                'lecturer_id' => $cursor,
                'member_role_code' => 'member',
                'owner_lecturer_id' => 1,
                'hours_claimed_before' => 0.0,
            ];
            $cursor++;
        }

        while (count($previewMembers) < $normalizedMemberCount) {
            $previewMembers[] = (object) [
                'id' => $cursor,
                'lecturer_id' => $cursor,
                'member_role_code' => 'member',
                'owner_lecturer_id' => 1,
                'hours_claimed_before' => 0.0,
            ];
            $cursor++;
        }

        return $previewMembers;
    }

    private function resolveRuleTypeId(
        int $kindId,
        ?int $typeId,
        ?int $activityId,
        ?string $activityTitle,
        ?string $kindCode,
        ?string $typeCode
    ): ?int {
        if ($typeId !== null) {
            return $typeId;
        }

        $normalizedKindCode = strtolower(trim((string) ($kindCode ?? '')));
        if ($normalizedKindCode === '') {
            $normalizedKindCode = strtolower((string) (DB::table('activity_kinds')->where('id', $kindId)->value('code') ?? ''));
        }

        if ($normalizedKindCode === 'book') {
            $bookSignal = $this->resolveBookSubtypeSignal($activityId, $activityTitle, $typeCode);
            if ($bookSignal === 'textbook') {
                return $this->lookupTypeIdByAliases($kindId, ['textbook', 'giao_trinh', 'book_textbook']);
            }
            if ($bookSignal === 'reference') {
                return $this->lookupTypeIdByAliases($kindId, ['reference', 'tai_lieu', 'tham_khao', 'book_reference']);
            }
        }

        if ($normalizedKindCode === 'conference') {
            $conferenceSignal = $this->resolveConferenceSubtypeSignal($activityId, $activityTitle, $typeCode);
            if ($conferenceSignal === 'attend') {
                return $this->lookupTypeIdByAliases($kindId, ['attend', 'tham_du', 'conference_attend']);
            }
            if ($conferenceSignal === 'report') {
                return $this->lookupTypeIdByAliases($kindId, ['report', 'bao_cao', 'presentation', 'conference_report']);
            }
        }

        return null;
    }

    private function resolveBookSubtypeSignal(?int $activityId, ?string $activityTitle, ?string $typeCode): ?string
    {
        $normalized = $this->normalizeToken(((string) $activityTitle) . ' ' . ((string) $typeCode));
        if ($this->containsAny($normalized, ['textbook', 'giao_trinh'])) {
            return 'textbook';
        }
        if ($this->containsAny($normalized, ['reference', 'tham_khao', 'tai_lieu'])) {
            return 'reference';
        }

        if (! $activityId) {
            return null;
        }

        $row = DB::table('book_details')
            ->where('activity_id', $activityId)
            ->select(['isbn', 'approval_decision_no', 'approval_decision_date'])
            ->first();
        if (! $row) {
            return null;
        }

        $hasTextbookSignals = trim((string) ($row->isbn ?? '')) !== ''
            || trim((string) ($row->approval_decision_no ?? '')) !== ''
            || ! empty($row->approval_decision_date);

        return $hasTextbookSignals ? 'textbook' : 'reference';
    }

    private function resolveConferenceSubtypeSignal(?int $activityId, ?string $activityTitle, ?string $typeCode): ?string
    {
        $buffer = ((string) $activityTitle) . ' ' . ((string) $typeCode);
        if ($activityId) {
            $conferenceName = DB::table('conference_details')
                ->where('activity_id', $activityId)
                ->value('conference_name');
            if ($conferenceName) {
                $buffer .= ' ' . (string) $conferenceName;
            }
        }

        $normalized = $this->normalizeToken($buffer);
        if ($this->containsAny($normalized, ['attend', 'tham_du', 'thamdu', 'participant'])) {
            return 'attend';
        }
        if ($this->containsAny($normalized, ['report', 'bao_cao', 'presentation', 'present'])) {
            return 'report';
        }

        return null;
    }

    private function lookupTypeIdByAliases(int $kindId, array $aliases): ?int
    {
        $normalizedAliases = array_values(array_unique(array_filter(array_map(
            fn($alias) => $this->normalizeToken((string) $alias),
            $aliases
        ))));

        if ($normalizedAliases === []) {
            return null;
        }

        $id = DB::table('activity_types')
            ->where('kind_id', $kindId)
            ->where(function ($query) use ($normalizedAliases) {
                foreach ($normalizedAliases as $alias) {
                    $query->orWhereRaw('LOWER(code) = ?', [$alias])
                        ->orWhereRaw('LOWER(code) LIKE ?', ['%' . $alias . '%']);
                }
            })
            ->orderByDesc('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function buildMissingRuleSummary(
        int $kindId,
        ?int $typeId,
        ?int $academicYearId,
        ?string $kindCode,
        ?string $kindName,
        ?string $typeCode,
        ?string $typeName,
        ?string $academicYearCode
    ): string {
        $yearLabel = trim((string) ($academicYearCode ?? ''));
        if ($yearLabel === '' && $academicYearId) {
            $yearLabel = (string) (DB::table('academic_years')->where('id', $academicYearId)->value('code') ?? '');
        }
        if ($yearLabel === '') {
            $yearLabel = 'Chưa xác định năm học';
        }

        $kindCodeResolved = strtolower(trim((string) ($kindCode ?? '')));
        $kindLabel = trim((string) ($kindName ?? ''));
        if ($kindLabel === '') {
            if ($kindCodeResolved === '') {
                $kindCodeResolved = strtolower((string) (DB::table('activity_kinds')->where('id', $kindId)->value('code') ?? ''));
            }
            $kindNameDb = DB::table('activity_kinds')->where('id', $kindId)->value('name');
            $kindLabel = $this->mapKindName($kindCodeResolved !== '' ? $kindCodeResolved : null, $kindNameDb ? (string) $kindNameDb : null)
                ?? 'Chưa xác định loại công trình';
        }

        $typeLabel = trim((string) ($typeName ?? ''));
        $typeCodeResolved = strtolower(trim((string) ($typeCode ?? '')));
        if ($typeCodeResolved === '' && $typeId) {
            $typeCodeResolved = strtolower((string) (DB::table('activity_types')->where('id', $typeId)->value('code') ?? ''));
        }
        if ($typeLabel === '') {
            $typeLabel = $this->mapTypeName($kindCodeResolved !== '' ? $kindCodeResolved : null, $typeCodeResolved !== '' ? $typeCodeResolved : null)
                ?? 'Chưa xác định hình thức';
        }

        return sprintf(
            'Chưa cấu hình quy tắc quy đổi cho: %s - %s - Cấp: Mặc định - Hình thức: %s.',
            $yearLabel,
            $kindLabel,
            $typeLabel
        );
    }

    private function mapTypeName(?string $kindCode, ?string $typeCode): ?string
    {
        $kind = strtolower(trim((string) $kindCode));
        $type = strtolower(trim((string) $typeCode));
        if ($kind === '' || $type === '') {
            return null;
        }

        if ($kind === 'book' && $type === 'textbook') {
            return 'Giáo trình';
        }
        if ($kind === 'book' && $type === 'reference') {
            return 'Tài liệu tham khảo';
        }
        if ($kind === 'conference' && $type === 'report') {
            return 'Báo cáo hội thảo';
        }
        if ($kind === 'conference' && $type === 'attend') {
            return 'Tham dự hội thảo';
        }
        if ($kind === 'project' && $type === 'bo') {
            return 'Đề tài cấp Bộ';
        }
        if ($kind === 'project' && $type === 'coso') {
            return 'Đề tài cấp cơ sở';
        }
        if ($kind === 'paper' && $type === 'hdgsnn_900') {
            return 'Bài báo HDGSNN 1-2 điểm';
        }
        if ($kind === 'paper' && $type === 'hdgsnn_600') {
            return 'Bài báo HDGSNN đến 1 điểm';
        }
        if ($kind === 'paper' && $type === 'hdgsnn_300') {
            return 'Bài báo có ISSN/ISBN';
        }

        return strtoupper($type);
    }

    private function normalizeToken(string $value): string
    {
        $ascii = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $ascii);
        return trim((string) $normalized, '_');
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function calculateRuleSnapshot(
        ?object $rule,
        ?int $quantity,
        ?int $memberCount,
        ?int $principalCount,
        ?string $memberRoleCode
    ): array {
        $normalizedQuantity = max(1, (int) ($quantity ?? 1));
        $normalizedMemberCount = max(1, (int) ($memberCount ?? 1));
        $normalizedPrincipalCount = max(0, (int) ($principalCount ?? 0));
        $baseHours = null;
        $modifiers = [];
        $memberHours = null;
        $calculatedTotalByRule = null;

        if (! $rule) {
            return [
                'rule_name' => 'Chưa có quy tắc quy đổi',
                'distribution_strategy' => null,
                'base_hours' => null,
                'modifiers' => [],
                'total_hours_activity' => null,
                'member_hours' => null,
            ];
        }

        $strategy = (string) $rule->distribution_strategy;
        if ($strategy === 'per_lecturer_fixed') {
            $baseHours = $rule->hours_per_occurrence !== null ? (float) $rule->hours_per_occurrence : null;
            $effectiveOccurrences = $normalizedQuantity;
            if ($rule->max_occurrences_per_year !== null) {
                $effectiveOccurrences = min($effectiveOccurrences, (int) $rule->max_occurrences_per_year);
                $modifiers[] = [
                    'name' => 'Giới hạn số lần trong năm',
                    'value' => (int) $rule->max_occurrences_per_year,
                ];
            }
            $modifiers[] = ['name' => 'Số lần được tính', 'value' => $effectiveOccurrences];
            $memberHours = $baseHours !== null ? round($baseHours * $effectiveOccurrences, 2) : null;
            $calculatedTotalByRule = $memberHours !== null
                ? round($memberHours * $normalizedMemberCount, 2)
                : null;
        } elseif ($this->hoursRuleResolver->isProjectPoolRule($rule)) {
            $baseHours = $rule->hours_total_per_activity !== null
                ? (float) $rule->hours_total_per_activity
                : null;
            $leaderHoursTotal = round((float) $rule->hours_total_per_activity * $normalizedQuantity, 2);
            $memberPoolTotal = round((float) $rule->hours_per_occurrence * $normalizedQuantity, 2);

            $nonPrincipalCount = max(0, $normalizedMemberCount - $normalizedPrincipalCount);
            $memberPoolAppliedTotal = $nonPrincipalCount > 0 ? $memberPoolTotal : 0.0;
            $calculatedTotalByRule = round($leaderHoursTotal + $memberPoolAppliedTotal, 2);
            $modifiers[] = ['name' => 'Giờ chủ nhiệm', 'value' => $leaderHoursTotal];
            $modifiers[] = ['name' => 'Quỹ giờ thành viên', 'value' => $memberPoolTotal];
            $modifiers[] = ['name' => 'Quỹ giờ thành viên áp dụng', 'value' => $memberPoolAppliedTotal];
            $modifiers[] = ['name' => 'Số chủ nhiệm/chủ biên', 'value' => $normalizedPrincipalCount];
            $modifiers[] = ['name' => 'Số thành viên', 'value' => $nonPrincipalCount];

            if ($normalizedPrincipalCount === 0) {
                $memberHours = round($calculatedTotalByRule / $normalizedMemberCount, 2);
            } elseif ($this->isPrincipalRole($memberRoleCode)) {
                $memberHours = round($leaderHoursTotal / $normalizedPrincipalCount, 2);
            } else {
                $memberHours = $nonPrincipalCount > 0
                    ? round($memberPoolAppliedTotal / $nonPrincipalCount, 2)
                    : 0.0;
            }
        } elseif ($strategy === 'principal_fraction_others_equal') {
            $baseHours = $rule->hours_total_per_activity !== null ? (float) $rule->hours_total_per_activity : null;
            if ($normalizedQuantity > 1) {
                $modifiers[] = ['name' => 'Số lượng công trình', 'value' => $normalizedQuantity];
            }
            $calculatedTotalByRule = $baseHours !== null ? round($baseHours * $normalizedQuantity, 2) : null;
            $principalFraction = $rule->principal_fraction !== null ? (float) $rule->principal_fraction : 0.2;
            $othersFractionTotal = $rule->others_fraction_total !== null
                ? (float) $rule->others_fraction_total
                : max(0.0, 1 - $principalFraction);

            $modifiers[] = ['name' => 'Tỷ lệ chủ biên', 'value' => $principalFraction];
            $modifiers[] = ['name' => 'Tỷ lệ chia nhóm tác giả', 'value' => $othersFractionTotal];

            $memberHours = null;
            if ($calculatedTotalByRule !== null) {
                $sharedPerMember = round(($calculatedTotalByRule * $othersFractionTotal) / $normalizedMemberCount, 2);
                $principalBonusPool = $calculatedTotalByRule * $principalFraction;
                $principalBonus = $normalizedPrincipalCount > 0
                    ? round($principalBonusPool / $normalizedPrincipalCount, 2)
                    : 0.0;

                $memberHours = $sharedPerMember;
                if ($this->isPrincipalRole($memberRoleCode)) {
                    $memberHours = round($sharedPerMember + $principalBonus, 2);
                }
            }
        } else {
            $baseHours = $rule->hours_total_per_activity !== null ? (float) $rule->hours_total_per_activity : null;
            if ($normalizedQuantity > 1) {
                $modifiers[] = ['name' => 'Số lượng công trình', 'value' => $normalizedQuantity];
            }
            $calculatedTotalByRule = $baseHours !== null ? round($baseHours * $normalizedQuantity, 2) : null;
            $memberHours = $calculatedTotalByRule !== null
                ? round($calculatedTotalByRule / $normalizedMemberCount, 2)
                : null;
        }

        return [
            'rule_name' => $this->buildRuleName($rule, $strategy),
            'distribution_strategy' => $strategy,
            'base_hours' => $baseHours,
            'modifiers' => $modifiers,
            'total_hours_activity' => $calculatedTotalByRule,
            'member_hours' => $memberHours,
        ];
    }

    private function buildRuleName(object $rule, string $strategy): string
    {
        $kindCode = strtolower((string) ($rule->kind_code ?? ''));
        $typeCode = strtolower((string) ($rule->type_code ?? ''));

        return match (true) {
            $kindCode === 'paper' && $typeCode === 'hdgsnn_900' => 'Bài báo HDGSNN 1-2 điểm',
            $kindCode === 'paper' && $typeCode === 'hdgsnn_600' => 'Bài báo HDGSNN đến 1 điểm',
            $kindCode === 'paper' && $typeCode === 'hdgsnn_300' => 'Bài báo có ISSN/ISBN',
            $kindCode === 'book' && $typeCode === 'textbook' => 'Giáo trình ISBN',
            $kindCode === 'book' && $typeCode === 'reference' => 'Tài liệu tham khảo',
            $kindCode === 'project' && $typeCode === 'bo' => 'Đề tài cấp Bộ',
            $kindCode === 'project' && $typeCode === 'coso' => 'Đề tài cấp cơ sở',
            $kindCode === 'conference' && $typeCode === 'report' => 'Hội nghị/Hội thảo - Báo cáo',
            $kindCode === 'conference' && $typeCode === 'attend' => 'Hội nghị/Hội thảo - Tham dự',
            default => 'Quy tắc ' . $strategy,
        };
    }

    private function isPrincipalRole(?string $memberRoleCode): bool
    {
        if (! $memberRoleCode) {
            return false;
        }

        return in_array(strtolower($memberRoleCode), ['principal', 'chief_editor'], true);
    }

    private function resolveAccessibleApprovedActivity(int $lecturerId, int $activityId, int $approvedStatusId): ?object
    {
        return DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where('ra.id', $activityId)
            ->where('ra.status_id', $approvedStatusId)
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->select(['ra.id', 'ra.title', 'ra.kind_id', 'ra.type_id'])
            ->first();
    }

    private function upsertMemberHoursApprovals(
        array $activityIds,
        int $lecturerId,
        int $stageId,
        string $status,
        ?int $decidedByUserId,
        $decidedAt,
        ?string $note,
        $timestamp
    ): void {
        $activityIds = array_values(array_unique(array_map('intval', $activityIds)));
        if ($activityIds === [] || $lecturerId <= 0 || $stageId <= 0) {
            return;
        }

        $records = array_map(function (int $activityId) use (
            $lecturerId,
            $stageId,
            $status,
            $decidedByUserId,
            $decidedAt,
            $note,
            $timestamp
        ) {
            return [
                'activity_id' => $activityId,
                'lecturer_id' => $lecturerId,
                'stage_id' => $stageId,
                'status' => $status,
                'decided_by_user_id' => $decidedByUserId,
                'decided_at' => $decidedAt,
                'note' => $note,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, $activityIds);

        DB::table('activity_member_approvals')->upsert(
            $records,
            ['activity_id', 'lecturer_id', 'stage_id'],
            ['status', 'decided_by_user_id', 'decided_at', 'note', 'updated_at']
        );
    }

    private function resolveHoursApprovalStatus(int $activityId, int $hoursStageId, ?int $lecturerId = null): ?string
    {
        if ($lecturerId && $lecturerId > 0) {
            $memberStatus = DB::table('activity_member_approvals')
                ->where('activity_id', $activityId)
                ->where('stage_id', $hoursStageId)
                ->where('lecturer_id', $lecturerId)
                ->value('status');

            if ($memberStatus) {
                return strtolower((string) $memberStatus);
            }
        }

        $status = DB::table('activity_approvals')
            ->where('activity_id', $activityId)
            ->where('stage_id', $hoursStageId)
            ->value('status');

        return $status ? strtolower((string) $status) : null;
    }

    private function fetchEvidenceFiles(int $activityId): array
    {
        return $this->validEvidenceBaseQuery('ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.file_type_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.sha256',
                'ef.uploaded_by_user_id',
                'ef.uploaded_at',
                'ef.created_at',
                'ef.updated_at',
                'eft.name as file_type_name',
            ])
            ->orderByDesc('ef.uploaded_at')
            ->get()
            ->map(fn($row) => $this->mapEvidenceRow($row))
            ->all();
    }

    private function mapEvidenceRow(object $row): array
    {
        $id = (int) $row->id;

        return [
            'id' => $id,
            'activity_id' => (int) $row->activity_id,
            'file_type_id' => (int) $row->file_type_id,
            'file_type_name' => $row->file_type_name ?? null,
            'disk' => $row->disk,
            'path' => $row->path,
            'original_name' => $row->original_name,
            'mime_type' => $row->mime_type,
            'size_bytes' => (int) $row->size_bytes,
            'sha256' => $row->sha256,
            'uploaded_by_user_id' => (int) $row->uploaded_by_user_id,
            'uploaded_at' => $row->uploaded_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'download_url' => route('lecturer.hours.evidence.download', ['evidence' => $id]),
        ];
    }
}
