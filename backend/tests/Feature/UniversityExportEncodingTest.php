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
                        'faculty' => 'Táº¥t cáº£',
                        'department' => 'Táº¥t cáº£',
                        'academic_year' => '2024-2025',
                        'status' => 'Táº¥t cáº£',
                        'keyword' => 'Táº¥t cáº£',
                    ],
                ],
                'expected' => [
                    'Quáº£n lÃ½ cÃ´ng trÃ¬nh NCKH theo giáº£ng viÃªn',
                    'Giáº£ng viÃªn',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
                ],
            ],
            [
                'view' => 'exports.admin_hours_summary',
                'data' => [
                    'rows' => [],
                    'meta' => [
                        'academic_year_code' => '2024-2025',
                        'scope_label' => 'Toàn trường',
                    ],
                ],
                'expected' => [
                    'BÁO CÁO TỔNG HỢP GIỜ NGHIÊN CỨU KHOA HỌC',
                    'Hội nghị, hội thảo, seminar',
                    'Không có dữ liệu cho phạm vi báo cáo đã chọn.',
                ],
            ],
            [
                'view' => 'exports.admin_lecturer_report',
                'data' => [
                    'rows' => [],
                    'filters' => [
                        'faculty' => 'Táº¥t cáº£',
                        'degree' => 'Táº¥t cáº£',
                        'academic_rank' => 'Táº¥t cáº£',
                        'gender' => 'Táº¥t cáº£',
                    ],
                ],
                'expected' => [
                    'BÃ¡o cÃ¡o nhÃ¢n sá»± giáº£ng viÃªn',
                    'Giá»›i tÃ­nh',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
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
                        'year' => 'Táº¥t cáº£',
                        'department' => 'Táº¥t cáº£',
                        'research_type' => 'Táº¥t cáº£',
                        'lecturer' => 'Táº¥t cáº£',
                        'keyword' => 'Táº¥t cáº£',
                    ],
                ],
                'expected' => [
                    'BÃ¡o cÃ¡o cÃ´ng trÃ¬nh nghiÃªn cá»©u khoa há»c',
                    'Äá» tÃ i / Dá»± Ã¡n',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
                ],
            ],
            [
                'view' => 'exports.admin_hour_research_report',
                'data' => [
                    'rows' => [],
                    'kpis' => [],
                    'filters' => [
                        'faculty' => 'Táº¥t cáº£',
                        'academic_year' => '2024-2025',
                        'status' => 'Táº¥t cáº£',
                    ],
                ],
                'expected' => [
                    'BÃ¡o cÃ¡o thá»‘ng kÃª giá» NCKH',
                    'Giáº£ng viÃªn',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
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
                'expected' => ['Giáº£ng viÃªn', 'ÄÃ£ duyá»‡t', 'Tá»« chá»‘i'],
            ],
            [
                'name' => 'AdminLecturerHoursSummaryExport',
                'payload' => (new AdminLecturerHoursSummaryExport([]))->headings(),
                'expected' => ['Họ và tên', 'Đề tài cấp Trường', 'Tổng số giờ'],
            ],
            [
                'name' => 'AdminLecturerReportExport.headings',
                'payload' => (new AdminLecturerReportExport([]))->headings(),
                'expected' => ['Há» vÃ  tÃªn', 'Giá»›i tÃ­nh', 'ThÃ¢m niÃªn (nÄƒm)'],
            ],
            [
                'name' => 'AdminLecturerReportExport.map',
                'payload' => (new AdminLecturerReportExport([]))->map([
                    'full_name' => 'Nguyá»…n Thá»‹ A',
                    'faculty' => ['name' => 'Khoa Khoa há»c'],
                    'gender' => 'female',
                    'degree' => ['name' => 'Tiáº¿n sÄ©'],
                    'academic_rank' => ['name' => 'PhÃ³ giÃ¡o sÆ°'],
                    'seniority_years' => 8,
                ]),
                'expected' => ['Nguyá»…n Thá»‹ A', 'Ná»¯', 'Tiáº¿n sÄ©'],
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
                        'year' => 'Táº¥t cáº£',
                        'department' => 'Táº¥t cáº£',
                        'research_type' => 'Táº¥t cáº£',
                        'lecturer' => 'Táº¥t cáº£',
                        'keyword' => 'Táº¥t cáº£',
                    ]
                ))->array(),
                'expected' => [
                    'BÃ¡o cÃ¡o cÃ´ng trÃ¬nh nghiÃªn cá»©u khoa há»c',
                    'Äá» tÃ i / Dá»± Ã¡n',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
                ],
            ],
            [
                'name' => 'AdminResearchHoursReportExport',
                'payload' => (new AdminResearchHoursReportExport(
                    [],
                    [],
                    [
                        'faculty' => 'Táº¥t cáº£',
                        'academic_year' => '2024-2025',
                        'status' => 'Táº¥t cáº£',
                    ]
                ))->array(),
                'expected' => [
                    'BÃ¡o cÃ¡o thá»‘ng kÃª giá» NCKH',
                    'Giáº£ng viÃªn',
                    'KhÃ´ng cÃ³ dá»¯ liá»‡u',
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

