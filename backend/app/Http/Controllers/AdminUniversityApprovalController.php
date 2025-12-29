<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UniversityApprovals\UniversityApprovalFinalizeRequest;
use App\Http\Requests\Admin\UniversityApprovals\UniversityApprovalListRequest;
use App\Http\Requests\Admin\UniversityApprovals\UniversityApprovalRejectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminUniversityApprovalController extends Controller
{
    private const STATUS_PENDING = 'PENDING_UNIVERSITY_APPROVAL';
    private const STATUS_APPROVED = 'APPROVED_BY_UNIVERSITY_FINALIZED_HOURS';
    private const STATUS_REJECTED = 'REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY';

    public function index(UniversityApprovalListRequest $request)
    {
        $filters = $request->validated();
        $stageIds = $this->getStageIds();

        $query = $this->baseQuery($stageIds);

        $academicYearId = $filters['academic_year_id'] ?? null;
        if (! $academicYearId && ! empty($filters['academic_year_code'])) {
            $academicYearId = DB::table('academic_years')
                ->where('code', $filters['academic_year_code'])
                ->value('id');
        }

        if ($academicYearId) {
            $query->where('ra.academic_year_id', $academicYearId);
        }
        if (! empty($filters['faculty_id'])) {
            $query->where('f.id', $filters['faculty_id']);
        }
        if (! empty($filters['department_id'])) {
            $query->where('d.id', $filters['department_id']);
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

        $rows = $query
            ->orderByDesc('ra.submitted_at')
            ->orderByDesc('ra.id')
            ->get()
            ->map(function ($row) {
                $approvalStatus = $this->resolveUniversityApprovalStatus($row);
                if (! $approvalStatus) {
                    return null;
                }

                $payload = (array) $row;
                $payload['approval_status'] = $approvalStatus;
                $payload['declared_hours'] = (float) ($row->declared_hours ?? 0);
                $payload['official_hours'] = (float) ($row->declared_hours ?? 0);
                return $payload;
            })
            ->filter()
            ->values();

        $counters = $this->countByApprovalStatus($rows);

        $statusFilter = $filters['status'] ?? null;
        if ($statusFilter && $statusFilter !== 'all') {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                $status = $row['approval_status'] ?? null;
                if (! $status) {
                    return false;
                }

                if ($statusFilter === 'pending') {
                    return $status === self::STATUS_PENDING;
                }
                if ($statusFilter === 'approved') {
                    return $status === self::STATUS_APPROVED;
                }
                if ($statusFilter === 'rejected') {
                    return $status === self::STATUS_REJECTED;
                }

                return $status === $statusFilter;
            })->values();
        }

        $activityIds = $rows->pluck('activity_id')->all();
        $authorsByActivity = $this->fetchAuthorsByActivity($activityIds);

        $data = $rows->map(function ($row) use ($authorsByActivity) {
            $activityId = (int) $row['activity_id'];
            return [
                'activity_id' => $activityId,
                'activity_code' => $row['activity_code'],
                'title' => $row['title'],
                'kind_code' => $row['kind_code'],
                'kind_name' => $row['kind_name'],
                'type_code' => $row['type_code'],
                'type_name' => $row['type_name'],
                'academic_year_id' => $row['academic_year_id'],
                'academic_year_code' => $row['academic_year_code'],
                'status_code' => $row['status_code'],
                'approval_status' => $row['approval_status'],
                'submitted_at' => $row['submitted_at'],
                'approved_at' => $row['approved_at'],
                'declared_hours' => $row['declared_hours'],
                'official_hours' => $row['official_hours'],
                'evidence_count' => (int) ($row['evidence_count'] ?? 0),
                'lecturer' => [
                    'id' => (int) $row['lecturer_id'],
                    'code' => $row['lecturer_code'],
                    'full_name' => $row['lecturer_full_name'],
                    'department_id' => $row['department_id'],
                    'department_name' => $row['department_name'],
                    'faculty_id' => $row['faculty_id'],
                    'faculty_name' => $row['faculty_name'],
                ],
                'authors' => $authorsByActivity[$activityId] ?? [],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'filters' => [
                    'academic_year_id' => $academicYearId,
                    'faculty_id' => $filters['faculty_id'] ?? null,
                    'department_id' => $filters['department_id'] ?? null,
                    'kind_code' => $filters['kind_code'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'q' => $filters['q'] ?? null,
                ],
                'counters' => $counters,
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activity)
    {
        $stageIds = $this->getStageIds();
        $row = $this->baseQuery($stageIds)
            ->where('ra.id', $activity)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $approvalStatus = $this->resolveUniversityApprovalStatus($row);
        if (! $approvalStatus) {
            return response()->json(['message' => 'activity not available for university approval'], Response::HTTP_NOT_FOUND);
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
            $payload['official_hours'] = $member->hours_assigned !== null
                ? (float) $member->hours_assigned
                : null;
            return $payload;
        }, $members);

        $approvals = $this->fetchApprovals($activity);

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
                    'official_hours' => (float) ($row->declared_hours ?? 0),
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
                'approvals' => $approvals,
            ],
        ], Response::HTTP_OK);
    }

    public function finalize(UniversityApprovalFinalizeRequest $request, int $activity)
    {
        $stageIds = $this->getStageIds();
        $current = $this->baseQuery($stageIds)
            ->where('ra.id', $activity)
            ->first();

        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $approvalStatus = $this->resolveUniversityApprovalStatus($current);
        if ($approvalStatus !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending university approval'], Response::HTTP_CONFLICT);
        }

        $data = $request->validated();
        $memberPayload = $data['members'] ?? [];
        $memberIds = array_map(fn($item) => (int) $item['lecturer_id'], $memberPayload);

        $existingMembers = DB::table('research_activity_members')
            ->where('activity_id', $activity)
            ->pluck('lecturer_id')
            ->all();

        sort($memberIds);
        $existingSorted = $existingMembers;
        sort($existingSorted);

        if ($existingSorted !== $memberIds) {
            return response()->json(['message' => 'members mismatch'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $approvedStatusId = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        if (! $approvedStatusId) {
            return response()->json(['message' => 'approved status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        DB::transaction(function () use ($activity, $memberPayload, $approvedStatusId, $stageIds, $now, $current, $request) {
            foreach ($memberPayload as $member) {
                DB::table('research_activity_members')
                    ->where('activity_id', $activity)
                    ->where('lecturer_id', $member['lecturer_id'])
                    ->update([
                        'hours_assigned' => $member['official_hours'],
                        'updated_at' => $now,
                    ]);
            }

            $totalHours = array_reduce($memberPayload, function ($carry, $member) {
                return $carry + (float) $member['official_hours'];
            }, 0.0);

            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $approvedStatusId,
                'approved_at' => $now,
                'total_hours_calc' => $totalHours,
                'updated_at' => $now,
            ]);

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activity,
                    'stage_id' => $stageIds['manager'],
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

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $current->status_id,
                'to_status_id' => $approvedStatusId,
                'acted_by_user_id' => $request->user()->id,
                'acted_at' => $now,
                'note' => 'university approval finalized',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return response()->json([
            'message' => 'approval finalized',
        ], Response::HTTP_OK);
    }

    public function reject(UniversityApprovalRejectRequest $request, int $activity)
    {
        $stageIds = $this->getStageIds();
        $current = $this->baseQuery($stageIds)
            ->where('ra.id', $activity)
            ->first();

        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $approvalStatus = $this->resolveUniversityApprovalStatus($current);
        if ($approvalStatus !== self::STATUS_PENDING) {
            return response()->json(['message' => 'activity is not pending university approval'], Response::HTTP_CONFLICT);
        }

        $rejectedStatusId = DB::table('activity_statuses')->where('code', 'rejected')->value('id');
        if (! $rejectedStatusId) {
            return response()->json(['message' => 'rejected status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $note = $this->buildRejectNote($request);

        DB::transaction(function () use ($activity, $rejectedStatusId, $stageIds, $now, $current, $request, $note) {
            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $rejectedStatusId,
                'approved_at' => null,
                'updated_at' => $now,
            ]);

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activity,
                    'stage_id' => $stageIds['manager'],
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
            'message' => 'approval rejected',
        ], Response::HTTP_OK);
    }

    private function baseQuery(array $stageIds)
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

    private function resolveUniversityApprovalStatus(object $row): ?string
    {
        if ($row->manager_approval_status === 'approved' || $row->status_code === 'approved') {
            return self::STATUS_APPROVED;
        }

        if ($row->manager_approval_status === 'rejected' || $row->status_code === 'rejected') {
            return self::STATUS_REJECTED;
        }

        if ($row->assistant_approval_status === 'approved') {
            return self::STATUS_PENDING;
        }

        return null;
    }

    private function countByApprovalStatus($rows): array
    {
        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['approval_status'] ?? null;
            if (! $status) {
                continue;
            }

            $counts['total']++;

            if ($status === self::STATUS_PENDING) {
                $counts['pending']++;
            } elseif ($status === self::STATUS_APPROVED) {
                $counts['approved']++;
            } elseif ($status === self::STATUS_REJECTED) {
                $counts['rejected']++;
            }
        }

        return $counts;
    }

    private function fetchAuthorsByActivity(array $activityIds): array
    {
        if (count($activityIds) === 0) {
            return [];
        }

        $rows = DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->whereIn('ram.activity_id', $activityIds)
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
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activityId)
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
            ->map(fn($row) => (array) $row)
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
                $payload['url'] = null;
                return $payload;
            })
            ->all();
    }

    private function getStageIds(): array
    {
        return [
            'assistant' => (int) DB::table('approval_stages')->where('code', 'assistant')->value('id'),
            'manager' => (int) DB::table('approval_stages')->where('code', 'manager')->value('id'),
        ];
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
