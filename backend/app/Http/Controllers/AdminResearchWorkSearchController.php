<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\WorkSearchRequest;
use App\Support\StorageDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchWorkSearchController extends Controller
{
    public function lookups(Request $request)
    {
        $faculties = DB::table('faculties')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $departments = DB::table('departments')
            ->select(['id', 'faculty_id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'faculty_id' => (int) $row->faculty_id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $workTypes = DB::table('activity_kinds')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $authorRoles = DB::table('member_roles')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $statuses = DB::table('activity_statuses')
            ->select(['id', 'code', 'name'])
            ->orderBy('id')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $managementLevels = DB::table('activity_types as at')
            ->join('activity_kinds as ak', 'at.kind_id', '=', 'ak.id')
            ->where('ak.code', 'project')
            ->select(['at.id', 'at.code', 'at.name'])
            ->orderBy('at.name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->all();

        $years = $this->availableYears($request);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'faculties' => $faculties,
                'departments' => $departments,
                'work_types' => $workTypes,
                'author_roles' => $authorRoles,
                'statuses' => $statuses,
                'management_levels' => $managementLevels,
                'years' => $years,
            ],
        ], Response::HTTP_OK);
    }

    public function index(WorkSearchRequest $request)
    {
        $filters = $this->normalizeFilters($request->validated());

        $query = $this->baseQuery();
        $this->applyRoleScope($query, $request);
        $this->applyFilters($query, $filters);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $yearExpr = $this->activityYearExpression();
        $query->orderByRaw($yearExpr . ' desc')->orderByDesc('ra.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = $paginator->items();

        $rows = array_map(function ($row) {
            return $this->buildSummaryRow($row);
        }, $items);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'summary' => [
                    'total' => $paginator->total(),
                ],
                'table' => [
                    'items' => $rows,
                    'pagination' => [
                        'page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                ],
                'applied_filters' => $filters,
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activity)
    {
        $query = $this->baseQuery();
        $this->applyRoleScope($query, $request);

        $row = $query->where('ra.id', $activity)->first();
        if (! $row) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $detail = [
            'work_id' => (int) $row->activity_id,
            'title' => $row->title,
            'kind_code' => $row->kind_code,
            'kind_name' => $row->kind_name,
            'status_code' => $row->status_code,
            'status_name' => $row->status_name,
            'year' => $row->activity_year !== null ? (int) $row->activity_year : null,
            'abstract' => $row->abstract,
            'info_rows' => $this->buildDetailInfoRows($row),
            'participants' => $this->buildParticipants((int) $row->activity_id),
            'files' => $this->buildFiles($row),
        ];

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $detail,
        ], Response::HTTP_OK);
    }

    public function downloadAttachment(Request $request, int $attachment)
    {
        $file = DB::table('evidence_files as ef')
            ->where('ef.id', $attachment)
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
            ])
            ->first();

        if (! $file) {
            return response()->json(['message' => 'attachment not found'], Response::HTTP_NOT_FOUND);
        }

        $accessQuery = DB::table('research_activities as ra')->where('ra.id', $file->activity_id);
        $this->applyRoleScope($accessQuery, $request);
        if (! $accessQuery->exists()) {
            return response()->json(['message' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $disk = $file->disk ?: 'public';
        $path = $file->path;
        $filename = $file->original_name ?: ('attachment-' . $file->id);

        return StorageDownload::stream($disk, $path, $filename, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ]);
    }

    protected function normalizeFilters(array $validated): array
    {
        $filters = [
            'q' => $validated['q'] ?? null,
            'lecturer_q' => $validated['lecturer_q'] ?? null,
            'faculty_id' => $validated['faculty_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'work_type_id' => $validated['work_type_id'] ?? null,
            'author_role' => $validated['author_role'] ?? null,
            'year_from' => $validated['year_from'] ?? null,
            'year_to' => $validated['year_to'] ?? null,
            'status' => $validated['status'] ?? null,
            'management_level' => $validated['management_level'] ?? null,
            'page' => $validated['page'] ?? null,
            'per_page' => $validated['per_page'] ?? null,
        ];

        if ($filters['year_from'] !== null && $filters['year_to'] !== null) {
            if ((int) $filters['year_from'] > (int) $filters['year_to']) {
                [$filters['year_from'], $filters['year_to']] = [$filters['year_to'], $filters['year_from']];
            }
        }

        return $filters;
    }

    protected function baseQuery()
    {
        $yearExpr = $this->activityYearExpression();
        $primaryMemberSub = $this->primaryMemberSubquery();
        $departmentExpr = 'COALESCE(pl.department_id, ol.department_id)';

        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->leftJoinSub($primaryMemberSub, 'pm', function ($join) {
                $join->on('pm.activity_id', '=', 'ra.id')->where('pm.rn', '=', 1);
            })
            ->leftJoin('lecturers as pl', 'pl.id', '=', 'pm.lecturer_id')
            ->leftJoin('lecturers as ol', 'ol.id', '=', 'ra.owner_lecturer_id')
            ->leftJoin('departments as d', 'd.id', '=', DB::raw($departmentExpr))
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.abstract',
                'ra.owner_lecturer_id',
                'ak.id as kind_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'at.id as type_id',
                'at.code as type_code',
                'at.name as type_name',
                'ast.id as status_id',
                'ast.code as status_code',
                'ast.name as status_name',
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
                DB::raw($yearExpr . ' as activity_year'),
                'pl.id as primary_lecturer_id',
                'pl.code as primary_lecturer_code',
                'pl.full_name as primary_lecturer_name',
                'ol.id as owner_lecturer_id',
                'ol.code as owner_lecturer_code',
                'ol.full_name as owner_lecturer_name',
                'd.id as department_id',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.name as faculty_name',
                'pd.journal_name',
                'pd.issn',
                'pd.doi',
                'pd.article_url',
                'pd.volume',
                'pd.issue',
                'pd.page_start',
                'pd.page_end',
                'pd.year as paper_year',
                'bd.publisher',
                'bd.approval_decision_no',
                'bd.approval_decision_date',
                'bd.isbn',
                'bd.pages',
                'bd.year as book_year',
                'prd.project_code',
                'prd.decision_no',
                'prd.decision_date',
                'prd.funding',
                'prd.start_month',
                'prd.end_month',
                'cd.conference_name',
                'cd.location',
                'cd.held_on',
            ]);
    }

    protected function applyFilters($query, array $filters): void
    {
        $yearExpr = $this->activityYearExpression();

        if (! empty($filters['faculty_id'])) {
            $query->where('f.id', $filters['faculty_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('d.id', $filters['department_id']);
        }

        if (! empty($filters['work_type_id'])) {
            $query->where('ak.id', $filters['work_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('ast.code', $filters['status']);
        }

        if (! empty($filters['management_level'])) {
            $query->where('at.code', $filters['management_level']);
        }

        if (! empty($filters['author_role'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('research_activity_members as ram')
                    ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
                    ->whereColumn('ram.activity_id', 'ra.id')
                    ->where('mr.code', $filters['author_role']);
            });
        }

        if (! empty($filters['year_from']) || ! empty($filters['year_to'])) {
            $query->whereRaw($yearExpr . ' is not null');
            if (! empty($filters['year_from'])) {
                $query->whereRaw($yearExpr . ' >= ?', [$filters['year_from']]);
            }
            if (! empty($filters['year_to'])) {
                $query->whereRaw($yearExpr . ' <= ?', [$filters['year_to']]);
            }
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . $filters['q'] . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhere('ra.activity_code', 'like', $keyword)
                    ->orWhere('pd.journal_name', 'like', $keyword)
                    ->orWhere('pd.doi', 'like', $keyword)
                    ->orWhere('pd.issn', 'like', $keyword)
                    ->orWhere('bd.publisher', 'like', $keyword)
                    ->orWhere('bd.isbn', 'like', $keyword)
                    ->orWhere('prd.project_code', 'like', $keyword)
                    ->orWhere('prd.decision_no', 'like', $keyword)
                    ->orWhere('cd.conference_name', 'like', $keyword)
                    ->orWhere('cd.location', 'like', $keyword);
            });
        }

        if (! empty($filters['lecturer_q'])) {
            $keyword = '%' . $filters['lecturer_q'] . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('pl.full_name', 'like', $keyword)
                    ->orWhere('pl.code', 'like', $keyword)
                    ->orWhere('ol.full_name', 'like', $keyword)
                    ->orWhere('ol.code', 'like', $keyword)
                    ->orWhereExists(function ($inner) use ($keyword) {
                        $inner->select(DB::raw(1))
                            ->from('research_activity_members as ram')
                            ->join('lecturers as lm', 'ram.lecturer_id', '=', 'lm.id')
                            ->whereColumn('ram.activity_id', 'ra.id')
                            ->where(function ($nested) use ($keyword) {
                                $nested->where('lm.full_name', 'like', $keyword)
                                    ->orWhere('lm.code', 'like', $keyword);
                            });
                    });
            });
        }
    }

    protected function applyRoleScope($query, Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('ADMIN') || $user->hasRole('QL')) {
            return;
        }

        $lecturer = $user->lecturer;
        if (! $lecturer) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('DL')) {
            $departmentId = $lecturer->department_id;
            if (! $departmentId) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where(function ($scope) use ($departmentId) {
                $scope->whereExists(function ($sub) use ($departmentId) {
                    $sub->select(DB::raw(1))
                        ->from('research_activity_members as ram')
                        ->join('lecturers as lm', 'ram.lecturer_id', '=', 'lm.id')
                        ->whereColumn('ram.activity_id', 'ra.id')
                        ->where('lm.department_id', $departmentId);
                })->orWhereExists(function ($sub) use ($departmentId) {
                    $sub->select(DB::raw(1))
                        ->from('lecturers as lo')
                        ->whereColumn('lo.id', 'ra.owner_lecturer_id')
                        ->where('lo.department_id', $departmentId);
                });
            });

            return;
        }

        $lecturerId = $lecturer->id;
        $query->where(function ($sub) use ($lecturerId) {
            $sub->where('ra.owner_lecturer_id', $lecturerId)
                ->orWhereExists(function ($inner) use ($lecturerId) {
                    $inner->select(DB::raw(1))
                        ->from('research_activity_members as ram')
                        ->whereColumn('ram.activity_id', 'ra.id')
                        ->where('ram.lecturer_id', $lecturerId);
                });
        });
    }

    protected function buildSummaryRow(object $row): array
    {
        $mainLecturerId = $row->primary_lecturer_id ?: $row->owner_lecturer_id;
        $mainLecturerName = $row->primary_lecturer_name ?: $row->owner_lecturer_name;
        $mainLecturerCode = $row->primary_lecturer_code ?: $row->owner_lecturer_code;

        return [
            'work_id' => (int) $row->activity_id,
            'title' => $row->title,
            'subtitle' => $this->buildSubtitle($row),
            'kind_code' => $row->kind_code,
            'kind_name' => $row->kind_name,
            'status_code' => $row->status_code,
            'status_name' => $row->status_name,
            'main_lecturer_id' => $mainLecturerId ? (int) $mainLecturerId : null,
            'main_lecturer_code' => $mainLecturerCode,
            'main_lecturer_name' => $mainLecturerName,
            'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
            'faculty_name' => $row->faculty_name,
            'department_id' => $row->department_id ? (int) $row->department_id : null,
            'department_name' => $row->department_name,
            'year' => $row->activity_year !== null ? (int) $row->activity_year : null,
        ];
    }

    protected function buildSubtitle(object $row): string
    {
        $parts = [];

        switch ($row->kind_code) {
            case 'paper':
                if ($row->journal_name) {
                    $parts[] = $row->journal_name;
                }
                if ($row->doi) {
                    $parts[] = 'DOI: ' . $row->doi;
                } elseif ($row->issn) {
                    $parts[] = 'ISSN: ' . $row->issn;
                }
                break;
            case 'book':
                if ($row->publisher) {
                    $parts[] = $row->publisher;
                }
                if ($row->isbn) {
                    $parts[] = 'ISBN: ' . $row->isbn;
                }
                break;
            case 'project':
                if ($row->project_code) {
                    $parts[] = 'Mã: ' . $row->project_code;
                } elseif ($row->decision_no) {
                    $parts[] = 'QĐ: ' . $row->decision_no;
                }
                if ($row->type_name) {
                    $parts[] = $row->type_name;
                }
                break;
            case 'conference':
                if ($row->conference_name) {
                    $parts[] = $row->conference_name;
                }
                if ($row->location) {
                    $parts[] = $row->location;
                }
                break;
            default:
                if ($row->activity_code) {
                    $parts[] = $row->activity_code;
                }
                break;
        }

        return implode(' • ', array_filter($parts));
    }

    protected function buildDetailInfoRows(object $row): array
    {
        $rows = [];

        $this->addInfoRow($rows, 'Mã công trình', $row->activity_code);
        $this->addInfoRow($rows, 'Năm học', $row->academic_year_code);

        switch ($row->kind_code) {
            case 'paper':
                $this->addInfoRow($rows, 'Tạp chí', $row->journal_name);
                $this->addInfoRow($rows, 'ISSN', $row->issn);
                $this->addInfoRow($rows, 'DOI', $row->doi);
                $this->addInfoRow($rows, 'Tập', $row->volume);
                $this->addInfoRow($rows, 'Số', $row->issue);
                $this->addInfoRow($rows, 'Trang', $this->formatPageRange($row->page_start, $row->page_end));
                break;
            case 'book':
                $this->addInfoRow($rows, 'Nhà xuất bản', $row->publisher);
                $this->addInfoRow($rows, 'ISBN', $row->isbn);
                $this->addInfoRow($rows, 'Số trang', $row->pages ? (string) $row->pages : null);
                $this->addInfoRow($rows, 'Số quyết định', $row->approval_decision_no);
                $this->addInfoRow($rows, 'Ngày quyết định', $row->approval_decision_date);
                break;
            case 'project':
                $this->addInfoRow($rows, 'Mã đề tài', $row->project_code);
                $this->addInfoRow($rows, 'Cấp quản lý', $row->type_name);
                $this->addInfoRow($rows, 'Số quyết định', $row->decision_no);
                $this->addInfoRow($rows, 'Ngày quyết định', $row->decision_date);
                $this->addInfoRow($rows, 'Kinh phí', $row->funding !== null ? (string) $row->funding : null);
                $this->addInfoRow($rows, 'Thời gian', $this->formatDateRange($row->start_month, $row->end_month));
                break;
            case 'conference':
                $this->addInfoRow($rows, 'Hội nghị/Hội thảo', $row->conference_name);
                $this->addInfoRow($rows, 'Địa điểm', $row->location);
                $this->addInfoRow($rows, 'Thời gian', $row->held_on);
                break;
        }

        return $rows;
    }

    protected function addInfoRow(array &$rows, string $label, ?string $value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $rows[] = [
            'label' => $label,
            'value' => $value,
        ];
    }

    protected function formatPageRange(?int $start, ?int $end): ?string
    {
        if ($start && $end) {
            return $start . ' - ' . $end;
        }
        if ($start) {
            return (string) $start;
        }
        if ($end) {
            return (string) $end;
        }
        return null;
    }

    protected function formatDateRange(?string $start, ?string $end): ?string
    {
        $start = $start ? trim($start) : '';
        $end = $end ? trim($end) : '';

        if ($start && $end) {
            return $start . ' - ' . $end;
        }
        if ($start) {
            return $start;
        }
        if ($end) {
            return $end;
        }
        return null;
    }

    protected function buildParticipants(int $activityId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('ram.activity_id', $activityId)
            ->orderBy('ram.id')
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_name',
                'mr.code as role_code',
                'mr.name as role_name',
                'd.name as department_name',
                'f.name as faculty_name',
            ])
            ->get()
            ->map(function ($row) {
                $unit = $row->faculty_name ?: $row->department_name;
                return [
                    'lecturer_id' => (int) $row->lecturer_id,
                    'lecturer_code' => $row->lecturer_code,
                    'lecturer_name' => $row->lecturer_name,
                    'unit_name' => $unit,
                    'role_key' => $row->role_code,
                    'role_label' => $row->role_name,
                ];
            })
            ->all();
    }

    protected function buildFiles(object $row): array
    {
        $items = DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $row->activity_id)
            ->orderByDesc('ef.uploaded_at')
            ->select([
                'ef.id',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'eft.name as file_type_name',
            ])
            ->get();

        $files = [];
        foreach ($items as $item) {
            $files[] = [
                'file_id' => (string) $item->id,
                'kind' => 'file',
                'label' => $item->file_type_name ?: ($item->original_name ?: 'Tệp minh chứng'),
                'url' => route('admin.works.attachments.download', ['attachment' => $item->id], false),
                'file_name' => $item->original_name,
                'mime_type' => $item->mime_type,
                'size_bytes' => $item->size_bytes !== null ? (int) $item->size_bytes : null,
            ];
        }

        if (! empty($row->article_url)) {
            $files[] = [
                'file_id' => 'link-' . $row->activity_id,
                'kind' => 'link',
                'label' => 'Trang bài báo',
                'url' => $row->article_url,
                'file_name' => null,
                'mime_type' => null,
                'size_bytes' => null,
            ];
        }

        return $files;
    }

    protected function availableYears(Request $request): array
    {
        $yearExpr = $this->activityYearExpression();

        $query = DB::table('research_activities as ra')
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->selectRaw($yearExpr . ' as activity_year')
            ->whereRaw($yearExpr . ' is not null')
            ->distinct()
            ->orderByDesc('activity_year');

        $this->applyRoleScope($query, $request);

        return $query->pluck('activity_year')
            ->map(fn($year) => (int) $year)
            ->values()
            ->all();
    }

    protected function activityYearExpression(): string
    {
        return 'COALESCE(pd.year, bd.year, YEAR(prd.start_month), YEAR(cd.held_on), YEAR(ra.start_date), YEAR(ra.created_at))';
    }

    protected function primaryMemberSubquery()
    {
        $roleOrder = "CASE
            WHEN mr.code = 'principal' THEN 1
            WHEN mr.code = 'corresponding_author' THEN 2
            WHEN mr.code = 'chief_editor' THEN 3
            WHEN mr.code = 'secretary' THEN 4
            WHEN mr.code = 'member' THEN 5
            WHEN mr.code = 'coauthor' THEN 6
            ELSE 99
        END";

        return DB::table('research_activity_members as ram')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->select([
                'ram.activity_id',
                'ram.lecturer_id',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY ram.activity_id ORDER BY ' . $roleOrder . ', ram.id) as rn'),
            ]);
    }
}
