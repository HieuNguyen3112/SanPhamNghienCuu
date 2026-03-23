<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerPersonalHoursRequest;
use App\Services\Hours\HoursRecomputeService;
use App\Support\AcademicYearResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerPersonalHoursController extends Controller
{
    private HoursRecomputeService $hoursRecomputeService;

    public function __construct(
        HoursRecomputeService $hoursRecomputeService
    ) {
        $this->hoursRecomputeService = $hoursRecomputeService;
    }

    public function overview(LecturerPersonalHoursRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();
        $hoursStageId = $this->resolveStageId('hours');
        $scope = $this->resolveHoursScope(
            (int) $lecturer->id,
            $hoursStageId,
            $filters
        );
        if (! $scope) {
            return response()->json(['message' => 'Không tìm thấy năm học.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $hoursStageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $this->emptyOverviewPayload($scope),
            ], Response::HTTP_OK);
        }

        $scopeAcademicYearId = $scope['academic_year'] ? (int) $scope['academic_year']->id : null;
        $this->backfillComputedHoursForLecturer((int) $lecturer->id, $scopeAcademicYearId);

        $totals = $this->summaryTotals((int) $lecturer->id, $scopeAcademicYearId, $hoursStageId);
        $requiredHours = $this->requiredHoursByScope($scope);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'mode' => $scope['mode'],
                'academic_year_id' => $scopeAcademicYearId,
                'academic_year_code' => $scope['label'],
                'required_hours' => $requiredHours,
                'approved_hours' => $totals['approved_hours'],
                'pending_hours' => $totals['pending_hours'],
                'rejected_hours' => $totals['rejected_hours'],
            ],
        ], Response::HTTP_OK);
    }
    public function distribution(LecturerPersonalHoursRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();
        $hoursStageId = $this->resolveStageId('hours');
        $scope = $this->resolveHoursScope(
            (int) $lecturer->id,
            $hoursStageId,
            $filters
        );
        if (! $scope) {
            return response()->json(['message' => 'Không tìm thấy năm học.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $hoursStageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'mode' => $scope['mode'],
                    'academic_year_id' => $scope['academic_year'] ? (int) $scope['academic_year']->id : null,
                    'academic_year_code' => $scope['label'],
                    'total_approved_hours' => 0,
                    'items' => [],
                ],
            ], Response::HTTP_OK);
        }

        $scopeAcademicYearId = $scope['academic_year'] ? (int) $scope['academic_year']->id : null;
        $this->backfillComputedHoursForLecturer((int) $lecturer->id, $scopeAcademicYearId);

        $baseQuery = $this->hoursApprovalQuery((int) $lecturer->id, $scopeAcademicYearId, $hoursStageId);
        $rows = $baseQuery
            ->where('aa.status', 'approved')
            ->groupBy('ak.id', 'ak.code', 'ak.name')
            ->select([
                'ak.id as kind_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                DB::raw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as hours_total'),
            ])
            ->get();

        $totalApproved = (float) $rows->sum(function ($row) {
            return (float) $row->hours_total;
        });

        $items = $rows->map(function ($row) use ($totalApproved) {
            $hours = (float) $row->hours_total;
            $percentage = $totalApproved > 0
                ? (int) round(($hours / $totalApproved) * 100)
                : 0;

            return [
                'kind_id' => (int) $row->kind_id,
                'label' => $this->mapKindName($row->kind_code ?? null, $row->kind_name ?? null),
                'hours' => $hours,
                'percentage' => $percentage,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'mode' => $scope['mode'],
                'academic_year_id' => $scopeAcademicYearId,
                'academic_year_code' => $scope['label'],
                'total_approved_hours' => $totalApproved,
                'items' => $items,
            ],
        ], Response::HTTP_OK);
    }
    public function batches(LecturerPersonalHoursRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();
        $hoursStageId = $this->resolveStageId('hours');
        $scope = $this->resolveHoursScope(
            (int) $lecturer->id,
            $hoursStageId,
            $filters
        );
        if (! $scope) {
            return response()->json(['message' => 'Không tìm thấy năm học.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        if (! $hoursStageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'mode' => $scope['mode'],
                    'academic_year_id' => $scope['academic_year'] ? (int) $scope['academic_year']->id : null,
                    'academic_year_code' => $scope['label'],
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

        $scopeAcademicYearId = $scope['academic_year'] ? (int) $scope['academic_year']->id : null;
        $this->backfillComputedHoursForLecturer((int) $lecturer->id, $scopeAcademicYearId);

        $statusCase = "CASE
            WHEN SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
            WHEN SUM(CASE WHEN aa.status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
            ELSE 'approved'
        END";
        $batchIdExpr = $this->approvalBatchIdExpression('aa.created_at');

        $batchQuery = $this->hoursApprovalQuery((int) $lecturer->id, $scopeAcademicYearId, $hoursStageId)
            ->select([
                DB::raw($batchIdExpr . ' as batch_id'),
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
                DB::raw('MAX(aa.created_at) as submitted_at'),
                DB::raw('MAX(aa.decided_at) as decided_at'),
                DB::raw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as total_hours'),
                DB::raw($statusCase . ' as status_code'),
            ])
            ->groupBy(DB::raw($batchIdExpr), 'ay.id', 'ay.code');

        $paginator = $batchQuery
            ->orderByDesc(DB::raw('MAX(aa.created_at)'))
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            $statusCode = $row->status_code;
            $submittedAt = $row->submitted_at;
            return [
                'batch_id' => (int) $row->batch_id,
                'batch_name' => $this->formatBatchName($submittedAt),
                'academic_year_id' => $row->academic_year_id !== null ? (int) $row->academic_year_id : null,
                'academic_year_code' => $row->academic_year_code,
                'status' => $statusCode,
                'status_label' => $this->statusLabel($statusCode),
                'submitted_at' => $submittedAt,
                'decided_at' => $statusCode === 'pending' ? null : $row->decided_at,
                'total_hours' => (float) $row->total_hours,
            ];
        })->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'mode' => $scope['mode'],
                'academic_year_id' => $scopeAcademicYearId,
                'academic_year_code' => $scope['label'],
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
    public function batchDetail(Request $request, int $batchId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        if (! $hoursStageId) {
            return response()->json(['message' => 'Chưa cấu hình bước duyệt giờ NCKH.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->backfillComputedHoursForLecturer((int) $lecturer->id, null);
        $batchIdExpr = $this->approvalBatchIdExpression('aa.created_at');

        $items = $this->hoursApprovalQuery($lecturer->id, null, $hoursStageId)
            ->whereRaw($batchIdExpr . ' = ?', [$batchId])
            ->select([
                'ra.id as activity_id',
                'ra.title as activity_title',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'ram.hours_assigned as hours_assigned',
                'aa.status as approval_status',
                'aa.created_at as submitted_at',
                'aa.decided_at as decided_at',
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
            ])
            ->orderByDesc('aa.created_at')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đợt xét duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $statusCode = $this->aggregateStatus($items->pluck('approval_status')->all());
        $submittedAt = $items->max('submitted_at');
        $decidedAt = $statusCode === 'pending' ? null : $items->max('decided_at');
        $totalHours = (float) $items->sum(function ($row) {
            return (float) ($row->hours_assigned ?? 0);
        });

        $first = $items->first();

        $detailItems = $items->map(function ($row) {
            return [
                'activity_id' => (int) $row->activity_id,
                'title' => $row->activity_title,
                'kind_name' => $this->mapKindName($row->kind_code ?? null, $row->kind_name ?? null),
                'lecturer_hours' => $row->hours_assigned !== null ? (float) $row->hours_assigned : 0.0,
                'status' => $row->approval_status,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'batch_id' => $batchId,
                'batch_name' => $this->formatBatchName($submittedAt),
                'academic_year_id' => (int) $first->academic_year_id,
                'academic_year_code' => $first->academic_year_code,
                'status' => $statusCode,
                'status_label' => $this->statusLabel($statusCode),
                'submitted_at' => $submittedAt,
                'decided_at' => $decidedAt,
                'total_hours' => $totalHours,
                'items' => $detailItems,
            ],
        ], Response::HTTP_OK);
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function resolveStageId(string $code): ?int
    {
        $id = DB::table('approval_stages')->where('code', $code)->value('id');
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

        return null;
    }

    private function resolveAcademicYear(int $lecturerId, ?int $hoursStageId, ?int $academicYearId)
    {
        if ($academicYearId) {
            return AcademicYearResolver::resolve($academicYearId);
        }

        $currentAcademicYear = AcademicYearResolver::current();
        if ($currentAcademicYear) {
            return $currentAcademicYear;
        }

        if ($hoursStageId) {
            $yearIdWithData = DB::table('activity_approvals as aa')
                ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
                ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
                    $join->on('ram.activity_id', '=', 'ra.id')
                        ->where('ram.lecturer_id', '=', $lecturerId);
                })
                ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
                ->where('aa.stage_id', $hoursStageId)
                ->where(function ($query) use ($lecturerId) {
                    $query->where('ra.owner_lecturer_id', $lecturerId)
                        ->orWhere('ram.confirmation_status', 'accepted');
                })
                ->whereNotNull('ra.academic_year_id')
                ->orderByDesc('ay.is_active')
                ->orderByDesc('ay.start_date')
                ->value('ra.academic_year_id');

            if ($yearIdWithData) {
                return AcademicYearResolver::resolve((int) $yearIdWithData);
            }
        }

        return null;
    }

    private function resolveHoursScope(int $lecturerId, ?int $hoursStageId, array $filters): ?array
    {
        $mode = strtolower(trim((string) ($filters['mode'] ?? 'year')));
        $isOverallMode = $mode === 'overall';

        if ($isOverallMode) {
            $allAcademicYearIds = DB::table('academic_years')
                ->orderByDesc('start_date')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            if ($allAcademicYearIds === []) {
                return null;
            }

            return [
                'mode' => 'overall',
                'academic_year' => null,
                'year_ids' => $allAcademicYearIds,
                'label' => 'Tổng thể',
            ];
        }

        $academicYear = $this->resolveAcademicYear(
            $lecturerId,
            $hoursStageId,
            isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null
        );

        if (! $academicYear) {
            return null;
        }

        return [
            'mode' => 'year',
            'academic_year' => $academicYear,
            'year_ids' => [(int) $academicYear->id],
            'label' => (string) $academicYear->code,
        ];
    }

    private function requiredHours(int $academicYearId): float
    {
        $value = DB::table('workload_quotas')
            ->where('academic_year_id', $academicYearId)
            ->value('required_hours');

        return $value !== null ? (float) $value : 0.0;
    }

    private function requiredHoursByScope(array $scope): float
    {
        if (($scope['mode'] ?? 'year') !== 'overall') {
            $academicYear = $scope['academic_year'] ?? null;
            if (! $academicYear) {
                return 0.0;
            }

            return $this->requiredHours((int) $academicYear->id);
        }

        $yearIds = is_array($scope['year_ids'] ?? null) ? $scope['year_ids'] : [];
        return $this->requiredHoursForAcademicYears($yearIds);
    }

    private function requiredHoursForAcademicYears(array $academicYearIds): float
    {
        $academicYearIds = array_values(array_unique(array_map(
            static fn($id) => (int) $id,
            array_filter($academicYearIds, static fn($id) => (int) $id > 0)
        )));

        if ($academicYearIds === []) {
            return 0.0;
        }

        $rows = DB::table('workload_quotas')
            ->whereIn('academic_year_id', $academicYearIds)
            ->select([
                'academic_year_id',
                DB::raw('MAX(required_hours) as required_hours'),
            ])
            ->groupBy('academic_year_id')
            ->get();

        return (float) $rows->sum(function ($row) {
            return (float) ($row->required_hours ?? 0);
        });
    }

    private function backfillComputedHoursForLecturer(int $lecturerId, ?int $academicYearId): void
    {
        $approvedStatusId = (int) ($this->resolveStatusId('approved') ?? 0);

        if ($approvedStatusId <= 0) {
            return;
        }

        $this->hoursRecomputeService->recomputeApprovedActivitiesForLecturer(
            $lecturerId,
            $approvedStatusId,
            $academicYearId
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

    private function emptyOverviewPayload(array $scope): array
    {
        $scopeAcademicYearId = $scope['academic_year'] ? (int) $scope['academic_year']->id : null;

        return [
            'mode' => $scope['mode'],
            'academic_year_id' => $scopeAcademicYearId,
            'academic_year_code' => $scope['label'],
            'required_hours' => $this->requiredHoursByScope($scope),
            'approved_hours' => 0.0,
            'pending_hours' => 0.0,
            'rejected_hours' => 0.0,
        ];
    }

    private function summaryTotals(int $lecturerId, ?int $academicYearId, int $hoursStageId): array
    {
        $row = $this->hoursApprovalQuery($lecturerId, $academicYearId, $hoursStageId)
            ->selectRaw("COALESCE(SUM(CASE WHEN aa.status = 'approved' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as approved_hours")
            ->selectRaw("COALESCE(SUM(CASE WHEN aa.status = 'pending' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as pending_hours")
            ->selectRaw("COALESCE(SUM(CASE WHEN aa.status = 'rejected' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as rejected_hours")
            ->first();

        return [
            'approved_hours' => $row?->approved_hours !== null ? (float) $row->approved_hours : 0.0,
            'pending_hours' => $row?->pending_hours !== null ? (float) $row->pending_hours : 0.0,
            'rejected_hours' => $row?->rejected_hours !== null ? (float) $row->rejected_hours : 0.0,
        ];
    }

    private function hoursApprovalQuery(int $lecturerId, ?int $academicYearId, int $hoursStageId)
    {
        $query = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('aa.stage_id', $hoursStageId);

        if ($academicYearId) {
            $query->where('ra.academic_year_id', $academicYearId);
        }

        return $query;
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

    private function formatBatchName(?string $submittedAt): string
    {
        if (! $submittedAt) {
            return "\u{0110}\u{1EE3}t";
        }

        return "\u{0110}\u{1EE3}t " . Carbon::parse($submittedAt)->format('d/m/Y');
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

    private function approvalBatchIdExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "CAST(EXTRACT(EPOCH FROM {$column}) AS BIGINT)",
            'sqlite' => "CAST(strftime('%s', {$column}) AS INTEGER)",
            default => "UNIX_TIMESTAMP({$column})",
        };
    }
}
