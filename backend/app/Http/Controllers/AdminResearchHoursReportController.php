<?php

namespace App\Http\Controllers;

use App\Exports\AdminResearchHoursReportExport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchHoursReportController extends Controller
{
    public function filters()
    {
        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'faculties' => $this->loadFaculties(),
                'academic_years' => $this->loadAcademicYears(),
                'status_options' => $this->statusOptions(),
            ],
        ], Response::HTTP_OK);
    }

    public function index(Request $request)
    {
        $result = $this->reportData($request, true);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'kpis' => $result['kpis'],
                'charts' => $result['charts'],
                'table' => $result['table'],
                'applied_filters' => $result['filters'],
            ],
        ], Response::HTTP_OK);
    }

    public function exportExcel(Request $request)
    {
        $result = $this->reportData($request, false);
        $filename = $this->buildExportFilename('xlsx');

        return Excel::download(
            new AdminResearchHoursReportExport(
                $result['table']['items'],
                $result['kpis'],
                $result['filters_label']
            ),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $result = $this->reportData($request, false);
        $filename = $this->buildExportFilename('pdf');

        return Pdf::loadView('exports.admin_hour_research_report', [
            'rows' => $result['table']['items'],
            'kpis' => $result['kpis'],
            'filters' => $result['filters_label'],
        ])->setPaper('A4', 'landscape')->download($filename);
    }

    private function reportData(Request $request, bool $paginate): array
    {
        $validated = $this->validateQuery($request);
        $filters = $this->normalizeFilters($validated);

        $baseQuery = $this->baseQuery($filters);

        $kpis = $this->buildKpis($baseQuery);
        $charts = $this->buildCharts($baseQuery, $kpis);
        $table = $this->buildTable($baseQuery, $paginate, $validated);
        $filtersLabel = $this->buildFilterLabels($filters);

        return [
            'filters' => $filters,
            'filters_label' => $filtersLabel,
            'kpis' => $kpis,
            'charts' => $charts,
            'table' => $table,
        ];
    }

    private function validateQuery(Request $request): array
    {
        return $request->validate([
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', 'string', 'in:all,met,not_met'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function normalizeFilters(array $validated): array
    {
        return [
            'faculty_id' => $validated['faculty_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'status' => $validated['status'] ?? 'all',
        ];
    }

    private function baseQuery(array $filters)
    {
        $hoursSource = $this->resolveHoursSourceQuery($filters);
        $selectedAcademicYearId = $filters['academic_year_id'] ?? null;

        $query = DB::table('lecturers as l')
            ->leftJoinSub($hoursSource, 'lyh', function ($join) use ($selectedAcademicYearId) {
                $join->on('lyh.lecturer_id', '=', 'l.id');

                if (! empty($selectedAcademicYearId)) {
                    $join->where('lyh.academic_year_id', '=', $selectedAcademicYearId);
                }
            })
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('academic_years as ay', function ($join) use ($selectedAcademicYearId) {
                if (! empty($selectedAcademicYearId)) {
                    $join->where('ay.id', '=', $selectedAcademicYearId);
                    return;
                }

                $join->on('ay.id', '=', 'lyh.academic_year_id');
            })
            ->leftJoin('workload_quotas as wq', function ($join) use ($selectedAcademicYearId) {
                if (! empty($selectedAcademicYearId)) {
                    $join->where('wq.academic_year_id', '=', $selectedAcademicYearId);
                    return;
                }

                $join->on('wq.academic_year_id', '=', 'lyh.academic_year_id');
            });

        $this->applyLecturerRoleScope($query);

        if (! empty($filters['faculty_id'])) {
            $query->where('f.id', $filters['faculty_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $this->applyStatusFilter($query, $filters['status']);
        }

        return $query;
    }

    private function applyLecturerRoleScope($query): void
    {
        $userMorphType = (new User())->getMorphClass();

        $query->where(function ($scopeQuery) use ($userMorphType) {
            $scopeQuery
                ->whereNull('l.user_id')
                ->orWhereExists(function ($roleQuery) use ($userMorphType) {
                    $roleQuery
                        ->selectRaw('1')
                        ->from('model_has_roles as mhr')
                        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                        ->where('mhr.model_type', $userMorphType)
                        ->whereColumn('mhr.model_id', 'l.user_id')
                        ->whereIn('r.name', ['LECTURER', 'DEPARTMENT_BOARD']);
                });
        });
    }

    private function buildKpis($baseQuery): array
    {
        $hoursExpr = $this->hoursTotalExpression();
        $requiredExpr = $this->requiredHoursExpression();

        $row = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT l.id) as lecturer_count')
            ->selectRaw("SUM({$hoursExpr}) as total_hours")
            ->selectRaw("SUM(CASE WHEN {$hoursExpr} >= {$requiredExpr} THEN 1 ELSE 0 END) as met_count")
            ->first();

        $lecturerCount = (int) ($row->lecturer_count ?? 0);
        $totalHours = (float) ($row->total_hours ?? 0);
        $metCount = (int) ($row->met_count ?? 0);
        $notMetCount = max(0, $lecturerCount - $metCount);
        $avgHours = $lecturerCount > 0 ? $totalHours / $lecturerCount : 0.0;
        $complianceRate = $lecturerCount > 0 ? ($metCount / $lecturerCount) * 100 : 0.0;

        return [
            'lecturer_count' => $lecturerCount,
            'total_hours' => $totalHours,
            'avg_hours' => $avgHours,
            'met_count' => $metCount,
            'not_met_count' => $notMetCount,
            'compliance_rate' => $complianceRate,
        ];
    }

    private function buildCharts($baseQuery, array $kpis): array
    {
        $hoursExpr = $this->hoursTotalExpression();

        $facultyRows = (clone $baseQuery)
            ->select('f.id', 'f.name')
            ->selectRaw("SUM({$hoursExpr}) as total_hours")
            ->groupBy('f.id', 'f.name')
            ->orderBy('f.name')
            ->get();

        $yearRows = (clone $baseQuery)
            ->select('ay.id', 'ay.code')
            ->selectRaw("SUM({$hoursExpr}) as total_hours")
            ->groupBy('ay.id', 'ay.code')
            ->orderBy('ay.code')
            ->get();

        return [
            'hours_by_faculty' => [
                'labels' => $facultyRows->map(fn($row) => $row->name ?? 'Chưa rõ')->values()->all(),
                'values' => $facultyRows->map(fn($row) => (float) $row->total_hours)->values()->all(),
            ],
            'status_distribution' => [
                'labels' => ['Đạt chuẩn', 'Chưa đạt'],
                'values' => [
                    (int) ($kpis['met_count'] ?? 0),
                    (int) ($kpis['not_met_count'] ?? 0),
                ],
            ],
            'hours_by_year' => [
                'labels' => $yearRows->map(fn($row) => (string) $row->code)->values()->all(),
                'values' => $yearRows->map(fn($row) => (float) $row->total_hours)->values()->all(),
            ],
        ];
    }

    private function buildTable($baseQuery, bool $paginate, array $validated): array
    {
        $hoursExpr = $this->hoursTotalExpression();
        $requiredExpr = $this->requiredHoursExpression();

        $query = (clone $baseQuery)
            ->select([
                'l.id as lecturer_id',
                'l.full_name as lecturer_full_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
            ])
            ->selectRaw("{$hoursExpr} as hours_total")
            ->selectRaw("{$requiredExpr} as required_hours")
            ->orderBy('l.full_name');

        $pagination = null;
        if ($paginate) {
            $page = max(1, (int) ($validated['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);
            $pagination = [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
            $items = $paginator->items();
        } else {
            $items = $query->get()->all();
        }

        $rows = [];
        foreach ($items as $row) {
            $hoursTotal = (float) $row->hours_total;
            $requiredHours = (float) $row->required_hours;
            $status = $hoursTotal >= $requiredHours ? 'met' : 'not_met';

            $rows[] = [
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_name' => $row->lecturer_full_name,
                'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
                'faculty_name' => $row->faculty_name,
                'academic_year_id' => $row->academic_year_id ? (int) $row->academic_year_id : null,
                'academic_year_code' => $row->academic_year_code,
                'total_hours' => $hoursTotal,
                'required_hours' => $requiredHours,
                'status' => $status,
            ];
        }

        return [
            'items' => $rows,
            'pagination' => $pagination,
        ];
    }

    private function hoursTotalExpression(): string
    {
        return 'COALESCE(lyh.hours_total, 0)';
    }

    private function requiredHoursExpression(): string
    {
        return 'COALESCE(wq.required_hours, 600)';
    }

    private function applyStatusFilter($query, string $status): void
    {
        $hoursExpr = $this->hoursTotalExpression();
        $requiredExpr = $this->requiredHoursExpression();

        if ($status === 'met') {
            $query->whereRaw("{$hoursExpr} >= {$requiredExpr}");
        }

        if ($status === 'not_met') {
            $query->whereRaw("{$hoursExpr} < {$requiredExpr}");
        }
    }

    private function resolveHoursSourceQuery(array $filters)
    {
        if ($this->hasYearlyHoursRows($filters)) {
            return DB::table('lecturer_yearly_hours as lyh')
                ->select([
                    'lyh.lecturer_id',
                    'lyh.academic_year_id',
                    DB::raw('COALESCE(lyh.hours_total, 0) as hours_total'),
                ]);
        }

        if ($this->hasApprovedHoursRows($filters)) {
            return $this->approvedHoursByLecturerYearQuery($filters);
        }

        return $this->approvedActivityHoursByLecturerYearQuery($filters);
    }

    private function hasYearlyHoursRows(array $filters): bool
    {
        $query = DB::table('lecturer_yearly_hours as lyh')
            ->join('lecturers as l', 'lyh.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id');

        if (! empty($filters['faculty_id'])) {
            $query->where('f.id', $filters['faculty_id']);
        }

        if (! empty($filters['academic_year_id'])) {
            $query->where('lyh.academic_year_id', $filters['academic_year_id']);
        }

        return $query->exists();
    }

    private function hasApprovedHoursRows(array $filters): bool
    {
        return $this->approvedHoursByLecturerYearQuery($filters)->exists();
    }

    private function approvedHoursByLecturerYearQuery(array $filters)
    {
        $query = DB::table('activity_approvals as aa')
            ->join('approval_stages as st', 'aa.stage_id', '=', 'st.id')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l2', 'ram.lecturer_id', '=', 'l2.id')
            ->leftJoin('departments as d2', 'l2.department_id', '=', 'd2.id')
            ->leftJoin('faculties as f2', 'd2.faculty_id', '=', 'f2.id')
            ->where('st.code', 'hours')
            ->where('aa.status', 'approved')
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->selectRaw('ram.lecturer_id as lecturer_id')
            ->selectRaw('ra.academic_year_id as academic_year_id')
            ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as hours_total')
            ->groupBy('ram.lecturer_id', 'ra.academic_year_id');

        if (! empty($filters['faculty_id'])) {
            $query->where('f2.id', $filters['faculty_id']);
        }

        if (! empty($filters['academic_year_id'])) {
            $query->where('ra.academic_year_id', $filters['academic_year_id']);
        }

        return $query;
    }

    private function approvedActivityHoursByLecturerYearQuery(array $filters)
    {
        $query = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l2', 'ram.lecturer_id', '=', 'l2.id')
            ->leftJoin('departments as d2', 'l2.department_id', '=', 'd2.id')
            ->leftJoin('faculties as f2', 'd2.faculty_id', '=', 'f2.id')
            ->where('ast.code', 'approved')
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->selectRaw('ram.lecturer_id as lecturer_id')
            ->selectRaw('ra.academic_year_id as academic_year_id')
            ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as hours_total')
            ->groupBy('ram.lecturer_id', 'ra.academic_year_id');

        if (! empty($filters['faculty_id'])) {
            $query->where('f2.id', $filters['faculty_id']);
        }

        if (! empty($filters['academic_year_id'])) {
            $query->where('ra.academic_year_id', $filters['academic_year_id']);
        }

        return $query;
    }

    private function loadFaculties(): array
    {
        return DB::table('faculties')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => ['id' => (int) $row->id, 'name' => $row->name])
            ->all();
    }

    private function loadAcademicYears(): array
    {
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
    }

    private function statusOptions(): array
    {
        return [
            ['code' => 'all', 'label' => 'Tất cả'],
            ['code' => 'met', 'label' => 'Đạt chuẩn'],
            ['code' => 'not_met', 'label' => 'Chưa đạt'],
        ];
    }

    private function buildExportFilename(string $ext): string
    {
        return 'hour_research_report_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function buildFilterLabels(array $filters): array
    {
        $facultyName = $filters['faculty_id']
            ? DB::table('faculties')->where('id', $filters['faculty_id'])->value('name')
            : 'Tất cả';

        $academicYearCode = $filters['academic_year_id']
            ? DB::table('academic_years')->where('id', $filters['academic_year_id'])->value('code')
            : 'Tất cả';

        $statusLabel = match ($filters['status']) {
            'met' => 'Đạt chuẩn',
            'not_met' => 'Chưa đạt',
            default => 'Tất cả',
        };

        return [
            'faculty' => $facultyName ?: 'Tất cả',
            'academic_year' => $academicYearCode ?: 'Tất cả',
            'status' => $statusLabel,
        ];
    }
}
