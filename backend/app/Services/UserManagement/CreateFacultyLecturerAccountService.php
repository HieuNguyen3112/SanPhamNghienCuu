<?php

namespace App\Services\UserManagement;

use App\DTO\UserManagement\CreateLecturerAccountData;
use App\Models\Lecturer;
use App\Repositories\UserManagement\LecturerAccountRepository;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateFacultyLecturerAccountService
{
    private LecturerAccountRepository $lecturerAccountRepository;

    private const ALLOWED_ASSIGNED_ROLES = ['LECTURER', 'DEPARTMENT_BOARD'];

    public function __construct(
        LecturerAccountRepository $lecturerAccountRepository
    ) {
        $this->lecturerAccountRepository = $lecturerAccountRepository;
    }

    /**
     * @param  array<string, mixed>  $auditOverrides
     */
    public function handle(
        CreateLecturerAccountData $data,
        Request $request,
        string $assignedRole = 'LECTURER',
        array $auditOverrides = [],
    ): Lecturer {
        return DB::transaction(function () use ($data, $request, $assignedRole, $auditOverrides) {
            $normalizedRole = strtoupper($assignedRole);
            if (! in_array($normalizedRole, self::ALLOWED_ASSIGNED_ROLES, true)) {
                $normalizedRole = 'LECTURER';
            }

            $user = $this->lecturerAccountRepository->createUser([
                'name' => $data->fullName,
                'email' => $data->email,
                'password' => Hash::make((string) config('users_management.default_lecturer_password', 'Password!123')),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            // `email_verified_at` may be ignored by mass-assignment rules on User.
            // Force set it to keep faculty-created internal accounts login-ready.
            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Role::findOrCreate($normalizedRole, 'web');
            $user->syncRoles([$normalizedRole]);

            $lecturer = $this->lecturerAccountRepository->createLecturer([
                'user_id' => $user->id,
                'code' => $data->lecturerCode,
                'full_name' => $data->fullName,
                'email' => $data->email,
                'phone' => $data->phoneNumber,
                'degree_id' => $data->degreeId,
                'academic_rank_id' => $data->academicRankId,
                'department_id' => $data->departmentId,
                'active' => $data->isActive,
            ]);

            $this->lecturerAccountRepository->upsertLecturerProfile($lecturer->id, [
                'current_position' => $data->academicTitle,
            ]);

            AuditLogger::log($request, array_merge([
                'action_group' => 'faculty.lecturer_accounts',
                'action_code' => 'FACULTY_LECTURER_ACCOUNT_CREATED',
                'action_label' => 'Khoa tạo tài khoản giảng viên',
                'severity' => 'important',
                'result_status' => 'success',
                'target_type' => 'lecturer',
                'target_id' => (string) $lecturer->id,
                'target_display' => $data->fullName,
                'faculty_id' => $data->facultyId,
                'changes' => [
                    'lecturer_code' => $data->lecturerCode,
                    'department_id' => $data->departmentId,
                    'assigned_role' => $normalizedRole,
                    'must_change_password' => false,
                ],
            ], $auditOverrides), $request->user());

            return $lecturer;
        });
    }
}
