<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\WorkSearchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyResearchWorkSearchController extends AdminResearchWorkSearchController
{
    public function lookups(Request $request)
    {
        $facultyId = $this->resolveFacultyIdForUser($request->user());
        if (! $facultyId) {
            return response()->json([
                'message' => 'Faculty scope is not available.',
            ], Response::HTTP_FORBIDDEN);
        }

        $faculties = DB::table('faculties')
            ->where('id', $facultyId)
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $departments = DB::table('departments')
            ->where('faculty_id', $facultyId)
            ->select(['id', 'faculty_id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'faculty_id' => (int) $row->faculty_id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $workTypes = DB::table('activity_kinds')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $authorRoles = DB::table('member_roles')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $statuses = DB::table('activity_statuses')
            ->whereNotIn('code', ['draft', 'new'])
            ->select(['id', 'code', 'name'])
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $managementLevels = DB::table('activity_types as at')
            ->join('activity_kinds as ak', 'at.kind_id', '=', 'ak.id')
            ->where('ak.code', 'project')
            ->select(['at.id', 'at.code', 'at.name'])
            ->orderBy('at.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $years = $this->availableYears($request);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'faculties' => $faculties,
                'departments' => $departments,
                'work_types' => $workTypes,
                'author_roles' => $authorRoles,
                'statuses' => $statuses,
                'management_levels' => $managementLevels,
                'years' => $years,
            ],
        ], Response::HTTP_OK);
    }

    public function index(WorkSearchRequest $request)
    {
        $facultyId = $this->resolveFacultyIdForUser($request->user());
        if (! $facultyId) {
            return response()->json([
                'message' => 'Faculty scope is not available.',
            ], Response::HTTP_FORBIDDEN);
        }

        $filters = $this->normalizeFilters($request->validated());

        if (! empty($filters['faculty_id']) && (int) $filters['faculty_id'] !== $facultyId) {
            return response()->json([
                'message' => 'Cross-faculty access is not allowed.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! empty($filters['department_id']) && ! $this->departmentInScope((int) $filters['department_id'], $facultyId)) {
            return response()->json([
                'message' => 'Cross-faculty access is not allowed.',
            ], Response::HTTP_FORBIDDEN);
        }

        $query = $this->baseQuery();
        $this->applyRoleScope($query, $request);
        $this->applyFilters($query, $filters);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $yearExpr = $this->activityYearExpression();
        $query->orderByRaw($yearExpr . ' desc')->orderByDesc('ra.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = $paginator->items();

        $rows = array_map(function ($row) {
            return $this->buildSummaryRow($row);
        }, $items);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'summary' => [
                    'total' => $paginator->total(),
                ],
                'table' => [
                    'items' => $rows,
                    'pagination' => [
                        'page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                ],
                'applied_filters' => $filters,
            ],
        ], Response::HTTP_OK);
    }

    public function lecturers(Request $request)
    {
        $facultyId = $this->resolveFacultyIdForUser($request->user());
        if (! $facultyId) {
            return response()->json([
                'message' => 'Faculty scope is not available.',
            ], Response::HTTP_FORBIDDEN);
        }

        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('d.faculty_id', $facultyId)
            ->select([
                'l.id',
                'l.code',
                'l.full_name',
                'l.department_id',
                'd.name as department_name',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('l.full_name', 'like', '%' . $search . '%')
                    ->orWhere('l.code', 'like', '%' . $search . '%');
            });
        }

        $data = $query
            ->orderBy('l.full_name')
            ->limit(50)
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    protected function normalizeFilters(array $validated): array
    {
        $filters = parent::normalizeFilters($validated);

        if (! empty($filters['status']) && in_array($filters['status'], ['draft', 'new'], true)) {
            $filters['status'] = null;
        }

        return $filters;
    }

    protected function availableYears(Request $request): array
    {
        $yearExpr = $this->activityYearExpression();

        $query = $this->baseQuery()
            ->selectRaw($yearExpr . ' as activity_year')
            ->whereRaw($yearExpr . ' is not null')
            ->distinct()
            ->orderByDesc('activity_year');

        $this->applyRoleScope($query, $request);

        return $query->pluck('activity_year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();
    }

    protected function applyRoleScope($query, Request $request): void
    {
        $facultyId = $this->resolveFacultyIdForUser($request->user());
        if (! $facultyId) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereNotIn('ast.code', ['draft', 'new']);
        $query->where(function ($sub) use ($facultyId) {
            $sub->whereExists(function ($inner) use ($facultyId) {
                $inner->select(DB::raw(1))
                    ->from('lecturers as l1')
                    ->join('departments as d1', 'l1.department_id', '=', 'd1.id')
                    ->whereColumn('l1.id', 'ra.owner_lecturer_id')
                    ->where('d1.faculty_id', $facultyId);
            })->orWhereExists(function ($inner) use ($facultyId) {
                $inner->select(DB::raw(1))
                    ->from('research_activity_members as ram')
                    ->join('lecturers as l2', 'ram.lecturer_id', '=', 'l2.id')
                    ->join('departments as d2', 'l2.department_id', '=', 'd2.id')
                    ->whereColumn('ram.activity_id', 'ra.id')
                    ->where('d2.faculty_id', $facultyId);
            });
        });
    }

    protected function buildFiles(object $row): array
    {
        $files = parent::buildFiles($row);

        foreach ($files as $index => $file) {
            if (($file['kind'] ?? null) !== 'file') {
                continue;
            }

            $files[$index]['url'] = route(
                'faculty.works.search.attachments.download',
                ['attachment' => $file['file_id']],
                false
            );
        }

        return $files;
    }

    private function resolveFacultyIdForUser($user): ?int
    {
        if (! $user) {
            return null;
        }

        $departmentId = DB::table('lecturers')
            ->where('user_id', $user->id)
            ->value('department_id');

        if (! $departmentId) {
            return null;
        }

        return DB::table('departments')
            ->where('id', $departmentId)
            ->value('faculty_id');
    }

    private function departmentInScope(int $departmentId, int $facultyId): bool
    {
        return DB::table('departments')
            ->where('id', $departmentId)
            ->where('faculty_id', $facultyId)
            ->exists();
    }
}
