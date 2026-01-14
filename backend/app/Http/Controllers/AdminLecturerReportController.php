<?php

namespace App\Http\Controllers;

use App\Exports\AdminLecturerReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AdminLecturerReportController extends Controller
{
    private const SORT_FIELDS = [
        'full_name' => 'l.full_name',
        'faculty_name' => 'f.name',
        'gender' => 'lp.gender',
        'degree_name' => 'deg.name',
        'academic_rank_name' => 'ar.name',
        'seniority_years' => 'seniority_years',
    ];

    private const DEGREE_SUMMARY_CODES = [
        'doctor' => 'PHD',
        'master' => 'MASTER',
        'bachelor' => 'BACHELOR',
    ];

    private const ACADEMIC_RANK_CODES = [
        'PROFESSOR',
        'ASSOCIATE_PROFESSOR',
    ];

    public function filters()
    {
        $faculties = DB::table('faculties')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name])
            ->all();

        $degrees = DB::table('degrees')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $ranks = DB::table('academic_ranks')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $genderValues = DB::table('lecturer_profiles')
            ->whereNotNull('gender')
            ->select('gender')
            ->distinct()
            ->orderBy('gender')
            ->pluck('gender')
            ->filter()
            ->values()
            ->all();

        if (! $genderValues) {
            $genderValues = ['Male', 'Female', 'Other'];
        }

        $genders = array_map(function ($value) {
            return [
                'value' => $value,
                'label' => $this->formatGenderLabel($value),
            ];
        }, $genderValues);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'faculties' => $faculties,
                'degrees' => $degrees,
                'academic_ranks' => $ranks,
                'genders' => $genders,
            ],
        ], Response::HTTP_OK);
    }

    public function index(Request $request)
    {
        $validated = $this->validateQuery($request);
        $filters = $this->normalizeFilters($validated);

        $baseQuery = $this->baseQuery($filters);

        $summary = $this->buildSummary($baseQuery);
        $charts = $this->buildCharts($baseQuery);
        $table = $this->buildTable($filters, $validated, true);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'summary' => $summary,
                'charts' => $charts,
                'table' => $table,
                'applied_filters' => $filters,
            ],
        ], Response::HTTP_OK);
    }

    public function exportExcel(Request $request)
    {
        $validated = $this->validateQuery($request);
        $filters = $this->normalizeFilters($validated);
        $table = $this->buildTable($filters, $validated, false);

        $filename = $this->buildExportFilename('lecturer_report', 'xlsx');

        return Excel::download(new AdminLecturerReportExport($table['items']), $filename);
    }

    public function exportPdf(Request $request)
    {
        $validated = $this->validateQuery($request);
        $filters = $this->normalizeFilters($validated);
        $table = $this->buildTable($filters, $validated, false);

        $filename = $this->buildExportFilename('lecturer_report', 'pdf');
        $filtersLabel = $this->buildFilterLabels($filters);
        $rows = array_map(function (array $row) {
            $row['gender'] = $this->formatGenderLabel($row['gender'] ?? null);

            return $row;
        }, $table['items']);

        return Pdf::loadView('exports.admin_lecturer_report', [
            'rows' => $rows,
            'filters' => $filtersLabel,
        ])->setPaper('A4', 'landscape')->download($filename);
    }

    private function validateQuery(Request $request): array
    {
        return $request->validate([
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function normalizeFilters(array $validated): array
    {
        return [
            'faculty_id' => $validated['faculty_id'] ?? null,
            'degree_id' => $validated['degree_id'] ?? null,
            'academic_rank_id' => $validated['academic_rank_id'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'sort' => $validated['sort'] ?? 'full_name:asc',
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->leftJoin('lecturer_profiles as lp', 'lp.lecturer_id', '=', 'l.id');

        if (! empty($filters['faculty_id'])) {
            $query->where('f.id', $filters['faculty_id']);
        }

        if (! empty($filters['degree_id'])) {
            $query->where('l.degree_id', $filters['degree_id']);
        }

        if (! empty($filters['academic_rank_id'])) {
            $query->where('l.academic_rank_id', $filters['academic_rank_id']);
        }

        if (! empty($filters['gender'])) {
            $query->where('lp.gender', $filters['gender']);
        }

        return $query;
    }

    private function buildSummary($baseQuery): array
    {
        $row = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT l.id) as total_lecturers')
            ->selectRaw(
                'SUM(CASE WHEN deg.code = ? THEN 1 ELSE 0 END) as doctor_count',
                [self::DEGREE_SUMMARY_CODES['doctor']]
            )
            ->selectRaw(
                'SUM(CASE WHEN deg.code = ? THEN 1 ELSE 0 END) as master_count',
                [self::DEGREE_SUMMARY_CODES['master']]
            )
            ->selectRaw(
                'SUM(CASE WHEN deg.code = ? THEN 1 ELSE 0 END) as bachelor_count',
                [self::DEGREE_SUMMARY_CODES['bachelor']]
            )
            ->selectRaw(
                'SUM(CASE WHEN ar.code IN (?, ?) THEN 1 ELSE 0 END) as professor_associate_count',
                self::ACADEMIC_RANK_CODES
            )
            ->first();

        return [
            'total_lecturers' => (int) ($row->total_lecturers ?? 0),
            'doctor_count' => (int) ($row->doctor_count ?? 0),
            'master_count' => (int) ($row->master_count ?? 0),
            'bachelor_count' => (int) ($row->bachelor_count ?? 0),
            'professor_associate_count' => (int) ($row->professor_associate_count ?? 0),
        ];
    }

    private function buildCharts($baseQuery): array
    {
        $facultyRows = (clone $baseQuery)
            ->select('f.id', 'f.name', DB::raw('COUNT(DISTINCT l.id) as total'))
            ->groupBy('f.id', 'f.name')
            ->orderBy('f.name')
            ->get();

        $degreeRows = (clone $baseQuery)
            ->select('deg.id', 'deg.name', DB::raw('COUNT(DISTINCT l.id) as total'))
            ->groupBy('deg.id', 'deg.name')
            ->orderBy('deg.name')
            ->get();

        $rankRows = (clone $baseQuery)
            ->select('ar.id', 'ar.name', DB::raw('COUNT(DISTINCT l.id) as total'))
            ->groupBy('ar.id', 'ar.name')
            ->orderBy('ar.name')
            ->get();

        $genderRows = (clone $baseQuery)
            ->select('lp.gender', DB::raw('COUNT(DISTINCT l.id) as total'))
            ->groupBy('lp.gender')
            ->orderBy('lp.gender')
            ->get();

        return [
            'by_faculty' => [
                'labels' => $facultyRows->map(fn ($row) => $row->name ?? 'Chưa rõ')->values()->all(),
                'values' => $facultyRows->map(fn ($row) => (int) $row->total)->values()->all(),
            ],
            'by_degree' => [
                'labels' => $degreeRows->map(fn ($row) => $row->name ?? 'Chưa rõ')->values()->all(),
                'values' => $degreeRows->map(fn ($row) => (int) $row->total)->values()->all(),
            ],
            'by_academic_rank' => [
                'labels' => $rankRows->map(fn ($row) => $row->name ?? 'Chưa rõ')->values()->all(),
                'values' => $rankRows->map(fn ($row) => (int) $row->total)->values()->all(),
            ],
            'by_gender' => [
                'labels' => $genderRows->map(fn ($row) => $this->formatGenderLabel($row->gender))->values()->all(),
                'values' => $genderRows->map(fn ($row) => (int) $row->total)->values()->all(),
            ],
        ];
    }

    private function buildTable(array $filters, array $validated, bool $paginate): array
    {
        [$sortField, $sortDir] = $this->parseSort($filters['sort'] ?? null);

        $senioritySub = DB::table('lecturer_work_histories')
            ->select('lecturer_id', DB::raw('MIN(start_date) as first_start_date'))
            ->groupBy('lecturer_id');

        $query = $this->baseQuery($filters)
            ->leftJoinSub($senioritySub, 'lwh', 'lwh.lecturer_id', '=', 'l.id')
            ->select([
                'l.id as lecturer_id',
                'l.full_name as lecturer_full_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'lp.gender as lecturer_gender',
                'deg.id as degree_id',
                'deg.code as degree_code',
                'deg.name as degree_name',
                'ar.id as academic_rank_id',
                'ar.code as academic_rank_code',
                'ar.name as academic_rank_name',
                DB::raw('TIMESTAMPDIFF(YEAR, COALESCE(lwh.first_start_date, DATE(l.created_at)), CURDATE()) as seniority_years'),
            ]);

        if ($sortField === 'seniority_years') {
            $query->orderByRaw('seniority_years ' . $sortDir);
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
            $rows[] = [
                'id' => (int) $row->lecturer_id,
                'full_name' => $row->lecturer_full_name,
                'faculty' => [
                    'id' => $row->faculty_id ? (int) $row->faculty_id : null,
                    'name' => $row->faculty_name,
                ],
                'gender' => $row->lecturer_gender,
                'degree' => [
                    'id' => $row->degree_id ? (int) $row->degree_id : null,
                    'code' => $row->degree_code,
                    'name' => $row->degree_name,
                ],
                'academic_rank' => [
                    'id' => $row->academic_rank_id ? (int) $row->academic_rank_id : null,
                    'code' => $row->academic_rank_code,
                    'name' => $row->academic_rank_name,
                ],
                'seniority_years' => max(0, (int) $row->seniority_years),
            ];
        }

        return [
            'items' => $rows,
            'pagination' => $pagination,
        ];
    }

    private function parseSort(?string $sort): array
    {
        $raw = trim((string) $sort);
        if ($raw === '') {
            $raw = 'full_name:asc';
        }

        $parts = explode(':', $raw, 2);
        $field = $parts[0] ?? 'full_name';
        $dir = strtolower($parts[1] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $resolvedField = self::SORT_FIELDS[$field] ?? self::SORT_FIELDS['full_name'];

        return [$resolvedField, $dir];
    }

    private function formatGenderLabel(?string $gender): string
    {
        $raw = trim((string) $gender);
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($raw, 'UTF-8')
            : strtolower($raw);

        return match ($normalized) {
            'male', 'nam' => 'Nam',
            'female', 'nu', 'nữ' => 'Nữ',
            'other', 'khac', 'khác' => 'Khác',
            default => $raw !== '' ? $raw : 'Chưa rõ',
        };
    }

    private function buildExportFilename(string $prefix, string $ext): string
    {
        return $prefix . '_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function buildFilterLabels(array $filters): array
    {
        $facultyName = $filters['faculty_id']
            ? DB::table('faculties')->where('id', $filters['faculty_id'])->value('name')
            : 'Tất cả';

        $degreeName = $filters['degree_id']
            ? DB::table('degrees')->where('id', $filters['degree_id'])->value('name')
            : 'Tất cả';

        $rankName = $filters['academic_rank_id']
            ? DB::table('academic_ranks')->where('id', $filters['academic_rank_id'])->value('name')
            : 'Tất cả';

        $genderLabel = $filters['gender'] ? $this->formatGenderLabel($filters['gender']) : 'Tất cả';

        return [
            'faculty' => $facultyName ?: 'Tất cả',
            'degree' => $degreeName ?: 'Tất cả',
            'academic_rank' => $rankName ?: 'Tất cả',
            'gender' => $genderLabel,
        ];
    }
}
