<?php

namespace Tests\Feature;

use App\Exports\AdminLecturerHoursSummaryExport;
use App\Exports\AdminLecturerReportExport;
use App\Exports\AdminResearchHoursReportExport;
use App\Exports\AdminResearchReportExport;
use App\Exports\AdminResearchWorksSummaryExport;
use Illuminate\Support\Arr;
use Tests\TestCase;

class UniversityExportEncodingTest extends TestCase
{

    public function test_university_pdf_export_views_render_clean_vietnamese_labels(): void
    {
        $cases = [
            [
                'view' => 'exports.admin_works_summary',
                'data' => [
                    'rows' => [],
                    'filters' => [
                        'faculty' => 'Tất cả',
                        'department' => 'Tất cả',
                        'academic_year' => '2024-2025',
                        'status' => 'Tất cả',
                        'keyword' => 'Tất cả',
                    ],
                ],
                'expected' => [
                    'Quản lý công trình NCKH theo giảng viên',
                    'Giảng viên',
                    'Không có dữ liệu',
                ],
            ],
            [
                'view' => 'exports.admin_hours_summary',
                'data' => [
                    'rows' => [],
                    'filters' => [
                        'faculty' => 'Tất cả',
                        'academic_year' => '2024-2025',
                        'status' => 'Tất cả',
                        'keyword' => 'Tất cả',
                    ],
                ],
                'expected' => [
                    'Quản lý giờ nghiên cứu khoa học theo giảng viên',
                    'Trạng thái KPI',
                    'Không có dữ liệu',
                ],
            ],
            [
                'view' => 'exports.admin_lecturer_report',
                'data' => [
                    'rows' => [],
                    'filters' => [
                        'faculty' => 'Tất cả',
                        'degree' => 'Tất cả',
                        'academic_rank' => 'Tất cả',
                        'gender' => 'Tất cả',
                    ],
                ],
                'expected' => [
                    'Báo cáo nhân sự giảng viên',
                    'Giới tính',
                    'Không có dữ liệu',
                ],
            ],
            [
                'view' => 'exports.admin_research_report',
                'data' => [
                    'rows' => [],
                    'kpis' => [],
                    'charts' => [
                        'by_department_stacked' => [
                            'labels' => [],
                            'isi' => [],
                            'scopus' => [],
                            'conference' => [],
                            'project' => [],
                            'book' => [],
                        ],
                        'distribution_donut' => [
                            'labels' => [],
                            'values' => [],
                        ],
                        'by_year_line' => [
                            'labels' => [],
                            'values' => [],
                        ],
                    ],
                    'filters' => [
                        'year' => 'Tất cả',
                        'department' => 'Tất cả',
                        'research_type' => 'Tất cả',
                        'lecturer' => 'Tất cả',
                        'keyword' => 'Tất cả',
                    ],
                ],
                'expected' => [
                    'Báo cáo công trình nghiên cứu khoa học',
                    'Đề tài / Dự án',
                    'Không có dữ liệu',
                ],
            ],
            [
                'view' => 'exports.admin_hour_research_report',
                'data' => [
                    'rows' => [],
                    'kpis' => [],
                    'filters' => [
                        'faculty' => 'Tất cả',
                        'academic_year' => '2024-2025',
                        'status' => 'Tất cả',
                    ],
                ],
                'expected' => [
                    'Báo cáo thống kê giờ NCKH',
                    'Giảng viên',
                    'Không có dữ liệu',
                ],
            ],
        ];

        foreach ($cases as $case) {
            $html = view($case['view'], $case['data'])->render();

            $this->assertUtf8VietnameseText($html, $case['view'], $case['expected']);
        }
    }

    public function test_university_excel_exports_keep_clean_vietnamese_labels(): void
    {
        $cases = [
            [
                'name' => 'AdminResearchWorksSummaryExport',
                'payload' => (new AdminResearchWorksSummaryExport([]))->headings(),
                'expected' => ['Giảng viên', 'Đã duyệt', 'Từ chối'],
            ],
            [
                'name' => 'AdminLecturerHoursSummaryExport',
                'payload' => (new AdminLecturerHoursSummaryExport([]))->headings(),
                'expected' => ['Giảng viên', 'Chênh lệch', 'Trạng thái'],
            ],
            [
                'name' => 'AdminLecturerReportExport.headings',
                'payload' => (new AdminLecturerReportExport([]))->headings(),
                'expected' => ['Họ và tên', 'Giới tính', 'Thâm niên (năm)'],
            ],
            [
                'name' => 'AdminLecturerReportExport.map',
                'payload' => (new AdminLecturerReportExport([]))->map([
                    'full_name' => 'Nguyễn Thị A',
                    'faculty' => ['name' => 'Khoa Khoa học'],
                    'gender' => 'female',
                    'degree' => ['name' => 'Tiến sĩ'],
                    'academic_rank' => ['name' => 'Phó giáo sư'],
                    'seniority_years' => 8,
                ]),
                'expected' => ['Nguyễn Thị A', 'Nữ', 'Tiến sĩ'],
            ],
            [
                'name' => 'AdminResearchReportExport',
                'payload' => (new AdminResearchReportExport(
                    [],
                    [],
                    [
                        'by_department_stacked' => [
                            'labels' => [],
                            'isi' => [],
                            'scopus' => [],
                            'conference' => [],
                            'project' => [],
                            'book' => [],
                        ],
                        'distribution_donut' => [
                            'labels' => [],
                            'values' => [],
                        ],
                        'by_year_line' => [
                            'labels' => [],
                            'values' => [],
                        ],
                    ],
                    [
                        'year' => 'Tất cả',
                        'department' => 'Tất cả',
                        'research_type' => 'Tất cả',
                        'lecturer' => 'Tất cả',
                        'keyword' => 'Tất cả',
                    ]
                ))->array(),
                'expected' => [
                    'Báo cáo công trình nghiên cứu khoa học',
                    'Đề tài / Dự án',
                    'Không có dữ liệu',
                ],
            ],
            [
                'name' => 'AdminResearchHoursReportExport',
                'payload' => (new AdminResearchHoursReportExport(
                    [],
                    [],
                    [
                        'faculty' => 'Tất cả',
                        'academic_year' => '2024-2025',
                        'status' => 'Tất cả',
                    ]
                ))->array(),
                'expected' => [
                    'Báo cáo thống kê giờ NCKH',
                    'Giảng viên',
                    'Không có dữ liệu',
                ],
            ],
        ];

        foreach ($cases as $case) {
            $text = $this->flattenExportPayload($case['payload']);

            $this->assertUtf8VietnameseText($text, $case['name'], $case['expected']);
        }
    }

    private function flattenExportPayload(array $payload): string
    {
        $flattened = Arr::flatten($payload);

        return implode(
            ' | ',
            array_map(
                static fn ($value): string => is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $flattened
            )
        );
    }

    private function assertUtf8VietnameseText(string $text, string $context, array $expectedLabels): void
    {
        foreach ($this->mojibakeMarkers() as $marker) {
            $this->assertStringNotContainsString(
                $marker,
                $text,
                sprintf('Unexpected mojibake marker "%s" found in %s.', $marker, $context)
            );
        }

        foreach ($expectedLabels as $label) {
            $this->assertStringContainsString($label, $text, sprintf('Missing label "%s" in %s.', $label, $context));
        }
    }

    private function mojibakeMarkers(): array
    {
        return [
            hex2bin('C383'),
            hex2bin('C382'),
            hex2bin('C384'),
            hex2bin('E280'),
        ];
    }
}
