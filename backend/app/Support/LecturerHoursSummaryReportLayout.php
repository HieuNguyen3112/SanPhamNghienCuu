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

    public static function groups(): array
    {
        return [
            [
                'label' => 'Đề tài cấp Nhà nước / Bộ / Tỉnh / Thành phố',
                'columns' => [
                    ['key' => 'national_projects_principal_hours', 'label' => 'Chủ nhiệm', 'format' => 'hours'],
                    ['key' => 'national_projects_participant_hours', 'label' => 'Tham gia', 'format' => 'hours'],
                    ['key' => 'national_projects_count', 'label' => 'Số đề tài', 'format' => 'count'],
                ],
            ],
            [
                'label' => 'Đề tài cấp Trường',
                'columns' => [
                    ['key' => 'school_projects_principal_hours', 'label' => 'Chủ nhiệm', 'format' => 'hours'],
                    ['key' => 'school_projects_participant_hours', 'label' => 'Tham gia', 'format' => 'hours'],
                    ['key' => 'school_projects_count', 'label' => 'Số đề tài', 'format' => 'count'],
                ],
            ],
            [
                'label' => 'Bài báo / Báo cáo khoa học',
                'columns' => [
                    ['key' => 'papers_count', 'label' => 'Số tác phẩm', 'format' => 'count'],
                    ['key' => 'papers_hours_total', 'label' => 'Số giờ quy đổi', 'format' => 'hours'],
                ],
            ],
            [
                'label' => 'Biên soạn giáo trình, tài liệu tham khảo',
                'columns' => [
                    ['key' => 'textbooks_principal_hours', 'label' => 'Chủ biên', 'format' => 'hours'],
                    ['key' => 'textbooks_participant_hours', 'label' => 'Tham gia', 'format' => 'hours'],
                    ['key' => 'textbooks_hours_total', 'label' => 'Số giờ quy đổi', 'format' => 'hours'],
                ],
            ],
            [
                'label' => 'Sách chuyên khảo, sách hướng dẫn, từ điển',
                'columns' => [
                    ['key' => 'scholarly_books_principal_hours', 'label' => 'Chủ biên', 'format' => 'hours'],
                    ['key' => 'scholarly_books_participant_hours', 'label' => 'Tham gia', 'format' => 'hours'],
                    ['key' => 'scholarly_books_hours_total', 'label' => 'Số giờ quy đổi', 'format' => 'hours'],
                ],
            ],
            [
                'label' => 'Hội nghị, hội thảo, seminar',
                'columns' => [
                    ['key' => 'conferences_report_count', 'label' => 'Báo cáo', 'format' => 'count'],
                    ['key' => 'conferences_attend_count', 'label' => 'Tham dự', 'format' => 'count'],
                    ['key' => 'conferences_hours_total', 'label' => 'Số giờ quy đổi', 'format' => 'hours'],
                ],
            ],
            [
                'label' => 'Tổng số giờ',
                'columns' => [
                    ['key' => 'hours_total', 'label' => 'Tổng số giờ', 'format' => 'hours'],
                ],
            ],
        ];
    }

    public static function leafColumns(): array
    {
        $columns = [
            ['key' => 'tt', 'label' => 'TT', 'format' => 'count'],
            ['key' => 'lecturer_full_name', 'label' => 'Họ và tên', 'format' => 'text'],
        ];

        foreach (self::groups() as $group) {
            foreach ($group['columns'] as $column) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    public static function topHeaderRow(): array
    {
        $row = ['TT', 'Họ và tên'];

        foreach (self::groups() as $group) {
            $row[] = $group['label'];
            $columnCount = count($group['columns']);

            for ($index = 1; $index < $columnCount; $index++) {
                $row[] = '';
            }
        }

        return $row;
    }

    public static function subHeaderRow(): array
    {
        $row = ['', ''];

        foreach (self::groups() as $group) {
            foreach ($group['columns'] as $column) {
                $row[] = $column['label'];
            }
        }

        return $row;
    }

    public static function dataRow(array $row): array
    {
        $values = [
            (int) ($row['tt'] ?? 0),
            (string) ($row['lecturer_full_name'] ?? ''),
        ];

        foreach (self::groups() as $group) {
            foreach ($group['columns'] as $column) {
                $values[] = $row[$column['key']] ?? 0;
            }
        }

        return $values;
    }

    public static function headings(): array
    {
        return [
            self::topHeaderRow(),
            self::subHeaderRow(),
        ];
    }

    public static function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 28,
            'C' => 12,
            'D' => 12,
            'E' => 11,
            'F' => 12,
            'G' => 12,
            'H' => 11,
            'I' => 11,
            'J' => 13,
            'K' => 12,
            'L' => 12,
            'M' => 13,
            'N' => 12,
            'O' => 12,
            'P' => 13,
            'Q' => 10,
            'R' => 10,
            'S' => 13,
            'T' => 13,
        ];
    }

    public static function lastColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex(count(self::leafColumns()));
    }

    public static function headerMergeRanges(int $headerRowStart, int $headerRowEnd): array
    {
        $ranges = [
            sprintf('A%d:A%d', $headerRowStart, $headerRowEnd),
            sprintf('B%d:B%d', $headerRowStart, $headerRowEnd),
        ];

        $columnIndex = 3;
        foreach (self::groups() as $group) {
            $columnCount = count($group['columns']);
            $startColumn = Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = Coordinate::stringFromColumnIndex($columnIndex + $columnCount - 1);

            if ($columnCount === 1) {
                $ranges[] = sprintf('%s%d:%s%d', $startColumn, $headerRowStart, $endColumn, $headerRowEnd);
            } else {
                $ranges[] = sprintf('%s%d:%s%d', $startColumn, $headerRowStart, $endColumn, $headerRowStart);
            }

            $columnIndex += $columnCount;
        }

        return $ranges;
    }

    public static function formatValue(mixed $value, string $format): string
    {
        if ($format === 'text') {
            return trim((string) $value);
        }

        if ($format === 'count') {
            return number_format((float) $value, 0, ',', '.');
        }

        $numeric = round((float) $value, 2);
        if (abs($numeric - round($numeric)) < 0.00001) {
            return number_format($numeric, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($numeric, 2, ',', '.'), '0'), ',');
    }

    public static function metaLine(string $label, ?string $value, string $fallback): string
    {
        return $label . ': ' . (($value !== null && trim($value) !== '') ? $value : $fallback);
    }
}
