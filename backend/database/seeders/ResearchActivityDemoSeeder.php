<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResearchActivityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $seedUsers = User::whereIn('email', [
            'gv@local.test',
            'khoa@local.test',
            'truong@local.test',
        ])->get()->keyBy('email');

        $lecturerMap = [];
        foreach ($seedUsers as $email => $user) {
            $lecturer = Lecturer::where('user_id', $user->id)->first();
            if ($lecturer) {
                $lecturerMap[$email] = [
                    'user' => $user,
                    'lecturer' => $lecturer,
                ];
            }
        }

        $gvContext = $lecturerMap['gv@local.test'] ?? null;
        if (! $gvContext) {
            return;
        }

        $now = now();
        $kindIds = DB::table('activity_kinds')->pluck('id', 'code')->all();
        $typeIds = DB::table('activity_types')->pluck('id', 'code')->all();
        $statusIds = DB::table('activity_statuses')
            ->whereIn('code', ['approved', 'draft', 'submitted', 'rejected'])
            ->pluck('id', 'code')
            ->all();
        $statusId = $statusIds['approved'] ?? null;
        $academicYearId = DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('id');
        $memberRoleIds = DB::table('member_roles')->pluck('id', 'code')->all();
        $fileTypeIds = DB::table('evidence_file_types')->pluck('id', 'code')->all();
        $assistantStageId = DB::table('approval_stages')->where('code', 'assistant')->value('id');
        $managerStageId = DB::table('approval_stages')->where('code', 'manager')->value('id');

        if (! $statusId || ! $academicYearId || ! $kindIds) {
            return;
        }

        $activities = [
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_900',
                'activity_code' => 'RA-PAPER-001',
                'title' => 'Ứng dụng LLM trong trợ giảng',
                'abstract' => 'Nghiên cứu ứng dụng LLM trong hỗ trợ giảng dạy và đánh giá.',
                'start_date' => '2024-03-01',
                'end_date' => '2024-11-15',
                'details_table' => 'paper_details',
                'status_code' => 'approved',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 40],
                    ['email' => 'dl@local.test', 'role_code' => 'coauthor', 'hours' => 20],
                ],
                'attachments' => [
                    [
                        'file_type_code' => 'content',
                        'file_name' => 'paper-llm-001.pdf',
                        'content' => 'SEED-PDF-RA-PAPER-001',
                    ],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí Khoa học Giáo dục số',
                    'issn' => '1234-5678',
                    'doi' => '10.1000/xyz123',
                    'article_url' => 'https://example.local/paper/ai-edu',
                    'volume' => '12',
                    'issue' => '2',
                    'page_start' => 101,
                    'page_end' => 120,
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'book',
                'type_code' => 'textbook',
                'activity_code' => 'RA-BOOK-001',
                'title' => 'Giáo trình Lập trình Web',
                'abstract' => 'Giáo trình tổng hợp về lập trình web và hệ thống thông tin.',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-01',
                'details_table' => 'book_details',
                'status_code' => 'approved',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 60],
                    ['email' => 'ql@local.test', 'role_code' => 'member', 'hours' => 30],
                ],
                'attachments' => [
                    [
                        'file_type_code' => 'publication_decision',
                        'file_name' => 'book-approval-001.pdf',
                        'content' => 'SEED-PDF-RA-BOOK-001',
                    ],
                ],
                'details' => [
                    'publisher' => 'Nhà xuất bản Đại học',
                    'approval_decision_no' => 'QD-2023-01',
                    'approval_decision_date' => '2023-02-01',
                    'isbn' => '978-604-000000-1',
                    'pages' => 320,
                    'year' => 2023,
                ],
            ],
            [
                'kind_code' => 'project',
                'type_code' => 'bo',
                'activity_code' => 'RA-PROJECT-001',
                'title' => 'Hệ thống quản lý NCKH SPNC',
                'abstract' => 'Đề tài cấp Bộ về quản lý hoạt động NCKH.',
                'start_date' => '2022-01-01',
                'end_date' => '2024-12-31',
                'details_table' => 'project_details',
                'status_code' => 'approved',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 80],
                    ['email' => 'dl@local.test', 'role_code' => 'member', 'hours' => 40],
                ],
                'details' => [
                    'project_code' => 'DA-2022-01',
                    'decision_no' => 'QD-2022-05',
                    'decision_date' => '2022-05-15',
                    'funding' => 1500000000,
                    'start_month' => '2022-01-01',
                    'end_month' => '2024-12-01',
                ],
            ],
            [
                'kind_code' => 'conference',
                'type_code' => 'report',
                'activity_code' => 'RA-CONF-001',
                'title' => 'Hội thảo Khoa học Quốc gia 2025 - Hà Nội',
                'abstract' => 'Báo cáo về chuyển đổi số trong giáo dục.',
                'start_date' => '2024-05-01',
                'end_date' => '2024-05-30',
                'details_table' => 'conference_details',
                'status_code' => 'approved',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 40],
                ],
                'details' => [
                    'conference_name' => 'Hội thảo Khoa học Quốc gia 2025',
                    'location' => 'Hà Nội',
                    'held_on' => '2024-05-30',
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_900',
                'activity_code' => 'RA-PAPER-DRAFT-001',
                'title' => 'Bài báo bản nháp phục vụ kê khai',
                'start_date' => '2024-06-01',
                'end_date' => '2024-12-01',
                'details_table' => 'paper_details',
                'status_code' => 'draft',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 20],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí bản nháp',
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_600',
                'activity_code' => 'RA-PAPER-SUBMITTED-001',
                'title' => 'Bài báo chờ Trường duyệt',
                'assistant_approval_status' => 'approved',
                'manager_approval_status' => 'pending',
                'start_date' => '2024-02-01',
                'end_date' => '2024-09-01',
                'details_table' => 'paper_details',
                'status_code' => 'submitted',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 30],
                    ['email' => 'dl@local.test', 'role_code' => 'member', 'hours' => 15],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí Khoa học Ứng dụng',
                    'issn' => '9876-5432',
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_600',
                'activity_code' => 'RA-PAPER-002',
                'title' => 'Mô hình dự báo tài nguyên giáo dục',
                'abstract' => 'Mô hình dự báo tài nguyên và tối ưu hóa đào tạo.',
                'start_date' => '2025-01-15',
                'end_date' => '2025-10-20',
                'details_table' => 'paper_details',
                'status_code' => 'approved',
                'owner_email' => 'dl@local.test',
                'members' => [
                    ['email' => 'dl@local.test', 'role_code' => 'principal', 'hours' => 40],
                    ['email' => 'gv@local.test', 'role_code' => 'coauthor', 'hours' => 20],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí Toán Tin Ứng dụng',
                    'issn' => '4567-8910',
                    'doi' => '10.2000/seed.2002',
                    'article_url' => 'https://example.local/paper/resource-forecast',
                    'volume' => '5',
                    'issue' => '1',
                    'page_start' => 45,
                    'page_end' => 58,
                    'year' => 2025,
                ],
            ],
            [
                'kind_code' => 'book',
                'type_code' => 'reference',
                'activity_code' => 'RA-BOOK-002',
                'title' => 'Tài liệu tham khảo AI',
                'abstract' => 'Tài liệu tham khảo về ứng dụng AI trong giáo dục.',
                'start_date' => '2025-03-01',
                'end_date' => '2025-12-01',
                'details_table' => 'book_details',
                'status_code' => 'rejected',
                'owner_email' => 'ql@local.test',
                'members' => [
                    ['email' => 'ql@local.test', 'role_code' => 'principal', 'hours' => 50],
                    ['email' => 'gv@local.test', 'role_code' => 'member', 'hours' => 20],
                ],
                'details' => [
                    'publisher' => 'Nhà xuất bản Giáo dục',
                    'approval_decision_no' => 'QD-2025-09',
                    'approval_decision_date' => '2025-04-15',
                    'isbn' => '978-604-999999-2',
                    'pages' => 280,
                    'year' => 2025,
                ],
            ],
            [
                'kind_code' => 'project',
                'type_code' => 'coso',
                'activity_code' => 'RA-PROJECT-002',
                'title' => 'Hệ thống quản lý giờ NCKH',
                'abstract' => 'Đề tài cấp cơ sở về tính toán giờ NCKH.',
                'start_date' => '2024-09-01',
                'end_date' => '2026-03-31',
                'details_table' => 'project_details',
                'status_code' => 'approved',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 60],
                    ['email' => 'dl@local.test', 'role_code' => 'member', 'hours' => 30],
                    ['email' => 'ql@local.test', 'role_code' => 'member', 'hours' => 30],
                ],
                'attachments' => [
                    [
                        'file_type_code' => 'acceptance_decision',
                        'file_name' => 'project-acceptance-002.pdf',
                        'content' => 'SEED-PDF-RA-PROJECT-002',
                    ],
                ],
                'details' => [
                    'project_code' => 'DA-CO-2024-02',
                    'decision_no' => 'QD-2024-18',
                    'decision_date' => '2024-10-05',
                    'funding' => 450000000,
                    'start_month' => '2024-09-01',
                    'end_month' => '2026-03-01',
                ],
            ],
            [
                'kind_code' => 'conference',
                'type_code' => 'attend',
                'activity_code' => 'RA-CONF-002',
                'title' => 'Tham dự Hội thảo Khoa học Giáo dục 2026',
                'abstract' => null,
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-20',
                'details_table' => 'conference_details',
                'status_code' => 'submitted',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'member', 'hours' => 10],
                ],
                'details' => [
                    'conference_name' => 'Hội thảo Giáo dục 2026',
                    'location' => 'Đà Nẵng',
                    'held_on' => '2026-02-20',
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_300',
                'activity_code' => 'RA-PAPER-FAC-PENDING-001',
                'title' => 'Bài báo đang chờ khoa duyệt',
                'abstract' => 'Bản dữ liệu đang chờ khoa xác nhận.',
                'start_date' => '2024-04-01',
                'end_date' => '2024-09-15',
                'details_table' => 'paper_details',
                'status_code' => 'submitted',
                'assistant_approval_status' => 'pending',
                'assistant_approval_note' => null,
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 25],
                    ['email' => 'dl@local.test', 'role_code' => 'member', 'hours' => 15],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí Khoa học Sư phạm',
                    'issn' => '2222-3333',
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_300',
                'activity_code' => 'RA-PAPER-003',
                'title' => 'Nghiên cứu hệ thống đánh giá năng lực',
                'abstract' => 'Bài báo của khoa Toán Tin.',
                'start_date' => '2024-01-01',
                'end_date' => '2024-08-01',
                'details_table' => 'paper_details',
                'status_code' => 'approved',
                'owner_email' => 'dl@local.test',
                'members' => [
                    ['email' => 'dl@local.test', 'role_code' => 'principal', 'hours' => 30],
                ],
                'details' => [
                    'journal_name' => 'Tạp chí Toán Tin',
                    'issn' => '1111-2222',
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'project',
                'type_code' => 'coso',
                'activity_code' => 'RA-PROJECT-FAC-REJECT-001',
                'title' => 'Đề tài bị từ chối cấp khoa',
                'abstract' => 'Hồ sơ chưa đạt tại cấp khoa.',
                'start_date' => '2024-02-01',
                'end_date' => '2024-12-31',
                'details_table' => 'project_details',
                'status_code' => 'rejected',
                'assistant_approval_status' => 'rejected',
                'assistant_approval_note' => 'MISSING_EVIDENCE',
                'owner_email' => 'gv@local.test',
                'members' => [
                    ['email' => 'gv@local.test', 'role_code' => 'principal', 'hours' => 40],
                ],
                'details' => [
                    'project_code' => 'DA-CO-2024-03',
                    'decision_no' => 'QD-2024-30',
                    'decision_date' => '2024-04-20',
                    'funding' => 350000000,
                    'start_month' => '2024-02-01',
                    'end_month' => '2024-12-01',
                ],
            ],
            [
                'kind_code' => 'project',
                'type_code' => 'bo',
                'activity_code' => 'RA-PROJECT-003',
                'title' => 'Đề tài cấp Bộ về giáo dục thông minh',
                'abstract' => 'Đề tài cấp Bộ do QL chủ trì.',
                'start_date' => '2023-02-01',
                'end_date' => '2025-12-31',
                'details_table' => 'project_details',
                'status_code' => 'approved',
                'owner_email' => 'ql@local.test',
                'members' => [
                    ['email' => 'ql@local.test', 'role_code' => 'principal', 'hours' => 90],
                ],
                'details' => [
                    'project_code' => 'DA-BO-2023-09',
                    'decision_no' => 'QD-2023-22',
                    'decision_date' => '2023-03-10',
                    'funding' => 2000000000,
                    'start_month' => '2023-02-01',
                    'end_month' => '2025-12-01',
                ],
            ],
        ];

        foreach ($activities as $activity) {
            $kindId = $kindIds[$activity['kind_code']] ?? null;
            if (! $kindId) {
                continue;
            }

            $typeId = $typeIds[$activity['type_code']] ?? null;

            $statusCode = $activity['status_code'] ?? 'approved';
            $statusId = $statusIds[$statusCode] ?? null;
            if (! $statusId) {
                continue;
            }

            $ownerEmail = $activity['owner_email'] ?? 'gv@local.test';
            $ownerContext = $lecturerMap[$ownerEmail] ?? $gvContext;
            $ownerLecturer = $ownerContext['lecturer'];
            $ownerUser = $ownerContext['user'];

            DB::table('research_activities')->updateOrInsert(
                ['activity_code' => $activity['activity_code']],
                [
                    'owner_lecturer_id' => $ownerLecturer->id,
                    'kind_id' => $kindId,
                    'type_id' => $typeId,
                    'academic_year_id' => $academicYearId,
                    'status_id' => $statusId,
                    'title' => $activity['title'],
                    'abstract' => $activity['abstract'] ?? null,
                    'start_date' => $activity['start_date'],
                    'end_date' => $activity['end_date'],
                    'quantity' => 1,
                    'submitted_at' => in_array($statusCode, ['approved', 'submitted'], true) ? $now : null,
                    'approved_at' => $statusCode === 'approved' ? $now : null,
                    'total_hours_calc' => null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $activityId = DB::table('research_activities')
                ->where('activity_code', $activity['activity_code'])
                ->value('id');

            if (! $activityId) {
                continue;
            }

            DB::table($activity['details_table'])->updateOrInsert(
                ['activity_id' => $activityId],
                array_merge($activity['details'], [
                    'activity_id' => $activityId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );

            foreach ($activity['members'] ?? [] as $member) {
                $memberContext = $lecturerMap[$member['email']] ?? null;
                if (! $memberContext) {
                    continue;
                }

                $roleId = $memberRoleIds[$member['role_code']] ?? null;
                if (! $roleId) {
                    continue;
                }

                DB::table('research_activity_members')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'lecturer_id' => $memberContext['lecturer']->id,
                    ],
                    [
                        'member_role_id' => $roleId,
                        'contribution_share' => 1,
                        'hours_assigned' => $member['hours'] ?? 40,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            foreach ($activity['attachments'] ?? [] as $attachment) {
                $fileTypeId = $fileTypeIds[$attachment['file_type_code']] ?? null;
                if (! $fileTypeId) {
                    continue;
                }

                $disk = 'public';
                $filename = $attachment['file_name'];
                $path = 'demo/evidence/' . $filename;
                $content = "%PDF-1.4\n% Seeded file for " . $activity['activity_code'] . "\n" .
                    "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n" .
                    "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n" .
                    "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R >>\nendobj\n" .
                    "4 0 obj\n<< /Length 55 >>\nstream\nBT /F1 12 Tf 10 120 Td (" .
                    $attachment['content'] . ") Tj ET\nendstream\nendobj\nxref\n0 5\n" .
                    "0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n" .
                    "0000000117 00000 n \n0000000200 00000 n \ntrailer\n<< /Root 1 0 R /Size 5 >>\n" .
                    "startxref\n280\n%%EOF\n";

                if (! config("filesystems.disks.{$disk}")) {
                    continue;
                }

                Storage::disk($disk)->put($path, $content);

                $sha = hash('sha256', $content);
                DB::table('evidence_files')->updateOrInsert(
                    ['sha256' => $sha],
                    [
                        'activity_id' => $activityId,
                        'file_type_id' => $fileTypeId,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $filename,
                        'mime_type' => 'application/pdf',
                        'size_bytes' => strlen($content),
                        'uploaded_by_user_id' => $ownerUser->id,
                        'uploaded_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $assistantApprovalStatus = $activity['assistant_approval_status'] ?? null;
            $assistantApprovalNote = $activity['assistant_approval_note'] ?? null;
            if (! array_key_exists('assistant_approval_status', $activity)) {
                if ($statusCode === 'approved') {
                    $assistantApprovalStatus = 'approved';
                }
            }

            if ($assistantStageId && $assistantApprovalStatus) {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $assistantStageId,
                    ],
                    [
                        'status' => $assistantApprovalStatus,
                        'decided_by_user_id' => in_array($assistantApprovalStatus, ['approved', 'rejected'], true)
                            ? $ownerUser->id
                            : null,
                        'decided_at' => in_array($assistantApprovalStatus, ['approved', 'rejected'], true)
                            ? $now
                            : null,
                        'note' => $assistantApprovalNote,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $managerApprovalStatus = $activity['manager_approval_status'] ?? null;
            $managerApprovalNote = $activity['manager_approval_note'] ?? null;
            if (! array_key_exists('manager_approval_status', $activity)) {
                if ($statusCode === 'approved') {
                    $managerApprovalStatus = 'approved';
                }
            }

            if ($managerStageId && $managerApprovalStatus) {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $managerStageId,
                    ],
                    [
                        'status' => $managerApprovalStatus,
                        'decided_by_user_id' => in_array($managerApprovalStatus, ['approved', 'rejected'], true)
                            ? $ownerUser->id
                            : null,
                        'decided_at' => in_array($managerApprovalStatus, ['approved', 'rejected'], true)
                            ? $now
                            : null,
                        'note' => $managerApprovalNote,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
