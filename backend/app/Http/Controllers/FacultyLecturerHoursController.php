<?php

namespace App\Http\Controllers;

use App\Exports\AdminLecturerHoursSummaryExport;
use App\Support\AcademicYearResolver;
use App\Support\LecturerHoursSummaryReportBuilder;
use App\Http\Requests\Faculty\FacultyLecturerHoursDetailRequest;
use App\Http\Requests\Faculty\FacultyLecturerHoursSummaryRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class FacultyLecturerHoursController extends Controller
{
    public function index(FacultyLecturerHoursSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'Không xác định được phạm vi khoa của tài khoản.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $requestedFacultyId = $validated['faculty_id'] ?? null;
        if ($requestedFacultyId && (int) $requestedFacultyId !== $scope['faculty_id']) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu ngoài phạm vi khoa.'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->summaryData($validated, $scope, true);

        return response()->json([
            'data' => $result['rows'],
            'meta' => [
                'filters' => $result['filters'],
                'options' => $result['options'],
                'totals' => $result['totals'],
                'pagination' => $result['pagination'],
            ],
        ], Response::HTTP_OK);
    }

    public function show(FacultyLecturerHoursDetailRequest $request, int $lecturer)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'Không xác định được phạm vi khoa của tài khoản.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $academicYearId = $this->resolveAcademicYearId($validated, $scope['faculty_id'], $lecturer);
        $academicYearCode = $this->resolveAcademicYearCode($academicYearId);
        $requiredHours = $this->resolveRequiredHours($academicYearId);
        $hoursStageId = $this->resolveHoursStageId();

        $lecturerRow = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->where('l.id', $lecturer)
            ->where('f.id', $scope['faculty_id'])
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'l.department_id',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'deg.name as degree_name',
                'ar.name as academic_rank_name',
            ])
            ->first();

        if (! $lecturerRow) {
            return response()->json(['message' => 'Không tìm thấy giảng viên.'], Response::HTTP_NOT_FOUND);
        }

        $hoursTotal = $hoursStageId
            ? $this->approvedHoursForLecturer($lecturer, $academicYearId, $hoursStageId)
            : 0.0;

        $detailRows = [];
        if ($hoursStageId) {
            $detailRows = DB::table('activity_approvals as aa')
                ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
                ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
                ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
                ->join('research_activity_members as ram', function ($join) use ($lecturer) {
                    $join->on('ram.activity_id', '=', 'ra.id')
                        ->where('ram.lecturer_id', '=', $lecturer);
                })
                ->where('aa.stage_id', $hoursStageId)
                ->where('aa.status', 'approved')
                ->where('ra.academic_year_id', $academicYearId)
                ->orderByDesc('aa.decided_at')
                ->orderByDesc('ra.id')
                ->select([
                    'ra.id as activity_id',
                    'ra.title as activity_title',
                    'ak.name as activity_kind_name',
                    'ay.code as academic_year_code',
                    'ram.hours_assigned as hours_converted',
                ])
                ->get()
                ->map(function ($row) {
                    return [
                        'activity_id' => (int) $row->activity_id,
                        'activity_title' => $row->activity_title,
                        'activity_kind_name' => $row->activity_kind_name,
                        'academic_year_code' => $row->academic_year_code,
                        'hours_converted' => $row->hours_converted !== null ? (float) $row->hours_converted : 0.0,
                    ];
                })
                ->all();
        }

        $diffHours = $hoursTotal - $requiredHours;
        $progressPercent = $requiredHours > 0 ? ($hoursTotal / $requiredHours) * 100 : 0.0;
        $kpiStatus = $diffHours >= 0 ? 'hit' : 'miss';

        return response()->json([
            'data' => [
                'lecturer_id' => (int) $lecturerRow->lecturer_id,
                'lecturer_code' => $lecturerRow->lecturer_code,
                'lecturer_full_name' => $lecturerRow->lecturer_full_name,
                'faculty_id' => $lecturerRow->faculty_id ? (int) $lecturerRow->faculty_id : null,
                'faculty_name' => $lecturerRow->faculty_name,
                'department_id' => $lecturerRow->department_id ? (int) $lecturerRow->department_id : null,
                'department_name' => $lecturerRow->department_name,
                'degree_name' => $lecturerRow->degree_name,
                'academic_rank_name' => $lecturerRow->academic_rank_name,
                'academic_year_id' => $academicYearId,
                'academic_year_code' => $academicYearCode,
                'hours_total' => $hoursTotal,
                'required_hours' => $requiredHours,
                'diff_hours' => $diffHours,
                'progress_percent' => $progressPercent,
                'kpi_status' => $kpiStatus,
                'rows' => $detailRows,
            ],
        ], Response::HTTP_OK, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function exportSummaryExcel(FacultyLecturerHoursSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'Không xác định được phạm vi khoa của tài khoản.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $requestedFacultyId = $validated['faculty_id'] ?? null;
        if ($requestedFacultyId && (int) $requestedFacultyId !== $scope['faculty_id']) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu ngoài phạm vi khoa.'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->summaryData($validated, $scope, false);
        $filename = $this->buildExportFilename('xlsx');
        $academicYearId = (int) ($result['filters']['academic_year_id'] ?? 0);
        $exportRows = $this->buildSummaryReportRows(
            $result['rows'],
            $academicYearId,
            $this->resolveHoursStageId()
        );

        return Excel::download(new AdminLecturerHoursSummaryExport($exportRows, [
            'academic_year_code' => $result['rows'][0]['academic_year_code'] ?? $this->resolveAcademicYearCode($academicYearId),
            'scope_label' => (string) ($scope['faculty_name'] ?? 'Toàn khoa'),
        ]), $filename);
    }

    public function exportSummaryPdf(FacultyLecturerHoursSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'Không xác định được phạm vi khoa của tài khoản.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $requestedFacultyId = $validated['faculty_id'] ?? null;
        if ($requestedFacultyId && (int) $requestedFacultyId !== $scope['faculty_id']) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu ngoài phạm vi khoa.'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->summaryData($validated, $scope, false);
        $filename = $this->buildExportFilename('pdf');
        $academicYearId = (int) ($result['filters']['academic_year_id'] ?? 0);
        $exportRows = $this->buildSummaryReportRows(
            $result['rows'],
            $academicYearId,
            $this->resolveHoursStageId()
        );
        $meta = [
            'academic_year_code' => $result['rows'][0]['academic_year_code'] ?? $this->resolveAcademicYearCode($academicYearId),
            'scope_label' => (string) ($scope['faculty_name'] ?? 'Toàn khoa'),
        ];

        return Pdf::loadView('exports.admin_hours_summary', [
            'rows' => $exportRows,
            'meta' => $meta,
        ])->setPaper('A3', 'landscape')->download($filename);
    }

    private function summaryData(array $validated, array $scope, bool $paginate): array
    {
        $hoursStageId = $this->resolveHoursStageId();
        $academicYearId = $this->resolveAcademicYearId($validated, $scope['faculty_id'], null, $hoursStageId);
        $academicYearCode = $this->resolveAcademicYearCode($academicYearId);
        $requiredHours = $this->resolveRequiredHours($academicYearId);

        $search = trim((string) ($validated['q'] ?? ''));
        $kpiStatus = $this->normalizeKpiStatus($validated['kpi_status'] ?? null);

        $approvedHoursByLecturer = null;
        if ($hoursStageId) {
            $approvedHoursByLecturer = DB::table('activity_approvals as aa')
                ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
                ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
                ->join('lecturers as lh', 'ram.lecturer_id', '=', 'lh.id')
                ->leftJoin('departments as dh', 'lh.department_id', '=', 'dh.id')
                ->where('aa.stage_id', $hoursStageId)
                ->where('aa.status', 'approved')
                ->where('ra.academic_year_id', $academicYearId)
                ->where('dh.faculty_id', $scope['faculty_id'])
                ->where(function ($query) {
                    $query->where('ram.confirmation_status', 'accepted')
                        ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
                })
                ->selectRaw('ram.lecturer_id as lecturer_id')
                ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as approved_hours_total')
                ->groupBy('ram.lecturer_id');
        }

        $baseQuery = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->where('f.id', $scope['faculty_id'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('l.full_name', 'like', '%' . $search . '%')
                        ->orWhere('l.code', 'like', '%' . $search . '%')
                        ->orWhere('l.email', 'like', '%' . $search . '%');
                });
            });

        $hoursExpression = '0';
        if ($approvedHoursByLecturer !== null) {
            $baseQuery->leftJoinSub($approvedHoursByLecturer, 'ahl', function ($join) {
                $join->on('ahl.lecturer_id', '=', 'l.id');
            });
            $hoursExpression = 'COALESCE(ahl.approved_hours_total, 0)';
        }

        $totalsRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_lecturers')
            ->selectRaw("SUM(CASE WHEN {$hoursExpression} >= ? THEN 1 ELSE 0 END) as met_count", [$requiredHours])
            ->first();

        $totalLecturers = (int) ($totalsRow->total_lecturers ?? 0);
        $metCount = (int) ($totalsRow->met_count ?? 0);
        $missingCount = max(0, $totalLecturers - $metCount);
        $ratio = $totalLecturers > 0 ? ($metCount / $totalLecturers) * 100 : 0.0;

        $dataQuery = (clone $baseQuery)
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'l.department_id',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'deg.name as degree_name',
                'ar.name as academic_rank_name',
                DB::raw($hoursExpression . ' as hours_total'),
            ]);

        if ($kpiStatus === 'hit') {
            $dataQuery->whereRaw($hoursExpression . ' >= ?', [$requiredHours]);
        } elseif ($kpiStatus === 'miss') {
            $dataQuery->whereRaw($hoursExpression . ' < ?', [$requiredHours]);
        }

        $dataQuery->orderBy('l.full_name');

        $pagination = null;
        $rows = [];

        if ($paginate) {
            $page = max(1, (int) ($validated['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

            $paginator = $dataQuery->paginate($perPage, ['*'], 'page', $page);
            $pagination = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
            $items = $paginator->items();
        } else {
            $items = $dataQuery->get()->all();
        }

        foreach ($items as $row) {
            $hoursTotal = (float) $row->hours_total;
            $diff = $hoursTotal - $requiredHours;
            $progress = $requiredHours > 0 ? ($hoursTotal / $requiredHours) * 100 : 0.0;
            $rows[] = [
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
                'faculty_name' => $row->faculty_name,
                'department_id' => $row->department_id ? (int) $row->department_id : null,
                'department_name' => $row->department_name,
                'degree_name' => $row->degree_name,
                'academic_rank_name' => $row->academic_rank_name,
                'academic_year_id' => $academicYearId,
                'academic_year_code' => $academicYearCode,
                'hours_total' => $hoursTotal,
                'required_hours' => $requiredHours,
                'diff_hours' => $diff,
                'progress_percent' => $progress,
                'kpi_status' => $diff >= 0 ? 'hit' : 'miss',
            ];
        }

        return [
            'rows' => $rows,
            'filters' => [
                'faculty_id' => $scope['faculty_id'],
                'academic_year_id' => $academicYearId,
                'kpi_status' => $kpiStatus,
                'q' => $search,
            ],
            'options' => [
                'faculties' => [
                    ['id' => $scope['faculty_id'], 'name' => $scope['faculty_name']],
                ],
                'academic_years' => $this->loadAcademicYears(),
                'kpi_statuses' => $this->kpiStatusOptions(),
            ],
            'totals' => [
                'total_lecturers' => $totalLecturers,
                'met_count' => $metCount,
                'missing_count' => $missingCount,
                'kpi_ratio_percent' => $ratio,
            ],
            'pagination' => $pagination,
        ];
    }

    private function resolveFacultyScope($request): ?array
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

    private function resolveAcademicYearId(
        array $validated,
        ?int $facultyId = null,
        ?int $lecturerId = null,
        ?int $hoursStageId = null
    ): int
    {
        if (! empty($validated['academic_year_id'])) {
            return (int) $validated['academic_year_id'];
        }

        $resolved = AcademicYearResolver::currentId();
        if ($resolved) {
            return (int) $resolved;
        }

        $stageId = $hoursStageId ?? $this->resolveHoursStageId();
        if ($stageId) {
            $yearIdWithData = $this->findAcademicYearIdWithApprovedHours($stageId, $facultyId, $lecturerId);
            if ($yearIdWithData) {
                return $yearIdWithData;
            }
        }

        return (int) (DB::table('academic_years')->orderByDesc('id')->value('id') ?? 0);
    }

    private function resolveHoursStageId(): ?int
    {
        $value = DB::table('approval_stages')
            ->where('code', 'hours')
            ->value('id');

        return $value ? (int) $value : null;
    }

    private function findAcademicYearIdWithApprovedHours(
        int $hoursStageId,
        ?int $facultyId = null,
        ?int $lecturerId = null
    ): ?int {
        $query = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as lh', 'ram.lecturer_id', '=', 'lh.id')
            ->leftJoin('departments as dh', 'lh.department_id', '=', 'dh.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('aa.stage_id', $hoursStageId)
            ->where('aa.status', 'approved')
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->orderByDesc('ay.is_active')
            ->orderByDesc('ay.start_date');

        if ($facultyId) {
            $query->where('dh.faculty_id', $facultyId);
        }

        if ($lecturerId) {
            $query->where('ram.lecturer_id', $lecturerId);
        }

        $value = $query->value('ra.academic_year_id');
        return $value ? (int) $value : null;
    }

    private function approvedHoursForLecturer(int $lecturerId, int $academicYearId, int $hoursStageId): float
    {
        return (float) DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where('aa.stage_id', $hoursStageId)
            ->where('aa.status', 'approved')
            ->where('ra.academic_year_id', $academicYearId)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->sum(DB::raw('COALESCE(ram.hours_assigned, 0)'));
    }

    private function resolveAcademicYearCode(int $academicYearId): string
    {
        return (string) (DB::table('academic_years')->where('id', $academicYearId)->value('code') ?? '');
    }

    private function resolveRequiredHours(int $academicYearId): float
    {
        return (float) (DB::table('workload_quotas')
            ->where('academic_year_id', $academicYearId)
            ->value('required_hours') ?? 0);
    }

    private function normalizeKpiStatus(?string $kpiStatus): string
    {
        $normalized = strtolower((string) ($kpiStatus ?? 'all'));
        if ($normalized === 'met') {
            return 'hit';
        }
        if ($normalized === 'missing') {
            return 'miss';
        }

        return in_array($normalized, ['all', 'hit', 'miss'], true) ? $normalized : 'all';
    }

    private function loadAcademicYears(): array
    {
        return DB::table('academic_years')
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
    }

    private function kpiStatusOptions(): array
    {
        return [
            ['code' => 'all', 'label' => 'Tất cả'],
            ['code' => 'hit', 'label' => 'Đạt'],
            ['code' => 'miss', 'label' => 'Thiếu'],
        ];
    }

    private function buildExportFilename(string $ext): string
    {
        return 'hours_summary_lecturers_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function buildFilterPayload(array $filters, string $facultyName): array
    {
        $academicYearCode = $filters['academic_year_id']
            ? DB::table('academic_years')->where('id', $filters['academic_year_id'])->value('code')
            : 'Tất cả';

        $statusLabel = match ($filters['kpi_status'] ?? 'all') {
            'hit' => 'Đạt',
            'miss' => 'Thiếu',
            default => 'Tất cả',
        };

        return [
            'faculty' => $facultyName ?: 'Tất cả',
            'academic_year' => $academicYearCode ?: 'Tất cả',
            'status' => $statusLabel,
            'keyword' => $filters['q'] ?: 'Tất cả',
        ];
    }
    private function buildSummaryReportRows(array $rows, int $academicYearId, ?int $hoursStageId): array
    {
        if ($rows === []) {
            return [];
        }

        $lecturerIds = collect($rows)
            ->pluck('lecturer_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($lecturerIds === [] || ! $hoursStageId || $academicYearId <= 0) {
            return LecturerHoursSummaryReportBuilder::build($rows, []);
        }

        $activityRows = DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('aa.stage_id', $hoursStageId)
            ->where('aa.status', 'approved')
            ->where('ra.academic_year_id', $academicYearId)
            ->whereIn('ram.lecturer_id', $lecturerIds)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->select([
                'ram.lecturer_id',
                'ra.id as activity_id',
                'ak.code as kind_code',
                'at.code as type_code',
                'mr.code as member_role_code',
                'ram.hours_assigned',
            ])
            ->get()
            ->map(static function ($row) {
                return [
                    'lecturer_id' => (int) $row->lecturer_id,
                    'activity_id' => (int) $row->activity_id,
                    'kind_code' => (string) ($row->kind_code ?? ''),
                    'type_code' => (string) ($row->type_code ?? ''),
                    'member_role_code' => $row->member_role_code ? (string) $row->member_role_code : null,
                    'hours_assigned' => $row->hours_assigned !== null ? (float) $row->hours_assigned : 0.0,
                ];
            })
            ->all();

        return LecturerHoursSummaryReportBuilder::build($rows, $activityRows);
    }
}
