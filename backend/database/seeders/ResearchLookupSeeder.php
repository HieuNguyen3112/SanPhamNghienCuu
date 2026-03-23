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
                'start_date' => '2024-11-01',
                'end_date' => '2025-10-31',
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
            ['code' => 'paper', 'name' => 'Bài báo / Báo cáo khoa học'],
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
            ['code' => 'hdgsnn_600', 'name' => 'Bài báo HDGSNN <= 1 điểm (600 giờ)', 'kind_code' => 'paper'],
            ['code' => 'hdgsnn_300', 'name' => 'Bài báo có ISSN/ISBN (300 giờ)', 'kind_code' => 'paper'],
            ['code' => 'scientific_report_900', 'name' => 'Báo cáo khoa học (900 giờ)', 'kind_code' => 'paper'],
            ['code' => 'scientific_report_600', 'name' => 'Báo cáo khoa học (600 giờ)', 'kind_code' => 'paper'],
            ['code' => 'scientific_report', 'name' => 'Báo cáo khoa học (300 giờ)', 'kind_code' => 'paper'],
            ['code' => 'textbook', 'name' => 'Giáo trình', 'kind_code' => 'book'],
            ['code' => 'reference', 'name' => 'Tài liệu tham khảo', 'kind_code' => 'book'],
            ['code' => 'bo', 'name' => 'Đề tài cấp Bộ (2 năm)', 'kind_code' => 'project'],
            ['code' => 'coso', 'name' => 'Đề tài cấp cơ sở (1 năm)', 'kind_code' => 'project'],
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

        // Tương thích dữ liệu cũ: giữ code cũ nếu đã tồn tại, chỉ Việt hóa nhãn.
        $legacyTypeLabels = [
            'ministry' => 'Đề tài cấp Bộ (2 năm)',
            'university' => 'Đề tài cấp cơ sở (1 năm)',
            'province' => 'Đề tài cấp Tỉnh',
            'faculty' => 'Đề tài cấp Khoa',
            'other' => 'Khác',
        ];

        foreach ($legacyTypeLabels as $legacyCode => $label) {
            DB::table('activity_types')
                ->where('code', $legacyCode)
                ->update([
                    'name' => $label,
                    'updated_at' => $now,
                ]);
        }

        $statuses = [
            ['code' => 'draft', 'name' => 'Bản nháp'],
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
            ['code' => 'paper_first_page', 'name' => 'Trang đầu bài báo'],
            ['code' => 'paper_doi_or_article_link', 'name' => 'DOI hoặc liên kết bài báo'],
            ['code' => 'paper_journal_publication_info', 'name' => 'Thông tin tạp chí xuất bản'],
            ['code' => 'paper_acceptance_letter', 'name' => 'Thư chấp nhận đăng'],
            ['code' => 'project_assignment_or_approval_decision', 'name' => 'Quyết định giao hoặc phê duyệt đề tài'],
            ['code' => 'project_proposal_document', 'name' => 'Thuyết minh đề tài'],
            ['code' => 'project_final_or_summary_report', 'name' => 'Báo cáo tổng kết đề tài'],
            ['code' => 'project_acceptance_minutes_or_recognition_decision', 'name' => 'Biên bản nghiệm thu hoặc quyết định công nhận'],
            ['code' => 'book_assignment_decision', 'name' => 'Quyết định giao biên soạn giáo trình'],
            ['code' => 'book_complete_manuscript', 'name' => 'Toàn văn giáo trình'],
            ['code' => 'book_appraisal_minutes_or_approval_decision', 'name' => 'Biên bản thẩm định hoặc quyết định phê duyệt'],
            ['code' => 'book_cover_or_publication_info_isbn', 'name' => 'Bìa hoặc thông tin xuất bản/ISBN'],
            ['code' => 'conference_invitation_or_program', 'name' => 'Thư mời hoặc chương trình hội thảo'],
            ['code' => 'conference_paper_or_slides', 'name' => 'Bài báo cáo hoặc slide trình bày'],
            ['code' => 'conference_proceedings_page', 'name' => 'Trang kỷ yếu có tên tác giả'],
            ['code' => 'conference_participation_certificate', 'name' => 'Giấy xác nhận tham dự hoặc báo cáo viên'],
            ['code' => 'paper_link_doi', 'name' => 'Link DOI'],
            ['code' => 'paper_link_journal_page', 'name' => 'Link bài báo trên tạp chí'],
            ['code' => 'paper_link_pdf', 'name' => 'Link PDF bài báo'],
            ['code' => 'paper_link_indexing', 'name' => 'Link chỉ mục'],
            ['code' => 'project_link_overview_page', 'name' => 'Link trang giới thiệu đề tài'],
            ['code' => 'project_link_summary_report', 'name' => 'Link báo cáo tóm tắt'],
            ['code' => 'project_link_output_product', 'name' => 'Link sản phẩm đầu ra'],
            ['code' => 'project_link_acceptance_evidence', 'name' => 'Link minh chứng nghiệm thu/công nhận'],
            ['code' => 'book_link_publisher', 'name' => 'Link nhà xuất bản'],
            ['code' => 'book_link_digital_library', 'name' => 'Link thư viện số'],
            ['code' => 'book_link_preview', 'name' => 'Link file xem trước'],
            ['code' => 'book_link_pdf', 'name' => 'Link file PDF giáo trình'],
            ['code' => 'conference_link_website', 'name' => 'Link website hội thảo'],
            ['code' => 'conference_link_program', 'name' => 'Link chương trình hội thảo'],
            ['code' => 'conference_link_proceedings', 'name' => 'Link kỷ yếu'],
            ['code' => 'conference_link_paper', 'name' => 'Link bài tham luận'],
            ['code' => 'conference_link_slide_video', 'name' => 'Link slide/video'],
        ];

        foreach ($evidenceTypes as $type) {
            DB::table('evidence_file_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
