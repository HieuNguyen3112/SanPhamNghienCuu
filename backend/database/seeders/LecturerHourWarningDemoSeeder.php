<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LecturerHourWarningDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $academicYears = [
            ['code' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-08-31', 'is_active' => false],
            ['code' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-08-31', 'is_active' => true],
            ['code' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-08-31', 'is_active' => false],
        ];

        foreach ($academicYears as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['code' => $year['code']],
                array_merge($year, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $academicYearId = DB::table('academic_years')->where('code', '2024-2025')->value('id');
        if (! $academicYearId) {
            return;
        }

        DB::table('workload_quotas')->updateOrInsert(
            ['academic_year_id' => $academicYearId],
            ['required_hours' => 300, 'notes' => 'Demo quota', 'created_at' => $now, 'updated_at' => $now]
        );

        $faculties = [
            ['code' => 'FOS', 'name' => 'Khoa Khoa học Tự nhiên'],
            ['code' => 'FOE', 'name' => 'Khoa Khoa học Giáo dục'],
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ Thông tin'],
            ['code' => 'TOANTIN', 'name' => 'Khoa Toán - Tin'],
        ];

        foreach ($faculties as $faculty) {
            DB::table('faculties')->updateOrInsert(
                ['code' => $faculty['code']],
                ['name' => $faculty['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $facultyIds = DB::table('faculties')->pluck('id', 'code')->all();

        $departments = [
            ['code' => 'IT', 'name' => 'Công nghệ Thông tin', 'faculty_code' => 'CNTT'],
            ['code' => 'MATH', 'name' => 'Toán', 'faculty_code' => 'TOANTIN'],
            ['code' => 'EDU', 'name' => 'Giáo dục', 'faculty_code' => 'FOE'],
            ['code' => 'NATSCI', 'name' => 'Khoa học Tự nhiên', 'faculty_code' => 'FOS'],
        ];

        foreach ($departments as $department) {
            $facultyId = $facultyIds[$department['faculty_code']] ?? null;
            if (! $facultyId) {
                continue;
            }

            DB::table('departments')->updateOrInsert(
                ['code' => $department['code']],
                [
                    'faculty_id' => $facultyId,
                    'name' => $department['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $departmentIds = DB::table('departments')->pluck('id', 'code')->all();
        if (empty($departmentIds)) {
            return;
        }

        $lecturers = [
            ['code' => 'GV-001', 'full_name' => 'Nguyễn Văn A', 'email' => 'gv001@local.test', 'phone' => '090000001', 'department_code' => 'IT'],
            ['code' => 'GV-002', 'full_name' => 'Trần Thị B', 'email' => 'gv002@local.test', 'phone' => '090000002', 'department_code' => 'IT'],
            ['code' => 'GV-003', 'full_name' => 'Phạm Quốc C', 'email' => 'gv003@local.test', 'phone' => '090000003', 'department_code' => 'IT'],
            ['code' => 'GV-004', 'full_name' => 'Lê Thị D', 'email' => 'gv004@local.test', 'phone' => '090000004', 'department_code' => 'EDU'],
            ['code' => 'GV-005', 'full_name' => 'Hoàng Văn E', 'email' => 'gv005@local.test', 'phone' => '090000005', 'department_code' => 'EDU'],
            ['code' => 'GV-006', 'full_name' => 'Đặng Thị F', 'email' => 'gv006@local.test', 'phone' => '090000006', 'department_code' => 'EDU'],
            ['code' => 'GV-007', 'full_name' => 'Vũ Quốc G', 'email' => 'gv007@local.test', 'phone' => '090000007', 'department_code' => 'MATH'],
            ['code' => 'GV-008', 'full_name' => 'Ngô Thị H', 'email' => 'gv008@local.test', 'phone' => '090000008', 'department_code' => 'MATH'],
            ['code' => 'GV-009', 'full_name' => 'Đỗ Văn I', 'email' => 'gv009@local.test', 'phone' => '090000009', 'department_code' => 'NATSCI'],
            ['code' => 'GV-010', 'full_name' => 'Bùi Thị K', 'email' => 'gv010@local.test', 'phone' => '090000010', 'department_code' => 'NATSCI'],
        ];

        $lecturerIds = [];
        foreach ($lecturers as $lecturer) {
            $departmentId = $departmentIds[$lecturer['department_code']] ?? null;
            if (! $departmentId) {
                continue;
            }

            $existing = DB::table('lecturers')->where('code', $lecturer['code'])->first();
            if ($existing) {
                DB::table('lecturers')->where('id', $existing->id)->update([
                    'full_name' => $lecturer['full_name'],
                    'email' => $lecturer['email'],
                    'phone' => $lecturer['phone'],
                    'department_id' => $departmentId,
                    'active' => true,
                    'updated_at' => $now,
                ]);
                $lecturerIds[$lecturer['code']] = $existing->id;
                continue;
            }

            $lecturerIds[$lecturer['code']] = DB::table('lecturers')->insertGetId([
                'user_id' => null,
                'code' => $lecturer['code'],
                'full_name' => $lecturer['full_name'],
                'email' => $lecturer['email'],
                'phone' => $lecturer['phone'],
                'degree_id' => null,
                'academic_rank_id' => null,
                'department_id' => $departmentId,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $hoursSeed = [
            ['code' => 'GV-001', 'hours' => 120, 'warned' => true, 'created_days_ago' => 12, 'warned_days_ago' => 2],
            ['code' => 'GV-002', 'hours' => 250, 'warned' => false, 'created_days_ago' => 10, 'warned_days_ago' => null],
            ['code' => 'GV-003', 'hours' => 180, 'warned' => true, 'created_days_ago' => 9, 'warned_days_ago' => 1],
            ['code' => 'GV-004', 'hours' => 90, 'warned' => false, 'created_days_ago' => 11, 'warned_days_ago' => null],
            ['code' => 'GV-005', 'hours' => 210, 'warned' => true, 'created_days_ago' => 8, 'warned_days_ago' => 3],
            ['code' => 'GV-006', 'hours' => 280, 'warned' => false, 'created_days_ago' => 7, 'warned_days_ago' => null],
            ['code' => 'GV-007', 'hours' => 60, 'warned' => true, 'created_days_ago' => 14, 'warned_days_ago' => 5],
            ['code' => 'GV-008', 'hours' => 190, 'warned' => false, 'created_days_ago' => 6, 'warned_days_ago' => null],
            ['code' => 'GV-009', 'hours' => 310, 'warned' => false, 'created_days_ago' => 5, 'warned_days_ago' => null],
            ['code' => 'GV-010', 'hours' => 130, 'warned' => true, 'created_days_ago' => 13, 'warned_days_ago' => 4],
        ];

        foreach ($hoursSeed as $seed) {
            $lecturerId = $lecturerIds[$seed['code']] ?? null;
            if (! $lecturerId) {
                continue;
            }

            $createdAt = $now->copy()->subDays($seed['created_days_ago']);
            $updatedAt = $createdAt;
            if ($seed['warned'] && $seed['warned_days_ago'] !== null) {
                $updatedAt = $now->copy()->subDays($seed['warned_days_ago']);
            }

            DB::table('lecturer_yearly_hours')->updateOrInsert(
                [
                    'lecturer_id' => $lecturerId,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'hours_total' => $seed['hours'],
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]
            );
        }

        if (! Schema::hasTable('lecturer_hour_warnings')) {
            return;
        }

        $primaryLecturerId = DB::table('lecturers')->where('code', 'GV-001')->value('id');
        if (! $primaryLecturerId) {
            return;
        }

        $warningSeeds = [
            [
                'type_key' => 'missing_hours',
                'status_key' => 'unseen',
                'seen_at' => null,
            ],
            [
                'type_key' => 'approved_not_submitted',
                'status_key' => 'seen',
                'seen_at' => $now->copy()->subDays(1),
            ],
            [
                'type_key' => 'hours_rejected',
                'status_key' => 'resolved',
                'seen_at' => $now->copy()->subDays(2),
                'resolved_at' => $now->copy()->subDays(1),
            ],
        ];

        foreach ($warningSeeds as $seed) {
            DB::table('lecturer_hour_warnings')->updateOrInsert(
                [
                    'lecturer_id' => $primaryLecturerId,
                    'academic_year_id' => $academicYearId,
                    'type_key' => $seed['type_key'],
                ],
                [
                    'status_key' => $seed['status_key'],
                    'seen_at' => $seed['seen_at'],
                    'resolved_at' => $seed['resolved_at'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
