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
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $academicYear = $this->resolveAcademicYear(
            (int) $lecturer->id,
            $hoursStageId,
            $request->validated()['academic_year_id'] ?? null
        );
        if (! $academicYear) {
            return response()->json(['message' => 'academic year not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $hoursStageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $this->emptyOverviewPayload($academicYear),
            ], Response::HTTP_OK);
        }

        $this->backfillComputedHoursForLecturer((int) $lecturer->id, (int) $academicYear->id);

        $totals = $this->summaryTotals($lecturer->id, (int) $academicYear->id, $hoursStageId);
        $requiredHours = $this->requiredHours((int) $academicYear->id);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'academic_year_id' => (int) $academicYear->id,
                'academic_year_code' => $academicYear->code,
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
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $academicYear = $this->resolveAcademicYear(
            (int) $lecturer->id,
            $hoursStageId,
            $request->validated()['academic_year_id'] ?? null
        );
        if (! $academicYear) {
            return response()->json(['message' => 'academic year not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $hoursStageId) {
            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'academic_year_id' => (int) $academicYear->id,
                    'academic_year_code' => $academicYear->code,
                    'total_approved_hours' => 0,
                    'items' => [],
                ],
            ], Response::HTTP_OK);
        }

        $this->backfillComputedHoursForLecturer((int) $lecturer->id, (int) $academicYear->id);

        $baseQuery = $this->hoursApprovalQuery($lecturer->id, (int) $academicYear->id, $hoursStageId);
        $rows = $baseQuery
            ->where('aa.status', 'approved')
            ->groupBy('ak.id', 'ak.name')
            ->select([
                'ak.id as kind_id',
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
                'label' => $row->kind_name,
                'hours' => $hours,
                'percentage' => $percentage,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'academic_year_id' => (int) $academicYear->id,
                'academic_year_code' => $academicYear->code,
                'total_approved_hours' => $totalApproved,
                'items' => $items,
            ],
        ], Response::HTTP_OK);
    }

    public function batches(LecturerPersonalHoursRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        $academicYear = $this->resolveAcademicYear(
            (int) $lecturer->id,
            $hoursStageId,
            $request->validated()['academic_year_id'] ?? null
        );
        if (! $academicYear) {
            return response()->json(['message' => 'academic year not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $page = max(1, (int) ($request->validated()['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($request->validated()['per_page'] ?? 12)));

        if (! $hoursStageId) {
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

        $this->backfillComputedHoursForLecturer((int) $lecturer->id, (int) $academicYear->id);

        $statusCase = "CASE
            WHEN SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
            WHEN SUM(CASE WHEN aa.status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
            ELSE 'approved'
        END";

        $batchQuery = $this->hoursApprovalQuery($lecturer->id, (int) $academicYear->id, $hoursStageId)
            ->select([
                DB::raw('UNIX_TIMESTAMP(aa.created_at) as batch_id'),
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
                DB::raw('MAX(aa.created_at) as submitted_at'),
                DB::raw('MAX(aa.decided_at) as decided_at'),
                DB::raw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as total_hours'),
                DB::raw($statusCase . ' as status_code'),
            ])
            ->groupBy(DB::raw('UNIX_TIMESTAMP(aa.created_at)'), 'ay.id', 'ay.code');

        $paginator = $batchQuery
            ->orderByDesc(DB::raw('MAX(aa.created_at)'))
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            $statusCode = $row->status_code;
            $submittedAt = $row->submitted_at;
            return [
                'batch_id' => (int) $row->batch_id,
                'batch_name' => $this->formatBatchName($submittedAt),
                'academic_year_id' => (int) $row->academic_year_id,
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
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $hoursStageId = $this->resolveStageId('hours');
        if (! $hoursStageId) {
            return response()->json(['message' => 'hours approval stage not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->backfillComputedHoursForLecturer((int) $lecturer->id, null);

        $items = $this->hoursApprovalQuery($lecturer->id, null, $hoursStageId)
            ->whereRaw('UNIX_TIMESTAMP(aa.created_at) = ?', [$batchId])
            ->select([
                'ra.id as activity_id',
                'ra.title as activity_title',
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
            return response()->json(['message' => 'batch not found'], Response::HTTP_NOT_FOUND);
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
                'kind_name' => $row->kind_name,
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
        return $id ? (int) $id : null;
    }

    private function resolveAcademicYear(int $lecturerId, ?int $hoursStageId, ?int $academicYearId)
    {
        if ($academicYearId) {
            return AcademicYearResolver::resolve($academicYearId);
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
                $resolved = AcademicYearResolver::resolve((int) $yearIdWithData);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        return AcademicYearResolver::current();
    }

    private function requiredHours(int $academicYearId): float
    {
        $value = DB::table('workload_quotas')
            ->where('academic_year_id', $academicYearId)
            ->value('required_hours');

        return $value !== null ? (float) $value : 0.0;
    }

    private function backfillComputedHoursForLecturer(int $lecturerId, ?int $academicYearId): void
    {
        $approvedStatusId = (int) (DB::table('activity_statuses')
            ->where('code', 'approved')
            ->value('id') ?? 0);

        if ($approvedStatusId <= 0) {
            return;
        }

        $this->hoursRecomputeService->recomputeApprovedActivitiesForLecturer(
            $lecturerId,
            $approvedStatusId,
            $academicYearId
        );
    }

    private function emptyOverviewPayload(object $academicYear): array
    {
        return [
            'academic_year_id' => (int) $academicYear->id,
            'academic_year_code' => $academicYear->code,
            'required_hours' => $this->requiredHours((int) $academicYear->id),
            'approved_hours' => 0.0,
            'pending_hours' => 0.0,
            'rejected_hours' => 0.0,
        ];
    }

    private function summaryTotals(int $lecturerId, int $academicYearId, int $hoursStageId): array
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
            return 'Đợt';
        }

        return 'Đợt ' . Carbon::parse($submittedAt)->format('d/m/Y');
    }
}
