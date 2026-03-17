<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $types = [
            ['code' => 'paper_first_page', 'name' => 'Trang đầu bài báo'],
            ['code' => 'paper_doi_or_article_link', 'name' => 'DOI hoặc liên kết bài báo'],
            ['code' => 'paper_journal_publication_info', 'name' => 'Thông tin tạp chí xuất bản'],
            ['code' => 'paper_acceptance_letter', 'name' => 'Thư chấp nhận đăng'],
            ['code' => 'paper_link_doi', 'name' => 'Link DOI'],
            ['code' => 'paper_link_journal_page', 'name' => 'Link bài báo trên tạp chí'],
            ['code' => 'paper_link_pdf', 'name' => 'Link PDF bài báo'],
            ['code' => 'paper_link_indexing', 'name' => 'Link chỉ mục'],

            ['code' => 'project_assignment_or_approval_decision', 'name' => 'Quyết định giao hoặc phê duyệt đề tài'],
            ['code' => 'project_proposal_document', 'name' => 'Thuyết minh đề tài'],
            ['code' => 'project_final_or_summary_report', 'name' => 'Báo cáo tổng kết đề tài'],
            ['code' => 'project_acceptance_minutes_or_recognition_decision', 'name' => 'Biên bản nghiệm thu hoặc quyết định công nhận'],
            ['code' => 'project_link_overview_page', 'name' => 'Link trang giới thiệu đề tài'],
            ['code' => 'project_link_summary_report', 'name' => 'Link báo cáo tóm tắt'],
            ['code' => 'project_link_output_product', 'name' => 'Link sản phẩm đầu ra'],
            ['code' => 'project_link_acceptance_evidence', 'name' => 'Link minh chứng nghiệm thu/công nhận'],

            ['code' => 'book_assignment_decision', 'name' => 'Quyết định giao biên soạn giáo trình'],
            ['code' => 'book_complete_manuscript', 'name' => 'Toàn văn giáo trình'],
            ['code' => 'book_appraisal_minutes_or_approval_decision', 'name' => 'Biên bản thẩm định hoặc quyết định phê duyệt'],
            ['code' => 'book_cover_or_publication_info_isbn', 'name' => 'Bìa hoặc thông tin xuất bản/ISBN'],
            ['code' => 'book_link_publisher', 'name' => 'Link nhà xuất bản'],
            ['code' => 'book_link_digital_library', 'name' => 'Link thư viện số'],
            ['code' => 'book_link_preview', 'name' => 'Link file xem trước'],
            ['code' => 'book_link_pdf', 'name' => 'Link file PDF giáo trình'],

            ['code' => 'conference_invitation_or_program', 'name' => 'Thư mời hoặc chương trình hội thảo'],
            ['code' => 'conference_paper_or_slides', 'name' => 'Bài báo cáo hoặc slide trình bày'],
            ['code' => 'conference_proceedings_page', 'name' => 'Trang kỷ yếu có tên tác giả'],
            ['code' => 'conference_participation_certificate', 'name' => 'Giấy xác nhận tham dự hoặc báo cáo viên'],
            ['code' => 'conference_link_website', 'name' => 'Link website hội thảo'],
            ['code' => 'conference_link_program', 'name' => 'Link chương trình hội thảo'],
            ['code' => 'conference_link_proceedings', 'name' => 'Link kỷ yếu'],
            ['code' => 'conference_link_paper', 'name' => 'Link bài tham luận'],
            ['code' => 'conference_link_slide_video', 'name' => 'Link slide/video'],
        ];

        foreach ($types as $type) {
            DB::table('evidence_file_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $codes = [
            'paper_first_page',
            'paper_doi_or_article_link',
            'paper_journal_publication_info',
            'paper_acceptance_letter',
            'paper_link_doi',
            'paper_link_journal_page',
            'paper_link_pdf',
            'paper_link_indexing',
            'project_assignment_or_approval_decision',
            'project_proposal_document',
            'project_final_or_summary_report',
            'project_acceptance_minutes_or_recognition_decision',
            'project_link_overview_page',
            'project_link_summary_report',
            'project_link_output_product',
            'project_link_acceptance_evidence',
            'book_assignment_decision',
            'book_complete_manuscript',
            'book_appraisal_minutes_or_approval_decision',
            'book_cover_or_publication_info_isbn',
            'book_link_publisher',
            'book_link_digital_library',
            'book_link_preview',
            'book_link_pdf',
            'conference_invitation_or_program',
            'conference_paper_or_slides',
            'conference_proceedings_page',
            'conference_participation_certificate',
            'conference_link_website',
            'conference_link_program',
            'conference_link_proceedings',
            'conference_link_paper',
            'conference_link_slide_video',
        ];

        DB::table('evidence_file_types')->whereIn('code', $codes)->delete();
    }
};
