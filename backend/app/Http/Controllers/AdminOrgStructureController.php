<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminOrgStructureController extends Controller
{
    public function listFaculties(Request $request)
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $query = $this->facultyQuery()
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

    public function storeFaculty(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:faculties,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $now = now();

        $id = DB::table('faculties')->insertGetId([
            'code' => $data['code'],
            'name' => $data['name'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = $this->facultyQuery()->where('f.id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->facultyRowToPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateFaculty(Request $request, int $facultyId)
    {
        $faculty = DB::table('faculties')->where('id', $facultyId)->first();
        if (! $faculty) {
            return response()->json([
                'message' => 'Không tìm thấy khoa.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('faculties', 'code')->ignore($facultyId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $incomingCode = $data['code'] ?? $faculty->code;
        if (
            array_key_exists('code', $data) &&
            $incomingCode !== $faculty->code &&
            $this->countFacultyLecturers($facultyId) > 0
        ) {
            return response()->json([
                'message' => 'Không thể sửa mã khi đã có giảng viên.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::table('faculties')
            ->where('id', $facultyId)
            ->update([
                'code' => $incomingCode,
                'name' => $data['name'],
                'updated_at' => now(),
            ]);

        $row = $this->facultyQuery()->where('f.id', $facultyId)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->facultyRowToPayload($row),
        ], Response::HTTP_OK);
    }

    public function listDepartments(Request $request)
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $facultyId = $validated['faculty_id'] ?? null;
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $query = $this->departmentQuery()
            ->when($facultyId, function ($q) use ($facultyId) {
                $q->where('d.faculty_id', $facultyId);
            })
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

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where(function ($q) use ($request) {
                    $q->where('faculty_id', $request->input('faculty_id'));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

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

    public function updateDepartment(Request $request, int $departmentId)
    {
        $department = DB::table('departments')->where('id', $departmentId)->first();
        if (! $department) {
            return response()->json([
                'message' => 'Không tìm thấy đơn vị.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where(function ($q) use ($request) {
                        $q->where('faculty_id', $request->input('faculty_id'));
                    })
                    ->ignore($departmentId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if (
            $data['code'] !== $department->code &&
            $this->countDepartmentLecturers($departmentId) > 0
        ) {
            return response()->json([
                'message' => 'Không thể sửa mã khi đã có giảng viên.',
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

    private function countFacultyLecturers(int $facultyId): int
    {
        return (int) DB::table('lecturers as l')
            ->join('departments as d', 'd.id', '=', 'l.department_id')
            ->where('d.faculty_id', $facultyId)
            ->count();
    }

    private function countDepartmentLecturers(int $departmentId): int
    {
        return (int) DB::table('lecturers')
            ->where('department_id', $departmentId)
            ->count();
    }
}
