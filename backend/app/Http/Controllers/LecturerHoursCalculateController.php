<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerHoursCalculateIndexRequest;
use App\Http\Requests\Lecturer\LecturerHoursCalculateSubmitRequest;
use App\Services\Evidence\ResearchEvidenceStorageService;
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

    public function __construct(
        HoursRuleResolver $hoursRuleResolver,
        HoursRecomputeService $hoursRecomputeService
    ) {
        $this->hoursRuleResolver = $hoursRuleResolver;
        $this->hoursRecomputeService = $hoursRecomputeService;
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

        // Đồng bộ lại giờ cho các công trình đã khoa duyệt để tránh dữ liệu cũ bị lệch quy tắc.
        $this->hoursRecomputeService->recomputeApprovedActivitiesForLecturer(
            (int) $lecturer->id,
            $approvedStatusId,
            $selectedAcademicYearId
        );

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

        $items = collect($paginator->items())->map(fn ($row) => $this->mapListItem($row))->all();
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

        $this->hoursRecomputeService->recomputeActivity((int) $activityId, now(), true);

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
            $row->academic_year_code ? (string) $row->academic_year_code : null
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

        $this->hoursRecomputeService->recomputeActivities($eligibleIds, now());

        $rows = $this->baseQuery((int) $lecturer->id, $hoursStageId, $approvedStatusId)
            ->whereIn('ra.id', $eligibleIds)
            ->get()
            ->keyBy('activity_id');

        $evidenceCountByActivity = $this->validEvidenceCountByActivity($eligibleIds);

        $missingHours = [];
        $missingEvidence = [];

        foreach ($eligibleIds as $activityId) {
            $row = $rows->get($activityId);
            if (! $row) {
                $missingHours[] = [
                    'activity_id' => (int) $activityId,
                    'reason' => 'ACTIVITY_CONTEXT_NOT_FOUND',
                ];
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
                $row->academic_year_code ? (string) $row->academic_year_code : null
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

        DB::transaction(function () use ($eligibleIds, $hoursStageId, $now, &$submittedIds, &$skipped) {
            $existing = DB::table('activity_approvals')
                ->where('stage_id', $hoursStageId)
                ->whereIn('activity_id', $eligibleIds)
                ->get()
                ->keyBy('activity_id');

            foreach ($eligibleIds as $activityId) {
                $row = $existing[$activityId] ?? null;
                if (! $row) {
                    DB::table('activity_approvals')->insert([
                        'activity_id' => $activityId,
                        'stage_id' => $hoursStageId,
                        'status' => 'pending',
                        'decided_by_user_id' => null,
                        'decided_at' => null,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $submittedIds[] = $activityId;
                    continue;
                }

                if ($row->status === 'rejected') {
                    DB::table('activity_approvals')
                        ->where('id', $row->id)
                        ->update([
                            'status' => 'pending',
                            'decided_by_user_id' => null,
                            'decided_at' => null,
                            'note' => null,
                            'updated_at' => $now,
                        ]);
                    $submittedIds[] = $activityId;
                    continue;
                }

                $skipped[] = [
                    'activity_id' => (int) $activityId,
                    'status' => (string) $row->status,
                ];
            }
        });

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

        $hoursStatus = $this->resolveHoursApprovalStatus($activityId, $hoursStageId);
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

        $hoursStatus = $this->resolveHoursApprovalStatus((int) $evidence->activity_id, $hoursStageId);
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
                'ram.contribution_share',
                'ast.code as activity_status_code',
                'aa_hours.status as hours_approval_status',
                'aa_hours.note as hours_approval_note',
                DB::raw('COALESCE(efc.evidence_count, 0) as evidence_count'),
                DB::raw('COALESCE(rms.member_count, 1) as member_count'),
                DB::raw('COALESCE(rms.principal_count, 0) as principal_count'),
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
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
        if (in_array($normalized, ['not_submitted', 'hours_not_submitted'], true)) {
            $query->whereNull('aa_hours.status');
        } elseif (in_array($normalized, ['pending', 'hours_pending_faculty'], true)) {
            $query->where('aa_hours.status', 'pending');
        } elseif (in_array($normalized, ['approved', 'hours_approved'], true)) {
            $query->where('aa_hours.status', 'approved');
        } elseif (in_array($normalized, ['rejected', 'hours_rejected'], true)) {
            $query->where('aa_hours.status', 'rejected');
        }
    }

    private function applyMissingEvidenceOnlyFilter($query, bool $missingEvidenceOnly): void
    {
        if (! $missingEvidenceOnly) {
            return;
        }

        $query
            ->whereNull('aa_hours.status')
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
            ->map(fn ($row) => $this->mapListItem($row));

        return [
            'missing_evidence_count' => $items->count(),
            'missing_evidence_hours_total' => round(
                (float) $items->sum(fn (array $item) => (float) ($item['effective_hours_display'] ?? 0)),
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
            ->map(fn ($id) => (int) $id)
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
            $row->academic_year_code ? (string) $row->academic_year_code : null
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
        return in_array($hoursRequestState, ['hours_not_submitted', 'hours_rejected'], true)
            && $effectiveHoursDisplay !== null
            && $evidenceCount > 0;
    }

    private function resolveHoursMeta(?string $hoursStatus, ?string $note): array
    {
        $normalized = $hoursStatus ? strtolower(trim($hoursStatus)) : null;

        if (! $normalized) {
            return [
                'state' => 'hours_not_submitted',
                'rejection_reason' => null,
                'next_action_code' => 'submit_hours',
                'next_action_text' => 'Tải tối thiểu 1 minh chứng PDF và bấm Gửi duyệt giờ',
            ];
        }

        if ($normalized === 'approved') {
            return [
                'state' => 'hours_approved',
                'rejection_reason' => null,
                'next_action_code' => 'none',
                'next_action_text' => 'Đã duyệt giờ',
            ];
        }

        if ($normalized === 'rejected') {
            return [
                'state' => 'hours_rejected',
                'rejection_reason' => $this->resolveRejectionReason($note),
                'next_action_code' => 'resubmit_hours',
                'next_action_text' => 'Khoa từ chối giờ',
            ];
        }

        return [
            'state' => 'hours_pending_faculty',
            'rejection_reason' => null,
            'next_action_code' => 'wait_faculty',
            'next_action_text' => 'Chờ khoa duyệt giờ',
        ];
    }

    private function resolveRejectionReason(?string $note): ?string
    {
        if (! $note || trim($note) === '') {
            return null;
        }

        $decoded = json_decode($note, true);
        if (is_array($decoded)) {
            $reasonDetail = isset($decoded['reason_detail']) ? trim((string) $decoded['reason_detail']) : '';
            if ($reasonDetail !== '') {
                return $reasonDetail;
            }

            $reasonCode = isset($decoded['reason_code']) ? trim((string) $decoded['reason_code']) : '';
            return match ($reasonCode) {
                'hours_not_reasonable' => 'Giờ quy đổi chưa hợp lý',
                'work_not_eligible' => 'Công trình chưa đủ điều kiện',
                'missing_evidence' => 'Thiếu minh chứng',
                'other' => 'Lý do khác',
                default => $reasonCode !== '' ? $reasonCode : null,
            };
        }

        return trim($note);
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
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'paper' => 'Bài báo',
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
        ?string $academicYearCode = null
    ): array
    {
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
        $ruleSnapshot = $this->calculateRuleSnapshot(
            $rule,
            $quantity,
            $memberCount,
            $principalCount,
            $memberRoleCode
        );

        $totalHoursActivity = $totalHoursCalc ?? $ruleSnapshot['total_hours_activity'];
        $memberHours = $hoursAssigned;

        if ($memberHours === null && $totalHoursActivity !== null && $contributionShare !== null) {
            $memberHours = round($totalHoursActivity * $contributionShare, 2);
        }
        if ($memberHours === null) {
            $memberHours = $ruleSnapshot['member_hours'];
        }

        $memberSharePercent = null;
        if ($totalHoursActivity !== null && $totalHoursActivity > 0 && $memberHours !== null) {
            $memberSharePercent = round(($memberHours / $totalHoursActivity) * 100, 2);
        } elseif ($contributionShare !== null) {
            $memberSharePercent = round($contributionShare * 100, 2);
        }

        $formulaExplanation = [
            'rule_name' => $ruleSnapshot['rule_name'],
            'distribution_strategy' => $ruleSnapshot['distribution_strategy'],
            'base_hours' => $ruleSnapshot['base_hours'],
            'modifiers' => $ruleSnapshot['modifiers'],
            'total_hours_activity' => $totalHoursActivity,
            'member_hours' => $memberHours,
            'member_share_percent' => $memberSharePercent,
            'member_role_code' => $memberRoleCode,
            'contribution_share' => $contributionShare,
        ];

        $calculatedHours = $rulePresent ? $memberHours : null;
        $proposedHours = null;
        $effectiveHours = $calculatedHours;
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
            fn ($alias) => $this->normalizeToken((string) $alias),
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
            return 'Đề tài cấp Trường';
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
            $kindCode === 'project' && $typeCode === 'coso' => 'Đề tài cấp Trường',
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

    private function resolveHoursApprovalStatus(int $activityId, int $hoursStageId): ?string
    {
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
            ->map(fn ($row) => $this->mapEvidenceRow($row))
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
