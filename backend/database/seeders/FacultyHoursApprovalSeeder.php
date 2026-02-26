<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\DepartmentCatalog;
use App\Support\FacultyCatalog;

class FacultyHoursApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->seedAcademicYears($now);
        $academicYearIds = DB::table('academic_years')->pluck('id', 'code')->all();

        DB::table('approval_stages')->updateOrInsert(
            ['code' => 'hours'],
            ['name' => 'Khoa duyệt giờ NCKH', 'order_no' => 3, 'created_at' => $now, 'updated_at' => $now]
        );
        $stageId = DB::table('approval_stages')->where('code', 'hours')->value('id');

        $statusId = DB::table('activity_statuses')->where('code', 'approved')->value('id')
            ?? DB::table('activity_statuses')->value('id');

        $kindIds = DB::table('activity_kinds')->pluck('id', 'code')->all();
        $typeIds = DB::table('activity_types')->pluck('id', 'code')->all();
        $roleIds = DB::table('member_roles')->pluck('id', 'code')->all();

        if (! $stageId || ! $statusId || empty($kindIds) || empty($typeIds) || empty($roleIds)) {
            return;
        }

        $principalRoleId = $roleIds['principal'] ?? reset($roleIds);
        $secondaryRoleId = $roleIds['coauthor'] ?? ($roleIds['member'] ?? $principalRoleId);

        $this->seedFacultiesAndDepartments($now);
        $facultyIds = DB::table('faculties')->pluck('id', 'code')->all();
        $departmentIds = DB::table('departments')->pluck('id', 'code')->all();

        $facultyAId = $facultyIds['CNTT'] ?? null;
        $facultyBId = $facultyIds['TOANTIN'] ?? null;
        $departmentA1 = $departmentIds['BM-KTPM'] ?? null;
        $departmentA2 = $departmentIds['TT-DL'] ?? null;
        $departmentB1 = $departmentIds['BM-TT'] ?? null;

        if (! $facultyAId || ! $facultyBId || ! $departmentA1 || ! $departmentA2 || ! $departmentB1) {
            return;
        }

        $facultyBoardUser = User::updateOrCreate(
            ['email' => 'faculty.board@local.test'],
            ['name' => 'Nguyễn Văn A', 'password' => Hash::make('Password!123')]
        );

        if (is_null($facultyBoardUser->email_verified_at)) {
            $facultyBoardUser->forceFill(['email_verified_at' => $now])->save();
        }

        // DL -> DEPARTMENT_BOARD
        $facultyBoardUser->syncRoles(['DEPARTMENT_BOARD']);

        DB::table('lecturers')->updateOrInsert(
            ['code' => 'DL-A01'],
            [
                'user_id' => $facultyBoardUser->id,
                'full_name' => 'Trưởng Khoa CNTT',
                'email' => $facultyBoardUser->email,
                'phone' => '0900000100',
                'department_id' => $departmentA1,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $legacyBoardUser = User::where('email', 'dl@local.test')->first();
        if ($legacyBoardUser) {
            // DL -> DEPARTMENT_BOARD
            $legacyBoardUser->syncRoles(['DEPARTMENT_BOARD']);

            DB::table('lecturers')
                ->where('user_id', $legacyBoardUser->id)
                ->update(['department_id' => $departmentA1, 'updated_at' => $now]);
        }

        $lecturerIds = $this->seedLecturers($now, [
            ['code' => 'GV-A01', 'name' => 'Nguyễn Văn A', 'email' => 'gv-a01@local.test', 'department_id' => $departmentA1],
            ['code' => 'GV-A02', 'name' => 'Trần Thị B', 'email' => 'gv-a02@local.test', 'department_id' => $departmentA1],
            ['code' => 'GV-A03', 'name' => 'Lê Quốc C', 'email' => 'gv-a03@local.test', 'department_id' => $departmentA2],
            ['code' => 'GV-A04', 'name' => 'Phạm Mỹ D', 'email' => 'gv-a04@local.test', 'department_id' => $departmentA2],
            ['code' => 'GV-A05', 'name' => 'Đoàn Minh E', 'email' => 'gv-a05@local.test', 'department_id' => $departmentA1],
            ['code' => 'GV-A06', 'name' => 'Hoàng Anh F', 'email' => 'gv-a06@local.test', 'department_id' => $departmentA2],
            ['code' => 'GV-B01', 'name' => 'Vũ Khoa G', 'email' => 'gv-b01@local.test', 'department_id' => $departmentB1],
            ['code' => 'GV-B02', 'name' => 'Bùi An H', 'email' => 'gv-b02@local.test', 'department_id' => $departmentB1],
            ['code' => 'GV-B03', 'name' => 'Nguyễn Hải I', 'email' => 'gv-b03@local.test', 'department_id' => $departmentB1],
        ]);

        $pendingLecturerUser = User::updateOrCreate(
            ['email' => 'gv-a01@local.test'],
            ['name' => 'Nguyễn Văn A', 'password' => Hash::make('Password!123')]
        );

        if (is_null($pendingLecturerUser->email_verified_at)) {
            $pendingLecturerUser->forceFill(['email_verified_at' => $now])->save();
        }

        // GV -> LECTURER
        $pendingLecturerUser->syncRoles(['LECTURER']);

        DB::table('lecturers')
            ->where('code', 'GV-A01')
            ->update(['user_id' => $pendingLecturerUser->id, 'updated_at' => $now]);

        $adminUserId = User::where('email', 'admin@local.test')->value('id');

        $activities = [
            // Faculty A - pending
            ['code' => 'HFA-A01-01', 'lecturer' => 'GV-A01', 'kind' => 'paper', 'type' => 'hdgsnn_900', 'title' => 'Ứng dụng AI trong giáo dục', 'hours' => 12, 'status' => 'pending', 'days_ago' => 3, 'year' => '2024-2025', 'members' => ['GV-A02']],
            ['code' => 'HFA-A01-02', 'lecturer' => 'GV-A01', 'kind' => 'conference', 'type' => 'report', 'title' => 'Hội thảo giáo dục số 2025', 'hours' => 8, 'status' => 'pending', 'days_ago' => 6, 'year' => '2024-2025', 'members' => ['GV-A03']],
            ['code' => 'HFA-A02-01', 'lecturer' => 'GV-A02', 'kind' => 'project', 'type' => 'bo', 'title' => 'Đề tài nâng cao chất lượng dạy học', 'hours' => 18, 'status' => 'pending', 'days_ago' => 5, 'year' => '2024-2025', 'members' => ['GV-A04']],
            ['code' => 'HFA-A02-02', 'lecturer' => 'GV-A02', 'kind' => 'paper', 'type' => 'hdgsnn_600', 'title' => 'Khai thác dữ liệu học tập', 'hours' => 10, 'status' => 'pending', 'days_ago' => 9, 'year' => '2024-2025', 'members' => ['GV-A05']],

            // Faculty A - approved
            ['code' => 'HFA-A03-01', 'lecturer' => 'GV-A03', 'kind' => 'book', 'type' => 'textbook', 'title' => 'Giáo trình phương pháp giảng dạy', 'hours' => 20, 'status' => 'approved', 'days_ago' => 20, 'year' => '2024-2025', 'members' => ['GV-A01']],
            ['code' => 'HFA-A03-02', 'lecturer' => 'GV-A03', 'kind' => 'paper', 'type' => 'hdgsnn_900', 'title' => 'Học máy trong giáo dục', 'hours' => 15, 'status' => 'approved', 'days_ago' => 25, 'year' => '2024-2025', 'members' => ['GV-A02']],

            // Faculty A - rejected
            ['code' => 'HFA-A04-01', 'lecturer' => 'GV-A04', 'kind' => 'conference', 'type' => 'report', 'title' => 'Báo cáo STEM 2024', 'hours' => 9, 'status' => 'rejected', 'days_ago' => 12, 'year' => '2024-2025', 'members' => ['GV-A03']],
            ['code' => 'HFA-A04-02', 'lecturer' => 'GV-A04', 'kind' => 'paper', 'type' => 'hdgsnn_600', 'title' => 'Mô hình học tập kết hợp', 'hours' => 11, 'status' => 'rejected', 'days_ago' => 14, 'year' => '2024-2025', 'members' => ['GV-A06']],

            // Faculty A - year 2023-2024
            ['code' => 'HFA-A05-01', 'lecturer' => 'GV-A05', 'kind' => 'project', 'type' => 'bo', 'title' => 'Đề tài chuyển đổi số', 'hours' => 16, 'status' => 'approved', 'days_ago' => 40, 'year' => '2023-2024', 'members' => ['GV-A01']],
            ['code' => 'HFA-A05-02', 'lecturer' => 'GV-A05', 'kind' => 'paper', 'type' => 'hdgsnn_900', 'title' => 'Hệ thống gợi ý học tập', 'hours' => 13, 'status' => 'approved', 'days_ago' => 46, 'year' => '2023-2024', 'members' => ['GV-A02']],
            ['code' => 'HFA-A05-03', 'lecturer' => 'GV-A05', 'kind' => 'conference', 'type' => 'report', 'title' => 'Hội thảo công nghệ giáo dục 2024', 'hours' => 7, 'status' => 'approved', 'days_ago' => 43, 'year' => '2023-2024', 'members' => ['GV-A03']],

            // Faculty A - extra pending
            ['code' => 'HFA-A06-01', 'lecturer' => 'GV-A06', 'kind' => 'paper', 'type' => 'hdgsnn_600', 'title' => 'Đánh giá năng lực học tập', 'hours' => 9, 'status' => 'pending', 'days_ago' => 2, 'year' => '2024-2025', 'members' => ['GV-A05']],
            ['code' => 'HFA-A06-02', 'lecturer' => 'GV-A06', 'kind' => 'book', 'type' => 'textbook', 'title' => 'Tài liệu hướng dẫn giảng dạy', 'hours' => 12, 'status' => 'pending', 'days_ago' => 4, 'year' => '2024-2025', 'members' => ['GV-A04']],
            ['code' => 'HFA-A06-03', 'lecturer' => 'GV-A06', 'kind' => 'conference', 'type' => 'report', 'title' => 'Báo cáo đào tạo 2025', 'hours' => 6, 'status' => 'pending', 'days_ago' => 8, 'year' => '2024-2025', 'members' => ['GV-A02']],

            // Faculty B - pending (should be hidden for faculty A)
            ['code' => 'HFB-B01-01', 'lecturer' => 'GV-B01', 'kind' => 'paper', 'type' => 'hdgsnn_900', 'title' => 'Nghiên cứu xã hội học', 'hours' => 14, 'status' => 'pending', 'days_ago' => 5, 'year' => '2024-2025', 'members' => ['GV-B02']],
            ['code' => 'HFB-B01-02', 'lecturer' => 'GV-B01', 'kind' => 'conference', 'type' => 'report', 'title' => 'Hội thảo xã hội 2025', 'hours' => 8, 'status' => 'pending', 'days_ago' => 9, 'year' => '2024-2025', 'members' => ['GV-B03']],

            // Faculty B - approved/rejected
            ['code' => 'HFB-B02-01', 'lecturer' => 'GV-B02', 'kind' => 'project', 'type' => 'bo', 'title' => 'Đề tài văn hóa số', 'hours' => 17, 'status' => 'approved', 'days_ago' => 22, 'year' => '2024-2025', 'members' => ['GV-B01']],
            ['code' => 'HFB-B03-01', 'lecturer' => 'GV-B03', 'kind' => 'book', 'type' => 'textbook', 'title' => 'Giáo trình nghiên cứu xã hội', 'hours' => 19, 'status' => 'rejected', 'days_ago' => 18, 'year' => '2024-2025', 'members' => ['GV-B02']],
        ];

        foreach ($activities as $activity) {
            $lecturerId = $lecturerIds[$activity['lecturer']] ?? null;
            $kindId = $kindIds[$activity['kind']] ?? null;
            $typeId = $typeIds[$activity['type']] ?? reset($typeIds);
            $academicYearId = $academicYearIds[$activity['year']] ?? null;

            if (! $lecturerId || ! $kindId || ! $typeId || ! $academicYearId) {
                continue;
            }

            $submittedAt = $now->copy()->subDays($activity['days_ago']);

            DB::table('research_activities')->updateOrInsert(
                ['activity_code' => $activity['code']],
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
                    'total_hours_calc' => $activity['hours'],
                    'notes' => 'dữ liệu mẫu',
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );

            $activityId = DB::table('research_activities')
                ->where('activity_code', $activity['code'])
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
                    'member_role_id' => $principalRoleId,
                    'contribution_share' => 1,
                    'hours_assigned' => $activity['hours'],
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );

            foreach ($activity['members'] as $memberCode) {
                $memberId = $lecturerIds[$memberCode] ?? null;
                if (! $memberId || $memberId === $lecturerId) {
                    continue;
                }
                DB::table('research_activity_members')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'lecturer_id' => $memberId,
                    ],
                    [
                        'member_role_id' => $secondaryRoleId,
                        'contribution_share' => 0.3,
                        'hours_assigned' => max(1, round($activity['hours'] * 0.3, 2)),
                        'created_at' => $submittedAt,
                        'updated_at' => $submittedAt,
                    ]
                );
            }

            $status = $activity['status'];
            $decidedAt = $status === 'pending' ? null : $submittedAt->copy()->addDays(1);
            $decidedBy = $status === 'pending' ? null : $adminUserId;
            $note = null;

            if ($status === 'rejected') {
                $note = json_encode([
                    'reason_code' => 'missing_evidence',
                    'reason_detail' => 'Thiếu minh chứng',
                ], JSON_UNESCAPED_UNICODE);
            } elseif ($status === 'approved') {
                $note = 'dữ liệu mẫu';
            }

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

    private function seedAcademicYears($now): void
    {
        $years = [
            ['code' => '2024-2025', 'start' => '2024-09-01', 'end' => '2025-08-31', 'is_active' => true],
            ['code' => '2023-2024', 'start' => '2023-09-01', 'end' => '2024-08-31', 'is_active' => false],
        ];

        foreach ($years as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['code' => $year['code']],
                [
                    'start_date' => $year['start'],
                    'end_date' => $year['end'],
                    'is_active' => $year['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedFacultiesAndDepartments($now): void
    {
        $facultyCatalog = FacultyCatalog::byCode();
        $faculties = array_intersect_key($facultyCatalog, array_flip(['CNTT', 'TOANTIN']));

        foreach ($faculties as $faculty) {
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
        $departments = DepartmentCatalog::all();

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
    }

    private function seedLecturers($now, array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            DB::table('lecturers')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'user_id' => null,
                    'full_name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => '0900000000',
                    'department_id' => $row['department_id'],
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $ids[$row['code']] = (int) DB::table('lecturers')
                ->where('code', $row['code'])
                ->value('id');
        }

        return $ids;
    }
}
