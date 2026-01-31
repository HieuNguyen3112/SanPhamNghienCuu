<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminResearchReportExport implements FromArray, WithColumnWidths, WithStyles
{
    private array $rows;
    private array $kpis;
    private array $charts;
    private array $filters;
    private int $kpiHeaderRow = 0;
    private int $departmentHeaderRow = 0;
    private int $distributionHeaderRow = 0;
    private int $yearHeaderRow = 0;
    private int $tableHeaderRow = 0;

    public function __construct(array $rows, array $kpis, array $charts, array $filters)
    {
        $this->rows = $rows;
        $this->kpis = $kpis;
        $this->charts = $charts;
        $this->filters = $filters;
    }

    public function array(): array
    {
        $allLabel = 'Tất cả';

        $data = [
            ['Báo cáo công trình nghiên cứu khoa học'],
            ['Năm', $this->filters['year'] ?? $allLabel],
            ['Khoa / Đơn vị', $this->filters['department'] ?? $allLabel],
            ['Loại công trình', $this->filters['research_type'] ?? $allLabel],
            ['Giảng viên', $this->filters['lecturer'] ?? $allLabel],
            ['Từ khóa', $this->filters['keyword'] ?? $allLabel],
            [],
        ];

        $this->kpiHeaderRow = count($data) + 1;
        $data[] = ['Chỉ số', 'Giá trị'];
        $data[] = ['Tổng công trình', (int) ($this->kpis['total_count'] ?? 0)];
        $data[] = ['ISI', (int) ($this->kpis['isi_count'] ?? 0)];
        $data[] = ['Scopus', (int) ($this->kpis['scopus_count'] ?? 0)];
        $data[] = ['Hội nghị / Hội thảo', (int) ($this->kpis['conference_count'] ?? 0)];
        $data[] = ['Đề tài / Dự án', (int) ($this->kpis['project_count'] ?? 0)];
        $data[] = ['Sách / Giáo trình', (int) ($this->kpis['book_count'] ?? 0)];
        $data[] = [];

        $data[] = ['Công trình theo khoa / đơn vị'];
        $this->departmentHeaderRow = count($data) + 1;
        $data[] = ['Khoa / Đơn vị', 'ISI', 'Scopus', 'Hội nghị / Hội thảo', 'Đề tài / Dự án', 'Sách / Giáo trình'];

        $deptLabels = $this->charts['by_department_stacked']['labels'] ?? [];
        $deptIsi = $this->charts['by_department_stacked']['isi'] ?? [];
        $deptScopus = $this->charts['by_department_stacked']['scopus'] ?? [];
        $deptConference = $this->charts['by_department_stacked']['conference'] ?? [];
        $deptProject = $this->charts['by_department_stacked']['project'] ?? [];
        $deptBook = $this->charts['by_department_stacked']['book'] ?? [];

        if (count($deptLabels) === 0) {
            $data[] = ['Không có dữ liệu'];
        } else {
            foreach ($deptLabels as $index => $label) {
                $data[] = [
                    $label,
                    (int) ($deptIsi[$index] ?? 0),
                    (int) ($deptScopus[$index] ?? 0),
                    (int) ($deptConference[$index] ?? 0),
                    (int) ($deptProject[$index] ?? 0),
                    (int) ($deptBook[$index] ?? 0),
                ];
            }
        }

        $data[] = [];
        $data[] = ['Phân bố theo loại công trình'];
        $this->distributionHeaderRow = count($data) + 1;
        $data[] = ['Loại công trình', 'Số lượng'];

        $distLabels = $this->charts['distribution_donut']['labels'] ?? [];
        $distValues = $this->charts['distribution_donut']['values'] ?? [];

        if (count($distLabels) === 0) {
            $data[] = ['Không có dữ liệu'];
        } else {
            foreach ($distLabels as $index => $label) {
                $data[] = [$label, (int) ($distValues[$index] ?? 0)];
            }
        }

        $data[] = [];
        $data[] = ['Công trình theo năm'];
        $this->yearHeaderRow = count($data) + 1;
        $data[] = ['Năm', 'Số lượng'];

        $yearLabels = $this->charts['by_year_line']['labels'] ?? [];
        $yearValues = $this->charts['by_year_line']['values'] ?? [];

        if (count($yearLabels) === 0) {
            $data[] = ['Không có dữ liệu'];
        } else {
            foreach ($yearLabels as $index => $label) {
                $data[] = [$label, (int) ($yearValues[$index] ?? 0)];
            }
        }

        $data[] = [];
        $data[] = ['Danh sách công trình'];
        $this->tableHeaderRow = count($data) + 1;
        $data[] = ['Tên công trình', 'Mã công trình', 'Loại', 'Nơi công bố', 'Giảng viên', 'Đơn vị', 'Năm'];

        if (count($this->rows) === 0) {
            $data[] = ['Không có dữ liệu'];
        } else {
            foreach ($this->rows as $row) {
                $data[] = [
                    $row['title'] ?? '',
                    $row['activity_code'] ?? '',
                    $row['category_label'] ?? '',
                    $row['venue_label'] ?? '',
                    $row['lecturer_names'] ?? '',
                    $row['department_name'] ?? '',
                    $row['year'] ?? '',
                ];
            }
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 36,
            'B' => 16,
            'C' => 16,
            'D' => 28,
            'E' => 28,
            'F' => 24,
            'G' => 10,
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

        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $styles = [];
        foreach (
            [
                $this->kpiHeaderRow,
                $this->departmentHeaderRow,
                $this->distributionHeaderRow,
                $this->yearHeaderRow,
                $this->tableHeaderRow,
            ] as $rowIndex
        ) {
            if ($rowIndex > 0) {
                $styles[$rowIndex] = $headerStyle;
            }
        }

        return $styles;
    }
}
