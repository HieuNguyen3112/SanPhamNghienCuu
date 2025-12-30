<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LecturerHourApprovalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $stageId = DB::table('approval_stages')->where('code', 'hours')->value('id');
        if (! $stageId) {
            DB::table('approval_stages')->updateOrInsert(
                ['code' => 'hours'],
                [
                    'name' => 'Hours Approval',
                    'order_no' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $stageId = DB::table('approval_stages')->where('code', 'hours')->value('id');
        }

        $academicYearId = DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('id');
        $statusId = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        $kindIds = DB::table('activity_kinds')->pluck('id', 'code')->all();
        $typeIds = DB::table('activity_types')->pluck('id', 'code')->all();
        $roleIds = DB::table('member_roles')->pluck('id', 'code')->all();
        $adminUserId = DB::table('users')->where('email', 'admin@local.test')->value('id');

        if (! $stageId || ! $academicYearId || ! $statusId || empty($kindIds) || empty($roleIds)) {
            return;
        }

        $facultySeed = [
            ['code' => 'FOS', 'name' => 'Khoa Khoa học Tự nhiên'],
            ['code' => 'FOE', 'name' => 'Khoa Khoa học Giáo dục'],
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ Thông tin'],
            ['code' => 'TOANTIN', 'name' => 'Khoa Toán - Tin'],
        ];

        foreach ($facultySeed as $faculty) {
            DB::table('faculties')->updateOrInsert(
                ['code' => $faculty['code']],
                [
                    'name' => $faculty['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $facultyIds = DB::table('faculties')->pluck('id', 'code')->all();

        $departmentSeed = [
            ['code' => 'IT', 'name' => 'Công nghệ Thông tin', 'faculty_code' => 'FOS'],
            ['code' => 'MATH', 'name' => 'Toán', 'faculty_code' => 'FOS'],
            ['code' => 'EDU', 'name' => 'Giáo dục', 'faculty_code' => 'FOE'],
        ];

        foreach ($departmentSeed as $department) {
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

        $lecturerSeed = [
            ['code' => 'GV-001', 'full_name' => 'Nguyễn Văn A', 'email' => 'gv-001@local.test', 'phone' => '0900000001', 'department_code' => 'IT'],
            ['code' => 'DL-001', 'full_name' => 'Duyệt', 'email' => 'dl-001@local.test', 'phone' => '0900000002', 'department_code' => 'MATH'],
            ['code' => 'QL-001', 'full_name' => 'Quản Lý', 'email' => 'ql-001@local.test', 'phone' => '0900000003', 'department_code' => 'EDU'],
            ['code' => 'GV-002', 'full_name' => 'Trần Thị B', 'email' => 'gv-002@local.test', 'phone' => '0900000004', 'department_code' => 'IT'],
            ['code' => 'GV-003', 'full_name' => 'Phạm Quốc C', 'email' => 'gv-003@local.test', 'phone' => '0900000005', 'department_code' => 'IT'],
            ['code' => 'GV-004', 'full_name' => 'Lê Thị D', 'email' => 'gv-004@local.test', 'phone' => '0900000006', 'department_code' => 'EDU'],
        ];

        $lecturerIds = [];
        foreach ($lecturerSeed as $seed) {
            $departmentId = $departmentIds[$seed['department_code']] ?? null;
            if (! $departmentId) {
                continue;
            }

            $existing = DB::table('lecturers')->where('code', $seed['code'])->first();

            if ($existing) {
                DB::table('lecturers')->where('id', $existing->id)->update([
                    'full_name' => $seed['full_name'],
                    'email' => $seed['email'],
                    'phone' => $seed['phone'],
                    'department_id' => $departmentId,
                    'active' => true,
                    'updated_at' => $now,
                ]);
                $lecturerIds[$seed['code']] = $existing->id;
                continue;
            }

            $lecturerIds[$seed['code']] = DB::table('lecturers')->insertGetId([
                'user_id' => null,
                'code' => $seed['code'],
                'full_name' => $seed['full_name'],
                'email' => $seed['email'],
                'phone' => $seed['phone'],
                'degree_id' => null,
                'academic_rank_id' => null,
                'department_id' => $departmentId,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $memberRoleId = $roleIds['principal'] ?? null;
        if (! $memberRoleId) {
            return;
        }

        $activities = [
            [
                'activity_code' => 'HOURS-GV-001-1',
                'lecturer_code' => 'GV-001',
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_900',
                'title' => 'Ứng dụng AI trong giáo dục',
                'hours_assigned' => 55,
                'approval_status' => 'pending',
                'days_ago' => 2,
            ],
            [
                'activity_code' => 'HOURS-GV-001-2',
                'lecturer_code' => 'GV-001',
                'kind_code' => 'conference',
                'type_code' => 'report',
                'title' => 'Hội thảo công nghệ giáo dục 2025',
                'hours_assigned' => 20,
                'approval_status' => 'approved',
                'days_ago' => 6,
            ],
            [
                'activity_code' => 'HOURS-DL-001-1',
                'lecturer_code' => 'DL-001',
                'kind_code' => 'project',
                'type_code' => 'bo',
                'title' => 'Dự án nâng cao chất lượng đào tạo',
                'hours_assigned' => 40,
                'approval_status' => 'rejected',
                'days_ago' => 3,
            ],
            [
                'activity_code' => 'HOURS-QL-001-1',
                'lecturer_code' => 'QL-001',
                'kind_code' => 'book',
                'type_code' => 'textbook',
                'title' => 'Giáo trình quản lý giáo dục',
                'hours_assigned' => 50,
                'approval_status' => 'approved',
                'days_ago' => 4,
            ],
            [
                'activity_code' => 'HOURS-GV-002-1',
                'lecturer_code' => 'GV-002',
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_600',
                'title' => 'Nghiên cứu dữ liệu giáo dục mở',
                'hours_assigned' => 35,
                'approval_status' => 'pending',
                'days_ago' => 1,
            ],
            [
                'activity_code' => 'HOURS-GV-003-1',
                'lecturer_code' => 'GV-003',
                'kind_code' => 'conference',
                'type_code' => 'report',
                'title' => 'Báo cáo hội thảo STEM 2025',
                'hours_assigned' => 25,
                'approval_status' => 'rejected',
                'days_ago' => 8,
            ],
            [
                'activity_code' => 'HOURS-GV-004-1',
                'lecturer_code' => 'GV-004',
                'kind_code' => 'book',
                'type_code' => 'textbook',
                'title' => 'Giáo trình phương pháp giảng dạy',
                'hours_assigned' => 45,
                'approval_status' => 'approved',
                'days_ago' => 9,
            ],
        ];

        foreach ($activities as $activity) {
            $lecturerId = $lecturerIds[$activity['lecturer_code']] ?? null;
            $kindId = $kindIds[$activity['kind_code']] ?? null;
            $typeId = $typeIds[$activity['type_code']] ?? null;

            if (! $lecturerId || ! $kindId) {
                continue;
            }

            $submittedAt = $now->copy()->subDays($activity['days_ago']);

            DB::table('research_activities')->updateOrInsert(
                ['activity_code' => $activity['activity_code']],
                [
                    'owner_lecturer_id' => $lecturerId,
                    'kind_id' => $kindId,
                    'type_id' => $typeId,
                    'academic_year_id' => $academicYearId,
                    'status_id' => $statusId,
                    'title' => $activity['title'],
                    'abstract' => null,
                    'start_date' => $submittedAt->toDateString(),
                    'end_date' => $submittedAt->toDateString(),
                    'quantity' => 1,
                    'submitted_at' => $submittedAt,
                    'approved_at' => $submittedAt,
                    'total_hours_calc' => $activity['hours_assigned'],
                    'notes' => 'hours seed',
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );

            $activityId = DB::table('research_activities')
                ->where('activity_code', $activity['activity_code'])
                ->value('id');

            if (! $activityId) {
                continue;
            }

            DB::table('research_activity_members')->updateOrInsert(
                [
                    'activity_id' => $activityId,
                    'lecturer_id' => $lecturerId,
                ],
                [
                    'member_role_id' => $memberRoleId,
                    'contribution_share' => 1,
                    'hours_assigned' => $activity['hours_assigned'],
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );

            $status = $activity['approval_status'];
            $decidedAt = $status === 'pending' ? null : $submittedAt->copy()->addDays(1);
            $decidedBy = $status === 'pending' ? null : $adminUserId;
            $note = $status === 'rejected'
                ? json_encode([
                    'reason_code' => 'missing_evidence',
                    'reason_detail' => 'Thiếu minh chứng',
                ], JSON_UNESCAPED_UNICODE)
                : ($status === 'approved' ? 'seeded' : null);

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activityId,
                    'stage_id' => $stageId,
                ],
                [
                    'status' => $status,
                    'decided_by_user_id' => $decidedBy,
                    'decided_at' => $decidedAt,
                    'note' => $note,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );
        }
    }
}
