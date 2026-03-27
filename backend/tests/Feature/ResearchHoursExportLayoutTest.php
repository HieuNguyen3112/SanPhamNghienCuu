<?php

namespace Tests\Feature;

use App\Exports\AdminResearchHoursReportExport;
use App\Support\LecturerHoursSummaryReportLayout;
use Tests\TestCase;

class ResearchHoursExportLayoutTest extends TestCase
{
    public function test_layout_uses_required_three_tier_header_structure(): void
    {
        $headings = LecturerHoursSummaryReportLayout::headings();
        $leafColumns = LecturerHoursSummaryReportLayout::leafColumns();
        $groups = LecturerHoursSummaryReportLayout::groups();

        $this->assertCount(3, $headings);
        $this->assertCount(16, $leafColumns);
        $this->assertSame(
            [2, 2, 3, 4, 2],
            array_map(
                static function (array $group): int {
                    if (($group['key'] ?? '') === 'hours_total') {
                        return 0;
                    }

                    if (isset($group['children'])) {
                        return array_sum(array_map(
                            static fn (array $child): int => count($child['columns']),
                            $group['children']
                        ));
                    }

                    return count($group['columns']);
                },
                array_slice($groups, 0, 5)
            )
        );

        $mergeRanges = LecturerHoursSummaryReportLayout::headerMergeRanges(6, 7, 8);

        $this->assertContains('A6:A8', $mergeRanges);
        $this->assertContains('B6:B8', $mergeRanges);
        $this->assertContains('C6:D6', $mergeRanges);
        $this->assertContains('C7:C8', $mergeRanges);
        $this->assertContains('D7:D8', $mergeRanges);
        $this->assertContains('J6:M6', $mergeRanges);
        $this->assertContains('J7:K7', $mergeRanges);
        $this->assertContains('L7:M7', $mergeRanges);
        $this->assertContains('N6:O6', $mergeRanges);
        $this->assertContains('N7:N8', $mergeRanges);
        $this->assertContains('O7:O8', $mergeRanges);
        $this->assertContains('P6:P8', $mergeRanges);
    }

    public function test_excel_array_starts_first_data_row_immediately_after_header(): void
    {
        $export = new AdminResearchHoursReportExport([
            [
                'tt' => 1,
                'lecturer_full_name' => 'ACC GIẢNG VIÊN DEMO',
                'national_projects_principal_summary' => ['count' => 1, 'hours' => 10],
                'national_projects_participant_summary' => ['count' => 0, 'hours' => 0],
                'school_projects_principal_summary' => ['count' => 0, 'hours' => 0],
                'school_projects_participant_summary' => ['count' => 1, 'hours' => 8],
                'papers_point_1_2_summary' => ['count' => 1, 'hours' => 6],
                'papers_point_le_1_summary' => ['count' => 0, 'hours' => 0],
                'papers_other_summary' => ['count' => 1, 'hours' => 4],
                'textbooks_principal_summary' => ['count' => 1, 'hours' => 12],
                'textbooks_participant_summary' => ['count' => 0, 'hours' => 0],
                'scholarly_books_principal_summary' => ['count' => 0, 'hours' => 0],
                'scholarly_books_participant_summary' => ['count' => 1, 'hours' => 5],
                'conferences_report_summary' => ['count' => 1, 'hours' => 3],
                'conferences_attend_summary' => ['count' => 1, 'hours' => 2],
                'hours_total' => 50,
            ],
        ], [
            'academic_year_code' => '2024-2025',
            'scope_label' => 'Toàn trường',
        ]);

        $rows = $export->array();

        $this->assertCount(9, $rows);
        $this->assertCount(16, $rows[5]);
        $this->assertCount(16, $rows[6]);
        $this->assertCount(16, $rows[7]);
        $this->assertSame('1', $rows[8][0]);
        $this->assertSame('ACC GIẢNG VIÊN DEMO', $rows[8][1]);
    }

    public function test_pdf_view_renders_same_nested_header_tree(): void
    {
        $html = view('exports.admin_hour_research_report', [
            'rows' => [],
            'meta' => [
                'academic_year_code' => '2024-2025',
                'scope_label' => 'Toàn trường',
            ],
        ])->render();

        $this->assertStringContainsString('rowspan="3" class="major-header identity-header">TT</th>', $html);
        $this->assertStringContainsString('rowspan="3" class="major-header identity-header">Họ và tên</th>', $html);
        $this->assertStringContainsString('Biên soạn giáo trình, tài liệu tham khảo', $html);
        $this->assertStringContainsString('Sách chuyên khảo, giáo trình', $html);
        $this->assertStringContainsString('Sách tham khảo, sách hướng dẫn, từ điển', $html);
        $this->assertStringContainsString('Hội nghị, hội thảo, seminar', $html);
        $this->assertStringContainsString('class="major-header total-column"', $html);
        $this->assertStringContainsString('Tổng số giờ', $html);
    }
}
