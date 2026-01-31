<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyWorkApprovalListRequest;
use App\Http\Requests\Faculty\FacultyWorkApprovalRejectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyResearchWorkApprovalController extends Controller
{
    private const STATUS_PENDING = 'PENDING_FACULTY_APPROVAL';
    private const STATUS_APPROVED = 'APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY';
    private const STATUS_REJECTED = 'REJECTED_BY_FACULTY';

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

        $statuses = [
            ['code' => 'all', 'name' => 'Tất cả'],
            ['code' => self::STATUS_PENDING, 'name' => 'Chờ khoa duyệt'],
            ['code' => self::STATUS_APPROVED, 'name' => 'Đã chuyển lên cấp trường'],
            ['code' => self::STATUS_REJECTED, 'name' => 'Bị từ chối ở cấp khoa'],
        ];

        return response()->json([
            'data' => [
                'academic_years' => $academicYears,
                'work_kinds' => $kinds,
                'statuses' => $statuses,
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
            ->filter()
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

        $members = $this->fetchMembers($activity);
        $memberCount = count($members);
        $recommendedPerMember = null;
        if ($row->total_hours_calc !== null && $memberCount > 0) {
            $recommendedPerMember = (float) $row->total_hours_calc / $memberCount;
        }

        $membersPayload = array_map(function ($member) use ($recommendedPerMember) {
            $payload = (array) $member;
            $payload['declared_hours'] = $member->hours_assigned !== null
                ? (float) $member->hours_assigned
                : null;
            $payload['recommended_hours'] = $recommendedPerMember !== null
                ? (float) $recommendedPerMember
                : null;
            $payload['official_hours'] = null;
            return $payload;
        }, $members);

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

        $approvalStatus = $this->resolveFacultyApprovalStatus($current);
        if ($approvalStatus !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending faculty approval'], Response::HTTP_CONFLICT);
        }

        $now = now();
        DB::table('activity_approvals')->updateOrInsert(
            [
                'activity_id' => $activity,
                'stage_id' => $stageIds['assistant'],
            ],
            [
                'status' => 'approved',
                'decided_by_user_id' => $request->user()->id,
                'decided_at' => $now,
                'note' => $request->input('note'),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return response()->json([
            'message' => 'faculty approval recorded',
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

        $approvalStatus = $this->resolveFacultyApprovalStatus($current);
        if ($approvalStatus !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending faculty approval'], Response::HTTP_CONFLICT);
        }

        $rejectedStatusId = $this->getStatusId('rejected');
        if (! $rejectedStatusId) {
            return response()->json(['message' => 'rejected status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = $this->buildRejectNote($request);
        $now = now();

        DB::transaction(function () use ($activity, $rejectedStatusId, $stageIds, $now, $current, $request, $note) {
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
                    'decided_by_user_id' => $request->user()->id,
                    'decided_at' => $now,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $current->status_id,
                'to_status_id' => $rejectedStatusId,
                'acted_by_user_id' => $request->user()->id,
                'acted_at' => $now,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

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
            ->where('ast.code', '!=', 'draft')
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
            ->where('ast.code', '!=', 'draft');
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
            $query->where('ast.code', 'submitted')
                ->where(function ($q) {
                    $q->whereNull('aa_assistant.status')
                        ->orWhere('aa_assistant.status', 'pending');
                });
            return;
        }

        if ($status === 'approved') {
            $query->where('aa_assistant.status', 'approved');
            return;
        }

        if ($status === 'rejected') {
            $query->where(function ($q) {
                $q->where('aa_assistant.status', 'rejected')
                    ->orWhere('ast.code', 'rejected');
            });
        }
    }

    private function resolveFacultyApprovalStatus(object $row): ?string
    {
        if ($row->assistant_approval_status === 'rejected' || $row->status_code === 'rejected') {
            return self::STATUS_REJECTED;
        }

        if ($row->assistant_approval_status === 'approved') {
            return self::STATUS_APPROVED;
        }

        if ($row->status_code === 'submitted') {
            return self::STATUS_PENDING;
        }

        return null;
    }

    private function mapListEntry(object $row, array $authorsByActivity): ?array
    {
        $approvalStatus = $this->resolveFacultyApprovalStatus($row);
        if (! $approvalStatus) {
            return null;
        }

        $activityId = (int) $row->activity_id;
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
                SUM(CASE WHEN aa_assistant.status = 'rejected' OR ast.code = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN aa_assistant.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN ast.code = 'submitted' AND (aa_assistant.status IS NULL OR aa_assistant.status = 'pending') THEN 1 ELSE 0 END) as pending_count
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
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
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
                'f.name as faculty_name',
            ])
            ->orderBy('ram.activity_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $activityId = (int) $row->activity_id;
            $grouped[$activityId][] = [
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'member_role_code' => $row->member_role_code,
                'member_role_name' => $row->member_role_name,
                'department_name' => $row->department_name,
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
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
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
                'd.name as department_name',
                'f.name as faculty_name',
            ])
            ->orderBy('ram.id')
            ->get()
            ->all();
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
                $payload['url'] = route('faculty.works.evidence.download', ['evidence' => $row->id], false);
                return $payload;
            })
            ->all();
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
}




