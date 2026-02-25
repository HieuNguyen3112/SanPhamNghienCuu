<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchLookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $academicYears = [
            [
                'code' => '2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-08-31',
                'is_active' => true,
            ],
        ];

        foreach ($academicYears as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['code' => $year['code']],
                array_merge($year, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $kinds = [
            ['code' => 'paper', 'name' => 'Bài báo khoa học'],
            ['code' => 'book', 'name' => 'Sách, giáo trình'],
            ['code' => 'project', 'name' => 'Đề tài KH&CN'],
            ['code' => 'conference', 'name' => 'Hội nghị, hội thảo'],
        ];

        foreach ($kinds as $kind) {
            DB::table('activity_kinds')->updateOrInsert(
                ['code' => $kind['code']],
                array_merge($kind, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $kindIds = DB::table('activity_kinds')
            ->whereIn('code', array_column($kinds, 'code'))
            ->pluck('id', 'code')
            ->all();

        $types = [
            ['code' => 'hdgsnn_900', 'name' => 'Bài báo HDGSNN 1-2 điểm (900 giờ)', 'kind_code' => 'paper'],
            ['code' => 'hdgsnn_600', 'name' => 'Bài báo HDGSNN >= 1 điểm (600 giờ)', 'kind_code' => 'paper'],
            ['code' => 'hdgsnn_300', 'name' => 'Bài báo có ISSN/ISBN (300 giờ)', 'kind_code' => 'paper'],
            ['code' => 'textbook', 'name' => 'Giáo trình', 'kind_code' => 'book'],
            ['code' => 'reference', 'name' => 'Tài liệu tham khảo', 'kind_code' => 'book'],
            ['code' => 'bo', 'name' => 'Đề tài cấp Bộ', 'kind_code' => 'project'],
            ['code' => 'coso', 'name' => 'Đề tài cấp Trường', 'kind_code' => 'project'],
            ['code' => 'report', 'name' => 'Báo cáo hội thảo', 'kind_code' => 'conference'],
            ['code' => 'attend', 'name' => 'Tham dự hội thảo', 'kind_code' => 'conference'],
        ];

        foreach ($types as $type) {
            $kindId = $kindIds[$type['kind_code']] ?? null;
            if (! $kindId) {
                continue;
            }

            DB::table('activity_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'kind_id' => $kindId,
                    'name' => $type['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $statuses = [
            ['code' => 'draft', 'name' => 'Nháp'],
            ['code' => 'pending_member_confirm', 'name' => 'Chờ thành viên xác nhận'],
            ['code' => 'member_rejected', 'name' => 'Thành viên từ chối'],
            ['code' => 'pending_faculty_review', 'name' => 'Chờ khoa duyệt'],
            ['code' => 'approved', 'name' => 'Đã duyệt'],
            ['code' => 'rejected', 'name' => 'Từ chối'],
        ];


        foreach ($statuses as $status) {
            DB::table('activity_statuses')->updateOrInsert(
                ['code' => $status['code']],
                [
                    'name'       => $status['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $memberRoles = [
            ['code' => 'principal', 'name' => 'Chủ nhiệm'],
            ['code' => 'member', 'name' => 'Thành viên'],
            ['code' => 'secretary', 'name' => 'Thư ký'],
            ['code' => 'corresponding_author', 'name' => 'Tác giả chính'],
            ['code' => 'coauthor', 'name' => 'Đồng tác giả'],
            ['code' => 'chief_editor', 'name' => 'Chủ biên'],
        ];

        foreach ($memberRoles as $role) {
            DB::table('member_roles')->updateOrInsert(
                ['code' => $role['code']],
                array_merge($role, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $approvalStages = [
            ['code' => 'assistant', 'name' => 'Khoa duyệt nội dung', 'order_no' => 1],
            ['code' => 'manager', 'name' => 'Trường duyệt nội dung', 'order_no' => 2],
            ['code' => 'hours', 'name' => 'Khoa duyệt giờ NCKH', 'order_no' => 3],
        ];

        foreach ($approvalStages as $stage) {
            DB::table('approval_stages')->updateOrInsert(
                ['code' => $stage['code']],
                array_merge($stage, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $evidenceTypes = [
            ['code' => 'content', 'name' => 'Toàn văn'],
            ['code' => 'cover', 'name' => 'Trang bìa'],
            ['code' => 'toc', 'name' => 'Mục lục'],
            ['code' => 'acceptance_decision', 'name' => 'Quyết định nghiệm thu'],
            ['code' => 'publication_decision', 'name' => 'Quyết định xuất bản'],
        ];

        foreach ($evidenceTypes as $type) {
            DB::table('evidence_file_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
