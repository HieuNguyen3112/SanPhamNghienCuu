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

class AdminLecturerReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
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
            'Họ và tên',
            'Khoa',
            'Giới tính',
            'Trình độ',
            'Học hàm',
            'Thâm niên (năm)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['full_name'] ?? '',
            $row['faculty']['name'] ?? '',
            $this->formatGenderLabel($row['gender'] ?? null),
            $row['degree']['name'] ?? '',
            $row['academic_rank']['name'] ?? '',
            (int) ($row['seniority_years'] ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 24,
            'C' => 14,
            'D' => 18,
            'E' => 18,
            'F' => 16,
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
                $sheet->getStyle('A2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    private function formatGenderLabel(?string $gender): string
    {
        $raw = trim((string) $gender);
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($raw, 'UTF-8')
            : strtolower($raw);

        return match ($normalized) {
            'male', 'nam' => 'Nam',
            'female', 'nu', 'nữ' => 'Nữ',
            'other', 'khac', 'khác' => 'Khác',
            default => $raw !== '' ? $raw : 'Chưa rõ',
        };
    }
}
