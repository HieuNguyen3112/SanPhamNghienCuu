<?php

namespace App\Http\Controllers;

use App\DTO\UserManagement\CreateLecturerAccountData;
use App\Http\Requests\Faculty\FacultyLecturerAccountListRequest;
use App\Http\Requests\Faculty\FacultyLecturerAccountStoreRequest;
use App\Http\Requests\Faculty\FacultyLecturerAccountRolesRequest;
use App\Http\Requests\Faculty\FacultyLecturerAccountStatusRequest;
use App\Http\Requests\Faculty\FacultyLecturerAccountUpdateRequest;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Services\UserManagement\CreateFacultyLecturerAccountService;
use App\Support\RoleMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyLecturerAccountController extends Controller
{
    private const FILTERABLE_ROLE_KEYS = ['LECTURER', 'DEPARTMENT_BOARD'];

    private CreateFacultyLecturerAccountService $createFacultyLecturerAccountService;

    public function __construct(
        CreateFacultyLecturerAccountService $createFacultyLecturerAccountService
    ) {
        $this->createFacultyLecturerAccountService = $createFacultyLecturerAccountService;
    }

    public function store(FacultyLecturerAccountStoreRequest $request)
    {
        $departmentScope = $this->resolveDepartmentScope($request);
        if (! $departmentScope) {
            return response()->json(['message' => 'department scope not found'], Response::HTTP_FORBIDDEN);
        }

        $data = CreateLecturerAccountData::fromFacultyRequest(
            payload: $request->validated(),
            creatorUserId: (int) $request->user()->id,
            departmentId: $departmentScope['department_id'],
            facultyId: $departmentScope['faculty_id'],
        );

        $lecturer = $this->createFacultyLecturerAccountService->handle($data, $request);
        $lecturer->load(['user.roles', 'department', 'profile']);

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_CREATED);
    }

    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $units = Department::query()
            ->where('faculty_id', $scope['faculty_id'])
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn(Department $d) => ['id' => (int) $d->id, 'name' => $d->name])
            ->all();

        $roles = $this->roleOptions();

        $statuses = [
            ['key' => 'ACTIVE', 'label' => 'Hoạt động'],
            ['key' => 'INACTIVE', 'label' => 'Vô hiệu'],
        ];

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'units' => $units,
                'roles' => $roles,
                'statuses' => $statuses,
                'scope' => [
                    'faculty_id' => $scope['faculty_id'],
                    'faculty_name' => $scope['faculty_name'],
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function index(FacultyLecturerAccountListRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $unitId = $validated['unit_id'] ?? null;
        $status = $this->normalizeStatusFilter($validated['status'] ?? null);
        $roleKeys = $this->normalizeRoleKeys($validated['role_keys'] ?? []);

        if ($unitId && ! $this->unitInScope((int) $unitId, $scope['faculty_id'])) {
            return response()->json(['message' => 'unit not in scope'], Response::HTTP_FORBIDDEN);
        }

        $query = Lecturer::query()
            ->with(['user.roles', 'department', 'profile'])
            ->whereHas('department', function ($q) use ($scope) {
                $q->where('faculty_id', $scope['faculty_id']);
            })
            ->when($unitId, function ($q, $unitId) {
                $q->where('department_id', $unitId);
            })
            ->when($status, function ($q, $status) {
                $q->where('active', $status === 'active');
            })
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('code', 'like', '%' . $keyword . '%')
                        ->orWhere('full_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhereHas('user', function ($userQuery) use ($keyword) {
                            $userQuery->where('email', 'like', '%' . $keyword . '%')
                                ->orWhere('name', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->when(! empty($roleKeys), function ($q) use ($roleKeys) {
                $backendRoles = RoleMapper::canonicalListToBackend($roleKeys);
                $q->whereHas('user.roles', function ($roleQuery) use ($backendRoles) {
                    $roleQuery->whereIn('name', $backendRoles);
                });
            });

        [$sortField, $sortDir] = $this->parseSort($validated['sort'] ?? null);
        $query->orderBy($sortField, $sortDir);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $rows = array_map(function (Lecturer $lecturer) {
            return $this->buildRowPayload($lecturer);
        }, $paginator->items());

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'items' => $rows,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
            'meta' => [
                'filters' => [
                    'keyword' => $keyword,
                    'unit_id' => $unitId,
                    'status' => $status ?? 'all',
                    'role_keys' => $roleKeys,
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function update(FacultyLecturerAccountUpdateRequest $request, Lecturer $lecturer)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope || ! $this->lecturerInScope($lecturer->id, $scope['faculty_id'])) {
            return response()->json(['message' => 'lecturer not in scope'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();
        if (! $this->unitInScope((int) $data['unit_id'], $scope['faculty_id'])) {
            return response()->json(['message' => 'unit not in scope'], Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($data, $lecturer) {
            $lecturer->fill([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'department_id' => $data['unit_id'],
            ]);
            $lecturer->save();

            if ($lecturer->user) {
                $lecturer->user->fill(['email' => $data['email']])->save();
            }

            $profile = LecturerProfile::firstOrNew(['lecturer_id' => $lecturer->id]);
            $profile->current_position = $data['position_title'] ?? null;
            $profile->save();
        });

        $lecturer->refresh()->load(['user.roles', 'department', 'profile']);

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_OK);
    }

    public function updateRoles(FacultyLecturerAccountRolesRequest $request, Lecturer $lecturer)
    {
        return response()->json([
            'message' => 'faculty role assignment is not allowed',
        ], Response::HTTP_FORBIDDEN);
    }

    public function updateStatus(FacultyLecturerAccountStatusRequest $request, Lecturer $lecturer)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope || ! $this->lecturerInScope($lecturer->id, $scope['faculty_id'])) {
            return response()->json(['message' => 'lecturer not in scope'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();
        $lecturer->active = (bool) $data['is_active'];
        $lecturer->save();

        $lecturer->refresh()->load(['user.roles', 'department', 'profile']);

        return response()->json([
            'success' => true,
            'message' => $lecturer->active ? 'activated' : 'deactivated',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_OK);
    }

    private function resolveFacultyScope(Request $request): ?array
    {
        $departmentScope = $this->resolveDepartmentScope($request);
        if (! $departmentScope) {
            return null;
        }

        return [
            'faculty_id' => $departmentScope['faculty_id'],
            'faculty_name' => $departmentScope['faculty_name'],
        ];
    }

    private function resolveDepartmentScope(Request $request): ?array
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
            'department_id' => (int) $lecturer->department_id,
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

    private function unitInScope(int $unitId, int $facultyId): bool
    {
        return DB::table('departments')
            ->where('id', $unitId)
            ->where('faculty_id', $facultyId)
            ->exists();
    }

    private function normalizeStatusFilter(?string $status): ?string
    {
        $value = strtolower((string) ($status ?? ''));
        if ($value === '' || $value === 'all') {
            return null;
        }
        if (in_array($value, ['active', 'inactive'], true)) {
            return $value;
        }

        return null;
    }

    private function normalizeRoleKeys(array $roleKeys): array
    {
        $normalized = [];
        foreach ($roleKeys as $raw) {
            $value = strtoupper((string) $raw);
            $canonical = RoleMapper::backendToCanonical($value) ?? $value;
            if (in_array($canonical, self::FILTERABLE_ROLE_KEYS, true)) {
                $normalized[] = $canonical;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function roleOptions(): array
    {
        return [
            [
                'key' => 'LECTURER',
                'label' => 'Giảng viên',
                'description' => 'Quyền kê khai và theo dõi công trình cá nhân.',
            ],
            [
                'key' => 'DEPARTMENT_BOARD',
                'label' => 'BCN Khoa',
                'description' => 'Quyền duyệt và giám sát công trình trong phạm vi khoa.',
            ],
        ];
    }

    private function parseSort(?string $sort): array
    {
        $raw = trim((string) $sort);
        $direction = str_starts_with($raw, '-') ? 'desc' : 'asc';
        $field = ltrim($raw, '-');

        $allowed = [
            'updated_at' => 'updated_at',
            'full_name' => 'full_name',
            'lecturer_code' => 'code',
            'email' => 'email',
        ];

        if (! $field || ! array_key_exists($field, $allowed)) {
            return ['updated_at', 'desc'];
        }

        return [$allowed[$field], $direction];
    }

    private function buildRowPayload(Lecturer $lecturer): array
    {
        $user = $lecturer->user;
        $backendRoles = $user ? $user->getRoleNames()->values()->all() : [];
        $profile = $lecturer->profile;

        return [
            'id' => (int) $lecturer->id,
            'lecturer_code' => $lecturer->code,
            'full_name' => $lecturer->full_name,
            'email' => $user?->email ?? $lecturer->email ?? '',
            'username' => $user?->name ?? '',
            'unit_id' => (int) $lecturer->department_id,
            'unit_name' => $lecturer->department?->name ?? '',
            'role_keys' => RoleMapper::backendListToCanonical($backendRoles),
            'status' => $lecturer->active ? 'ACTIVE' : 'INACTIVE',
            'position_title' => $profile?->current_position,
            'updated_at' => $lecturer->updated_at?->toISOString(),
            'updated_by' => null,
        ];
    }
}
