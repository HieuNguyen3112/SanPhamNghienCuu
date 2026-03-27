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
    private const LEGEND_ROW = 4;
    private const HEADER_ROW_1 = 5;
    private const HEADER_ROW_2 = 6;
    private const HEADER_ROW_3 = 7;
    private const DATA_START_ROW = 8;

    public function __construct(
        private array $rows,
        private array $meta = []
    ) {
    }

    public function array(): array
    {
        $data = [
            [LecturerHoursSummaryReportLayout::title()],
            [LecturerHoursSummaryReportLayout::metaLine('Năm học', $this->meta['academic_year_code'] ?? null, 'Tất cả')],
            [LecturerHoursSummaryReportLayout::metaLine('Phạm vi', $this->meta['scope_label'] ?? null, 'Toàn trường')],
            [LecturerHoursSummaryReportLayout::valueLegend()],
            LecturerHoursSummaryReportLayout::topHeaderRow(),
            LecturerHoursSummaryReportLayout::subHeaderRow(),
            LecturerHoursSummaryReportLayout::bottomHeaderRow(),
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
                $totalColumn = LecturerHoursSummaryReportLayout::totalColumnLetter();
                $hasRows = count($this->rows) > 0;

                foreach ([self::TITLE_ROW, self::YEAR_ROW, self::SCOPE_ROW, self::LEGEND_ROW] as $rowNumber) {
                    $sheet->mergeCells(sprintf('A%d:%s%d', $rowNumber, $lastColumn, $rowNumber));
                }

                foreach (LecturerHoursSummaryReportLayout::headerMergeRanges(self::HEADER_ROW_1, self::HEADER_ROW_2, self::HEADER_ROW_3) as $range) {
                    $sheet->mergeCells($range);
                }

                if (! $hasRows) {
                    $sheet->mergeCells(sprintf('A%d:%s%d', self::DATA_START_ROW, $lastColumn, self::DATA_START_ROW));
                }

                $fullRange = sprintf('A1:%s%d', $lastColumn, $highestRow);
                $tableRange = sprintf('A%d:%s%d', self::HEADER_ROW_1, $lastColumn, max($highestRow, self::DATA_START_ROW));
                $dataRange = sprintf('A%d:%s%d', self::DATA_START_ROW, $lastColumn, $highestRow);

                $sheet->freezePane('C8');
                $sheet->getStyle($fullRange)->getFont()->setName('Times New Roman')->setSize(10);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('A2:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '5B6570'],
                        ],
                    ],
                ]);

                foreach (LecturerHoursSummaryReportLayout::headerStyleRanges(self::HEADER_ROW_1, self::HEADER_ROW_2, self::HEADER_ROW_3) as $styleRange) {
                    $sheet->getStyle($styleRange['range'])
                        ->applyFromArray($this->headerStyle($styleRange['fill'], $styleRange['font_size']));
                }

                if ($hasRows) {
                    for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
                        if (($row - self::DATA_START_ROW) % 2 === 1) {
                            $sheet->getStyle(sprintf('A%d:%s%d', $row, $lastColumn, $row))
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('F9FBFD');
                        }
                    }
                } else {
                    $sheet->getStyle(sprintf('A%d', self::DATA_START_ROW))
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle(sprintf('A%d:A%d', self::DATA_START_ROW, $highestRow))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle(sprintf('B%d:B%d', self::DATA_START_ROW, $highestRow))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle(sprintf('B%d:B%d', self::DATA_START_ROW, $highestRow))
                    ->getFont()
                    ->setBold(true);
                $sheet->getStyle(sprintf('C%d:%s%d', self::DATA_START_ROW, $totalColumn, $highestRow))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle(sprintf('%s%d:%s%d', $totalColumn, self::HEADER_ROW_1, $totalColumn, $highestRow))
                    ->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E1D9C9'],
                        ],
                    ]);
                $sheet->getStyle(sprintf('%s%d:%s%d', $totalColumn, self::DATA_START_ROW, $totalColumn, $highestRow))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($dataRange)->getAlignment()->setWrapText(true);

                $sheet->getRowDimension(self::HEADER_ROW_1)->setRowHeight(34);
                $sheet->getRowDimension(self::HEADER_ROW_2)->setRowHeight(30);
                $sheet->getRowDimension(self::HEADER_ROW_3)->setRowHeight(34);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A3)
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

    private function headerStyle(string $fill, int $fontSize): array
    {
        return [
            'font' => ['bold' => true, 'size' => $fontSize],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fill],
            ],
        ];
    }
}
