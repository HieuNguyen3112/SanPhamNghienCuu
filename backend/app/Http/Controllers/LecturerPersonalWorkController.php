<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerPersonalWorkIndexRequest;
use App\Services\Hours\HoursRecomputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerPersonalWorkController extends Controller
{
    private HoursRecomputeService $hoursRecomputeService;

    public function __construct(HoursRecomputeService $hoursRecomputeService)
    {
        $this->hoursRecomputeService = $hoursRecomputeService;
    }

    public function index(LecturerPersonalWorkIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->backfillApprovedHours((int) $lecturer->id);

        $filters = $this->normalizeFilters($request->validated());
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $listQuery = $this->baseQuery($lecturer->id);
        $this->applyFilters($listQuery, $filters);

        [$sortColumn, $sortDirection] = $this->parseSort($filters['sort'] ?? null);
        $listQuery->orderBy($sortColumn, $sortDirection);

        $paginator = $listQuery->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn($row) => $this->mapRow($row, $lecturer->id))
            ->all();

        $stats = $this->buildStats($lecturer->id, $filters);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'stats' => $stats,
                'items' => $items,
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
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->backfillApprovedHours((int) $lecturer->id);

        $row = $this->baseQuery($lecturer->id)
            ->where('ra.id', $activity)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'work not found'], Response::HTTP_NOT_FOUND);
        }

        $detail = $this->buildDetail($row, $lecturer->id);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $detail,
        ], Response::HTTP_OK);
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function backfillApprovedHours(int $lecturerId): void
    {
        $approvedStatusId = (int) ($this->resolveStatusId('approved') ?? 0);

        if ($approvedStatusId <= 0) {
            return;
        }

        $this->hoursRecomputeService->recomputeApprovedActivitiesForLecturer(
            $lecturerId,
            $approvedStatusId,
            null
        );
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

    private function normalizeFilters(array $validated): array
    {
        return [
            'status' => $validated['status'] ?? null,
            'q' => $validated['q'] ?? null,
            'year' => $validated['year'] ?? null,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'kind_id' => $validated['kind_id'] ?? null,
            'type_id' => $validated['type_id'] ?? null,
            'role_id' => $validated['role_id'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'page' => $validated['page'] ?? null,
            'per_page' => $validated['per_page'] ?? null,
        ];
    }

    private function baseQuery(int $lecturerId)
    {
        $yearExpr = $this->activityYearExpression();
        $venueExpr = $this->activityVenueExpression();

        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', '=', $lecturerId)
                    ->orWhereNotNull('ram.lecturer_id');
            })
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.abstract',
                'ra.owner_lecturer_id',
                'ra.kind_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'ra.type_id',
                'at.code as type_code',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                'ra.status_id',
                'ast.code as status_code',
                'ast.name as status_name',
                'ram.member_role_id',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'ram.confirmation_status as member_confirmation_status',
                'ram.hours_assigned',
                'ra.submitted_at',
                'ra.approved_at',
                'ra.updated_at',
                'ra.total_hours_calc',
                DB::raw($yearExpr . ' as work_year'),
                DB::raw($venueExpr . ' as venue_name'),
                'pd.journal_name',
                'pd.issn',
                'pd.doi',
                'bd.publisher',
                'bd.isbn',
                'prd.project_code',
                'cd.conference_name',
                'cd.location',
            ]);
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'pending') {
                $query->whereIn('ast.code', ['pending_member_confirm', 'pending_faculty_review', 'submitted']);
            } elseif ($filters['status'] === 'rejected') {
                $query->whereIn('ast.code', ['member_rejected', 'rejected']);
            } else {
                $query->where('ast.code', $filters['status']);
            }
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhere('ra.activity_code', 'like', $keyword)
                    ->orWhere('pd.journal_name', 'like', $keyword)
                    ->orWhere('bd.publisher', 'like', $keyword)
                    ->orWhere('prd.project_code', 'like', $keyword)
                    ->orWhere('cd.conference_name', 'like', $keyword);
            });
        }

        if (! empty($filters['academic_year_id'])) {
            $query->where('ra.academic_year_id', $filters['academic_year_id']);
        }

        if (! empty($filters['kind_id'])) {
            $query->where('ra.kind_id', $filters['kind_id']);
        }

        if (! empty($filters['type_id'])) {
            $query->where('ra.type_id', $filters['type_id']);
        }

        if (! empty($filters['role_id'])) {
            $query->where('ram.member_role_id', $filters['role_id']);
        }

        if (! empty($filters['year'])) {
            $query->whereRaw($this->activityYearExpression() . ' = ?', [$filters['year']]);
        }
    }

    private function parseSort(?string $sort): array
    {
        $key = 'ra.updated_at';
        $direction = 'desc';

        if ($sort) {
            [$rawKey, $rawDir] = array_pad(explode(':', $sort, 2), 2, null);
            $rawDir = strtolower((string) $rawDir);
            $dir = in_array($rawDir, ['asc', 'desc'], true) ? $rawDir : null;

            $allowed = [
                'updated_at' => 'ra.updated_at',
                'title' => 'ra.title',
                'work_year' => DB::raw($this->activityYearExpression()),
                'role_name' => 'mr.name',
            ];

            if ($rawKey && array_key_exists($rawKey, $allowed)) {
                $key = $allowed[$rawKey];
            }

            if ($dir) {
                $direction = $dir;
            }
        }

        return [$key, $direction];
    }

    private function buildStats(int $lecturerId, array $filters): array
    {
        $statsQuery = $this->baseQuery($lecturerId);

        $statsFilters = $filters;
        $statsFilters['status'] = null;
        $statsFilters['page'] = null;
        $statsFilters['per_page'] = null;
        $statsFilters['sort'] = null;
        $this->applyFilters($statsQuery, $statsFilters);

        $rows = $statsQuery
            ->select([
                'ast.code as status_code',
                DB::raw('COUNT(DISTINCT ra.id) as total_count'),
            ])
            ->groupBy('ast.code')
            ->get();

        $counts = [
            'total_count' => 0,
            'approved_count' => 0,
            'pending_count' => 0,
            'rejected_count' => 0,
            'draft_count' => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row->total_count;
            $counts['total_count'] += $count;

            switch ($row->status_code) {
                case 'approved':
                    $counts['approved_count'] = $count;
                    break;
                case 'pending_member_confirm':
                case 'pending_faculty_review':
                case 'submitted':
                    $counts['pending_count'] += $count;
                    break;
                case 'member_rejected':
                case 'rejected':
                    $counts['rejected_count'] += $count;
                    break;
                case 'draft':
                    $counts['draft_count'] = $count;
                    break;
            }
        }

        return $counts;
    }

    private function mapRow(object $row, int $lecturerId): array
    {
        $statusCode = $row->status_code;
        $isOwner = (int) $row->owner_lecturer_id === (int) $lecturerId;
        $actions = $this->buildActions(
            $statusCode,
            $isOwner,
            $row->member_confirmation_status ?? null
        );

        return [
            'activity_id' => (int) $row->activity_id,
            'activity_code' => $row->activity_code,
            'title' => $row->title,
            'kind_id' => (int) $row->kind_id,
            'kind_code' => $row->kind_code,
            'kind_name' => $this->mapKindName($row->kind_code, $row->kind_name),
            'type_id' => $row->type_id ? (int) $row->type_id : null,
            'type_name' => $this->mapTypeName($row->type_code, $row->type_name),
            'academic_year_id' => (int) $row->academic_year_id,
            'academic_year_code' => $row->academic_year_code,
            'status_id' => (int) $row->status_id,
            'status_code' => $statusCode,
            'status_name' => $this->mapStatusName($statusCode, $row->status_name),
            'work_year' => $row->work_year !== null ? (int) $row->work_year : null,
            'venue_name' => $this->normalizeVenueName($row->venue_name, $row),
            'member_role_id' => $row->member_role_id ? (int) $row->member_role_id : null,
            'member_role_name' => $this->mapRoleName($row->member_role_code, $row->member_role_name),
            'lecturer_hours' => $row->hours_assigned !== null ? (string) $row->hours_assigned : null,
            'submitted_at' => $this->normalizeDateTime($row->submitted_at),
            'approved_at' => $this->normalizeDateTime($row->approved_at),
            'updated_at' => $this->normalizeDateTime($row->updated_at) ?? $row->updated_at,
            'actions' => $actions,
        ];
    }

    private function buildDetail(object $row, int $lecturerId): array
    {
        $detail = [
            'activity_id' => (int) $row->activity_id,
            'activity_code' => $row->activity_code,
            'title' => $row->title,
            'abstract' => $row->abstract ?? null,
            'kind_id' => (int) $row->kind_id,
            'kind_code' => $row->kind_code,
            'kind_name' => $this->mapKindName($row->kind_code, $row->kind_name),
            'type_id' => $row->type_id ? (int) $row->type_id : null,
            'type_name' => $this->mapTypeName($row->type_code, $row->type_name),
            'academic_year_id' => (int) $row->academic_year_id,
            'academic_year_code' => $row->academic_year_code,
            'status_id' => (int) $row->status_id,
            'status_code' => $row->status_code,
            'status_name' => $this->mapStatusName($row->status_code, $row->status_name),
            'work_year' => $row->work_year !== null ? (int) $row->work_year : null,
            'venue_name' => $this->normalizeVenueName($row->venue_name, $row),
            'member_role_id' => $row->member_role_id ? (int) $row->member_role_id : null,
            'member_role_name' => $this->mapRoleName($row->member_role_code, $row->member_role_name),
            'lecturer_hours' => $row->hours_assigned !== null ? (string) $row->hours_assigned : null,
            'submitted_at' => $this->normalizeDateTime($row->submitted_at),
            'approved_at' => $this->normalizeDateTime($row->approved_at),
            'total_hours_calc' => $row->total_hours_calc !== null ? (string) $row->total_hours_calc : null,
            'rejection_note' => $this->resolveRejectionNote((int) $row->activity_id),
            'authors' => $this->buildAuthors((int) $row->activity_id),
            'member_confirmations' => $this->buildMemberConfirmations((int) $row->activity_id),
            'rejected_members' => $this->buildRejectedMembers((int) $row->activity_id),
            'evidence_items' => $this->buildEvidenceItems((int) $row->activity_id),
            'approvals' => $this->buildApprovals((int) $row->activity_id),
            'status_histories' => $this->buildStatusHistories((int) $row->activity_id),
            'actions' => $this->buildActions(
                $row->status_code,
                (int) $row->owner_lecturer_id === (int) $lecturerId,
                $row->member_confirmation_status ?? null
            ),
        ];

        return $detail;
    }

    private function buildActions(?string $statusCode, bool $isOwner, ?string $memberConfirmationStatus): array
    {
        $canTeamReworkRejected = ! $isOwner
            && $statusCode === 'rejected'
            && $memberConfirmationStatus === 'accepted';

        $canEdit = ($isOwner && in_array($statusCode, ['draft', 'member_rejected', 'rejected'], true))
            || $canTeamReworkRejected;

        return [
            'can_edit' => $canEdit,
            'can_submit' => $canEdit,
            'can_delete' => $isOwner && in_array($statusCode, ['draft', 'member_rejected'], true),
            'can_view' => true,
            'can_reinvite' => $isOwner && $statusCode === 'member_rejected',
        ];
    }

    private function buildAuthors(int $activityId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ram.activity_id', $activityId)
            ->orderBy('ram.id')
            ->select([
                'l.id as lecturer_id',
                'l.full_name as lecturer_full_name',
                'mr.id as member_role_id',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'd.name as department_name',
                'ram.contribution_share',
                'ram.confirmation_status',
                'ram.confirmation_note',
                'ram.responded_at',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'lecturer_id' => (int) $row->lecturer_id,
                    'lecturer_full_name' => $row->lecturer_full_name,
                    'member_role_id' => $row->member_role_id ? (int) $row->member_role_id : null,
                    'member_role_name' => $this->mapRoleName($row->member_role_code, $row->member_role_name),
                    'department_name' => $row->department_name,
                    'contribution_share' => $row->contribution_share !== null ? (string) $row->contribution_share : null,
                    'confirmation_status' => $row->confirmation_status,
                    'confirmation_note' => $row->confirmation_note,
                    'responded_at' => $this->normalizeDateTime($row->responded_at),
                ];
            })
            ->all();
    }

    private function buildMemberConfirmations(int $activityId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activityId)
            ->orderBy('ram.id')
            ->select([
                'ram.id as member_id',
                'ram.lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'ram.confirmation_status',
                'ram.confirmation_note',
                'ram.responded_at',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'member_id' => (int) $row->member_id,
                    'lecturer_id' => (int) $row->lecturer_id,
                    'lecturer_code' => $row->lecturer_code,
                    'lecturer_full_name' => $row->lecturer_full_name,
                    'member_role_code' => $row->member_role_code,
                    'member_role_name' => $this->mapRoleName($row->member_role_code, $row->member_role_name),
                    'confirmation_status' => $row->confirmation_status,
                    'confirmation_note' => $row->confirmation_note,
                    'responded_at' => $this->normalizeDateTime($row->responded_at),
                ];
            })
            ->all();
    }

    private function buildRejectedMembers(int $activityId): array
    {
        return collect($this->buildMemberConfirmations($activityId))
            ->filter(fn($row) => ($row['confirmation_status'] ?? null) === 'rejected')
            ->values()
            ->all();
    }

    private function buildEvidenceItems(int $activityId): array
    {
        return DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->orderByDesc('ef.uploaded_at')
            ->select([
                'ef.id',
                'ef.file_type_id',
                'eft.name as file_type_name',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.uploaded_at',
            ])
            ->get()
            ->map(function ($row) use ($activityId) {
                return [
                    'evidence_file_id' => (int) $row->id,
                    'file_type_id' => (int) $row->file_type_id,
                    'file_type_name' => $row->file_type_name,
                    'disk' => $row->disk,
                    'path' => $row->path,
                    'preview_url' => route('research.activities.evidence.preview', [
                        'activity' => $activityId,
                        'evidence' => $row->id,
                    ], false),
                    'download_url' => route('research.activities.evidence.download', [
                        'activity' => $activityId,
                        'evidence' => $row->id,
                    ], false),
                    'url' => route('research.activities.evidence.preview', [
                        'activity' => $activityId,
                        'evidence' => $row->id,
                    ], false),
                    'original_name' => $row->original_name,
                    'mime_type' => $row->mime_type,
                    'size_bytes' => $row->size_bytes !== null ? (int) $row->size_bytes : 0,
                    'uploaded_at' => $this->normalizeDateTime($row->uploaded_at),
                ];
            })
            ->all();
    }

    private function buildApprovals(int $activityId): array
    {
        return DB::table('activity_approvals as aa')
            ->leftJoin('approval_stages as ast', 'aa.stage_id', '=', 'ast.id')
            ->leftJoin('users as u', 'aa.decided_by_user_id', '=', 'u.id')
            ->where('aa.activity_id', $activityId)
            ->orderBy('ast.order_no')
            ->select([
                'ast.code as stage_code',
                'ast.name as stage_name',
                'aa.status',
                'aa.decided_by_user_id',
                'u.name as decided_by_user_name',
                'aa.decided_at',
                'aa.note',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'stage_code' => $row->stage_code,
                    'stage_name' => $row->stage_name,
                    'status' => $row->status,
                    'decided_by_user_id' => $row->decided_by_user_id ? (int) $row->decided_by_user_id : null,
                    'decided_by_user_name' => $row->decided_by_user_name,
                    'decided_at' => $this->normalizeDateTime($row->decided_at),
                    'note' => $row->note,
                ];
            })
            ->all();
    }

    private function buildStatusHistories(int $activityId): array
    {
        return DB::table('activity_status_histories as ash')
            ->leftJoin('activity_statuses as from_status', 'ash.from_status_id', '=', 'from_status.id')
            ->leftJoin('activity_statuses as to_status', 'ash.to_status_id', '=', 'to_status.id')
            ->leftJoin('users as u', 'ash.acted_by_user_id', '=', 'u.id')
            ->where('ash.activity_id', $activityId)
            ->orderBy('ash.acted_at')
            ->select([
                'ash.acted_at',
                'ash.acted_by_user_id',
                'u.name as acted_by_user_name',
                'from_status.code as from_status_code',
                'to_status.code as to_status_code',
                'ash.note',
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'acted_at' => $this->normalizeDateTime($row->acted_at),
                    'acted_by_user_id' => (int) $row->acted_by_user_id,
                    'acted_by_user_name' => $row->acted_by_user_name,
                    'from_status_code' => $row->from_status_code,
                    'to_status_code' => $row->to_status_code,
                    'note' => $row->note,
                ];
            })
            ->all();
    }

    private function resolveRejectionNote(int $activityId): ?string
    {
        $row = DB::table('activity_status_histories as ash')
            ->join('activity_statuses as ast', 'ash.to_status_id', '=', 'ast.id')
            ->where('ash.activity_id', $activityId)
            ->where('ast.code', 'rejected')
            ->orderByDesc('ash.acted_at')
            ->select(['ash.note'])
            ->first();

        if ($row?->note) {
            return $row->note;
        }

        $memberRejected = DB::table('research_activity_members')
            ->where('activity_id', $activityId)
            ->where('confirmation_status', 'rejected')
            ->whereNotNull('confirmation_note')
            ->orderByDesc('responded_at')
            ->orderByDesc('id')
            ->value('confirmation_note');

        return $memberRejected ?: null;
    }

    private function activityYearExpression(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "COALESCE(pd.year, bd.year, CAST(strftime('%Y', prd.start_month) AS INTEGER), CAST(strftime('%Y', cd.held_on) AS INTEGER), CAST(strftime('%Y', ra.start_date) AS INTEGER), CAST(strftime('%Y', ra.created_at) AS INTEGER))";
        }

        return 'COALESCE(pd.year, bd.year, YEAR(prd.start_month), YEAR(cd.held_on), YEAR(ra.start_date), YEAR(ra.created_at))';
    }

    private function activityVenueExpression(): string
    {
        return "CASE
            WHEN ak.code = 'paper' THEN pd.journal_name
            WHEN ak.code = 'book' THEN bd.publisher
            WHEN ak.code = 'project' THEN prd.project_code
            WHEN ak.code = 'conference' THEN cd.conference_name
            ELSE NULL
        END";
    }

    private function mapStatusName(?string $code, ?string $fallback): ?string
    {
        $mapped = match ($code) {
            'draft' => 'Bản nháp',
            'pending_member_confirm' => 'Chờ thành viên xác nhận',
            'member_rejected' => 'Thành viên từ chối',
            'pending_faculty_review' => 'Chờ khoa duyệt',
            'submitted' => 'Đã gửi duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => null,
        };

        return $mapped ?? ($fallback !== null && $fallback !== '' ? $fallback : $code);
    }

    private function mapKindName(?string $code, ?string $fallback): ?string
    {
        $mapped = match ($code) {
            'paper' => 'Bài báo',
            'book' => 'Sách/Giáo trình',
            'project' => 'Đề tài KH&CN',
            'conference' => 'Hội nghị/Hội thảo',
            default => null,
        };

        return $mapped ?? ($fallback !== null && $fallback !== '' ? $fallback : $code);
    }

    private function mapTypeName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'hdgsnn_900' => 'Bài báo HDGSNN 1-2 điểm (900 giờ)',
            'hdgsnn_600' => 'Bài báo HDGSNN <= 1 điểm (600 giờ)',
            'hdgsnn_300' => 'Bài báo có ISSN/ISBN (300 giờ)',
            'textbook' => 'Giáo trình',
            'reference' => 'Tài liệu tham khảo',
            'bo', 'ministry' => 'Đề tài cấp Bộ (2 năm)',
            'coso', 'university' => 'Đề tài cấp Trường (1 năm)',
            'report' => 'Báo cáo hội thảo',
            'attend' => 'Tham dự hội thảo',
            default => null,
        };

        return $mapped ?? ($fallback !== null && $fallback !== '' ? $fallback : strtoupper($code));
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

        return $mapped ?? ($fallback !== null && $fallback !== '' ? $fallback : ucwords(str_replace('_', ' ', $code)));
    }

    private function normalizeDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private function normalizeVenueName(?string $venue, object $row): ?string
    {
        if ($venue !== null && trim($venue) !== '') {
            return $venue;
        }

        $fallbacks = [
            $row->journal_name ?? null,
            $row->publisher ?? null,
            $row->project_code ?? null,
            $row->conference_name ?? null,
            $row->location ?? null,
        ];

        foreach ($fallbacks as $fallback) {
            if ($fallback && trim($fallback) !== '') {
                return $fallback;
            }
        }

        return null;
    }
}
