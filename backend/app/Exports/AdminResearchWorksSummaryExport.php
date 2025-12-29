<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminResearchWorksSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    private Collection $rows;

    public function __construct(array $rows)
    {
        $this->rows = collect($rows);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Giảng viên',
            'Khoa',
            'Đã duyệt',
            'Chờ duyệt',
            'Từ chối',
            'Tổng',
        ];
    }

    public function map($row): array
    {
        $name = trim(($row['lecturer_full_name'] ?? '') . ' (' . ($row['lecturer_code'] ?? '') . ')');
        $faculty = $row['faculty_name'] ?? $row['department_name'] ?? '';

        return [
            $name,
            $faculty,
            (int) ($row['approved_research_work_count'] ?? 0),
            (int) ($row['pending_research_work_count'] ?? 0),
            (int) ($row['rejected_research_work_count'] ?? 0),
            (int) ($row['total_declared_research_work_count'] ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 36,
            'B' => 26,
            'C' => 12,
            'D' => 12,
            'E' => 12,
            'F' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = 'A1:' . $highestColumn . $highestRow;

                $sheet->freezePane('A2');
                $sheet->getStyle($range)->getFont()->setName('Arial');
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
