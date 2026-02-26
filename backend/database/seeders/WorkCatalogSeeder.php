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
                'name'           => 'Journal of Advanced Research',
                'address'        => 'Cairo University, Giza, Egypt',
                'issn'           => '2090-1232',
                'source_name'    => 'ISI',           // Chuyển ISI từ classification sang đây
                'point_min'      => 1.00,            // Thêm cột mới
                'point_max'      => 2.00,            // Thêm cột mới
                'classification' => 'HDGSNN_GE_1',   // Cập nhật lại format theo schema
                'research_hours' => 600,             // Thêm cột mới
                'country'        => 'Egypt',
                'notes'          => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Vietnam Journal of Science and Technology',
                'address'        => 'VAST, Hanoi, Vietnam',
                'issn'           => '0866-708X',
                'source_name'    => 'Scopus',        // Chuyển SCOPUS từ classification sang đây
                'point_min'      => 0.50,            // Thêm cột mới
                'point_max'      => 1.00,            // Thêm cột mới
                'classification' => 'HDGSNN_GE_2',   // Cập nhật lại format theo schema
                'research_hours' => 300,             // Thêm cột mới
                'country'        => 'Vietnam',
                'notes'          => 'Scopus indexed',
                'is_active'      => true,
            ],
            [
                'name'           => 'International Journal of Computer Science',
                'address'        => 'USA',
                'issn'           => null,
                'source_name'    => null,
                'point_min'      => null,
                'point_max'      => null,
                'classification' => 'OTHER',
                'research_hours' => 0,
                'country'        => 'USA',
                'notes'          => 'ISSN pending',
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
                    'point_min'      => $item['point_min'],
                    'point_max'      => $item['point_max'],
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
            ['name' => 'Hội nghị quốc tế về AI', 'level' => 'INTERNATIONAL', 'notes' => null, 'is_active' => true],
            ['name' => 'Hội nghị khoa học cấp trường', 'level' => 'UNIVERSITY', 'notes' => 'Tổ chức hằng năm', 'is_active' => true],
            ['name' => 'Hội thảo cấp khoa', 'level' => 'FACULTY', 'notes' => null, 'is_active' => true],
            ['name' => 'Hội thảo quốc gia về giáo dục', 'level' => 'NATIONAL', 'notes' => null, 'is_active' => false],
        ];

        foreach ($conferences as $item) {
            DB::table('conferences')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'level' => $item['level'],
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
