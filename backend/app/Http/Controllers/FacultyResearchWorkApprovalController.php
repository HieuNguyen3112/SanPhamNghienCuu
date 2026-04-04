<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyWorkApprovalListRequest;
use App\Http\Requests\Faculty\FacultyWorkApprovalRejectRequest;
use App\Services\Hours\HoursRecomputeService;
use App\Services\Hours\RecalculateLecturerYearlyHoursService;
use App\Support\AuditLogger;
use App\Support\ResearchWorkDetailSchemaBuilder;
use App\Support\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyResearchWorkApprovalController extends Controller
{
    private const STATUS_PENDING = 'PENDING_FACULTY_APPROVAL';
    private const STATUS_APPROVED = 'APPROVED_BY_FACULTY_FINAL';
    private const STATUS_REJECTED = 'REJECTED_BY_FACULTY';
    private const STATUS_NEED_REVISION = 'NEED_REVISION_BY_FACULTY';
    private HoursRecomputeService $hoursRecomputeService;
    private RecalculateLecturerYearlyHoursService $recalculateLecturerYearlyHoursService;
    private ResearchWorkDetailSchemaBuilder $researchWorkDetailSchemaBuilder;
    private ?array $stageIdsCache = null;

    public function __construct(
        HoursRecomputeService $hoursRecomputeService,
        RecalculateLecturerYearlyHoursService $recalculateLecturerYearlyHoursService,
        ResearchWorkDetailSchemaBuilder $researchWorkDetailSchemaBuilder
    ) {
        $this->hoursRecomputeService = $hoursRecomputeService;
        $this->recalculateLecturerYearlyHoursService = $recalculateLecturerYearlyHoursService;
        $this->researchWorkDetailSchemaBuilder = $researchWorkDetailSchemaBuilder;
    }

    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $academicYears = Cache::remember('faculty_work_approvals:academic_years:v1', 300, function () {
            return DB::table('academic_years')
                ->select(['id', 'code', 'is_active'])
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->get()
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'is_active' => (bool) $row->is_active,
                ])
                ->all();
        });

        $kinds = Cache::remember('faculty_work_approvals:work_kinds:v1', 300, function () {
            return DB::table('activity_kinds')
                ->select(['id', 'code', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                ])
                ->all();
        });

        return response()->json([
            'data' => [
                'academic_years' => $academicYears,
                'work_kinds' => $kinds,
                'statuses' => [
                    ['code' => 'all', 'name' => 'Tất cả'],
                    ['code' => self::STATUS_PENDING, 'name' => 'Chờ khoa duyệt'],
                    ['code' => self::STATUS_APPROVED, 'name' => 'Đã duyệt cuối cùng tại khoa'],
                    ['code' => self::STATUS_NEED_REVISION, 'name' => 'Yêu cầu chỉnh sửa'],
                    ['code' => self::STATUS_REJECTED, 'name' => 'Bị từ chối ở khoa'],
                ],
                'faculty' => [
                    'id' => $scope['faculty_id'],
                    'name' => $scope['faculty_name'],
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function index(FacultyWorkApprovalListRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $filters = $request->validated();
        $stageIds = $this->getStageIds();

        $query = $this->baseQuery($stageIds, $scope['faculty_id']);
        $this->applyFilters($query, $filters);
        $this->applyStatusFilter($query, $filters['status'] ?? null);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $query->orderByDesc('ra.submitted_at')->orderByDesc('ra.id');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items());
        $activityIds = $items->pluck('activity_id')->all();
        $actingLecturerId = $this->resolveActingLecturerId($request);
        $authorsByActivity = $this->fetchAuthorsByActivity($activityIds);
        $activityIdsParticipatedByActingLecturer = $this->fetchActivityIdsParticipatedByLecturer(
            $activityIds,
            $actingLecturerId
        );

        $rows = $items
            ->map(function ($row) use ($authorsByActivity, $actingLecturerId, $activityIdsParticipatedByActingLecturer) {
                return $this->mapListEntry(
                    $row,
                    $authorsByActivity,
                    $actingLecturerId,
                    $activityIdsParticipatedByActingLecturer
                );
            })
            ->values()
            ->all();

        $counters = $this->buildCounters($stageIds, $scope['faculty_id'], $filters);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'filters' => [
                    'academic_year_id' => $filters['academic_year_id'] ?? null,
                    'kind_code' => $filters['kind_code'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'q' => $filters['q'] ?? null,
                ],
                'counters' => $counters,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activity)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageIds = $this->getStageIds();
        $row = $this->baseQuery($stageIds, $scope['faculty_id'])
            ->where('ra.id', $activity)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $approvalStatus = $this->resolveFacultyApprovalStatus($row);
        if (! $approvalStatus) {
            return response()->json(['message' => 'activity not available for faculty approval'], Response::HTTP_NOT_FOUND);
        }

        $actingLecturerId = $this->resolveActingLecturerId($request);
        $approverConflict = $this->resolveApproverConflict(
            $actingLecturerId,
            (int) $activity,
            (int) $row->lecturer_id
        );

        // Detail view must remain read-only: compute for display without mutating stored snapshots.
        $now = now();
        $this->ensureOwnerMemberExists((int) $activity, (int) $row->lecturer_id, $now);
        $members = $this->fetchMembers($activity);
        $calculation = $this->hoursRecomputeService->recomputeActivity((int) $activity, $now, false);
        $computedHoursByLecturer = [];
        foreach (($calculation['members'] ?? []) as $memberHours) {
            $computedHoursByLecturer[(int) $memberHours['lecturer_id']] = $memberHours;
        }

        $computedTotalHours = $calculation['total_hours_activity'] !== null
            ? (float) $calculation['total_hours_activity']
            : ($row->total_hours_calc !== null ? (float) $row->total_hours_calc : null);

        $memberCount = count($members);
        $internalMemberCount = collect($members)
            ->filter(fn($member) => ! ((bool) ($member->is_external ?? false)) && (int) ($member->lecturer_id ?? 0) > 0)
            ->count();
        $recommendedPerMember = null;
        if ($computedTotalHours !== null && $internalMemberCount > 0) {
            $recommendedPerMember = (float) $computedTotalHours / $internalMemberCount;
        }

        $ruleResolved = ! empty($calculation['rule_id']);
        $ruleSummary = isset($calculation['rule_summary'])
            ? (string) $calculation['rule_summary']
            : null;
        $hoursResolutionNote = null;
        if (! $ruleResolved) {
            $hoursResolutionNote = $this->buildMissingRuleReason($row);
        } elseif ($internalMemberCount === 0) {
            $hoursResolutionNote = 'Chưa có thành viên hợp lệ để tính giờ quy đổi.';
        } elseif ($computedTotalHours === null) {
            $hoursResolutionNote = 'Không thể tính giờ quy đổi tự động cho công trình này.';
        }

        $membersPayload = array_map(function ($member) use ($recommendedPerMember, $computedHoursByLecturer) {
            $payload = (array) $member;
            $isExternal = (bool) ($member->is_external ?? false);
            $lecturerId = ! $isExternal && $member->lecturer_id !== null
                ? (int) $member->lecturer_id
                : null;
            $computed = $lecturerId !== null
                ? ($computedHoursByLecturer[$lecturerId]['hours_assigned'] ?? null)
                : null;
            $declared = ! $isExternal && $member->hours_assigned !== null
                ? (float) $member->hours_assigned
                : ($computed !== null ? (float) $computed : null);
            $ownerFacultyId = isset($member->owner_faculty_id) && $member->owner_faculty_id !== null
                ? (int) $member->owner_faculty_id
                : null;
            $memberFacultyId = isset($member->member_faculty_id) && $member->member_faculty_id !== null
                ? (int) $member->member_faculty_id
                : null;

            $payload['declared_hours'] = $declared;
            $payload['computed_member_hours'] = $computed !== null ? (float) $computed : $declared;
            $payload['recommended_hours'] = (! $isExternal && $recommendedPerMember !== null)
                ? (float) $recommendedPerMember
                : null;
            $payload['official_hours'] = null;
            $payload['owner_faculty_id'] = $ownerFacultyId;
            $payload['member_faculty_id'] = $memberFacultyId;
            $payload['is_outside_faculty'] = $isExternal || ($ownerFacultyId !== null
                && $memberFacultyId !== null
                && $ownerFacultyId !== $memberFacultyId);
            return $payload;
        }, $members);

        $hoursSummary = $this->resolveHoursApprovalSummary((int) $activity);

        return response()->json([
            'data' => [
                'activity' => array_merge([
                    'activity_id' => (int) $row->activity_id,
                    'activity_code' => $row->activity_code,
                    'title' => $row->title,
                    'kind_code' => $row->kind_code,
                    'kind_name' => $row->kind_name,
                    'type_code' => $row->type_code,
                    'type_name' => $row->type_name,
                    'academic_year_id' => $row->academic_year_id,
                    'academic_year_code' => $row->academic_year_code,
                    'status_code' => $row->status_code,
                    'approval_status' => $approvalStatus,
                    'submitted_at' => $row->submitted_at,
                    'approved_at' => $row->approved_at,
                    'declared_hours' => (float) ($row->declared_hours ?? 0),
                    'computed_total_hours' => $computedTotalHours,
                    'member_count' => $memberCount,
                    'hours_value_label' => 'Giờ quy đổi (dự kiến)',
                    'hours_request_state' => $hoursSummary['state'],
                    'hours_request_status_raw' => $hoursSummary['raw_status'],
                    'official_hours' => null,
                    'rule_resolved' => $ruleResolved,
                    'rule_summary' => $ruleSummary,
                    'hours_resolution_note' => $hoursResolutionNote,
                    'evidence_count' => (int) ($row->evidence_count ?? 0),
                    'lecturer' => [
                        'id' => (int) $row->lecturer_id,
                        'code' => $row->lecturer_code,
                        'full_name' => $row->lecturer_full_name,
                        'department_id' => $row->department_id,
                        'department_name' => $row->department_name,
                        'faculty_id' => $row->faculty_id,
                        'faculty_name' => $row->faculty_name,
                    ],
                    'work_detail' => $this->researchWorkDetailSchemaBuilder->build(
                        (int) $activity,
                        $row->kind_code !== null ? (string) $row->kind_code : null
                    ),
                    'journal' => $this->fetchPaperJournalDetail((int) $activity),
                ], $this->buildApproverConflictPayload($approverConflict)),
                'members' => $membersPayload,
                'evidence_files' => $this->fetchEvidenceFiles($activity),
                'approvals' => $this->fetchApprovals($activity),
            ],
        ], Response::HTTP_OK);
    }

    public function approve(Request $request, int $activity)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageIds = $this->getStageIds();
        $current = $this->baseQuery($stageIds, $scope['faculty_id'])
            ->where('ra.id', $activity)
            ->first();

        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if ($this->resolveFacultyApprovalStatus($current) !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending faculty approval'], Response::HTTP_CONFLICT);
        }

        $approverConflict = $this->resolveApproverConflict(
            $this->resolveActingLecturerId($request),
            $activity,
            (int) $current->lecturer_id
        );
        if ($approverConflict) {
            return $this->buildApproverConflictResponse($approverConflict);
        }

        $approvedStatusId = $this->getStatusId('approved');
        if (! $approvedStatusId) {
            return response()->json(['message' => 'approved status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $user = $request->user();

        DB::transaction(function () use ($activity, $current, $stageIds, $approvedStatusId, $now, $request, $user) {
            $locked = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $activity)
                ->lockForUpdate()
                ->select(['ra.status_id', 'ast.code as status_code'])
                ->first();

            if (! $locked) {
                abort(Response::HTTP_NOT_FOUND, 'activity not found');
            }

            if (! in_array($locked->status_code, ['pending_faculty_review'], true)) {
                abort(Response::HTTP_CONFLICT, 'activity is not pending faculty approval');
            }

            $this->ensureOwnerMemberExists((int) $activity, (int) $current->lecturer_id, $now);
            $calculation = $this->calculateAndPersistHoursDistribution($activity, $now);

            $activityUpdate = [
                'status_id' => $approvedStatusId,
                'approved_at' => $now,
                'updated_at' => $now,
            ];
            if ($calculation['total_hours_activity'] !== null) {
                $activityUpdate['total_hours_calc'] = $calculation['total_hours_activity'];
            }

            DB::table('research_activities')->where('id', $activity)->update($activityUpdate);

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activity,
                    'stage_id' => $stageIds['assistant'],
                ],
                [
                    'status' => 'approved',
                    'decided_by_user_id' => $user->id,
                    'decided_at' => $now,
                    'note' => $request->input('note'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $this->shadowWriteAssistantStageToMembers(
                (int) $activity,
                (int) $stageIds['assistant'],
                'approved',
                (int) $user->id,
                $now,
                $request->input('note')
            );

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $locked->status_id,
                'to_status_id' => $approvedStatusId,
                'acted_by_user_id' => $user->id,
                'acted_at' => $now,
                'note' => $request->input('note'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AuditLogger::log($request, [
                'action_group' => 'approval',
                'action_code' => 'FACULTY_WORK_APPROVED',
                'action_label' => 'Khoa duyet cong trinh',
                'severity' => 'important',
                'result_status' => 'success',
                'target_type' => 'research_activity',
                'target_id' => $activity,
                'request_http_status' => Response::HTTP_OK,
                'changes' => [
                    'from_status' => $locked->status_code,
                    'to_status' => 'approved',
                ],
            ], $user);
        });

        $this->recalculateLecturerYearlyHoursService->recalculateForActivities([(int) $activity]);

        $workTitle = trim((string) ($current->title ?? ''));
        WorkflowNotification::notifyLecturer(
            (int) $current->lecturer_id,
            WorkflowNotification::makePayload(
                'work_approved',
                'Công trình đã được duyệt',
                $workTitle !== ''
                    ? 'Công trình "' . $workTitle . '" đã được khoa duyệt.'
                    : 'Công trình của bạn đã được khoa duyệt.',
                '/works/personal?activity_id=' . $activity,
                [
                    'activity_id' => (int) $activity,
                    'lecturer_id' => (int) $current->lecturer_id,
                ]
            )
        );

        return response()->json([
            'message' => 'faculty approved (final)',
        ], Response::HTTP_OK);
    }

    public function reject(FacultyWorkApprovalRejectRequest $request, int $activity)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $stageIds = $this->getStageIds();
        $current = $this->baseQuery($stageIds, $scope['faculty_id'])
            ->where('ra.id', $activity)
            ->first();

        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if ($this->resolveFacultyApprovalStatus($current) !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending faculty approval'], Response::HTTP_CONFLICT);
        }

        $approverConflict = $this->resolveApproverConflict(
            $this->resolveActingLecturerId($request),
            $activity,
            (int) $current->lecturer_id
        );
        if ($approverConflict) {
            return $this->buildApproverConflictResponse($approverConflict);
        }

        $decision = strtolower((string) $request->input('decision', 'reject'));
        $isReturnForRevision = $decision === 'return_for_revision';
        $targetStatusCode = $isReturnForRevision ? 'need_revision' : 'rejected';
        $targetStatusId = $this->getStatusId($targetStatusCode);
        if (! $targetStatusId) {
            return response()->json(['message' => $targetStatusCode . ' status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = $this->buildRejectNote($request);
        $now = now();
        $user = $request->user();

        DB::transaction(function () use ($activity, $targetStatusId, $targetStatusCode, $stageIds, $now, $request, $note, $user) {
            $locked = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $activity)
                ->lockForUpdate()
                ->select(['ra.status_id', 'ast.code as status_code'])
                ->first();

            if (! $locked) {
                abort(Response::HTTP_NOT_FOUND, 'activity not found');
            }

            if (! in_array($locked->status_code, ['pending_faculty_review'], true)) {
                abort(Response::HTTP_CONFLICT, 'activity is not pending faculty approval');
            }

            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $targetStatusId,
                'approved_at' => null,
                'updated_at' => $now,
            ]);

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activity,
                    'stage_id' => $stageIds['assistant'],
                ],
                [
                    'status' => 'rejected',
                    'decided_by_user_id' => $user->id,
                    'decided_at' => $now,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $locked->status_id,
                'to_status_id' => $targetStatusId,
                'acted_by_user_id' => $user->id,
                'acted_at' => $now,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AuditLogger::log($request, [
                'action_group' => 'approval',
                'action_code' => $targetStatusCode === 'need_revision'
                    ? 'FACULTY_WORK_RETURNED_FOR_REVISION'
                    : 'FACULTY_WORK_REJECTED',
                'action_label' => $targetStatusCode === 'need_revision'
                    ? 'Khoa yeu cau chinh sua cong trinh'
                    : 'Khoa tu choi cong trinh',
                'severity' => 'important',
                'result_status' => 'success',
                'target_type' => 'research_activity',
                'target_id' => $activity,
                'request_http_status' => Response::HTTP_OK,
                'changes' => [
                    'from_status' => $locked->status_code,
                    'to_status' => $targetStatusCode,
                    'reason' => $note,
                ],
            ], $user);
        });

        $this->recalculateLecturerYearlyHoursService->recalculateForActivities([(int) $activity]);

        $workTitle = trim((string) ($current->title ?? ''));
        $recipientLecturerIds = $this->resolveTeamRecipientLecturerIds(
            (int) $activity,
            (int) $current->lecturer_id
        );

        WorkflowNotification::notifyLecturers(
            $recipientLecturerIds,
            WorkflowNotification::makePayload(
                $isReturnForRevision ? 'work_need_revision' : 'work_rejected',
                $isReturnForRevision ? 'Công trình cần chỉnh sửa' : 'Công trình bị từ chối',
                $isReturnForRevision
                    ? ($workTitle !== ''
                        ? 'Công trình "' . $workTitle . '" đã được khoa yêu cầu chỉnh sửa và gửi lại.'
                        : 'Công trình đã được khoa yêu cầu chỉnh sửa và gửi lại.')
                    : ($workTitle !== ''
                        ? 'Công trình "' . $workTitle . '" đã bị khoa từ chối và trả về để nhóm cập nhật.'
                        : 'Công trình đã bị khoa từ chối và trả về để nhóm cập nhật.'),
                '/works/personal?tab=' . ($isReturnForRevision ? 'pending' : 'rejected') . '&activity_id=' . $activity,
                [
                    'activity_id' => (int) $activity,
                    'activity_title' => $workTitle !== '' ? $workTitle : null,
                    'rejection_note' => $note,
                    'decision' => $decision,
                ]
            )
        );

        return response()->json([
            'message' => $isReturnForRevision ? 'faculty returned for revision' : 'faculty approval rejected',
        ], Response::HTTP_OK);
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

    private function resolveActingLecturerId(Request $request): ?int
    {
        $lecturerId = $request->user()?->lecturer?->id;

        return $lecturerId ? (int) $lecturerId : null;
    }

    private function getStageIds(): array
    {
        if ($this->stageIdsCache !== null) {
            return $this->stageIdsCache;
        }

        $stageIdMap = DB::table('approval_stages')
            ->whereIn('code', ['assistant', 'manager'])
            ->pluck('id', 'code');

        $this->stageIdsCache = [
            'assistant' => (int) ($stageIdMap->get('assistant') ?? 0),
            'manager' => (int) ($stageIdMap->get('manager') ?? 0),
        ];

        return $this->stageIdsCache;
    }

    private function baseQuery(array $stageIds, int $facultyId)
    {
        $evidenceAgg = DB::table('evidence_files')
            ->selectRaw('activity_id, COUNT(*) as evidence_count')
            ->groupBy('activity_id');

        $memberAgg = DB::table('research_activity_members')
            ->selectRaw('activity_id, COUNT(*) as member_count, COALESCE(SUM(hours_assigned), 0) as declared_hours')
            ->groupBy('activity_id');

        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoinSub($evidenceAgg, 'efc', 'efc.activity_id', '=', 'ra.id')
            ->leftJoinSub($memberAgg, 'ma', 'ma.activity_id', '=', 'ra.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($stageIds) {
                $join->on('ra.id', '=', 'aa_assistant.activity_id')
                    ->where('aa_assistant.stage_id', $stageIds['assistant']);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($stageIds) {
                $join->on('ra.id', '=', 'aa_manager.activity_id')
                    ->where('aa_manager.stage_id', $stageIds['manager']);
            })
            ->where('f.id', $facultyId)
            ->whereIn('ast.code', ['pending_faculty_review', 'need_revision', 'approved', 'rejected'])
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.status_id',
                'ast.code as status_code',
                'ra.kind_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'ra.type_id',
                'at.code as type_code',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                'ra.submitted_at',
                'ra.approved_at',
                'ra.total_hours_calc',
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'd.id as department_id',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'efc.evidence_count',
                'ma.declared_hours',
                'ma.member_count',
                'aa_assistant.status as assistant_approval_status',
                'aa_assistant.decided_at as assistant_decided_at',
                'aa_manager.status as manager_approval_status',
                'aa_manager.decided_at as manager_decided_at',
            ]);
    }

    private function baseCounterQuery(array $stageIds, int $facultyId)
    {
        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($stageIds) {
                $join->on('ra.id', '=', 'aa_assistant.activity_id')
                    ->where('aa_assistant.stage_id', $stageIds['assistant']);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($stageIds) {
                $join->on('ra.id', '=', 'aa_manager.activity_id')
                    ->where('aa_manager.stage_id', $stageIds['manager']);
            })
            ->where('f.id', $facultyId)
            ->whereIn('ast.code', ['pending_faculty_review', 'need_revision', 'approved', 'rejected']);
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['academic_year_id'])) {
            $query->where('ra.academic_year_id', $filters['academic_year_id']);
        }

        if (! empty($filters['kind_code'])) {
            $query->where('ak.code', $filters['kind_code']);
        }

        if (! empty($filters['q'])) {
            $keyword = trim((string) $filters['q']);
            $query->where(function ($q) use ($keyword) {
                $q->where('ra.title', 'like', '%' . $keyword . '%')
                    ->orWhere('l.full_name', 'like', '%' . $keyword . '%')
                    ->orWhere('l.code', 'like', '%' . $keyword . '%');
            });
        }
    }

    private function applyStatusFilter($query, ?string $status): void
    {
        if (! $status || $status === 'all') {
            return;
        }

        if ($status === 'pending') {
            $query->whereIn('ast.code', ['pending_faculty_review']);
            return;
        }

        if ($status === 'approved') {
            $query->where('ast.code', 'approved');
            return;
        }

        if ($status === 'need_revision') {
            $query->where('ast.code', 'need_revision');
            return;
        }

        if ($status === 'rejected') {
            $query->where('ast.code', 'rejected');
        }
    }

    private function resolveFacultyApprovalStatus(object $row): ?string
    {
        if ($row->status_code === 'need_revision') {
            return self::STATUS_NEED_REVISION;
        }

        if ($row->status_code === 'rejected') {
            return self::STATUS_REJECTED;
        }

        if ($row->status_code === 'approved') {
            return self::STATUS_APPROVED;
        }

        if (in_array($row->status_code, ['pending_faculty_review'], true)) {
            return self::STATUS_PENDING;
        }

        return null;
    }

    private function mapListEntry(
        object $row,
        array $authorsByActivity,
        ?int $actingLecturerId,
        array $activityIdsParticipatedByActingLecturer
    ): array {
        $activityId = (int) $row->activity_id;
        $approvalStatus = $this->resolveFacultyApprovalStatus($row) ?? self::STATUS_PENDING;
        $approverConflict = $this->resolveApproverConflict(
            $actingLecturerId,
            $activityId,
            (int) $row->lecturer_id,
            $activityIdsParticipatedByActingLecturer
        );

        return array_merge([
            'activity_id' => $activityId,
            'activity_code' => $row->activity_code,
            'title' => $row->title,
            'kind_code' => $row->kind_code,
            'kind_name' => $row->kind_name,
            'type_code' => $row->type_code,
            'type_name' => $row->type_name,
            'academic_year_id' => $row->academic_year_id,
            'academic_year_code' => $row->academic_year_code,
            'status_code' => $row->status_code,
            'approval_status' => $approvalStatus,
            'submitted_at' => $row->submitted_at,
            'approved_at' => $row->approved_at,
            'declared_hours' => (float) ($row->declared_hours ?? 0),
            'computed_total_hours' => $row->total_hours_calc !== null ? (float) $row->total_hours_calc : null,
            'hours_value_label' => 'Giờ hệ thống tính',
            'official_hours' => null,
            'evidence_count' => (int) ($row->evidence_count ?? 0),
            'lecturer' => [
                'id' => (int) $row->lecturer_id,
                'code' => $row->lecturer_code,
                'full_name' => $row->lecturer_full_name,
                'department_id' => $row->department_id,
                'department_name' => $row->department_name,
                'faculty_id' => $row->faculty_id,
                'faculty_name' => $row->faculty_name,
            ],
            'authors' => $authorsByActivity[$activityId] ?? [],
        ], $this->buildApproverConflictPayload($approverConflict));
    }

    private function fetchActivityIdsParticipatedByLecturer(array $activityIds, ?int $actingLecturerId): array
    {
        if (! $actingLecturerId || count($activityIds) === 0) {
            return [];
        }

        return DB::table('research_activity_members')
            ->where('lecturer_id', $actingLecturerId)
            ->whereIn('activity_id', $activityIds)
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveApproverConflict(
        ?int $actingLecturerId,
        int $activityId,
        ?int $ownerLecturerId = null,
        ?array $activityIdsParticipatedByActingLecturer = null
    ): ?array {
        if (! $actingLecturerId || $activityId <= 0) {
            return null;
        }

        if ($ownerLecturerId !== null && $actingLecturerId === $ownerLecturerId) {
            return [
                'code' => 'APPROVER_IS_ACTIVITY_PARTICIPANT',
                'message' => 'You cannot approve or reject an activity you participate in.',
                'participant_role' => 'owner',
            ];
        }

        $isParticipantMember = false;
        if (is_array($activityIdsParticipatedByActingLecturer)) {
            $isParticipantMember = in_array($activityId, $activityIdsParticipatedByActingLecturer, true);
        } else {
            $isParticipantMember = DB::table('research_activity_members')
                ->where('activity_id', $activityId)
                ->where('lecturer_id', $actingLecturerId)
                ->exists();
        }

        if ($isParticipantMember) {
            return [
                'code' => 'APPROVER_IS_ACTIVITY_PARTICIPANT',
                'message' => 'You cannot approve or reject an activity you participate in.',
                'participant_role' => 'member',
            ];
        }

        return null;
    }

    private function buildApproverConflictPayload(?array $conflict): array
    {
        return [
            'has_approver_conflict' => $conflict !== null,
            'approver_conflict_code' => $conflict['code'] ?? null,
            'approver_conflict_message' => $conflict['message'] ?? null,
        ];
    }

    private function buildApproverConflictResponse(array $conflict)
    {
        return response()->json([
            'code' => $conflict['code'],
            'message' => $conflict['message'],
            'details' => [
                'participant_role' => $conflict['participant_role'] ?? null,
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    private function buildCounters(array $stageIds, int $facultyId, array $filters): array
    {
        $query = $this->baseCounterQuery($stageIds, $facultyId);
        $this->applyFilters($query, $filters);

        $row = $query
            ->selectRaw("
                SUM(CASE WHEN ast.code = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN ast.code = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN ast.code = 'pending_faculty_review' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN ast.code = 'need_revision' THEN 1 ELSE 0 END) as need_revision_count
            ")
            ->first();

        $rejected = (int) ($row->rejected_count ?? 0);
        $approved = (int) ($row->approved_count ?? 0);
        $pending = (int) ($row->pending_count ?? 0);
        $needRevision = (int) ($row->need_revision_count ?? 0);

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'need_revision' => $needRevision,
            'total' => $pending + $approved + $rejected + $needRevision,
        ];
    }

    private function fetchAuthorsByActivity(array $activityIds): array
    {
        if (count($activityIds) === 0) {
            return [];
        }

        $rows = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->join('lecturers as owner_l', 'ra.owner_lecturer_id', '=', 'owner_l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('departments as owner_d', 'owner_l.department_id', '=', 'owner_d.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->whereIn('ram.activity_id', $activityIds)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->select([
                'ram.activity_id',
                'ram.id as member_id',
                'ram.lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'd.name as department_name',
                'f.id as member_faculty_id',
                'f.name as faculty_name',
                'owner_d.faculty_id as owner_faculty_id',
                'ram.is_external',
                'ram.external_full_name',
                'ram.external_department_name',
            ])
            ->orderBy('ram.activity_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $activityId = (int) $row->activity_id;
            $isExternal = (bool) ($row->is_external ?? false);
            $ownerFacultyId = $row->owner_faculty_id !== null ? (int) $row->owner_faculty_id : null;
            $memberFacultyId = $row->member_faculty_id !== null ? (int) $row->member_faculty_id : null;
            $displayName = $isExternal
                ? trim((string) ($row->external_full_name ?? ''))
                : trim((string) ($row->lecturer_full_name ?? ''));
            $displayUnit = $isExternal
                ? trim((string) ($row->external_department_name ?? ''))
                : trim((string) ($row->faculty_name ?? $row->department_name ?? ''));
            $grouped[$activityId][] = [
                'member_id' => (int) $row->member_id,
                'lecturer_id' => $row->lecturer_id !== null ? (int) $row->lecturer_id : null,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $displayName !== '' ? $displayName : '—',
                'member_role_code' => $row->member_role_code,
                'member_role_name' => $row->member_role_name,
                'department_name' => $isExternal
                    ? ($displayUnit !== '' ? $displayUnit : null)
                    : $row->department_name,
                'member_faculty_id' => $memberFacultyId,
                'owner_faculty_id' => $ownerFacultyId,
                'is_outside_faculty' => $isExternal || ($ownerFacultyId !== null
                    && $memberFacultyId !== null
                    && $ownerFacultyId !== $memberFacultyId),
                'faculty_name' => $displayUnit !== '' ? $displayUnit : $row->faculty_name,
                'is_external' => $isExternal,
            ];
        }

        return $grouped;
    }

    private function fetchMembers(int $activityId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->join('lecturers as owner_l', 'ra.owner_lecturer_id', '=', 'owner_l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('departments as owner_d', 'owner_l.department_id', '=', 'owner_d.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activityId)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->select([
                'ram.id as member_id',
                'ram.lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'ram.member_role_id',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'ram.contribution_share',
                'ram.hours_assigned',
                'owner_d.faculty_id as owner_faculty_id',
                'f.id as member_faculty_id',
                'd.name as department_name',
                'f.name as faculty_name',
                'ram.is_external',
                'ram.external_full_name',
                'ram.external_department_name',
            ])
            ->orderBy('ram.id')
            ->get()
            ->map(function ($row) {
                $isExternal = (bool) ($row->is_external ?? false);
                $displayName = $isExternal
                    ? trim((string) ($row->external_full_name ?? ''))
                    : trim((string) ($row->lecturer_full_name ?? ''));
                $displayUnit = $isExternal
                    ? trim((string) ($row->external_department_name ?? ''))
                    : trim((string) ($row->faculty_name ?? $row->department_name ?? ''));

                return (object) [
                    'member_id' => (int) $row->member_id,
                    'lecturer_id' => $row->lecturer_id !== null ? (int) $row->lecturer_id : null,
                    'lecturer_code' => $row->lecturer_code,
                    'lecturer_full_name' => $displayName !== '' ? $displayName : '—',
                    'member_role_id' => $row->member_role_id !== null ? (int) $row->member_role_id : null,
                    'member_role_code' => $row->member_role_code,
                    'member_role_name' => $row->member_role_name,
                    'contribution_share' => $row->contribution_share,
                    'hours_assigned' => $row->hours_assigned,
                    'owner_faculty_id' => $row->owner_faculty_id !== null ? (int) $row->owner_faculty_id : null,
                    'member_faculty_id' => $row->member_faculty_id !== null ? (int) $row->member_faculty_id : null,
                    'department_name' => $isExternal
                        ? ($displayUnit !== '' ? $displayUnit : null)
                        : $row->department_name,
                    'faculty_name' => $displayUnit !== '' ? $displayUnit : $row->faculty_name,
                    'is_external' => $isExternal,
                ];
            })
            ->all();
    }

    private function buildMissingRuleReason(object $row): string
    {
        $academicYear = trim((string) ($row->academic_year_code ?? ''));
        $kind = trim((string) ($row->kind_name ?? $row->kind_code ?? ''));
        $type = trim((string) ($row->type_name ?? $row->type_code ?? ''));

        return sprintf(
            'Chưa cấu hình quy tắc quy đổi cho: %s - %s - %s.',
            $academicYear !== '' ? $academicYear : 'Chưa xác định năm học',
            $kind !== '' ? $kind : 'Chưa xác định loại công trình',
            $type !== '' ? $type : 'Chưa xác định hình thức'
        );
    }

    private function ensureOwnerMemberExists(int $activityId, int $ownerLecturerId, $now): void
    {
        if ($activityId <= 0 || $ownerLecturerId <= 0) {
            return;
        }

        $ownerRow = DB::table('research_activity_members')
            ->where('activity_id', $activityId)
            ->where('lecturer_id', $ownerLecturerId)
            ->first();

        if ($ownerRow) {
            if (($ownerRow->confirmation_status ?? null) !== 'accepted') {
                DB::table('research_activity_members')
                    ->where('activity_id', $activityId)
                    ->where('lecturer_id', $ownerLecturerId)
                    ->update([
                        'confirmation_status' => 'accepted',
                        'responded_at' => $now,
                        'confirmation_note' => null,
                        'updated_at' => $now,
                    ]);
            }
            return;
        }

        $preferredCodes = ['principal', 'corresponding_author', 'chief_editor', 'member'];
        $roleIdsByCode = DB::table('member_roles')
            ->whereIn('code', $preferredCodes)
            ->pluck('id', 'code');

        $defaultRoleId = null;
        foreach ($preferredCodes as $code) {
            if (isset($roleIdsByCode[$code])) {
                $defaultRoleId = (int) $roleIdsByCode[$code];
                break;
            }
        }

        if (! $defaultRoleId) {
            $fallbackRoleId = DB::table('member_roles')->value('id');
            if (! $fallbackRoleId) {
                return;
            }
            $defaultRoleId = (int) $fallbackRoleId;
        }

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $ownerLecturerId,
            'member_role_id' => $defaultRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'confirmation_note' => null,
            'responded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function fetchApprovals(int $activityId): array
    {
        return DB::table('activity_approvals as aa')
            ->join('approval_stages as st', 'aa.stage_id', '=', 'st.id')
            ->leftJoin('users as u', 'aa.decided_by_user_id', '=', 'u.id')
            ->where('aa.activity_id', $activityId)
            ->orderBy('st.order_no')
            ->select([
                'aa.id',
                'aa.stage_id',
                'st.code as stage_code',
                'st.name as stage_name',
                'aa.status',
                'aa.decided_by_user_id',
                'u.name as decided_by_user_name',
                'aa.decided_at',
                'aa.note',
            ])
            ->get()
            ->map(fn($row) => (array) $row)
            ->all();
    }

    private function fetchPaperJournalDetail(int $activityId): ?array
    {
        $paper = DB::table('paper_details')
            ->where('activity_id', $activityId)
            ->select([
                'journal_name',
                'issn',
                'journal_scope',
                'journal_source_name',
                'journal_publisher',
                'journal_website',
                'work_score',
            ])
            ->first();

        if (! $paper) {
            return null;
        }

        $journal = null;
        $issn = trim((string) ($paper->issn ?? ''));
        $journalName = trim((string) ($paper->journal_name ?? ''));

        if ($issn !== '') {
            $journal = DB::table('journals')
                ->where('issn', $issn)
                ->select([
                    'name',
                    'issn',
                    'address',
                    'source_name',
                    'publisher',
                    'website',
                    'point',
                ])
                ->first();
        }

        if (! $journal && $journalName !== '') {
            $journal = DB::table('journals')
                ->where('name', $journalName)
                ->select([
                    'name',
                    'issn',
                    'address',
                    'source_name',
                    'publisher',
                    'website',
                    'point',
                ])
                ->first();
        }

        if ($journal) {
            return [
                'journal_name' => $journal->name,
                'issn' => $journal->issn,
                'journal_scope' => $journal->address,
                'journal_source_name' => $journal->source_name,
                'journal_publisher' => $journal->publisher,
                'journal_website' => $journal->website,
                'work_score' => $journal->point !== null ? (float) $journal->point : null,
            ];
        }

        return [
            'journal_name' => $paper->journal_name,
            'issn' => $paper->issn,
            'journal_scope' => $paper->journal_scope,
            'journal_source_name' => $paper->journal_source_name,
            'journal_publisher' => $paper->journal_publisher,
            'journal_website' => $paper->journal_website,
            'work_score' => $paper->work_score !== null ? (float) $paper->work_score : null,
        ];
    }

    private function fetchEvidenceFiles(int $activityId): array
    {
        return DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->select([
                'ef.id',
                'ef.file_type_id',
                'eft.name as file_type_name',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.path',
                'ef.disk',
                'ef.uploaded_at',
            ])
            ->orderByDesc('ef.uploaded_at')
            ->get()
            ->map(function ($row) {
                $payload = (array) $row;
                $payload['preview_url'] = route('faculty.works.evidence.preview', ['evidence' => $row->id], false);
                $payload['download_url'] = route('faculty.works.evidence.download', ['evidence' => $row->id], false);
                $payload['url'] = $payload['preview_url'];
                return $payload;
            })
            ->all();
    }

    private function resolveHoursApprovalSummary(int $activityId): array
    {
        $hoursStageId = (int) (DB::table('approval_stages')
            ->where('code', 'hours')
            ->value('id') ?? 0);

        if ($hoursStageId <= 0) {
            return [
                'state' => 'hours_not_submitted',
                'raw_status' => null,
            ];
        }

        $status = DB::table('activity_approvals')
            ->where('activity_id', $activityId)
            ->where('stage_id', $hoursStageId)
            ->value('status');

        $normalized = $status ? strtolower((string) $status) : null;
        if (! $normalized) {
            return [
                'state' => 'hours_not_submitted',
                'raw_status' => null,
            ];
        }

        if ($normalized === 'approved') {
            return [
                'state' => 'hours_approved',
                'raw_status' => 'approved',
            ];
        }

        if ($normalized === 'rejected') {
            return [
                'state' => 'hours_rejected',
                'raw_status' => 'rejected',
            ];
        }

        return [
            'state' => 'hours_pending_faculty',
            'raw_status' => $normalized,
        ];
    }

    private function calculateAndPersistHoursDistribution(int $activityId, $executedAt): array
    {
        return $this->hoursRecomputeService->recomputeActivity($activityId, $executedAt, true);
    }

    private function shadowWriteAssistantStageToMembers(
        int $activityId,
        int $assistantStageId,
        string $status,
        int $decidedByUserId,
        $decidedAt,
        ?string $note
    ): void {
        $lecturerIds = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ra.id', '=', 'ram.activity_id')
            ->where('ram.activity_id', $activityId)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->pluck('ram.lecturer_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($lecturerIds === []) {
            return;
        }

        $rows = array_map(function (int $lecturerId) use (
            $activityId,
            $assistantStageId,
            $status,
            $decidedByUserId,
            $decidedAt,
            $note
        ) {
            return [
                'activity_id' => $activityId,
                'lecturer_id' => $lecturerId,
                'stage_id' => $assistantStageId,
                'status' => $status,
                'decided_by_user_id' => $decidedByUserId,
                'decided_at' => $decidedAt,
                'note' => $note,
                'created_at' => $decidedAt,
                'updated_at' => $decidedAt,
            ];
        }, $lecturerIds);

        DB::table('activity_member_approvals')->upsert(
            $rows,
            ['activity_id', 'lecturer_id', 'stage_id'],
            ['status', 'decided_by_user_id', 'decided_at', 'note', 'updated_at']
        );
    }

    private function getStatusId(string $code): ?int
    {
        $id = DB::table('activity_statuses')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function buildRejectNote(Request $request): ?string
    {
        $reasonType = $request->input('reason_type');
        $reasonDetail = trim((string) $request->input('reason_detail'));
        if (! $reasonType) {
            return null;
        }

        if ($reasonDetail === '') {
            return $reasonType;
        }

        return $reasonType . ': ' . $reasonDetail;
    }

    private function resolveTeamRecipientLecturerIds(int $activityId, int $ownerLecturerId): array
    {
        $memberLecturerIds = DB::table('research_activity_members')
            ->where('activity_id', $activityId)
            ->where('confirmation_status', 'accepted')
            ->pluck('lecturer_id')
            ->map(fn($id) => (int) $id)
            ->all();

        return collect(array_merge([$ownerLecturerId], $memberLecturerIds))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
