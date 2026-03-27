<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LecturerHoursSummaryReportLayout
{
    public static function title(): string
    {
        return 'BÁO CÁO TỔNG HỢP GIỜ NGHIÊN CỨU KHOA HỌC';
    }

    public static function emptyStateMessage(): string
    {
        return 'Không có dữ liệu cho phạm vi báo cáo đã chọn.';
    }

    public static function valueLegend(): string
    {
        return 'Đơn vị trong ô: số lượng / giờ quy đổi';
    }

    public static function headerRowCount(): int
    {
        return 3;
    }

    public static function groups(): array
    {
        return [
            [
                'key' => 'national_projects',
                'label' => 'Đề tài cấp Nhà nước / Bộ / Tỉnh / Thành phố',
                'header_fill' => 'D8E6F3',
                'subheader_fill' => 'EDF4FA',
                'columns' => [
                    ['key' => 'national_projects_principal_summary', 'label' => 'Chủ nhiệm', 'format' => 'count_hours'],
                    ['key' => 'national_projects_participant_summary', 'label' => "Tham gia\nSố thành viên/số giờ", 'format' => 'count_hours'],
                ],
            ],
            [
                'key' => 'school_projects',
                'label' => 'Đề tài cấp Trường',
                'header_fill' => 'E3EDF8',
                'subheader_fill' => 'F1F6FB',
                'columns' => [
                    ['key' => 'school_projects_principal_summary', 'label' => 'Chủ nhiệm', 'format' => 'count_hours'],
                    ['key' => 'school_projects_participant_summary', 'label' => 'Tham gia', 'format' => 'count_hours'],
                ],
            ],
            [
                'key' => 'papers',
                'label' => 'Bài báo / Báo cáo khoa học',
                'header_fill' => 'EDE7D8',
                'subheader_fill' => 'F7F3E9',
                'columns' => [
                    ['key' => 'papers_point_1_2_summary', 'label' => "Điểm 1-2\n(Số tác giả/số giờ)", 'format' => 'count_hours'],
                    ['key' => 'papers_point_le_1_summary', 'label' => "Điểm ≤ 1\n(Số tác giả/số giờ)", 'format' => 'count_hours'],
                    ['key' => 'papers_other_summary', 'label' => "Bài báo / báo cáo khác\n(Số tác giả/số giờ)", 'format' => 'count_hours'],
                ],
            ],
            [
                'key' => 'books',
                'label' => 'Biên soạn giáo trình, tài liệu tham khảo',
                'header_fill' => 'E8E1F0',
                'subheader_fill' => 'F3EDF8',
                'children' => [
                    [
                        'key' => 'textbooks',
                        'label' => 'Sách chuyên khảo, giáo trình',
                        'columns' => [
                            ['key' => 'textbooks_principal_summary', 'label' => 'Chủ biên', 'format' => 'count_hours'],
                            ['key' => 'textbooks_participant_summary', 'label' => "Tham gia\n(Số tác giả/số giờ)", 'format' => 'count_hours'],
                        ],
                    ],
                    [
                        'key' => 'scholarly_books',
                        'label' => 'Sách tham khảo, sách hướng dẫn, từ điển',
                        'columns' => [
                            ['key' => 'scholarly_books_principal_summary', 'label' => 'Chủ biên', 'format' => 'count_hours'],
                            ['key' => 'scholarly_books_participant_summary', 'label' => "Tham gia\n(Số tác giả/số giờ)", 'format' => 'count_hours'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'conferences',
                'label' => 'Hội nghị, hội thảo, seminar',
                'header_fill' => 'F4E6D7',
                'subheader_fill' => 'FBF1E8',
                'columns' => [
                    ['key' => 'conferences_report_summary', 'label' => "Báo cáo\n(Số buổi/số giờ)", 'format' => 'count_hours'],
                    ['key' => 'conferences_attend_summary', 'label' => "Tham dự\n(Số lượt/số giờ)", 'format' => 'count_hours'],
                ],
            ],
            [
                'key' => 'hours_total',
                'label' => 'Tổng số giờ',
                'header_fill' => 'D9E2F0',
                'subheader_fill' => 'D9E2F0',
                'is_total' => true,
                'columns' => [
                    ['key' => 'hours_total', 'label' => 'Tổng số giờ', 'format' => 'hours'],
                ],
            ],
        ];
    }

    public static function leafColumns(): array
    {
        $columns = [
            ['key' => 'tt', 'label' => 'TT', 'format' => 'count', 'align' => 'center'],
            ['key' => 'lecturer_full_name', 'label' => 'Họ và tên', 'format' => 'text', 'align' => 'left'],
        ];

        foreach (self::groups() as $group) {
            if (isset($group['children'])) {
                foreach ($group['children'] as $child) {
                    foreach ($child['columns'] as $column) {
                        $columns[] = $column + ['align' => 'center'];
                    }
                }
                continue;
            }

            foreach ($group['columns'] as $column) {
                $columns[] = $column + ['align' => ($group['is_total'] ?? false) ? 'right' : 'center'];
            }
        }

        return $columns;
    }

    public static function topHeaderRow(): array
    {
        return self::headerRows()[0];
    }

    public static function subHeaderRow(): array
    {
        return self::headerRows()[1];
    }

    public static function bottomHeaderRow(): array
    {
        return self::headerRows()[2];
    }

    public static function headerRows(): array
    {
        $row1 = ['TT', 'Họ và tên'];
        $row2 = ['', ''];
        $row3 = ['', ''];

        foreach (self::groups() as $group) {
            $leafCount = self::leafCountForGroup($group);

            $row1[] = $group['label'];
            for ($index = 1; $index < $leafCount; $index++) {
                $row1[] = '';
            }

            if (isset($group['children'])) {
                foreach ($group['children'] as $child) {
                    $childLeafCount = count($child['columns']);
                    $row2[] = $child['label'];
                    for ($index = 1; $index < $childLeafCount; $index++) {
                        $row2[] = '';
                    }

                    foreach ($child['columns'] as $column) {
                        $row3[] = $column['label'];
                    }
                }
                continue;
            }

            foreach ($group['columns'] as $column) {
                $row2[] = $column['label'];
                $row3[] = '';
            }
        }

        return [$row1, $row2, $row3];
    }

    public static function dataRow(array $row): array
    {
        $values = [
            self::formatValue($row['tt'] ?? 0, 'count'),
            self::formatValue($row['lecturer_full_name'] ?? '', 'text'),
        ];

        foreach (self::leafColumns() as $index => $column) {
            if ($index < 2) {
                continue;
            }

            $values[] = self::formatValue(
                $row[$column['key']] ?? self::defaultValueForFormat($column['format']),
                $column['format']
            );
        }

        return $values;
    }

    public static function headings(): array
    {
        return self::headerRows();
    }

    public static function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 24,
            'C' => 10,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 11,
            'H' => 11,
            'I' => 13,
            'J' => 11,
            'K' => 11,
            'L' => 12,
            'M' => 12,
            'N' => 11,
            'O' => 11,
            'P' => 12,
        ];
    }

    public static function lastColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex(count(self::leafColumns()));
    }

    public static function totalColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex(count(self::leafColumns()));
    }

    public static function headerMergeRanges(int $headerRow1, int $headerRow2, int $headerRow3): array
    {
        $ranges = [
            sprintf('A%d:A%d', $headerRow1, $headerRow3),
            sprintf('B%d:B%d', $headerRow1, $headerRow3),
        ];

        $columnIndex = 3;
        foreach (self::groups() as $group) {
            $leafCount = self::leafCountForGroup($group);
            $startColumn = Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = Coordinate::stringFromColumnIndex($columnIndex + $leafCount - 1);

            if ($leafCount === 1) {
                $ranges[] = sprintf('%s%d:%s%d', $startColumn, $headerRow1, $endColumn, $headerRow3);
                $columnIndex += $leafCount;
                continue;
            }

            $ranges[] = sprintf('%s%d:%s%d', $startColumn, $headerRow1, $endColumn, $headerRow1);

            if (isset($group['children'])) {
                $childColumnIndex = $columnIndex;
                foreach ($group['children'] as $child) {
                    $childLeafCount = count($child['columns']);
                    $childStartColumn = Coordinate::stringFromColumnIndex($childColumnIndex);
                    $childEndColumn = Coordinate::stringFromColumnIndex($childColumnIndex + $childLeafCount - 1);
                    $ranges[] = sprintf('%s%d:%s%d', $childStartColumn, $headerRow2, $childEndColumn, $headerRow2);
                    $childColumnIndex += $childLeafCount;
                }
            } else {
                for ($offset = 0; $offset < $leafCount; $offset++) {
                    $leafColumn = Coordinate::stringFromColumnIndex($columnIndex + $offset);
                    $ranges[] = sprintf('%s%d:%s%d', $leafColumn, $headerRow2, $leafColumn, $headerRow3);
                }
            }

            $columnIndex += $leafCount;
        }

        return $ranges;
    }

    public static function headerStyleRanges(int $headerRow1, int $headerRow2, int $headerRow3): array
    {
        $ranges = [
            [
                'range' => sprintf('A%d:A%d', $headerRow1, $headerRow3),
                'fill' => 'D5DEE9',
                'font_size' => 10,
                'is_total' => false,
            ],
            [
                'range' => sprintf('B%d:B%d', $headerRow1, $headerRow3),
                'fill' => 'D5DEE9',
                'font_size' => 10,
                'is_total' => false,
            ],
        ];

        $columnIndex = 3;
        foreach (self::groups() as $group) {
            $leafCount = self::leafCountForGroup($group);
            $startColumn = Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = Coordinate::stringFromColumnIndex($columnIndex + $leafCount - 1);
            $isTotal = (bool) ($group['is_total'] ?? false);

            if ($leafCount === 1) {
                $ranges[] = [
                    'range' => sprintf('%s%d:%s%d', $startColumn, $headerRow1, $endColumn, $headerRow3),
                    'fill' => $group['header_fill'],
                    'font_size' => 10,
                    'is_total' => $isTotal,
                ];
                $columnIndex += $leafCount;
                continue;
            }

            $ranges[] = [
                'range' => sprintf('%s%d:%s%d', $startColumn, $headerRow1, $endColumn, $headerRow1),
                'fill' => $group['header_fill'],
                'font_size' => 10,
                'is_total' => $isTotal,
            ];

            if (isset($group['children'])) {
                $childColumnIndex = $columnIndex;
                foreach ($group['children'] as $child) {
                    $childLeafCount = count($child['columns']);
                    $childStartColumn = Coordinate::stringFromColumnIndex($childColumnIndex);
                    $childEndColumn = Coordinate::stringFromColumnIndex($childColumnIndex + $childLeafCount - 1);

                    $ranges[] = [
                        'range' => sprintf('%s%d:%s%d', $childStartColumn, $headerRow2, $childEndColumn, $headerRow2),
                        'fill' => $group['subheader_fill'],
                        'font_size' => 9,
                        'is_total' => false,
                    ];

                    for ($offset = 0; $offset < $childLeafCount; $offset++) {
                        $leafColumn = Coordinate::stringFromColumnIndex($childColumnIndex + $offset);
                        $ranges[] = [
                            'range' => sprintf('%s%d:%s%d', $leafColumn, $headerRow3, $leafColumn, $headerRow3),
                            'fill' => $group['subheader_fill'],
                            'font_size' => 9,
                            'is_total' => false,
                        ];
                    }

                    $childColumnIndex += $childLeafCount;
                }
            } else {
                for ($offset = 0; $offset < $leafCount; $offset++) {
                    $leafColumn = Coordinate::stringFromColumnIndex($columnIndex + $offset);
                    $ranges[] = [
                        'range' => sprintf('%s%d:%s%d', $leafColumn, $headerRow2, $leafColumn, $headerRow3),
                        'fill' => $group['subheader_fill'],
                        'font_size' => 9,
                        'is_total' => $isTotal,
                    ];
                }
            }

            $columnIndex += $leafCount;
        }

        return $ranges;
    }

    public static function formatValue(mixed $value, string $format): string
    {
        return match ($format) {
            'text' => trim((string) $value),
            'count' => self::formatCount((float) $value),
            'hours' => self::formatHours((float) $value),
            'count_hours' => self::formatCountHours($value),
            default => trim((string) $value),
        };
    }

    public static function metaLine(string $label, ?string $value, string $fallback): string
    {
        return $label . ': ' . self::metaValue($value, $fallback);
    }

    public static function metaValue(?string $value, string $fallback): string
    {
        $candidate = trim((string) $value);

        if ($candidate === '' || self::looksCorrupted($candidate)) {
            return $fallback;
        }

        return $candidate;
    }

    private static function leafCountForGroup(array $group): int
    {
        if (isset($group['children'])) {
            return array_sum(array_map(
                static fn (array $child): int => count($child['columns']),
                $group['children']
            ));
        }

        return count($group['columns']);
    }

    private static function defaultValueForFormat(string $format): mixed
    {
        return $format === 'count_hours'
            ? ['count' => 0, 'hours' => 0]
            : 0;
    }

    private static function formatCountHours(mixed $value): string
    {
        $count = is_array($value) ? (float) ($value['count'] ?? 0) : 0;
        $hours = is_array($value) ? (float) ($value['hours'] ?? 0) : 0;

        return self::formatCount($count) . ' / ' . self::formatHours($hours);
    }

    private static function formatCount(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private static function formatHours(float $value): string
    {
        $numeric = round($value, 2);
        if (abs($numeric - round($numeric)) < 0.00001) {
            return number_format($numeric, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($numeric, 2, ',', '.'), '0'), ',');
    }

    private static function looksCorrupted(string $value): bool
    {
        return preg_match('/\x{00C3}|\x{00C4}|\x{00C6}|\x{00E2}|\x{FFFD}/u', $value) === 1;
    }
}
