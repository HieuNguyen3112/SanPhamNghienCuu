<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\FacultyOrgStructureDepartmentListRequest;
use App\Http\Requests\Faculty\FacultyOrgStructureDepartmentStoreRequest;
use App\Http\Requests\Faculty\FacultyOrgStructureDepartmentUpdateRequest;
use App\Http\Requests\Faculty\FacultyOrgStructureFacultyListRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class FacultyOrgStructureController extends Controller
{
    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'scope' => [
                    'faculty_id' => $scope['faculty_id'],
                    'faculty_name' => $scope['faculty_name'],
                ],
                'faculties' => [
                    [
                        'id' => $scope['faculty_id'],
                        'name' => $scope['faculty_name'],
                    ],
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function listFaculties(FacultyOrgStructureFacultyListRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $query = $this->facultyQuery()
            ->where('f.id', $scope['faculty_id'])
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('f.code', 'like', '%' . $keyword . '%')
                        ->orWhere('f.name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('f.updated_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = $this->facultyRowToPayload($row);
        }

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

    public function listDepartments(FacultyOrgStructureDepartmentListRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $facultyId = $validated['faculty_id'] ?? null;
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        if ($facultyId && (int) $facultyId !== $scope['faculty_id']) {
            return response()->json(['message' => 'faculty scope mismatch'], Response::HTTP_FORBIDDEN);
        }

        $query = $this->departmentQuery()
            ->where('d.faculty_id', $scope['faculty_id'])
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('d.code', 'like', '%' . $keyword . '%')
                        ->orWhere('d.name', 'like', '%' . $keyword . '%')
                        ->orWhere('f.code', 'like', '%' . $keyword . '%')
                        ->orWhere('f.name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('d.updated_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = $this->departmentRowToPayload($row);
        }

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

    public function storeDepartment(FacultyOrgStructureDepartmentStoreRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();
        if ((int) $data['faculty_id'] !== $scope['faculty_id']) {
            return response()->json(['message' => 'faculty scope mismatch'], Response::HTTP_FORBIDDEN);
        }

        $now = now();

        $id = DB::table('departments')->insertGetId([
            'faculty_id' => $data['faculty_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = $this->departmentQuery()->where('d.id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->departmentRowToPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateDepartment(FacultyOrgStructureDepartmentUpdateRequest $request, int $departmentId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $department = DB::table('departments')->where('id', $departmentId)->first();
        if (! $department) {
            return response()->json(['message' => 'department not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        if ((int) $department->faculty_id !== $scope['faculty_id'] || (int) $data['faculty_id'] !== $scope['faculty_id']) {
            return response()->json(['message' => 'faculty scope mismatch'], Response::HTTP_FORBIDDEN);
        }

        if (
            $data['code'] !== $department->code &&
            $this->countDepartmentLecturers($departmentId) > 0
        ) {
            return response()->json([
                'message' => 'KhA\'ng thA\u1ec3 s\u1eeda mA\u00e3 khi \u0111\u00e3 c\u00f3 gi\u1ea3ng vi\u00ean.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::table('departments')
            ->where('id', $departmentId)
            ->update([
                'faculty_id' => $data['faculty_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'updated_at' => now(),
            ]);

        $row = $this->departmentQuery()->where('d.id', $departmentId)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->departmentRowToPayload($row),
        ], Response::HTTP_OK);
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

    private function facultyQuery()
    {
        return DB::table('faculties as f')
            ->leftJoin('departments as d', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('lecturers as l', 'l.department_id', '=', 'd.id')
            ->select([
                'f.id',
                'f.code',
                'f.name',
                'f.created_at',
                'f.updated_at',
                DB::raw('COUNT(l.id) as lecturers_count'),
            ])
            ->groupBy('f.id', 'f.code', 'f.name', 'f.created_at', 'f.updated_at');
    }

    private function departmentQuery()
    {
        return DB::table('departments as d')
            ->leftJoin('faculties as f', 'f.id', '=', 'd.faculty_id')
            ->leftJoin('lecturers as l', 'l.department_id', '=', 'd.id')
            ->select([
                'd.id',
                'd.faculty_id',
                'd.code',
                'd.name',
                'd.created_at',
                'd.updated_at',
                'f.code as faculty_code',
                'f.name as faculty_name',
                DB::raw('COUNT(l.id) as lecturers_count'),
            ])
            ->groupBy(
                'd.id',
                'd.faculty_id',
                'd.code',
                'd.name',
                'd.created_at',
                'd.updated_at',
                'f.code',
                'f.name'
            );
    }

    private function facultyRowToPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'lecturers_count' => (int) ($row->lecturers_count ?? 0),
        ];
    }

    private function departmentRowToPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'faculty_id' => (int) $row->faculty_id,
            'code' => $row->code,
            'name' => $row->name,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'lecturers_count' => (int) ($row->lecturers_count ?? 0),
            'faculty' => [
                'id' => (int) $row->faculty_id,
                'code' => $row->faculty_code,
                'name' => $row->faculty_name,
            ],
        ];
    }

    private function countDepartmentLecturers(int $departmentId): int
    {
        return (int) DB::table('lecturers')
            ->where('department_id', $departmentId)
            ->count();
    }
}
