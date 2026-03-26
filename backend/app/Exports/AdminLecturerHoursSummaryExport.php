<?php

namespace App\Exports;

use App\Support\LecturerHoursSummaryReportLayout;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AdminLecturerHoursSummaryExport implements FromArray, WithColumnWidths, WithEvents
{
    private const TITLE_ROW = 1;
    private const YEAR_ROW = 2;
    private const SCOPE_ROW = 3;
    private const HEADER_ROW_1 = 5;
    private const HEADER_ROW_2 = 6;
    private const DATA_START_ROW = 7;

    private array $rows;
    private array $meta;

    public function __construct(array $rows, array $meta = [])
    {
        $this->rows = $rows;
        $this->meta = $meta;
    }

    public function array(): array
    {
        $data = [
            [LecturerHoursSummaryReportLayout::title()],
            [LecturerHoursSummaryReportLayout::metaLine('Năm học', $this->meta['academic_year_code'] ?? null, 'Tất cả')],
            [LecturerHoursSummaryReportLayout::metaLine('Phạm vi', $this->meta['scope_label'] ?? null, 'Toàn trường')],
            [],
            LecturerHoursSummaryReportLayout::topHeaderRow(),
            LecturerHoursSummaryReportLayout::subHeaderRow(),
        ];

        foreach ($this->rows as $row) {
            $data[] = LecturerHoursSummaryReportLayout::dataRow($row);
        }

        if ($this->rows === []) {
            $data[] = [LecturerHoursSummaryReportLayout::emptyStateMessage()];
        }

        return $data;
    }

    public function headings(): array
    {
        return LecturerHoursSummaryReportLayout::headings();
    }

    public function columnWidths(): array
    {
        return LecturerHoursSummaryReportLayout::columnWidths();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $lastColumn = LecturerHoursSummaryReportLayout::lastColumnLetter();
                $hasRows = count($this->rows) > 0;

                $sheet->mergeCells(sprintf('A%d:%s%d', self::TITLE_ROW, $lastColumn, self::TITLE_ROW));
                $sheet->mergeCells(sprintf('A%d:%s%d', self::YEAR_ROW, $lastColumn, self::YEAR_ROW));
                $sheet->mergeCells(sprintf('A%d:%s%d', self::SCOPE_ROW, $lastColumn, self::SCOPE_ROW));

                foreach (LecturerHoursSummaryReportLayout::headerMergeRanges(self::HEADER_ROW_1, self::HEADER_ROW_2) as $range) {
                    $sheet->mergeCells($range);
                }

                if (! $hasRows) {
                    $sheet->mergeCells(sprintf('A%d:%s%d', self::DATA_START_ROW, $lastColumn, self::DATA_START_ROW));
                }

                $fullRange = sprintf('A1:%s%d', $lastColumn, $highestRow);
                $headerRange = sprintf('A%d:%s%d', self::HEADER_ROW_1, $lastColumn, self::HEADER_ROW_2);
                $tableRange = sprintf('A%d:%s%d', self::HEADER_ROW_1, $lastColumn, max($highestRow, self::DATA_START_ROW));
                $numericRange = sprintf('C%d:%s%d', self::DATA_START_ROW, $lastColumn, $highestRow);

                $sheet->freezePane('A7');
                $sheet->getStyle($fullRange)->getFont()->setName('Times New Roman')->setSize(11);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
                $sheet->getStyle('A1:A3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAF7'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle(sprintf('A%d:A%d', self::DATA_START_ROW, $highestRow))
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle(sprintf('B%d:B%d', self::DATA_START_ROW, $highestRow))
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($hasRows) {
                    $sheet->getStyle($numericRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $sheet->getStyle(sprintf('A%d', self::DATA_START_ROW))
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getRowDimension(self::HEADER_ROW_1)->setRowHeight(32);
                $sheet->getRowDimension(self::HEADER_ROW_2)->setRowHeight(36);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()
                    ->setTop(0.35)
                    ->setRight(0.2)
                    ->setLeft(0.2)
                    ->setBottom(0.35);
            },
        ];
    }
}
