<?php

namespace Database\Seeders;

use App\Models\Degree;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FacultyInformationTechnologyPersonnelSeeder extends Seeder
{
    private const FACULTY_CODE = 'CNTT';

    private const SUPPORT_DEPARTMENT_CODE = 'BM-GIAOVU';

    public function run(): void
    {
        DB::transaction(function (): void {
            $facultyId = $this->requireFacultyId();
            $departmentIds = $this->syncDepartments($facultyId);
            $degreeIds = Degree::query()
                ->whereIn('code', ['MASTER', 'PHD'])
                ->pluck('id', 'code')
                ->all();

            $users = $this->syncLoginAccounts();
            $this->syncLecturers($users, $departmentIds, $degreeIds);
            $this->cleanupSeedLocalUsers();
        });
    }

    private function requireFacultyId(): int
    {
        $facultyId = DB::table('faculties')
            ->where('code', self::FACULTY_CODE)
            ->value('id');

        if (! $facultyId) {
            throw new \RuntimeException('Không tìm thấy khoa CNTT trong bảng faculties.');
        }

        DB::table('faculties')
            ->where('id', $facultyId)
            ->update([
                'name' => 'Khoa Công nghệ Thông tin',
                'updated_at' => now(),
            ]);

        return (int) $facultyId;
    }

    /**
     * @return array<string, int>
     */
    private function syncDepartments(int $facultyId): array
    {
        $departmentIds = [];

        foreach ($this->departmentDefinitions() as $definition) {
            Department::query()->updateOrCreate(
                [
                    'faculty_id' => $facultyId,
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                ]
            );

            $departmentIds[$definition['code']] = (int) Department::query()
                ->where('faculty_id', $facultyId)
                ->where('code', $definition['code'])
                ->value('id');
        }

        return $departmentIds;
    }

    /**
     * @return array<string, User>
     */
    private function syncLoginAccounts(): array
    {
        $accounts = [
            'truong@hcmue.edu.vn' => [
                'name' => 'ACC TRUONG',
                'role' => 'SCIENCE_OFFICE',
            ],
            'bcnkhoa@hcmue.edu.vn' => [
                'name' => 'Nguyễn Viết Hưng',
                'role' => 'DEPARTMENT_BOARD',
            ],
            'giangvien@hcmue.edu.vn' => [
                'name' => 'Nguyễn Đỗ Thái Nguyên',
                'role' => 'LECTURER',
            ],
            'bcnkhoa2@hcmue.edu.vn' => [
                'name' => 'Trần Sơn Hải',
                'role' => 'DEPARTMENT_BOARD',
            ],
            'bcnkhoa3@hcmue.edu.vn' => [
                'name' => 'Trịnh Huy Hoàng',
                'role' => 'DEPARTMENT_BOARD',
            ],
            'giangvien2@hcmue.edu.vn' => [
                'name' => 'Lương Trần Hy Hiền',
                'role' => 'LECTURER',
            ],
            'giangvien3@hcmue.edu.vn' => [
                'name' => 'Nguyễn Trần Phi Phượng',
                'role' => 'LECTURER',
            ],
            'giangvien4@hcmue.edu.vn' => [
                'name' => 'Ngô Quốc Việt',
                'role' => 'LECTURER',
            ],
        ];

        $users = [];
        foreach ($accounts as $email => $definition) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make((string) config('users_management.default_lecturer_password', 'hcmue@123')),
                    'must_change_password' => false,
                ]
            );
            $user->name = $definition['name'];
            $user->must_change_password = false;
            $user->save();

            if (! $user->email_verified_at) {
                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            }

            Role::findOrCreate($definition['role'], 'web');
            $user->syncRoles([$definition['role']]);

            $users[$email] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, int>  $departmentIds
     * @param  array<string, int>  $degreeIds
     */
    private function syncLecturers(array $users, array $departmentIds, array $degreeIds): void
    {
        foreach ($this->personnelDefinitions() as $definition) {
            $lecturer = Lecturer::query()->firstOrNew(['code' => $definition['code']]);
            $linkedUser = $definition['user_email'] ? ($users[$definition['user_email']] ?? null) : null;
            $departmentCode = $definition['department_code'] ?? self::SUPPORT_DEPARTMENT_CODE;
            $departmentId = $departmentIds[$departmentCode] ?? $departmentIds[self::SUPPORT_DEPARTMENT_CODE];

            if (! $departmentId) {
                throw new \RuntimeException('Không tìm thấy bộ môn CNTT để gán dữ liệu seed.');
            }

            $lecturer->user_id = $linkedUser?->id;
            $lecturer->full_name = $definition['full_name'];
            $lecturer->email = $linkedUser?->email;
            $lecturer->phone = $lecturer->phone;
            $lecturer->degree_id = $definition['degree_code'] ? ($degreeIds[$definition['degree_code']] ?? null) : null;
            $lecturer->academic_rank_id = $lecturer->academic_rank_id;
            $lecturer->department_id = $departmentId;
            $lecturer->active = true;
            $lecturer->save();

            LecturerProfile::query()->updateOrCreate(
                ['lecturer_id' => $lecturer->id],
                [
                    'current_position' => $definition['current_position'],
                    'current_unit' => $definition['current_unit'],
                    'staff_type' => $definition['staff_type'],
                    'work_status' => 'Đang công tác',
                ]
            );
        }
    }

    private function cleanupSeedLocalUsers(): void
    {
        $orphanSeedUsers = User::query()
            ->where(function ($query): void {
                $query->where('email', 'like', '%@seed.spnc.local')
                    ->orWhereIn('email', [
                        'tran.son.hai@hcmue.edu.vn',
                        'trinh.huy.hoang@hcmue.edu.vn',
                        'luong.tran.hy.hien@hcmue.edu.vn',
                        'nguyen.tran.phi.phuong@hcmue.edu.vn',
                        'ngo.quoc.viet@hcmue.edu.vn',
                        'tran.ngoc.bao@hcmue.edu.vn',
                    ]);
            })
            ->whereDoesntHave('lecturer')
            ->get();

        foreach ($orphanSeedUsers as $user) {
            $user->syncRoles([]);
            $user->delete();
        }
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function departmentDefinitions(): array
    {
        return [
            ['code' => 'BM-KHMT', 'name' => 'Bộ môn Khoa học Máy tính'],
            ['code' => 'BM-HTTTMTT', 'name' => 'Bộ môn Hệ thống Thông tin & Mạng'],
            ['code' => 'BM-PPGDTIN', 'name' => 'Bộ môn Phương pháp Giảng dạy Tin học'],
            ['code' => 'BM-CNGD', 'name' => 'Bộ môn Công nghệ Giáo dục'],
            ['code' => self::SUPPORT_DEPARTMENT_CODE, 'name' => 'Giáo vụ khoa'],
        ];
    }

    /**
     * @return array<int, array{
     *     code: string,
     *     full_name: string,
     *     user_email: string|null,
     *     department_code: string|null,
     *     degree_code: string|null,
     *     current_position: string,
     *     current_unit: string,
     *     staff_type: string
     * }>
     */
    private function personnelDefinitions(): array
    {
        return [
            [
                'code' => 'GV-1',
                'full_name' => 'Nguyễn Viết Hưng',
                'user_email' => 'bcnkhoa@hcmue.edu.vn',
                'department_code' => 'BM-KHMT',
                'degree_code' => 'PHD',
                'current_position' => 'Trưởng khoa; Trưởng bộ môn Khoa học Máy tính',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'GV-3',
                'full_name' => 'Nguyễn Đỗ Thái Nguyên',
                'user_email' => 'giangvien@hcmue.edu.vn',
                'department_code' => 'BM-KHMT',
                'degree_code' => null,
                'current_position' => 'Phó Trưởng bộ môn Khoa học Máy tính',
                'current_unit' => 'Bộ môn Khoa học Máy tính',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-001',
                'full_name' => 'Trần Sơn Hải',
                'user_email' => 'bcnkhoa2@hcmue.edu.vn',
                'department_code' => 'BM-HTTTMTT',
                'degree_code' => 'PHD',
                'current_position' => 'Phó Trưởng khoa; Trưởng bộ môn Hệ thống Thông tin & Mạng',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-002',
                'full_name' => 'Trịnh Huy Hoàng',
                'user_email' => 'bcnkhoa3@hcmue.edu.vn',
                'department_code' => null,
                'degree_code' => 'MASTER',
                'current_position' => 'Phó Trưởng khoa',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-003',
                'full_name' => 'Lương Trần Hy Hiền',
                'user_email' => 'giangvien2@hcmue.edu.vn',
                'department_code' => 'BM-HTTTMTT',
                'degree_code' => null,
                'current_position' => 'Phó Trưởng bộ môn Hệ thống Thông tin & Mạng',
                'current_unit' => 'Bộ môn Hệ thống Thông tin & Mạng',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-004',
                'full_name' => 'Nguyễn Trần Phi Phượng',
                'user_email' => 'giangvien3@hcmue.edu.vn',
                'department_code' => 'BM-PPGDTIN',
                'degree_code' => null,
                'current_position' => 'Phụ trách bộ môn Phương pháp Giảng dạy Tin học',
                'current_unit' => 'Bộ môn Phương pháp Giảng dạy Tin học',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-005',
                'full_name' => 'Văn Thế Thành',
                'user_email' => null,
                'department_code' => 'BM-CNGD',
                'degree_code' => null,
                'current_position' => 'Trưởng bộ môn Công nghệ Giáo dục',
                'current_unit' => 'Bộ môn Công nghệ Giáo dục',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-006',
                'full_name' => 'Hồ Thị Ngọc Thanh',
                'user_email' => null,
                'department_code' => self::SUPPORT_DEPARTMENT_CODE,
                'degree_code' => null,
                'current_position' => 'Giáo vụ khoa',
                'current_unit' => 'Giáo vụ khoa',
                'staff_type' => 'Giáo vụ khoa',
            ],
            [
                'code' => 'CNTT-007',
                'full_name' => 'Lê Ca Nhạc',
                'user_email' => null,
                'department_code' => self::SUPPORT_DEPARTMENT_CODE,
                'degree_code' => null,
                'current_position' => 'Giáo vụ khoa',
                'current_unit' => 'Giáo vụ khoa',
                'staff_type' => 'Giáo vụ khoa',
            ],
            [
                'code' => 'CNTT-008',
                'full_name' => 'Ngô Quốc Việt',
                'user_email' => 'giangvien4@hcmue.edu.vn',
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-009',
                'full_name' => 'Trần Ngọc Bảo',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-010',
                'full_name' => 'Nguyễn Khắc Vân',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-011',
                'full_name' => 'Lê Minh Trung',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-012',
                'full_name' => 'Nguyễn Thị Ngọc Hoa',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-013',
                'full_name' => 'Hồ Diệu Khuôn',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-014',
                'full_name' => 'Trần Hữu Quốc Thư',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-015',
                'full_name' => 'Võ Hoàng Quân',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-016',
                'full_name' => 'Trần Quang Huy',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-017',
                'full_name' => 'Nguyễn Phương Nam',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-018',
                'full_name' => 'Nguyễn Văn Thịnh',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-019',
                'full_name' => 'Vy Vân',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-020',
                'full_name' => 'Lương Trần Ngọc Khiết',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-021',
                'full_name' => 'Lê Minh Triết',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-022',
                'full_name' => 'Trần Thanh Nhã',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-023',
                'full_name' => 'Ma Ngân Giang',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-024',
                'full_name' => 'Nguyễn Tấn Trung',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-025',
                'full_name' => 'Âu Bửu Long',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-026',
                'full_name' => 'Lê Thị Huyền',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
            [
                'code' => 'CNTT-027',
                'full_name' => 'Lê Thanh Thoại',
                'user_email' => null,
                'department_code' => null,
                'degree_code' => null,
                'current_position' => 'Giảng viên',
                'current_unit' => 'Khoa Công nghệ Thông tin',
                'staff_type' => 'Giảng viên',
            ],
        ];
    }
}
