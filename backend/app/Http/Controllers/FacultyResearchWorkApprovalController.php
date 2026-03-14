<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyWorkApprovalListRequest;
use App\Http\Requests\Faculty\FacultyWorkApprovalRejectRequest;
use App\Services\Hours\HoursRecomputeService;
use App\Support\AuditLogger;
use App\Support\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyResearchWorkApprovalController extends Controller
{
    private const STATUS_PENDING = 'PENDING_FACULTY_APPROVAL';
    private const STATUS_APPROVED = 'APPROVED_BY_FACULTY_FINAL';
    private const STATUS_REJECTED = 'REJECTED_BY_FACULTY';
    private HoursRecomputeService $hoursRecomputeService;

    public function __construct(
        HoursRecomputeService $hoursRecomputeService
    ) {
        $this->hoursRecomputeService = $hoursRecomputeService;
    }

    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $academicYears = DB::table('academic_years')
            ->select(['id', 'code', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'is_active' => (bool) $row->is_active,
            ])
            ->all();

        $kinds = DB::table('activity_kinds')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        return response()->json([
            'data' => [
                'academic_years' => $academicYears,
                'work_kinds' => $kinds,
                'statuses' => [
                    ['code' => 'all', 'name' => 'Tất cả'],
                    ['code' => self::STATUS_PENDING, 'name' => 'Chờ khoa duyệt'],
                    ['code' => self::STATUS_APPROVED, 'name' => 'Đã duyệt cuối cùng tại khoa'],
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
        $authorsByActivity = $this->fetchAuthorsByActivity($activityIds);

        $rows = $items
            ->map(function ($row) use ($authorsByActivity) {
                return $this->mapListEntry($row, $authorsByActivity);
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

        // Đồng bộ trước khi hiển thị để bảng "Thành viên & số giờ" luôn có dữ liệu dự kiến mới nhất.
        $now = now();
        $this->ensureOwnerMemberExists((int) $activity, (int) $row->lecturer_id, $now);
        $calculation = $this->hoursRecomputeService->recomputeActivity((int) $activity, $now, true);
        $members = $this->fetchMembers($activity);
        $computedHoursByLecturer = [];
        foreach (($calculation['members'] ?? []) as $memberHours) {
            $computedHoursByLecturer[(int) $memberHours['lecturer_id']] = $memberHours;
        }

        $computedTotalHours = $calculation['total_hours_activity'] !== null
            ? (float) $calculation['total_hours_activity']
            : ($row->total_hours_calc !== null ? (float) $row->total_hours_calc : null);

        $memberCount = count($members);
        $recommendedPerMember = null;
        if ($computedTotalHours !== null && $memberCount > 0) {
            $recommendedPerMember = (float) $computedTotalHours / $memberCount;
        }

        $ruleResolved = ! empty($calculation['rule_id']);
        $ruleSummary = isset($calculation['rule_summary'])
            ? (string) $calculation['rule_summary']
            : null;
        $hoursResolutionNote = null;
        if (! $ruleResolved) {
            $hoursResolutionNote = $this->buildMissingRuleReason($row);
        } elseif ($memberCount === 0) {
            $hoursResolutionNote = 'Chưa có thành viên hợp lệ để tính giờ quy đổi.';
        } elseif ($computedTotalHours === null) {
            $hoursResolutionNote = 'Không thể tính giờ quy đổi tự động cho công trình này.';
        }

        $membersPayload = array_map(function ($member) use ($recommendedPerMember, $computedHoursByLecturer) {
            $payload = (array) $member;
            $computed = $computedHoursByLecturer[(int) $member->lecturer_id]['hours_assigned'] ?? null;
            $declared = $member->hours_assigned !== null
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
            $payload['recommended_hours'] = $recommendedPerMember !== null
                ? (float) $recommendedPerMember
                : null;
            $payload['official_hours'] = null;
            $payload['owner_faculty_id'] = $ownerFacultyId;
            $payload['member_faculty_id'] = $memberFacultyId;
            $payload['is_outside_faculty'] = $ownerFacultyId !== null
                && $memberFacultyId !== null
                && $ownerFacultyId !== $memberFacultyId;
            return $payload;
        }, $members);

        $hoursSummary = $this->resolveHoursApprovalSummary((int) $activity);

        return response()->json([
            'data' => [
                'activity' => [
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
                ],
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

            if (! in_array($locked->status_code, ['pending_faculty_review', 'submitted'], true)) {
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

        $rejectedStatusId = $this->getStatusId('rejected');
        if (! $rejectedStatusId) {
            return response()->json(['message' => 'rejected status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = $this->buildRejectNote($request);
        $now = now();
        $user = $request->user();

        DB::transaction(function () use ($activity, $rejectedStatusId, $stageIds, $now, $request, $note, $user) {
            $locked = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $activity)
                ->lockForUpdate()
                ->select(['ra.status_id', 'ast.code as status_code'])
                ->first();

            if (! $locked) {
                abort(Response::HTTP_NOT_FOUND, 'activity not found');
            }

            if (! in_array($locked->status_code, ['pending_faculty_review', 'submitted'], true)) {
                abort(Response::HTTP_CONFLICT, 'activity is not pending faculty approval');
            }

            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $rejectedStatusId,
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
                'to_status_id' => $rejectedStatusId,
                'acted_by_user_id' => $user->id,
                'acted_at' => $now,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AuditLogger::log($request, [
                'action_group' => 'approval',
                'action_code' => 'FACULTY_WORK_REJECTED',
                'action_label' => 'Khoa tu choi cong trinh',
                'severity' => 'important',
                'result_status' => 'success',
                'target_type' => 'research_activity',
                'target_id' => $activity,
                'request_http_status' => Response::HTTP_OK,
                'changes' => [
                    'from_status' => $locked->status_code,
                    'to_status' => 'rejected',
                    'reason' => $note,
                ],
            ], $user);
        });

        $workTitle = trim((string) ($current->title ?? ''));
        $recipientLecturerIds = $this->resolveTeamRecipientLecturerIds(
            (int) $activity,
            (int) $current->lecturer_id
        );

        WorkflowNotification::notifyLecturers(
            $recipientLecturerIds,
            WorkflowNotification::makePayload(
                'work_rejected',
                'Công trình bị từ chối',
                $workTitle !== ''
                    ? 'Công trình "' . $workTitle . '" đã bị khoa từ chối và trả về để nhóm cập nhật.'
                    : 'Công trình đã bị khoa từ chối và trả về để nhóm cập nhật.',
                '/works/personal?activity_id=' . $activity,
                [
                    'activity_id' => (int) $activity,
                    'activity_title' => $workTitle !== '' ? $workTitle : null,
                    'rejection_note' => $note,
                ]
            )
        );

        return response()->json([
            'message' => 'faculty approval rejected',
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

    private function getStageIds(): array
    {
        return [
            'assistant' => (int) DB::table('approval_stages')->where('code', 'assistant')->value('id'),
            'manager' => (int) DB::table('approval_stages')->where('code', 'manager')->value('id'),
        ];
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
            ->whereIn('ast.code', ['pending_faculty_review', 'submitted', 'approved', 'rejected'])
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
            ->whereIn('ast.code', ['pending_faculty_review', 'submitted', 'approved', 'rejected']);
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
            $query->whereIn('ast.code', ['pending_faculty_review', 'submitted']);
            return;
        }

        if ($status === 'approved') {
            $query->where('ast.code', 'approved');
            return;
        }

        if ($status === 'rejected') {
            $query->where('ast.code', 'rejected');
        }
    }

    private function resolveFacultyApprovalStatus(object $row): ?string
    {
        if ($row->status_code === 'rejected') {
            return self::STATUS_REJECTED;
        }

        if ($row->status_code === 'approved') {
            return self::STATUS_APPROVED;
        }

        if (in_array($row->status_code, ['pending_faculty_review', 'submitted'], true)) {
            return self::STATUS_PENDING;
        }

        return null;
    }

    private function mapListEntry(object $row, array $authorsByActivity): array
    {
        $activityId = (int) $row->activity_id;
        $approvalStatus = $this->resolveFacultyApprovalStatus($row) ?? self::STATUS_PENDING;

        return [
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
        ];
    }

    private function buildCounters(array $stageIds, int $facultyId, array $filters): array
    {
        $query = $this->baseCounterQuery($stageIds, $facultyId);
        $this->applyFilters($query, $filters);

        $row = $query
            ->selectRaw("
                SUM(CASE WHEN ast.code = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN ast.code = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN ast.code IN ('pending_faculty_review', 'submitted') THEN 1 ELSE 0 END) as pending_count
            ")
            ->first();

        $rejected = (int) ($row->rejected_count ?? 0);
        $approved = (int) ($row->approved_count ?? 0);
        $pending = (int) ($row->pending_count ?? 0);

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $pending + $approved + $rejected,
        ];
    }

    private function fetchAuthorsByActivity(array $activityIds): array
    {
        if (count($activityIds) === 0) {
            return [];
        }

        $rows = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
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
                'ram.lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'd.name as department_name',
                'f.id as member_faculty_id',
                'f.name as faculty_name',
                'owner_d.faculty_id as owner_faculty_id',
            ])
            ->orderBy('ram.activity_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $activityId = (int) $row->activity_id;
            $ownerFacultyId = $row->owner_faculty_id !== null ? (int) $row->owner_faculty_id : null;
            $memberFacultyId = $row->member_faculty_id !== null ? (int) $row->member_faculty_id : null;
            $grouped[$activityId][] = [
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'member_role_code' => $row->member_role_code,
                'member_role_name' => $row->member_role_name,
                'department_name' => $row->department_name,
                'member_faculty_id' => $memberFacultyId,
                'owner_faculty_id' => $ownerFacultyId,
                'is_outside_faculty' => $ownerFacultyId !== null
                    && $memberFacultyId !== null
                    && $ownerFacultyId !== $memberFacultyId,
                'faculty_name' => $row->faculty_name,
            ];
        }

        return $grouped;
    }

    private function fetchMembers(int $activityId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
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
            ])
            ->orderBy('ram.id')
            ->get()
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
            ->map(fn ($row) => (array) $row)
            ->all();
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
            ->map(fn ($id) => (int) $id)
            ->all();

        return collect(array_merge([$ownerLecturerId], $memberLecturerIds))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
