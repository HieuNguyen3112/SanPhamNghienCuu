<?php

namespace App\Http\Controllers;

use App\Exports\AdminResearchWorksSummaryExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchWorkController extends Controller
{
    public function lecturerSummary(Request $request)
    {
        $result = $this->summaryData($request);

        return response()->json([
            'data' => $result['rows'],
            'meta' => [
                'filters' => $result['filters'],
            ],
        ], Response::HTTP_OK);
    }

    public function exportSummaryExcel(Request $request)
    {
        $result = $this->summaryData($request);
        $filename = $this->buildExportFilename('xlsx');
        return Excel::download(new AdminResearchWorksSummaryExport($result['rows']), $filename);
    }

    public function exportSummaryPdf(Request $request)
    {
        $result = $this->summaryData($request);
        $filename = $this->buildExportFilename('pdf');
        $filters = $this->buildFilterPayload($result['filters']);

        return Pdf::loadView('exports.admin_works_summary', [
            'rows' => $result['rows'],
            'filters' => $filters,
        ])->setPaper('A4', 'landscape')->download($filename);
    }

    public function approvedByLecturer(Request $request, int $lecturer)
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ]);

        $exists = DB::table('lecturers')->where('id', $lecturer)->exists();
        if (! $exists) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $academicYearId = $validated['academic_year_id'] ?? null;

        $rows = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturer) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturer);
            })
            ->where(function ($query) use ($lecturer) {
                $query->where('ra.owner_lecturer_id', '=', $lecturer)
                    ->orWhereNotNull('ram.lecturer_id');
            })
            ->where('ast.code', 'approved')
            ->when($academicYearId, function ($query, $academicYearId) {
                $query->where('ra.academic_year_id', $academicYearId);
            })
            ->distinct()
            ->orderByDesc('ra.approved_at')
            ->orderByDesc('ra.id')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.kind_id',
                'ak.name as kind_name',
                'ra.type_id',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                'ra.approved_at',
            ])
            ->get();

        return response()->json([
            'data' => $rows,
        ], Response::HTTP_OK);
    }

    public function approvedDetail(Request $request, int $activity)
    {
        $activityRow = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('ra.id', $activity)
            ->where('ast.code', 'approved')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.abstract',
                'ra.kind_id',
                'ak.name as kind_name',
                'ra.type_id',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                'ra.approved_at',
            ])
            ->first();

        if (! $activityRow) {
            return response()->json(['message' => 'approved activity not found'], Response::HTTP_NOT_FOUND);
        }

        $authors = DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activity)
            ->orderBy('ram.id')
            ->select([
                'ram.lecturer_id',
                'l.full_name as lecturer_full_name',
                'ram.member_role_id',
                'mr.name as member_role_name',
                'ram.contribution_share',
            ])
            ->get();

        $evidenceItems = DB::table('evidence_files as ef')
            ->join('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activity)
            ->orderByDesc('ef.uploaded_at')
            ->select([
                'ef.id as evidence_file_id',
                'ef.file_type_id',
                'eft.name as file_type_name',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.uploaded_at',
            ])
            ->get();

        $finalApproval = DB::table('activity_approvals as aa')
            ->join('approval_stages as st', 'aa.stage_id', '=', 'st.id')
            ->leftJoin('users as u', 'aa.decided_by_user_id', '=', 'u.id')
            ->where('aa.activity_id', $activity)
            ->where('st.code', 'manager')
            ->where('aa.status', 'approved')
            ->select([
                'st.code as stage_code',
                'aa.status',
                'aa.decided_by_user_id',
                'u.name as decided_by_user_name',
                'aa.decided_at',
                'aa.note',
            ])
            ->first();

        return response()->json([
            'data' => [
                'activity_id' => $activityRow->activity_id,
                'activity_code' => $activityRow->activity_code,
                'title' => $activityRow->title,
                'abstract' => $activityRow->abstract,
                'kind_id' => $activityRow->kind_id,
                'kind_name' => $activityRow->kind_name,
                'type_id' => $activityRow->type_id,
                'type_name' => $activityRow->type_name,
                'academic_year_id' => $activityRow->academic_year_id,
                'academic_year_code' => $activityRow->academic_year_code,
                'approved_at' => $activityRow->approved_at,
                'authors' => $authors,
                'evidence_items' => $evidenceItems,
                'final_approval' => $finalApproval,
            ],
        ], Response::HTTP_OK);
    }

    private function summaryData(Request $request): array
    {
        $validated = $request->validate([
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'lecturer_name' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'status_mode' => ['nullable', 'string', 'in:all,approved,pending,rejected,submitted'],
        ]);

        $academicYearId = $validated['academic_year_id'] ?? null;
        $facultyId = $validated['faculty_id'] ?? null;
        $departmentId = $validated['department_id'] ?? null;
        $search = trim((string) ($validated['q'] ?? $validated['lecturer_name'] ?? ''));

        $statusMode = $validated['status_mode'] ?? $request->input('status') ?? 'all';
        if ($statusMode === 'submitted') {
            $statusMode = 'pending';
        }

        $activityLecturers = $this->activityLecturerSubquery();

        $activityAgg = DB::query()
            ->fromSub($activityLecturers, 'al')
            ->join('research_activities as ra', 'al.activity_id', '=', 'ra.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->when($academicYearId, function ($query, $academicYearId) {
                $query->where('ra.academic_year_id', $academicYearId);
            })
            ->groupBy('al.lecturer_id')
            ->select([
                'al.lecturer_id as lecturer_id',
                DB::raw('COUNT(*) as total_declared_research_work_count'),
                DB::raw("SUM(CASE WHEN ast.code = 'approved' THEN 1 ELSE 0 END) as approved_research_work_count"),
                DB::raw("SUM(CASE WHEN ast.code = 'submitted' THEN 1 ELSE 0 END) as pending_research_work_count"),
                DB::raw("SUM(CASE WHEN ast.code = 'rejected' THEN 1 ELSE 0 END) as rejected_research_work_count"),
            ]);

        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->leftJoinSub($activityAgg, 'agg', 'agg.lecturer_id', '=', 'l.id')
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'd.id as department_id',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'deg.id as degree_id',
                'deg.name as degree_name',
                'ar.id as academic_rank_id',
                'ar.name as academic_rank_name',
                DB::raw('COALESCE(agg.total_declared_research_work_count, 0) as total_declared_research_work_count'),
                DB::raw('COALESCE(agg.approved_research_work_count, 0) as approved_research_work_count'),
                DB::raw('COALESCE(agg.pending_research_work_count, 0) as pending_research_work_count'),
                DB::raw('COALESCE(agg.rejected_research_work_count, 0) as rejected_research_work_count'),
            ]);

        if ($facultyId) {
            $query->where('f.id', $facultyId);
        }

        if ($departmentId) {
            $query->where('d.id', $departmentId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('l.full_name', 'like', '%' . $search . '%')
                    ->orWhere('l.code', 'like', '%' . $search . '%')
                    ->orWhere('l.email', 'like', '%' . $search . '%');
            });
        }

        $rows = $query
            ->orderBy('l.full_name')
            ->get()
            ->map(function ($row) use ($statusMode) {
                $payload = (array) $row;
                $payload['total_declared_research_work_count'] = (int) $row->total_declared_research_work_count;
                $payload['approved_research_work_count'] = (int) $row->approved_research_work_count;
                $payload['pending_research_work_count'] = (int) $row->pending_research_work_count;
                $payload['rejected_research_work_count'] = (int) $row->rejected_research_work_count;
                if ($statusMode === 'approved') {
                    $payload['total_declared_research_work_count'] = (int) $row->approved_research_work_count;
                    $payload['pending_research_work_count'] = 0;
                    $payload['rejected_research_work_count'] = 0;
                } elseif ($statusMode === 'pending') {
                    $payload['total_declared_research_work_count'] = (int) $row->pending_research_work_count;
                    $payload['approved_research_work_count'] = 0;
                    $payload['rejected_research_work_count'] = 0;
                } elseif ($statusMode === 'rejected') {
                    $payload['total_declared_research_work_count'] = (int) $row->rejected_research_work_count;
                    $payload['approved_research_work_count'] = 0;
                    $payload['pending_research_work_count'] = 0;
                }

                return $payload;
            })
            ->all();

        return [
            'rows' => $rows,
            'filters' => [
                'faculty_id' => $facultyId,
                'department_id' => $departmentId,
                'academic_year_id' => $academicYearId,
                'q' => $search,
                'status_mode' => $statusMode,
            ],
        ];
    }

    private function activityLecturerSubquery()
    {
        $members = DB::table('research_activity_members')
            ->select(['activity_id', 'lecturer_id']);

        $owners = DB::table('research_activities')
            ->whereNotNull('owner_lecturer_id')
            ->select([
                'id as activity_id',
                'owner_lecturer_id as lecturer_id',
            ]);

        return $members->union($owners);
    }

    private function buildSummaryPdf(array $rows, array $filters): string
    {
        $columns = [
            ['label' => 'Giảng viên', 'width' => 180, 'align' => 'left', 'key' => 'lecturer'],
            ['label' => 'Khoa', 'width' => 120, 'align' => 'left', 'key' => 'faculty'],
            ['label' => 'Đã duyệt', 'width' => 60, 'align' => 'right', 'key' => 'approved'],
            ['label' => 'Chờ duyệt', 'width' => 60, 'align' => 'right', 'key' => 'pending'],
            ['label' => 'Từ chối', 'width' => 60, 'align' => 'right', 'key' => 'rejected'],
            ['label' => 'Tổng', 'width' => 60, 'align' => 'right', 'key' => 'total'],
        ];

        $pages = [];
        $margin = 30;
        $pageHeight = 792;
        $headerHeight = 20;
        $rowHeight = 18;

        $page = $this->startPdfPage($filters, $columns, $margin, $pageHeight, $headerHeight);
        $content = $page['content'];
        $y = $page['y'];

        foreach ($rows as $row) {
            if ($y - $rowHeight < $margin) {
                $pages[] = $content;
                $page = $this->startPdfPage($filters, $columns, $margin, $pageHeight, $headerHeight);
                $content = $page['content'];
                $y = $page['y'];
            }

            $content .= $this->drawRowGrid($margin, $y, $rowHeight, $columns);

            $name = trim(($row['lecturer_full_name'] ?? '') . ' (' . ($row['lecturer_code'] ?? '') . ')');
            $faculty = $row['faculty_name'] ?? $row['department_name'] ?? '';
            $values = [
                'lecturer' => $name,
                'faculty' => $faculty,
                'approved' => (string) ((int) ($row['approved_research_work_count'] ?? 0)),
                'pending' => (string) ((int) ($row['pending_research_work_count'] ?? 0)),
                'rejected' => (string) ((int) ($row['rejected_research_work_count'] ?? 0)),
                'total' => (string) ((int) ($row['total_declared_research_work_count'] ?? 0)),
            ];

            $content .= $this->drawRowText($margin, $y, $rowHeight, $columns, $values);
            $y -= $rowHeight;
        }

        $pages[] = $content;

        return $this->buildPdfFromPages($pages);
    }

    private function buildFilterSummaryLine(array $filters): string
    {
        $facultyName = $filters['faculty_id']
            ? DB::table('faculties')->where('id', $filters['faculty_id'])->value('name')
            : 'ALL';
        $departmentName = $filters['department_id']
            ? DB::table('departments')->where('id', $filters['department_id'])->value('name')
            : 'ALL';
        $academicYearCode = $filters['academic_year_id']
            ? DB::table('academic_years')->where('id', $filters['academic_year_id'])->value('code')
            : 'ALL';
        $statusLabel = match ($filters['status_mode'] ?? 'all') {
            'approved' => 'APPROVED',
            'pending' => 'PENDING',
            'rejected' => 'REJECTED',
            default => 'ALL',
        };
        $keyword = $filters['q'] ?: 'ALL';

        return sprintf(
            'Filters: Faculty=%s; Department=%s; AcademicYear=%s; Status=%s; Keyword=%s',
            $facultyName ?: 'ALL',
            $departmentName ?: 'ALL',
            $academicYearCode ?: 'ALL',
            $statusLabel,
            $keyword
        );
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function buildExportFilename(string $ext): string
    {
        return 'works_summary_lecturers_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function buildFilterPayload(array $filters): array
    {
        $facultyName = $filters['faculty_id']
            ? DB::table('faculties')->where('id', $filters['faculty_id'])->value('name')
            : 'Tất cả';
        $departmentName = $filters['department_id']
            ? DB::table('departments')->where('id', $filters['department_id'])->value('name')
            : 'Tất cả';
        $academicYearCode = $filters['academic_year_id']
            ? DB::table('academic_years')->where('id', $filters['academic_year_id'])->value('code')
            : 'Tất cả';
        $statusLabel = match ($filters['status_mode'] ?? 'all') {
            'approved' => 'Đã duyệt',
            'pending' => 'Chờ duyệt',
            'rejected' => 'Từ chối',
            default => 'Tất cả',
        };

        return [
            'faculty' => $facultyName ?: 'Tất cả',
            'department' => $departmentName ?: 'Tất cả',
            'academic_year' => $academicYearCode ?: 'Tất cả',
            'status' => $statusLabel,
            'keyword' => $filters['q'] ?: 'Tất cả',
        ];
    }

    private function startPdfPage(array $filters, array $columns, int $margin, int $pageHeight, int $headerHeight): array
    {
        $y = $pageHeight - 32;
        $content = "0 0 0 RG\n0 0 0 rg\n0.5 w\n";

        $content .= $this->drawText($margin, $y, 'Quản lý công trình NCKH theo giảng viên', 'F2', 14);
        $y -= 18;
        $content .= $this->drawText($margin, $y, $this->buildFilterSummaryLine($filters), 'F1', 10);
        $y -= 16;

        $content .= $this->drawHeaderRow($margin, $y, $headerHeight, $columns);
        $y -= $headerHeight;

        return ['content' => $content, 'y' => $y];
    }

    private function drawHeaderRow(int $x, int $yTop, int $height, array $columns): string
    {
        $width = array_sum(array_column($columns, 'width'));
        $bottom = $yTop - $height;

        $content = "0.9 0.9 0.9 rg\n";
        $content .= $x . ' ' . $bottom . ' ' . $width . ' ' . $height . " re B\n";
        $content .= "0 0 0 rg\n";
        $content .= $this->drawRowGrid($x, $yTop, $height, $columns);

        $cursor = $x;
        foreach ($columns as $column) {
            $label = $column['label'] ?? '';
            $textWidth = $this->estimateTextWidth($label, 10);
            $textX = $cursor + max(4, ($column['width'] - $textWidth) / 2);
            $content .= $this->drawText($textX, $yTop - 13, $label, 'F2', 10);
            $cursor += $column['width'];
        }

        return $content;
    }

    private function drawRowGrid(int $x, int $yTop, int $height, array $columns): string
    {
        $width = array_sum(array_column($columns, 'width'));
        $bottom = $yTop - $height;
        $content = $x . ' ' . $bottom . ' ' . $width . ' ' . $height . " re S\n";

        $cursor = $x;
        foreach ($columns as $index => $column) {
            $cursor += $column['width'];
            if ($index === count($columns) - 1) {
                break;
            }
            $content .= $cursor . ' ' . $bottom . ' m ' . $cursor . ' ' . $yTop . " l S\n";
        }

        return $content;
    }

    private function drawRowText(int $x, int $yTop, int $height, array $columns, array $values): string
    {
        $cursor = $x;
        $content = '';
        foreach ($columns as $column) {
            $key = $column['key'];
            $value = (string) ($values[$key] ?? '');
            $textWidth = $this->estimateTextWidth($value, 10);
            $textY = $yTop - 13;

            if (($column['align'] ?? 'left') === 'right') {
                $textX = $cursor + $column['width'] - $textWidth - 4;
            } else {
                $textX = $cursor + 4;
            }

            $content .= $this->drawText($textX, $textY, $value, 'F1', 10);
            $cursor += $column['width'];
        }

        return $content;
    }

    private function drawText(float $x, float $y, string $text, string $font, int $size): string
    {
        return "BT /{$font} {$size} Tf {$x} {$y} Td (" . $this->escapePdfText($text) . ") Tj ET\n";
    }

    private function estimateTextWidth(string $text, int $size): float
    {
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        return $length * $size * 0.5;
    }

    private function buildPdfFromPages(array $pageStreams): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $pageIds = [];
        $nextId = 5;
        foreach ($pageStreams as $stream) {
            $contentId = $nextId++;
            $pageId = $nextId++;

            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';

            $pageIds[] = $pageId;
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $content) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $content . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }
}
