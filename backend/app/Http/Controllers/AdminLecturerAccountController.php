<?php

namespace App\Http\Controllers;

use App\DTO\UserManagement\CreateLecturerAccountData;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Services\UserManagement\CreateFacultyLecturerAccountService;
use App\Support\AuditLogger;
use App\Support\RoleMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class AdminLecturerAccountController extends Controller
{
    private const ASSIGNABLE_ROLE_KEYS = ['LECTURER', 'DEPARTMENT_BOARD'];

    private CreateFacultyLecturerAccountService $createFacultyLecturerAccountService;

    public function __construct(CreateFacultyLecturerAccountService $createFacultyLecturerAccountService)
    {
        $this->createFacultyLecturerAccountService = $createFacultyLecturerAccountService;
    }

    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge([
                'email' => $this->normalizeEmailInput((string) $request->input('email')),
            ]);
        }

        $validated = $request->validate([
            'lecturer_code' => ['required', 'string', 'max:50', Rule::unique('lecturers', 'code')],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{8,30}$/'],
            'academic_title' => ['nullable', 'string', 'max:255'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
            'unit_id' => ['nullable', 'integer', 'exists:departments,id', 'required_without:faculty_id'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $inputFacultyId = isset($validated['faculty_id']) ? (int) $validated['faculty_id'] : null;
        $inputUnitId = isset($validated['unit_id']) ? (int) $validated['unit_id'] : null;

        $unitId = $inputUnitId;
        $facultyId = $inputFacultyId;

        if ($inputFacultyId) {
            if ($inputUnitId) {
                $belongs = DB::table('departments')
                    ->where('id', $inputUnitId)
                    ->where('faculty_id', $inputFacultyId)
                    ->exists();
                if (! $belongs) {
                    return response()->json(['message' => 'unit not in faculty'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            } else {
                $resolvedUnitId = DB::table('departments')
                    ->where('faculty_id', $inputFacultyId)
                    ->orderBy('name')
                    ->value('id');
                if (! $resolvedUnitId) {
                    return response()->json(['message' => 'faculty has no unit'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $unitId = (int) $resolvedUnitId;
            }
        }

        if (! $facultyId && $unitId) {
            $facultyId = DB::table('departments')->where('id', $unitId)->value('faculty_id');
        }

        if (! $facultyId || ! $unitId) {
            return response()->json(['message' => 'unit not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = CreateLecturerAccountData::fromFacultyRequest(
            payload: $validated,
            creatorUserId: (int) $request->user()->id,
            departmentId: $unitId,
            facultyId: (int) $facultyId,
        );

        $lecturer = $this->createFacultyLecturerAccountService->handle(
            data: $data,
            request: $request,
            assignedRole: 'DEPARTMENT_BOARD',
            auditOverrides: [
                'action_group' => 'lecturer',
                'action_code' => 'ADMIN_DEPARTMENT_BOARD_ACCOUNT_CREATED',
                'action_label' => 'Truong tao tai khoan BCN khoa',
            ],
        );

        $lecturer->load(['user.roles', 'department', 'profile']);

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_CREATED);
    }

    public function index(Request $request)
    {
        $validated = $this->validateListRequest($request);

        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $unitId = $validated['unit_id'] ?? null;
        $facultyId = $validated['faculty_id'] ?? null;
        $status = $this->normalizeStatusFilter($validated['status'] ?? null);
        $roleKeys = $this->normalizeRoleKeys($validated['role_keys'] ?? [], $validated['role'] ?? null);

        $query = Lecturer::query()
            ->select([
                'lecturers.*',
                'departments.faculty_id as faculty_id',
                'faculties.name as faculty_name',
            ])
            ->leftJoin('departments', 'departments.id', '=', 'lecturers.department_id')
            ->leftJoin('faculties', 'faculties.id', '=', 'departments.faculty_id')
            ->with(['user.roles', 'department', 'profile'])
            ->when($unitId, function ($q, $unitId) {
                $q->where('lecturers.department_id', $unitId);
            })
            ->when($facultyId, function ($q, $facultyId) {
                $q->where('departments.faculty_id', $facultyId);
            })
            ->when($status, function ($q, $status) {
                $q->where('lecturers.active', $status === 'active');
            })
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('lecturers.code', 'like', '%' . $keyword . '%')
                        ->orWhere('lecturers.full_name', 'like', '%' . $keyword . '%')
                        ->orWhere('lecturers.email', 'like', '%' . $keyword . '%')
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
        $items = $paginator->items();

        $rows = array_map(function (Lecturer $lecturer) {
            return $this->buildRowPayload($lecturer);
        }, $items);

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
                    'faculty_id' => $facultyId,
                    'status' => $status ?? 'all',
                    'role_keys' => $roleKeys,
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function lookups()
    {
        $units = Department::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn(Department $d) => ['id' => (int) $d->id, 'name' => $d->name])
            ->all();

        $faculties = DB::table('faculties')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($f) => ['id' => (int) $f->id, 'name' => (string) $f->name])
            ->values()
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
                'faculties' => $faculties,
                'roles' => $roles,
                'statuses' => $statuses,
            ],
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($lecturer->user_id ?? 0),
            ],
            'unit_id' => ['required', 'integer', 'exists:departments,id'],
            'position_title' => ['nullable', 'string', 'max:255'],
        ]);

        $before = [
            'full_name' => $lecturer->full_name,
            'email' => $lecturer->user?->email ?? $lecturer->email,
            'unit_id' => $lecturer->department_id,
            'position_title' => $lecturer->profile?->current_position,
        ];

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
            $profile->current_position = $data['position_title'];
            $profile->save();
        });

        $lecturer->refresh()->load(['user.roles', 'department', 'profile']);
        $this->logLecturerAccountAction($request, 'ADMIN_LECTURER_ACCOUNT_UPDATED', 'Truong cap nhat tai khoan giang vien', $lecturer, [
            'before' => $before,
            'after' => [
                'full_name' => $lecturer->full_name,
                'email' => $lecturer->user?->email ?? $lecturer->email,
                'unit_id' => $lecturer->department_id,
                'position_title' => $lecturer->profile?->current_position,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_OK);
    }

    public function updateRoles(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'role_keys' => ['required', 'array'],
            'role_keys.*' => ['string', 'max:50'],
        ]);

        if (! $lecturer->user) {
            return response()->json([
                'message' => 'user not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $roleKeys = $this->normalizeAssignableRoleKeys($data['role_keys']);
        if (empty($roleKeys)) {
            return response()->json([
                'message' => 'role_keys must include LECTURER or DEPARTMENT_BOARD',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $backendRoles = RoleMapper::canonicalListToBackend($roleKeys);
        $beforeRoles = RoleMapper::backendListToCanonical($lecturer->user->getRoleNames()->values()->all());

        if (! empty($backendRoles)) {
            foreach ($backendRoles as $roleName) {
                Role::findOrCreate($roleName, 'web');
            }
        }

        $lecturer->user->syncRoles($backendRoles);

        $lecturer->refresh()->load(['user.roles', 'department', 'profile']);
        $this->logLecturerAccountAction($request, 'ADMIN_LECTURER_ACCOUNT_ROLES_UPDATED', 'Truong cap nhat vai tro tai khoan giang vien', $lecturer, [
            'before' => ['role_keys' => $beforeRoles],
            'after' => ['role_keys' => RoleMapper::backendListToCanonical($lecturer->user?->getRoleNames()->values()->all() ?? [])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_OK);
    }

    public function updateStatus(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $beforeStatus = (bool) $lecturer->active;
        $lecturer->active = (bool) $data['is_active'];
        $lecturer->save();

        $lecturer->refresh()->load(['user.roles', 'department', 'profile']);
        $this->logLecturerAccountAction($request, 'ADMIN_LECTURER_ACCOUNT_STATUS_UPDATED', 'Truong cap nhat trang thai tai khoan giang vien', $lecturer, [
            'before' => ['is_active' => $beforeStatus],
            'after' => ['is_active' => (bool) $lecturer->active],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $lecturer->active ? 'activated' : 'deactivated',
            'data' => $this->buildRowPayload($lecturer),
        ], Response::HTTP_OK);
    }

    private function validateListRequest(Request $request): array
    {
        return $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'integer', 'exists:departments,id'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'status' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'max:50'],
            'role_keys' => ['nullable', 'array'],
            'role_keys.*' => ['string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
        ]);
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

    private function normalizeRoleKeys(array $roleKeys, ?string $role): array
    {
        $candidates = $roleKeys;
        if ($role) {
            $candidates[] = $role;
        }

        $allowed = RoleMapper::canonicalRoles();
        $normalized = [];
        foreach ($candidates as $raw) {
            $value = strtoupper((string) $raw);
            $canonical = RoleMapper::backendToCanonical($value) ?? $value;
            if (in_array($canonical, $allowed, true)) {
                $normalized[] = $canonical;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeAssignableRoleKeys(array $roleKeys): array
    {
        $normalized = [];
        foreach ($roleKeys as $raw) {
            $value = strtoupper((string) $raw);
            $canonical = RoleMapper::backendToCanonical($value) ?? $value;
            if (in_array($canonical, self::ASSIGNABLE_ROLE_KEYS, true)) {
                $normalized[] = $canonical;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeEmailInput(string $rawEmail): string
    {
        $email = strtolower(trim($rawEmail));
        if ($email === '' || str_contains($email, '@')) {
            return $email;
        }

        $domain = strtolower((string) config('users_management.default_email_domain', 'hcmue.edu.vn'));

        return $email . '@' . ltrim($domain, '@');
    }

    private function parseSort(?string $sort): array
    {
        $raw = trim((string) $sort);
        $direction = str_starts_with($raw, '-') ? 'desc' : 'asc';
        $field = ltrim($raw, '-');

        $allowed = [
            'updated_at' => 'lecturers.updated_at',
            'full_name' => 'lecturers.full_name',
            'lecturer_code' => 'lecturers.code',
            'email' => 'lecturers.email',
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
            'faculty_id' => isset($lecturer->faculty_id) ? (int) $lecturer->faculty_id : null,
            'faculty_name' => isset($lecturer->faculty_name) ? (string) $lecturer->faculty_name : null,
            'role_keys' => RoleMapper::backendListToCanonical($backendRoles),
            'status' => $lecturer->active ? 'ACTIVE' : 'INACTIVE',
            'position_title' => $profile?->current_position,
            'updated_at' => $lecturer->updated_at?->toISOString(),
            'updated_by' => null,
        ];
    }

    private function logLecturerAccountAction(
        Request $request,
        string $actionCode,
        string $actionLabel,
        Lecturer $lecturer,
        array $changes = []
    ): void {
        AuditLogger::log($request, [
            'action_group' => 'lecturer',
            'action_code' => $actionCode,
            'action_label' => $actionLabel,
            'target_type' => 'lecturer_account',
            'target_id' => $lecturer->id,
            'target_display' => trim(($lecturer->code ? $lecturer->code . ' - ' : '') . ($lecturer->full_name ?? '')),
            'result_status' => 'success',
            'request_http_status' => Response::HTTP_OK,
            'changes' => $changes,
        ], $request->user());
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
}
