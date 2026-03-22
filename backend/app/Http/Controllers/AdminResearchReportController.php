<?php

namespace App\Http\Controllers;

use App\Exports\AdminResearchReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchReportController extends Controller
{
    private const CATEGORY_ORDER = ['ISI', 'SCOPUS', 'CONFERENCE', 'PROJECT', 'BOOK'];

    private const CATEGORY_LABELS = [
        'ISI' => 'ISI',
        'SCOPUS' => 'Scopus',
        'CONFERENCE' => 'Hội nghị / Hội thảo',
        'PROJECT' => 'Đề tài / Dự án',
        'BOOK' => 'Sách / Giáo trình',
    ];

    private const SORT_FIELDS = [
        'title' => 'ra.title',
        'category' => 'category_key',
        'venue' => 'venue_label',
        'lecturer' => 'primary_lecturer_name',
        'department' => 'department_name',
        'year' => 'activity_year',
    ];

    public function filters()
    {
        $years = $this->availableYears();

        $departments = DB::table('departments as d')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->select([
                'd.id',
                'd.name',
                'f.id as faculty_id',
                'f.name as faculty_name',
            ])
            ->orderBy('d.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
                'faculty_name' => $row->faculty_name,
            ])
            ->all();

        $lecturers = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->select([
                'l.id',
                'l.full_name',
                'l.department_id',
                'd.name as department_name',
            ])
            ->orderBy('l.full_name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->full_name,
                'department_id' => $row->department_id ? (int) $row->department_id : null,
                'department_name' => $row->department_name,
            ])
            ->all();

        $researchTypes = array_map(function ($key) {
            return [
                'value' => $key,
                'label' => self::CATEGORY_LABELS[$key] ?? $key,
            ];
        }, self::CATEGORY_ORDER);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'years' => $years,
                'departments' => $departments,
                'research_types' => $researchTypes,
                'lecturers' => $lecturers,
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
            new AdminResearchReportExport(
                $result['table']['items'],
                $result['kpis'],
                $result['charts'],
                $result['filters_label']
            ),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $result = $this->reportData($request, false);
        $filename = $this->buildExportFilename('pdf');

        return Pdf::loadView('exports.admin_research_report', [
            'rows' => $result['table']['items'],
            'kpis' => $result['kpis'],
            'charts' => $result['charts'],
            'filters' => $result['filters_label'],
        ])->setPaper('A4', 'landscape')->download($filename);
    }

    private function reportData(Request $request, bool $paginate): array
    {
        $validated = $this->validateQuery($request);
        $filters = $this->normalizeFilters($validated);

        $summary = $this->buildKpis($filters);
        $charts = $this->buildCharts($filters);
        $table = $this->buildTable($filters, $validated, $paginate);
        $filtersLabel = $this->buildFilterLabels($filters);

        return [
            'filters' => $filters,
            'filters_label' => $filtersLabel,
            'kpis' => $summary,
            'charts' => $charts,
            'table' => $table,
        ];
    }

    private function validateQuery(Request $request): array
    {
        return $request->validate([
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'research_type' => ['nullable', 'string', 'max:50'],
            'lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function normalizeFilters(array $validated): array
    {
        return [
            'year' => $validated['year'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'research_type' => $validated['research_type'] ?? null,
            'lecturer_id' => $validated['lecturer_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'sort' => $validated['sort'] ?? 'year:desc',
        ];
    }

    private function availableYears(): array
    {
        $yearExpr = $this->activityYearExpression();

        return $this->baseQuery()
            ->selectRaw($yearExpr . ' as activity_year')
            ->whereRaw($yearExpr . ' is not null')
            ->distinct()
            ->orderByRaw($yearExpr . ' desc')
            ->pluck('activity_year')
            ->map(fn ($year) => (string) $year)
            ->values()
            ->all();
    }

    private function buildKpis(array $filters): array
    {
        $baseQuery = empty($filters['department_id'])
            ? $this->baseQuery()
            : $this->baseQueryWithPrimaryMember($filters);

        if (empty($filters['department_id'])) {
            $this->applyFilters($baseQuery, $filters, false);
        }
        $categoryExpr = $this->categoryKeyExpression();

        $row = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT ra.id) as total_count')
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'ISI' THEN 1 ELSE 0 END) as isi_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'SCOPUS' THEN 1 ELSE 0 END) as scopus_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'CONFERENCE' THEN 1 ELSE 0 END) as conference_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'PROJECT' THEN 1 ELSE 0 END) as project_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'BOOK' THEN 1 ELSE 0 END) as book_count")
            ->first();

        return [
            'total_count' => (int) ($row->total_count ?? 0),
            'isi_count' => (int) ($row->isi_count ?? 0),
            'scopus_count' => (int) ($row->scopus_count ?? 0),
            'conference_count' => (int) ($row->conference_count ?? 0),
            'project_count' => (int) ($row->project_count ?? 0),
            'book_count' => (int) ($row->book_count ?? 0),
        ];
    }

    private function buildCharts(array $filters): array
    {
        $categoryExpr = $this->categoryKeyExpression();
        $yearExpr = $this->activityYearExpression();

        $departmentQuery = $this->baseQueryWithPrimaryMember($filters)
            ->select([
                'd.id as department_id',
                'd.name as department_name',
            ])
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'ISI' THEN 1 ELSE 0 END) as isi_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'SCOPUS' THEN 1 ELSE 0 END) as scopus_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'CONFERENCE' THEN 1 ELSE 0 END) as conference_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'PROJECT' THEN 1 ELSE 0 END) as project_count")
            ->selectRaw("SUM(CASE WHEN {$categoryExpr} = 'BOOK' THEN 1 ELSE 0 END) as book_count")
            ->groupBy('d.id', 'd.name')
            ->orderBy('d.name');

        $departmentRows = $departmentQuery->get();

        $donutValues = $this->buildKpis($filters);

        $yearQuery = empty($filters['department_id'])
            ? $this->baseQuery()
            : $this->baseQueryWithPrimaryMember($filters);

        if (empty($filters['department_id'])) {
            $this->applyFilters($yearQuery, $filters, false);
        }

        $yearRows = $yearQuery
            ->selectRaw($yearExpr . ' as activity_year')
            ->selectRaw('COUNT(DISTINCT ra.id) as total_count')
            ->whereRaw($yearExpr . ' is not null')
            ->groupByRaw($yearExpr)
            ->orderByRaw($yearExpr)
            ->get();

        return [
            'by_department_stacked' => [
                'labels' => $departmentRows->map(fn ($row) => $row->department_name ?? 'Chưa rõ')->values()->all(),
                'isi' => $departmentRows->map(fn ($row) => (int) $row->isi_count)->values()->all(),
                'scopus' => $departmentRows->map(fn ($row) => (int) $row->scopus_count)->values()->all(),
                'conference' => $departmentRows->map(fn ($row) => (int) $row->conference_count)->values()->all(),
                'project' => $departmentRows->map(fn ($row) => (int) $row->project_count)->values()->all(),
                'book' => $departmentRows->map(fn ($row) => (int) $row->book_count)->values()->all(),
            ],
            'distribution_donut' => [
                'labels' => [
                    self::CATEGORY_LABELS['ISI'],
                    self::CATEGORY_LABELS['SCOPUS'],
                    self::CATEGORY_LABELS['CONFERENCE'],
                    self::CATEGORY_LABELS['PROJECT'],
                    self::CATEGORY_LABELS['BOOK'],
                ],
                'values' => [
                    $donutValues['isi_count'],
                    $donutValues['scopus_count'],
                    $donutValues['conference_count'],
                    $donutValues['project_count'],
                    $donutValues['book_count'],
                ],
            ],
            'by_year_line' => [
                'labels' => $yearRows->map(fn ($row) => (string) $row->activity_year)->values()->all(),
                'values' => $yearRows->map(fn ($row) => (int) $row->total_count)->values()->all(),
            ],
        ];
    }

    private function buildTable(array $filters, array $validated, bool $paginate): array
    {
        [$sortField, $sortDir] = $this->parseSort($filters['sort'] ?? null);
        $categoryExpr = $this->categoryKeyExpression();
        $yearExpr = $this->activityYearExpression();

        $memberAgg = DB::table('research_activity_members as ram')
            ->join('lecturers as lm', 'ram.lecturer_id', '=', 'lm.id')
            ->select([
                'ram.activity_id',
                DB::raw($this->lecturerNamesAggregateExpression() . ' as lecturer_names'),
            ])
            ->groupBy('ram.activity_id');

        $primaryLecturerNameExpr = "COALESCE(pl.full_name, ol.full_name, '')";

        $query = $this->baseQueryWithPrimaryMember($filters)
            ->leftJoinSub($memberAgg, 'mag', 'mag.activity_id', '=', 'ra.id')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                DB::raw("{$categoryExpr} as category_key"),
                DB::raw($yearExpr . ' as activity_year'),
                DB::raw("COALESCE(pd.journal_name, cd.conference_name, bd.publisher, prd.project_code, '') as venue_label"),
                DB::raw("COALESCE(mag.lecturer_names, {$primaryLecturerNameExpr}) as lecturer_names"),
                DB::raw($primaryLecturerNameExpr . ' as primary_lecturer_name'),
                'd.name as department_name',
            ]);

        if ($sortField === 'category_key') {
            $query->orderByRaw($categoryExpr . ' ' . $sortDir);
        } elseif ($sortField === 'activity_year') {
            $query->orderByRaw($yearExpr . ' ' . $sortDir);
        } elseif ($sortField === 'venue_label') {
            $query->orderByRaw("COALESCE(pd.journal_name, cd.conference_name, bd.publisher, prd.project_code, '') " . $sortDir);
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $pagination = null;
        if ($paginate) {
            $page = max(1, (int) ($validated['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);
            $items = $paginator->items();
            $pagination = [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
        } else {
            $items = $query->get()->all();
        }

        $rows = [];
        foreach ($items as $row) {
            $categoryKey = $row->category_key ?: 'PROJECT';
            $rows[] = [
                'id' => (int) $row->activity_id,
                'activity_code' => $row->activity_code,
                'title' => $row->title,
                'category_key' => $categoryKey,
                'category_label' => self::CATEGORY_LABELS[$categoryKey] ?? $categoryKey,
                'venue_label' => $row->venue_label ?: 'Chưa rõ',
                'lecturer_names' => $row->lecturer_names ?: 'Chưa rõ',
                'department_name' => $row->department_name ?: 'Chưa rõ',
                'year' => $row->activity_year ? (int) $row->activity_year : null,
            ];
        }

        return [
            'items' => $rows,
            'pagination' => $pagination,
        ];
    }

    private function baseQuery()
    {
        $query = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->where('ast.code', 'approved');

        return $query;
    }

    private function baseQueryWithPrimaryMember(array $filters)
    {
        $primaryMemberSub = $this->primaryMemberSubquery();
        $departmentExpr = 'COALESCE(pl.department_id, ol.department_id)';
        $query = $this->baseQuery()
            ->leftJoinSub($primaryMemberSub, 'pm', function ($join) {
                $join->on('pm.activity_id', '=', 'ra.id')->where('pm.rn', '=', 1);
            })
            ->leftJoin('lecturers as pl', 'pl.id', '=', 'pm.lecturer_id')
            ->leftJoin('lecturers as ol', 'ol.id', '=', 'ra.owner_lecturer_id')
            ->leftJoin('departments as d', 'd.id', '=', DB::raw($departmentExpr))
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id');

        $this->applyFilters($query, $filters, true);

        return $query;
    }

    private function applyFilters($query, array $filters, bool $includeDepartmentFilter): void
    {
        $yearExpr = $this->activityYearExpression();

        if (! empty($filters['year'])) {
            $query->whereRaw($yearExpr . ' = ?', [$filters['year']]);
        }

        if (! empty($filters['research_type'])) {
            $this->applyCategoryFilter($query, $filters['research_type']);
        }

        if (! empty($filters['lecturer_id'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->where('ra.owner_lecturer_id', $filters['lecturer_id'])
                    ->orWhereExists(function ($inner) use ($filters) {
                        $inner->select(DB::raw(1))
                            ->from('research_activity_members as ram')
                            ->whereColumn('ram.activity_id', 'ra.id')
                            ->where('ram.lecturer_id', $filters['lecturer_id']);
                    });
            });
        }

        if ($includeDepartmentFilter && ! empty($filters['department_id'])) {
            $query->whereRaw('COALESCE(pl.department_id, ol.department_id) = ?', [$filters['department_id']]);
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . $filters['q'] . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhereExists(function ($inner) use ($keyword) {
                        $inner->select(DB::raw(1))
                            ->from('research_activity_members as ram')
                            ->join('lecturers as lq', 'ram.lecturer_id', '=', 'lq.id')
                            ->whereColumn('ram.activity_id', 'ra.id')
                            ->where('lq.full_name', 'like', $keyword);
                    });
            });
        }
    }

    private function applyCategoryFilter($query, string $type): void
    {
        switch ($type) {
            case 'ISI':
                $query->where('ak.code', 'paper')->where('at.code', 'hdgsnn_900');
                break;
            case 'SCOPUS':
                $query->where('ak.code', 'paper')->where(function ($sub) {
                    $sub->whereIn('at.code', ['hdgsnn_600', 'hdgsnn_300'])
                        ->orWhereNull('at.code');
                });
                break;
            case 'CONFERENCE':
                $query->where('ak.code', 'conference');
                break;
            case 'PROJECT':
                $query->where('ak.code', 'project');
                break;
            case 'BOOK':
                $query->where('ak.code', 'book');
                break;
        }
    }

    private function parseSort(?string $sort): array
    {
        $raw = trim((string) $sort);
        if ($raw === '') {
            $raw = 'year:desc';
        }

        $parts = explode(':', $raw, 2);
        $field = $parts[0] ?? 'year';
        $dir = strtolower($parts[1] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $resolvedField = self::SORT_FIELDS[$field] ?? self::SORT_FIELDS['year'];

        return [$resolvedField, $dir];
    }

    private function activityYearExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return 'COALESCE(pd.year, bd.year, CAST(EXTRACT(YEAR FROM prd.start_month) AS INTEGER), CAST(EXTRACT(YEAR FROM cd.held_on) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.start_date) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.created_at) AS INTEGER))';
        }

        if ($driver === 'sqlite') {
            return "COALESCE(pd.year, bd.year, CAST(strftime('%Y', prd.start_month) AS INTEGER), CAST(strftime('%Y', cd.held_on) AS INTEGER), CAST(strftime('%Y', ra.start_date) AS INTEGER), CAST(strftime('%Y', ra.created_at) AS INTEGER))";
        }

        return 'COALESCE(pd.year, bd.year, YEAR(prd.start_month), YEAR(cd.held_on), YEAR(ra.start_date), YEAR(ra.created_at))';
    }

    private function lecturerNamesAggregateExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "STRING_AGG(lm.full_name, ', ' ORDER BY ram.id)";
        }

        if ($driver === 'sqlite') {
            return "GROUP_CONCAT(lm.full_name, ', ')";
        }

        return "GROUP_CONCAT(lm.full_name ORDER BY ram.id SEPARATOR ', ')";
    }

    private function categoryKeyExpression(): string
    {
        return "CASE
            WHEN ak.code = 'paper' AND at.code = 'hdgsnn_900' THEN 'ISI'
            WHEN ak.code = 'paper' AND at.code IN ('hdgsnn_600', 'hdgsnn_300') THEN 'SCOPUS'
            WHEN ak.code = 'paper' THEN 'SCOPUS'
            WHEN ak.code = 'conference' THEN 'CONFERENCE'
            WHEN ak.code = 'project' THEN 'PROJECT'
            WHEN ak.code = 'book' THEN 'BOOK'
            ELSE 'PROJECT'
        END";
    }

    private function primaryMemberSubquery()
    {
        $roleOrder = "CASE
            WHEN mr.code = 'principal' THEN 1
            WHEN mr.code = 'corresponding_author' THEN 2
            WHEN mr.code = 'chief_editor' THEN 3
            WHEN mr.code = 'secretary' THEN 4
            WHEN mr.code = 'member' THEN 5
            WHEN mr.code = 'coauthor' THEN 6
            ELSE 99
        END";

        return DB::table('research_activity_members as ram')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->select([
                'ram.activity_id',
                'ram.lecturer_id',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY ram.activity_id ORDER BY ' . $roleOrder . ', ram.id) as rn'),
            ]);
    }

    private function buildExportFilename(string $ext): string
    {
        return 'research_report_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function buildFilterLabels(array $filters): array
    {
        $allLabel = 'Tất cả';

        $departmentName = $filters['department_id']
            ? DB::table('departments')->where('id', $filters['department_id'])->value('name')
            : null;

        $lecturer = null;
        if (! empty($filters['lecturer_id'])) {
            $lecturer = DB::table('lecturers')
                ->select(['full_name', 'code'])
                ->where('id', $filters['lecturer_id'])
                ->first();
        }

        $lecturerLabel = $lecturer
            ? trim($lecturer->full_name . ($lecturer->code ? ' (' . $lecturer->code . ')' : ''))
            : null;

        $researchTypeLabel = $filters['research_type']
            ? (self::CATEGORY_LABELS[$filters['research_type']] ?? $filters['research_type'])
            : null;

        return [
            'year' => $filters['year'] ? (string) $filters['year'] : $allLabel,
            'department' => $departmentName ?: $allLabel,
            'research_type' => $researchTypeLabel ?: $allLabel,
            'lecturer' => $lecturerLabel ?: $allLabel,
            'keyword' => $filters['q'] ?: $allLabel,
        ];
    }
}
