<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $workTypes = [
            ['name' => 'Bài báo', 'description' => 'Bài báo tạp chí hoặc kỷ yếu', 'is_active' => true],
            ['name' => 'Sách', 'description' => 'Sách hoặc giáo trình', 'is_active' => true],
            ['name' => 'Đề tài', 'description' => 'Đề tài nghiên cứu khoa học', 'is_active' => true],
            ['name' => 'Hội nghị', 'description' => 'Tham gia hội nghị, hội thảo', 'is_active' => false],
            ['name' => 'Khác', 'description' => null, 'is_active' => true],
        ];

        foreach ($workTypes as $item) {
            DB::table('work_types')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'is_active' => $item['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $workLevels = [
            ['name' => 'Quốc tế', 'priority' => 1, 'notes' => 'Ưu tiên cao nhất', 'is_active' => true],
            ['name' => 'Quốc gia', 'priority' => 2, 'notes' => null, 'is_active' => true],
            ['name' => 'Cấp Bộ', 'priority' => 3, 'notes' => 'Cấp Bộ hoặc cơ quan ngang Bộ', 'is_active' => true],
            ['name' => 'Cấp trường', 'priority' => 4, 'notes' => null, 'is_active' => true],
            ['name' => 'Cấp khoa', 'priority' => 5, 'notes' => null, 'is_active' => true],
            ['name' => 'Khác', 'priority' => 6, 'notes' => null, 'is_active' => false],
        ];

        foreach ($workLevels as $item) {
            DB::table('work_levels')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'priority' => $item['priority'],
                    'notes' => $item['notes'],
                    'is_active' => $item['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
        $journals = [
            [
                'name'           => 'Tạp chí Khoa học Trường Đại học Sư phạm TP. Hồ Chí Minh',
                'address'        => 'Trường Đại học Sư phạm Thành phố Hồ Chí Minh, TP. Hồ Chí Minh, Việt Nam',
                'issn'           => '2090-1232',
                'source_name'    => 'ISI',           // Chuyển ISI từ classification sang đây
                'point'          => 2.00,
                'classification' => 'HDGSNN_GE_1',   // Cập nhật lại format theo schema
                'research_hours' => 600,             // Thêm cột mới
                'country'        => 'Vietnam',
                'notes'          => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Vietnam Journal of Education',
                'address'        => 'Bộ Giáo dục và Đào tạo, Hà Nội, Việt Nam',
                'issn'           => '0866-708X',
                'source_name'    => 'Scopus',        // Chuyển SCOPUS từ classification sang đây
                'point'          => 1.00,
                'classification' => 'HDGSNN_GE_2',   // Cập nhật lại format theo schema
                'research_hours' => 300,             // Thêm cột mới
                'country'        => 'Vietnam',
                'notes'          => 'Tạp chí chuyên ngành giáo dục',
                'is_active'      => true,
            ],
            [
                'name'           => 'Tạp chí Công nghệ Giáo dục',
                'address'        => 'TP. Hồ Chí Minh, Việt Nam',
                'issn'           => null,
                'source_name'    => null,
                'point'          => null,
                'classification' => 'OTHER',
                'research_hours' => 0,
                'country'        => 'Vietnam',
                'notes'          => 'Chờ cập nhật ISSN chính thức',
                'is_active'      => false,
            ],
        ];

        // Chạy seeder cho Journals
        foreach ($journals as $item) {
            // Nếu có ISSN thì update theo ISSN (vì issn là unique), nếu không thì map theo name
            $key = $item['issn'] ? ['issn' => $item['issn']] : ['name' => $item['name']];

            DB::table('journals')->updateOrInsert(
                $key,
                [
                    'name'           => $item['name'],
                    'address'        => $item['address'],
                    'issn'           => $item['issn'],
                    'source_name'    => $item['source_name'],
                    'point'          => $item['point'],
                    'classification' => $item['classification'],
                    'research_hours' => $item['research_hours'],
                    'country'        => $item['country'],
                    'notes'          => $item['notes'],
                    'is_active'      => $item['is_active'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]
            );
        }

        // Lấy danh sách ID đã tạo để map cho Journal Rankings
        $journalIdByIssn = DB::table('journals')
            ->whereNotNull('issn')
            ->pluck('id', 'issn')
            ->all();

        $rankings = [
            ['issn' => '2090-1232', 'rank' => 'Q2', 'effective_from' => now()->subMonths(6)->toDateString()],
            ['issn' => '2090-1232', 'rank' => 'Q1', 'effective_from' => now()->subDays(20)->toDateString()],
            ['issn' => '0866-708X', 'rank' => 'Q3', 'effective_from' => now()->subMonths(3)->toDateString()],
        ];

        // Chạy seeder cho Journal Rankings
        foreach ($rankings as $item) {
            $journalId = $journalIdByIssn[$item['issn']] ?? null;
            if (! $journalId) {
                continue;
            }

            DB::table('journal_rankings')->updateOrInsert(
                [
                    'journal_id'     => $journalId,
                    'effective_from' => $item['effective_from']
                ],
                [
                    'rank'       => $item['rank'],
                    'note'       => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
        $conferences = [
            [
                'name' => 'Hội thảo quốc tế về Trí tuệ nhân tạo và Giáo dục',
                'level' => 'INTERNATIONAL',
                'research_field' => 'Trí tuệ nhân tạo',
                'year' => 2025,
                'organization' => 'Trường Đại học Sư phạm TP. Hồ Chí Minh',
                'has_proceedings' => true,
                'has_isbn' => true,
                'isbn' => '978-604-111111-1',
                'point' => 1.50,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Hội nghị Khoa học trẻ HCMUE',
                'level' => 'NATIONAL',
                'research_field' => 'Khoa học giáo dục',
                'year' => 2025,
                'organization' => 'HCMUE',
                'has_proceedings' => true,
                'has_isbn' => false,
                'isbn' => null,
                'point' => 1.00,
                'notes' => 'Tổ chức thường niên tại HCMUE',
                'is_active' => true,
            ],
            [
                'name' => 'Seminar Khoa học dữ liệu Khoa Công nghệ Thông tin',
                'level' => 'NATIONAL',
                'research_field' => 'Khoa học dữ liệu',
                'year' => 2024,
                'organization' => 'Khoa Công nghệ Thông tin',
                'has_proceedings' => false,
                'has_isbn' => false,
                'isbn' => null,
                'point' => 0.50,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Hội thảo quốc gia Toán học và Tin học ứng dụng',
                'level' => 'NATIONAL',
                'research_field' => 'Toán học ứng dụng',
                'year' => 2024,
                'organization' => 'Hội Toán học Việt Nam',
                'has_proceedings' => true,
                'has_isbn' => true,
                'isbn' => '978-604-222222-2',
                'point' => 1.25,
                'notes' => null,
                'is_active' => true,
            ],
        ];

        foreach ($conferences as $item) {
            DB::table('conferences')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'level' => $item['level'],
                    'research_field' => $item['research_field'],
                    'year' => $item['year'],
                    'organization' => $item['organization'],
                    'has_proceedings' => $item['has_proceedings'],
                    'has_isbn' => $item['has_isbn'],
                    'isbn' => $item['isbn'],
                    'point' => $item['point'],
                    'notes' => $item['notes'],
                    'is_active' => $item['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $researchFields = [
            ['code' => 'AI', 'name' => 'Trí tuệ nhân tạo', 'description' => 'Máy học, xử lý ngôn ngữ tự nhiên', 'is_active' => true],
            ['code' => 'EDU', 'name' => 'Khoa học giáo dục', 'description' => null, 'is_active' => true],
            ['code' => 'SE', 'name' => 'Kỹ thuật phần mềm', 'description' => null, 'is_active' => true],
            ['code' => 'DS', 'name' => 'Khoa học dữ liệu', 'description' => 'Phân tích dữ liệu và học máy ứng dụng', 'is_active' => true],
            ['code' => 'CYBER', 'name' => 'An toàn thông tin', 'description' => 'Bảo mật hệ thống và dữ liệu', 'is_active' => true],
            ['code' => 'MATH', 'name' => 'Toán học ứng dụng', 'description' => 'Mô hình hóa toán học và tối ưu', 'is_active' => true],
            ['code' => 'STAT', 'name' => 'Thống kê', 'description' => 'Suy luận thống kê và phân tích dữ liệu giáo dục', 'is_active' => true],
            ['code' => null, 'name' => 'Kinh tế học', 'description' => null, 'is_active' => false],
        ];

        foreach ($researchFields as $item) {
            $key = $item['code'] ? ['code' => $item['code']] : ['name' => $item['name']];
            DB::table('research_fields')->updateOrInsert(
                $key,
                [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'is_active' => $item['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
