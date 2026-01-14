<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminResearchHoursReportExport implements FromArray, WithColumnWidths, WithStyles
{
    private array $rows;
    private array $kpis;
    private array $filters;
    private int $kpiHeaderRow = 6;
    private int $tableHeaderRow = 14;

    public function __construct(array $rows, array $kpis, array $filters)
    {
        $this->rows = $rows;
        $this->kpis = $kpis;
        $this->filters = $filters;
    }

    public function array(): array
    {
        $data = [
            ['Báo cáo thống kê giờ NCKH'],
            ['Khoa', $this->filters['faculty'] ?? 'Tất cả'],
            ['Năm học', $this->filters['academic_year'] ?? 'Tất cả'],
            ['Trạng thái', $this->filters['status'] ?? 'Tất cả'],
            [],
            ['Chỉ số', 'Giá trị'],
            ['Tổng giảng viên', (int) ($this->kpis['lecturer_count'] ?? 0)],
            ['Tổng giờ NCKH', (float) ($this->kpis['total_hours'] ?? 0)],
            [
                'Giờ NCKH trung bình/giảng viên',
                (float) ($this->kpis['avg_hours'] ?? 0),
            ],
            ['Giảng viên đạt chuẩn', (int) ($this->kpis['met_count'] ?? 0)],
            ['Giảng viên chưa đạt', (int) ($this->kpis['not_met_count'] ?? 0)],
            ['Tỉ lệ đạt chuẩn (%)', (float) ($this->kpis['compliance_rate'] ?? 0)],
            [],
            ['Giảng viên', 'Khoa', 'Năm học', 'Tổng giờ NCKH', 'Giờ chuẩn', 'Trạng thái'],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['lecturer_name'] ?? '',
                $row['faculty_name'] ?? '',
                $row['academic_year_code'] ?? '',
                (float) ($row['total_hours'] ?? 0),
                (float) ($row['required_hours'] ?? 0),
                ($row['status'] ?? '') === 'met' ? 'Đạt chuẩn' : 'Chưa đạt',
            ];
        }

        if (count($this->rows) === 0) {
            $data[] = ['Không có dữ liệu'];
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 28,
            'C' => 16,
            'D' => 18,
            'E' => 14,
            'F' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        return [
            $this->kpiHeaderRow => $headerStyle,
            $this->tableHeaderRow => $headerStyle,
        ];
    }
}
