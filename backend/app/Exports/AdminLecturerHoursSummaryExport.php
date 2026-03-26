<?php

namespace App\Exports;

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
    private const LAST_COLUMN = 'T';
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
            ['BÁO CÁO TỔNG HỢP GIỜ NGHIÊN CỨU KHOA HỌC'],
            ['Năm học: ' . ($this->meta['academic_year_code'] ?? 'Tất cả')],
            ['Phạm vi: ' . ($this->meta['scope_label'] ?? 'Toàn trường')],
            [],
            [
                'TT',
                'Họ và tên',
                'Đề tài cấp Nhà nước / Bộ / Tỉnh / Thành phố',
                '',
                '',
                'Đề tài cấp Trường',
                '',
                '',
                'Bài báo / Báo cáo khoa học',
                '',
                'Biên soạn giáo trình, tài liệu tham khảo',
                '',
                '',
                'Sách chuyên khảo, sách hướng dẫn, từ điển',
                '',
                '',
                'Hội nghị, hội thảo, seminar',
                '',
                '',
                'Tổng số giờ',
            ],
            [
                '',
                '',
                'Chủ nhiệm',
                'Tham gia',
                'Số đề tài',
                'Chủ nhiệm',
                'Tham gia',
                'Số đề tài',
                'Số tác phẩm',
                'Số giờ quy đổi',
                'Chủ biên',
                'Tham gia',
                'Số giờ quy đổi',
                'Chủ biên',
                'Tham gia',
                'Số giờ quy đổi',
                'Báo cáo',
                'Tham dự',
                'Số giờ quy đổi',
                'Tổng số giờ',
            ],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                (int) ($row['tt'] ?? 0),
                (string) ($row['lecturer_full_name'] ?? ''),
                (float) ($row['national_projects_principal_hours'] ?? 0),
                (float) ($row['national_projects_participant_hours'] ?? 0),
                (int) ($row['national_projects_count'] ?? 0),
                (float) ($row['school_projects_principal_hours'] ?? 0),
                (float) ($row['school_projects_participant_hours'] ?? 0),
                (int) ($row['school_projects_count'] ?? 0),
                (int) ($row['papers_count'] ?? 0),
                (float) ($row['papers_hours_total'] ?? 0),
                (float) ($row['textbooks_principal_hours'] ?? 0),
                (float) ($row['textbooks_participant_hours'] ?? 0),
                (float) ($row['textbooks_hours_total'] ?? 0),
                (float) ($row['scholarly_books_principal_hours'] ?? 0),
                (float) ($row['scholarly_books_participant_hours'] ?? 0),
                (float) ($row['scholarly_books_hours_total'] ?? 0),
                (int) ($row['conferences_report_count'] ?? 0),
                (int) ($row['conferences_attend_count'] ?? 0),
                (float) ($row['conferences_hours_total'] ?? 0),
                (float) ($row['hours_total'] ?? 0),
            ];
        }

        if ($this->rows === []) {
            $data[] = ['Không có dữ liệu cho phạm vi báo cáo đã chọn.'];
        }

        return $data;
    }

    public function columnWidths(): array
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $hasRows = count($this->rows) > 0;

                $sheet->mergeCells('A1:' . self::LAST_COLUMN . '1');
                $sheet->mergeCells('A2:' . self::LAST_COLUMN . '2');
                $sheet->mergeCells('A3:' . self::LAST_COLUMN . '3');

                $sheet->mergeCells('A5:A6');
                $sheet->mergeCells('B5:B6');
                $sheet->mergeCells('C5:E5');
                $sheet->mergeCells('F5:H5');
                $sheet->mergeCells('I5:J5');
                $sheet->mergeCells('K5:M5');
                $sheet->mergeCells('N5:P5');
                $sheet->mergeCells('Q5:S5');
                $sheet->mergeCells('T5:T6');

                if (! $hasRows) {
                    $sheet->mergeCells('A7:' . self::LAST_COLUMN . '7');
                }

                $fullRange = 'A1:' . self::LAST_COLUMN . $highestRow;
                $headerRange = 'A5:' . self::LAST_COLUMN . self::HEADER_ROW_2;
                $tableRange = 'A5:' . self::LAST_COLUMN . max($highestRow, self::DATA_START_ROW);
                $numericRange = 'C' . self::DATA_START_ROW . ':' . self::LAST_COLUMN . $highestRow;

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
                $sheet->getStyle('A' . self::DATA_START_ROW . ':A' . $highestRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . self::DATA_START_ROW . ':B' . $highestRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($hasRows) {
                    $sheet->getStyle($numericRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
