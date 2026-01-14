<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyLecturerHourApprovalListRequest;
use App\Http\Requests\Faculty\FacultyLecturerHourApprovalRejectRequest;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyLecturerHourApprovalController extends Controller
{
    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

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
                    ['code' => 'pending', 'label' => 'Chờ duyệt'],
                    ['code' => 'approved', 'label' => 'Đã duyệt'],
                    ['code' => 'rejected', 'label' => 'Từ chối'],
                ],
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

        $statusCase = "CASE
            WHEN SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
            WHEN SUM(CASE WHEN aa.status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
            ELSE 'approved'
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
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $requestId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

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
            ->orderByDesc('aa.created_at')
            ->select([
                'ra.id as activity_id',
                'ra.title as activity_title',
                'ak.name as activity_kind_name',
                'mr.name as member_role_name',
                'ram.hours_assigned as hours_converted',
                'aa.status as approval_status',
                'aa.created_at as submitted_at',
            ])
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        $statusCode = $this->aggregateStatus($items->pluck('approval_status')->all());
        $submittedAt = $items->max('submitted_at');
        $totalHours = (float) $items->sum(function ($row) {
            return (float) ($row->hours_converted ?? 0);
        });

        $detailItems = $items->map(function ($row) {
            return [
                'activity_id' => (int) $row->activity_id,
                'activity_title' => $row->activity_title,
                'activity_kind_name' => $row->activity_kind_name,
                'member_role_name' => $row->member_role_name,
                'hours_converted' => $row->hours_converted !== null ? (float) $row->hours_converted : 0.0,
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
                'note_from_lecturer' => null,
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

        $pendingIds = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->where('aa.stage_id', $stageId)
            ->where('ra.owner_lecturer_id', $requestId)
            ->where('aa.status', 'pending')
            ->pluck('aa.id')
            ->all();

        if (empty($pendingIds)) {
            return response()->json(['message' => 'request is not pending'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::table('activity_approvals')
            ->whereIn('id', $pendingIds)
            ->update([
                'status' => 'approved',
                'decided_by_user_id' => $request->user()?->id,
                'decided_at' => now(),
                'note' => null,
                'updated_at' => now(),
            ]);

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
            ],
        ], $request->user());

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

        if ($validated['reason_code'] === 'other' && empty($validated['reason_detail'])) {
            return response()->json([
                'message' => 'reason_detail is required when reason_code is other',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingIds = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->where('aa.stage_id', $stageId)
            ->where('ra.owner_lecturer_id', $requestId)
            ->where('aa.status', 'pending')
            ->pluck('aa.id')
            ->all();

        if (empty($pendingIds)) {
            return response()->json(['message' => 'request is not pending'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = json_encode([
            'reason_code' => $validated['reason_code'],
            'reason_detail' => $validated['reason_detail'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        DB::table('activity_approvals')
            ->whereIn('id', $pendingIds)
            ->update([
                'status' => 'rejected',
                'decided_by_user_id' => $request->user()?->id,
                'decided_at' => now(),
                'note' => $note,
                'updated_at' => now(),
            ]);

        $context = $this->buildHoursApprovalContext($stageId, $requestId);
        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'HOURS_REJECTED',
            'action_label' => 'Từ chối duyệt giờ NCKH',
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
                'reason_code' => $validated['reason_code'],
                'reason_detail' => $validated['reason_detail'] ?? null,
            ],
        ], $request->user());

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

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $status,
        };
    }

    private function aggregateStatus(array $statuses): string
    {
        if (in_array('pending', $statuses, true)) {
            return 'pending';
        }
        if (in_array('rejected', $statuses, true)) {
            return 'rejected';
        }
        return 'approved';
    }
}
