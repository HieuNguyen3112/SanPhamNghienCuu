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
                    ['key' => 'national_projects_participant_summary', 'label' => 'Tham gia', 'format' => 'count_hours'],
                    ['key' => 'national_projects_count', 'label' => 'Số đề tài', 'format' => 'count'],
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
                    ['key' => 'school_projects_count', 'label' => 'Số đề tài', 'format' => 'count'],
                ],
            ],
            [
                'key' => 'papers',
                'label' => 'Bài báo / Báo cáo khoa học',
                'header_fill' => 'EDE7D8',
                'subheader_fill' => 'F7F3E9',
                'columns' => [
                    ['key' => 'papers_point_1_2_summary', 'label' => "Điểm 1-2\nSL / giờ", 'format' => 'count_hours'],
                    ['key' => 'papers_point_le_1_summary', 'label' => "Điểm <= 1\nSL / giờ", 'format' => 'count_hours'],
                    ['key' => 'papers_other_summary', 'label' => "Khác\nSL / giờ", 'format' => 'count_hours'],
                ],
            ],
            [
                'key' => 'textbooks',
                'label' => 'Biên soạn giáo trình, tài liệu tham khảo',
                'header_fill' => 'E8E1F0',
                'subheader_fill' => 'F3EDF8',
                'columns' => [
                    ['key' => 'textbooks_principal_summary', 'label' => 'Chủ biên', 'format' => 'count_hours'],
                    ['key' => 'textbooks_participant_summary', 'label' => 'Tham gia', 'format' => 'count_hours'],
                    ['key' => 'textbooks_hours_total', 'label' => 'Tổng giờ', 'format' => 'hours'],
                ],
            ],
            [
                'key' => 'scholarly_books',
                'label' => 'Sách chuyên khảo, sách hướng dẫn, từ điển',
                'header_fill' => 'E5EBDD',
                'subheader_fill' => 'F1F5EC',
                'columns' => [
                    ['key' => 'scholarly_books_principal_summary', 'label' => 'Chủ biên', 'format' => 'count_hours'],
                    ['key' => 'scholarly_books_participant_summary', 'label' => 'Tham gia', 'format' => 'count_hours'],
                    ['key' => 'scholarly_books_hours_total', 'label' => 'Tổng giờ', 'format' => 'hours'],
                ],
            ],
            [
                'key' => 'conferences',
                'label' => 'Hội nghị, hội thảo, seminar',
                'header_fill' => 'F4E6D7',
                'subheader_fill' => 'FBF1E8',
                'columns' => [
                    ['key' => 'conferences_report_summary', 'label' => 'Báo cáo', 'format' => 'count_hours'],
                    ['key' => 'conferences_attend_summary', 'label' => 'Tham dự', 'format' => 'count_hours'],
                    ['key' => 'conferences_hours_total', 'label' => 'Tổng giờ', 'format' => 'hours'],
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
            foreach ($group['columns'] as $column) {
                $columns[] = $column + ['align' => $group['is_total'] ?? false ? 'right' : 'center'];
            }
        }

        return $columns;
    }

    public static function topHeaderRow(): array
    {
        $row = ['TT', 'Họ và tên'];

        foreach (self::groups() as $group) {
            $row[] = $group['label'];

            for ($index = 1, $columnCount = count($group['columns']); $index < $columnCount; $index++) {
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
            self::formatValue($row['tt'] ?? 0, 'count'),
            self::formatValue($row['lecturer_full_name'] ?? '', 'text'),
        ];

        foreach (self::groups() as $group) {
            foreach ($group['columns'] as $column) {
                $values[] = self::formatValue($row[$column['key']] ?? self::defaultValueForFormat($column['format']), $column['format']);
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
            'C' => 13,
            'D' => 13,
            'E' => 10,
            'F' => 13,
            'G' => 13,
            'H' => 10,
            'I' => 13,
            'J' => 13,
            'K' => 13,
            'L' => 13,
            'M' => 13,
            'N' => 11,
            'O' => 13,
            'P' => 13,
            'Q' => 11,
            'R' => 13,
            'S' => 13,
            'T' => 11,
            'U' => 13,
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

    public static function headerGroupsByColumn(): array
    {
        $ranges = [
            [
                'key' => 'identity_tt',
                'header_fill' => 'D5DEE9',
                'subheader_fill' => 'ECF1F6',
                'start_column' => 'A',
                'end_column' => 'A',
                'column_count' => 1,
                'is_total' => false,
            ],
            [
                'key' => 'identity_name',
                'header_fill' => 'D5DEE9',
                'subheader_fill' => 'ECF1F6',
                'start_column' => 'B',
                'end_column' => 'B',
                'column_count' => 1,
                'is_total' => false,
            ],
        ];

        $columnIndex = 3;
        foreach (self::groups() as $group) {
            $columnCount = count($group['columns']);
            $ranges[] = [
                'key' => $group['key'],
                'header_fill' => $group['header_fill'],
                'subheader_fill' => $group['subheader_fill'],
                'start_column' => Coordinate::stringFromColumnIndex($columnIndex),
                'end_column' => Coordinate::stringFromColumnIndex($columnIndex + $columnCount - 1),
                'column_count' => $columnCount,
                'is_total' => (bool) ($group['is_total'] ?? false),
            ];
            $columnIndex += $columnCount;
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
