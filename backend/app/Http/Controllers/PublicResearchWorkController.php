<?php

namespace App\Http\Controllers;

use App\Services\Evidence\ResearchEvidenceStorageService;
use App\Support\StorageDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PublicResearchWorkController extends Controller
{
    private ResearchEvidenceStorageService $evidenceStorageService;

    private const PUBLIC_EVIDENCE_TYPE_CODES_BY_KIND = [
        'paper' => [
            'paper_link_pdf',
            'paper_first_page',
            'paper_doi_or_article_link',
            'paper_link_doi',
            'paper_link_journal_page',
            'paper_link_indexing',
            'paper_journal_publication_info',
            'paper_acceptance_letter',
        ],
        'project' => [
            'project_link_summary_report',
            'project_final_or_summary_report',
            'project_link_output_product',
            'project_acceptance_minutes_or_recognition_decision',
            'project_link_acceptance_evidence',
            'acceptance_decision',
            'project_link_overview_page',
        ],
        'book' => [
            'book_link_pdf',
            'book_link_preview',
            'book_cover_or_publication_info_isbn',
            'book_link_publisher',
            'book_link_digital_library',
        ],
        'conference' => [
            'conference_link_paper',
            'conference_paper_or_slides',
            'conference_link_proceedings',
            'conference_proceedings_page',
            'conference_link_slide_video',
            'conference_participation_certificate',
            'conference_link_program',
            'conference_link_website',
        ],
    ];

    public function __construct(ResearchEvidenceStorageService $evidenceStorageService)
    {
        $this->evidenceStorageService = $evidenceStorageService;
    }

    /**
     * GET /api/public/research-works/lookups
     * Trả về: faculties (KHOA) + academic_years
     */
    public function lookups(Request $request)
    {
        // ✅ Ưu tiên lấy KHOA từ bảng faculties (nếu có)
        if (Schema::hasTable('faculties')) {
            $faculties = DB::table('faculties')
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        } else {
            // ✅ Fallback: nếu không có faculties, cố gắng suy ra KHOA từ departments
            $dept = DB::table('departments')->select(['id', 'name']);

            if (Schema::hasColumn('departments', 'type')) {
                $faculties = $dept
                    ->whereIn(DB::raw('LOWER(type)'), ['faculty'])
                    ->orderBy('name')
                    ->get();
            } elseif (Schema::hasColumn('departments', 'is_faculty')) {
                $faculties = $dept
                    ->where('is_faculty', 1)
                    ->orderBy('name')
                    ->get();
            } elseif (Schema::hasColumn('departments', 'parent_id')) {
                // nếu có tree, lấy top-level
                $faculties = $dept
                    ->whereNull('parent_id')
                    ->orderBy('name')
                    ->get();
            } else {
                // cuối cùng: trả hết departments (đỡ bị thiếu)
                $faculties = $dept->orderBy('name')->get();
            }
        }

        $academicYears = DB::table('academic_years')
            ->select(['id', 'code'])
            ->orderByDesc('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                // ⚠️ giữ key "faculties" để frontend không cần sửa
                'faculties' => $faculties,
                'academic_years' => $academicYears,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * GET /api/public/research-works/{activityId}/evidence-files/{evidenceId}/preview
     */
    public function previewEvidence(Request $request, int $activityId, int $evidenceId)
    {
        $row = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ra.id', '=', 'ef.activity_id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ra.id', $activityId)
            ->where('ef.id', $evidenceId)
            ->where('ast.code', 'approved')
            ->select([
                'ef.disk',
                'ef.path',
                'ef.mime_type',
                'ef.original_name',
                'ef.id',
                'eft.code as file_type_code',
                'ak.code as kind_code',
            ])
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Không tìm thấy minh chứng công khai.'], Response::HTTP_NOT_FOUND);
        }

        $allowedCodes = self::PUBLIC_EVIDENCE_TYPE_CODES_BY_KIND[(string) $row->kind_code] ?? [];
        if ($allowedCodes === [] || ! in_array((string) ($row->file_type_code ?? ''), $allowedCodes, true)) {
            return response()->json(['message' => 'Minh chứng này không được phép công khai.'], Response::HTTP_FORBIDDEN);
        }

        $disk = trim((string) $row->disk);
        $path = trim((string) $row->path);
        $filename = trim((string) ($row->original_name ?? ''));
        if ($filename === '') {
            $filename = 'minh-chung.pdf';
        }

        if ($this->evidenceStorageService->isRcloneDisk($disk)) {
            try {
                return $this->evidenceStorageService->streamPreview(
                    $request,
                    $disk,
                    $path,
                    $filename,
                    trim((string) ($row->mime_type ?? '')) ?: 'application/pdf'
                );
            } catch (RuntimeException $exception) {
                return response()->json([
                    'message' => 'Không thể xem trước tệp minh chứng. Vui lòng thử lại.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return StorageDownload::stream($disk, $path, $filename, [
            'Content-Type' => trim((string) ($row->mime_type ?? '')) ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    /**
     * GET /api/public/research-works/{activityId}/evidence-files/{evidenceId}/download
     */
    public function downloadEvidence(Request $request, int $activityId, int $evidenceId)
    {
        $row = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ra.id', '=', 'ef.activity_id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ra.id', $activityId)
            ->where('ef.id', $evidenceId)
            ->where('ast.code', 'approved')
            ->select([
                'ef.disk',
                'ef.path',
                'ef.mime_type',
                'ef.original_name',
                'eft.code as file_type_code',
                'ak.code as kind_code',
            ])
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Không tìm thấy minh chứng công khai.'], Response::HTTP_NOT_FOUND);
        }

        $allowedCodes = self::PUBLIC_EVIDENCE_TYPE_CODES_BY_KIND[(string) $row->kind_code] ?? [];
        if ($allowedCodes === [] || ! in_array((string) ($row->file_type_code ?? ''), $allowedCodes, true)) {
            return response()->json(['message' => 'Minh chứng này không được phép công khai.'], Response::HTTP_FORBIDDEN);
        }

        $disk = trim((string) $row->disk);
        $path = trim((string) $row->path);
        $filename = trim((string) ($row->original_name ?? ''));
        if ($filename === '') {
            $filename = 'minh-chung.pdf';
        }

        return StorageDownload::stream($disk, $path, $filename, [
            'Content-Type' => trim((string) ($row->mime_type ?? '')) ?: 'application/pdf',
        ]);
    }

    /**
     * GET /api/public/research-works
     * Public chỉ show approved nên không cần filter "status"
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'lecturer_query' => ['nullable', 'string', 'max:255'],
            'faculty_id' => ['nullable', 'integer'], // ✅ đây là KHOA id (faculties.id nếu có)
            'work_type' => ['nullable', 'in:ARTICLE,BOOK,PROJECT,CONFERENCE,OTHER'],
            'academic_year_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:5', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $lecturerQuery = trim((string)($validated['lecturer_query'] ?? ''));
        $facultyId = $validated['faculty_id'] ?? null;
        $workType = $validated['work_type'] ?? null;
        $academicYearId = $validated['academic_year_id'] ?? null;
        $page = max(1, (int)($validated['page'] ?? 1));
        $pageSize = max(5, min(50, (int)($validated['page_size'] ?? 10)));

        $workTypeToKind = [
            'ARTICLE' => 'paper',
            'BOOK' => 'book',
            'PROJECT' => 'project',
            'CONFERENCE' => 'conference',
        ];

        $query = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ast.code', 'approved');

        // ✅ Nếu có faculties + departments.faculty_id -> join lên KHOA thật
        $hasFacultyJoin = Schema::hasTable('faculties') && Schema::hasColumn('departments', 'faculty_id');
        if ($hasFacultyJoin) {
            $query->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id');
        }

        $keyword = trim((string)($validated['q'] ?? ''));

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('ra.title', 'like', $like)
                    ->orWhere('ra.activity_code', 'like', $like);
            });
        }

        // ✅ Select (faculty_name đúng là KHOA)
        $select = [
            'ra.id',
            'ra.title',
            'ra.abstract',
            'ak.code as kind_code',
            'ay.id as academic_year_id',
            'ay.code as academic_year_code',
            'l.id as lecturer_id',
            'l.code as lecturer_code',
            'l.full_name as lecturer_name',
            'ra.approved_at',
        ];

        if ($hasFacultyJoin) {
            $select[] = 'f.id as faculty_id';
            $select[] = 'f.name as faculty_name';
            // (tuỳ chọn) nếu sau này bạn muốn show bộ môn/đơn vị:
            // $select[] = 'd.id as unit_id';
            // $select[] = 'd.name as unit_name';
        } else {
            // fallback: coi department là khoa
            $select[] = 'd.id as faculty_id';
            $select[] = 'd.name as faculty_name';
        }

        $query->select($select);

        // filter work_type (tab public)
        if ($workType && isset($workTypeToKind[$workType])) {
            $query->where('ak.code', $workTypeToKind[$workType]);
        } elseif ($workType === 'OTHER') {
            $query->whereNotIn('ak.code', ['paper', 'book', 'project', 'conference']);
        }

        // ✅ filter KHOA
        if ($facultyId) {
            if ($hasFacultyJoin) {
                $query->where('d.faculty_id', (int)$facultyId);
            } else {
                $query->where('l.department_id', (int)$facultyId);
            }
        }

        // filter year
        if ($academicYearId) {
            $query->where('ra.academic_year_id', (int)$academicYearId);
        }

        // filter lecturer: owner OR member
        if ($lecturerQuery !== '') {
            $like = '%' . $lecturerQuery . '%';

            $matchedLecturerIds = DB::table('lecturers')
                ->where('full_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->pluck('id')
                ->map(fn($x) => (int)$x)
                ->all();

            if (count($matchedLecturerIds) === 0) {
                return response()->json([
                    'success' => true,
                    'data' => ['items' => [], 'total' => 0],
                ], Response::HTTP_OK);
            }

            $query->where(function ($q) use ($matchedLecturerIds) {
                $q->whereIn('ra.owner_lecturer_id', $matchedLecturerIds)
                    ->orWhereExists(function ($sub) use ($matchedLecturerIds) {
                        $sub->select(DB::raw(1))
                            ->from('research_activity_members as ram')
                            ->whereColumn('ram.activity_id', 'ra.id')
                            ->whereIn('ram.lecturer_id', $matchedLecturerIds);
                    });
            });
        }

        $query->orderByDesc('ra.approved_at')->orderByDesc('ra.id');

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            $workType = match ($row->kind_code) {
                'paper' => 'ARTICLE',
                'book' => 'BOOK',
                'project' => 'PROJECT',
                'conference' => 'CONFERENCE',
                default => 'OTHER',
            };

            return [
                'id' => (int)$row->id,
                'title' => (string)$row->title,
                'abstract' => (string)($row->abstract ?? ''),

                'lecturer_id' => (int)$row->lecturer_id,
                'lecturer_code' => (string)$row->lecturer_code,
                'lecturer_name' => (string)$row->lecturer_name,

                'faculty_id' => (int)($row->faculty_id ?? 0),
                'faculty_name' => (string)($row->faculty_name ?? '—'),

                'work_type' => $workType,

                'academic_year_id' => (int)($row->academic_year_id ?? 0),
                'academic_year_code' => (string)($row->academic_year_code ?? '—'),

                'approval_status' => 'APPROVED',

                'pdf_url' => null,
                'cover_url' => null,
                'keywords' => [],
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $paginator->total(),
            ],
        ], Response::HTTP_OK);
    }
    public function show(Request $request, int $activityId)
    {
        // ✅ chỉ public item đã approved
        $base = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('paper_details as pd', 'pd.activity_id', '=', 'ra.id')
            ->leftJoin('book_details as bd', 'bd.activity_id', '=', 'ra.id')
            ->leftJoin('project_details as pjd', 'pjd.activity_id', '=', 'ra.id')
            ->leftJoin('conference_details as cd', 'cd.activity_id', '=', 'ra.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ast.code', 'approved')
            ->where('ra.id', $activityId);

        $hasFacultyJoin = Schema::hasTable('faculties') && Schema::hasColumn('departments', 'faculty_id');
        $hasJournalJoin = Schema::hasTable('journals') && Schema::hasColumn('paper_details', 'journal_catalog_id');
        if ($hasFacultyJoin) {
            $base->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id');
        }
        if ($hasJournalJoin) {
            $base->leftJoin('journals as j', 'j.id', '=', 'pd.journal_catalog_id');
        }

        $row = $base->select([
            'ra.id',
            'ra.activity_code',
            'ra.title',
            'ra.abstract',
            'ak.code as kind_code',
            'at.name as activity_type_name',
            'pd.keywords as paper_keywords',
            'ay.id as academic_year_id',
            'ay.code as academic_year_code',
            'l.id as lecturer_id',
            'l.code as lecturer_code',
            'l.full_name as lecturer_name',
            'pd.journal_name as paper_journal_name',
            'pd.issn as paper_issn',
            'pd.journal_scope as paper_journal_scope',
            'pd.journal_source_name as paper_journal_source_name',
            'pd.research_field as paper_research_field',
            'pd.year as paper_year',
            'pd.volume as paper_volume',
            'pd.issue as paper_issue',
            'pd.page_start as paper_page_start',
            'pd.page_end as paper_page_end',
            'pd.doi as paper_doi',
            'pd.article_url as paper_article_url',
            $hasJournalJoin ? 'j.journal_type as paper_journal_type' : DB::raw('NULL as paper_journal_type'),
            'bd.isbn as book_isbn',
            'bd.publisher as book_publisher',
            'bd.year as book_year',
            'bd.approval_decision_no as book_approval_decision_no',
            'bd.approval_decision_date as book_approval_decision_date',
            'pjd.project_code as project_code',
            'pjd.project_category as project_category',
            'pjd.research_field as project_research_field',
            'pjd.objectives as project_objectives',
            'pjd.content_summary as project_content_summary',
            'pjd.start_month as project_start_month',
            'pjd.end_month as project_end_month',
            'pjd.decision_no as project_decision_no',
            'pjd.decision_date as project_decision_date',
            'cd.conference_name as conference_name',
            'cd.location as conference_location',
            'cd.held_on as conference_held_on',
            // faculty (KHOA)
            $hasFacultyJoin ? 'f.id as faculty_id' : 'd.id as faculty_id',
            $hasFacultyJoin ? 'f.name as faculty_name' : 'd.name as faculty_name',
        ])->first();

        if (! $row) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $workType = match ($row->kind_code) {
            'paper' => 'ARTICLE',
            'book' => 'BOOK',
            'project' => 'PROJECT',
            'conference' => 'CONFERENCE',
            default => 'OTHER',
        };

        // ===== participants: owner + members =====
        $ownerRole = $row->kind_code === 'paper' ? 'Tác giả chính' : 'Chủ nhiệm';

        $participants = [];
        $participants[] = [
            'lecturer_id' => (int) $row->lecturer_id,
            'lecturer_code' => (string) $row->lecturer_code,
            'lecturer_name' => (string) $row->lecturer_name,
            'faculty_name' => (string) ($row->faculty_name ?? '—'),
            'role_name' => $ownerRole,
        ];

        $membersQ = DB::table('research_activity_members as ram')
            ->leftJoin('lecturers as ml', 'ram.lecturer_id', '=', 'ml.id')
            ->leftJoin('departments as md', 'ml.department_id', '=', 'md.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activityId)
            ->where(function ($sub) use ($row) {
                $sub->whereNull('ram.lecturer_id')
                    ->orWhere('ram.lecturer_id', '<>', (int) $row->lecturer_id);
            });

        if ($hasFacultyJoin) {
            $membersQ->leftJoin('faculties as mf', 'md.faculty_id', '=', 'mf.id');
        }

        $memberRows = $membersQ->select([
            'ml.id as lecturer_id',
            'ml.code as lecturer_code',
            'ml.full_name as lecturer_name',
            $hasFacultyJoin ? 'mf.name as faculty_name' : 'md.name as faculty_name',
            'mr.name as role_name',
            'ram.is_external as is_external',
            'ram.external_full_name as external_full_name',
            'ram.external_department_name as external_department_name',
        ])->get();

        foreach ($memberRows as $m) {
            $fallbackRole = $row->kind_code === 'paper' ? 'Đồng tác giả' : 'Thành viên';

            // If the member is external (not linked to a lecturer record), prefer external_full_name
            if (! empty($m->is_external)) {
                $participants[] = [
                    'lecturer_id' => null,
                    'lecturer_code' => null,
                    'lecturer_name' => (string) ($m->external_full_name ?? '—'),
                    'faculty_name' => (string) ($m->external_department_name ?? '—'),
                    'role_name' => $m->role_name ? (string)$m->role_name : $fallbackRole,
                ];
                continue;
            }

            $participants[] = [
                'lecturer_id' => (int) $m->lecturer_id,
                'lecturer_code' => (string) $m->lecturer_code,
                'lecturer_name' => (string) $m->lecturer_name,
                'faculty_name' => (string) ($m->faculty_name ?? '—'),
                'role_name' => $m->role_name ? (string)$m->role_name : $fallbackRole,
            ];
        }

        $evidenceLinks = $this->loadPublicEvidenceLinks($activityId, (string) $row->kind_code);
        $pdfUrl = collect($evidenceLinks)
            ->first(function (array $entry): bool {
                $url = strtolower((string)($entry['url'] ?? ''));
                return str_ends_with($url, '.pdf') || str_contains($url, '.pdf?');
            })['url'] ?? null;

        $keywords = $this->parseKeywords($row->paper_keywords ?? null);

        $displayMeta = [
            'article' => [
                'journal_name' => $this->nullableString($row->paper_journal_name ?? null),
                'issn' => $this->nullableString($row->paper_issn ?? null),
                'journal_scope' => $this->nullableString($row->paper_journal_scope ?? null),
                'journal_type' => $this->nullableString($row->paper_journal_type ?? null),
                'journal_source_name' => $this->nullableString($row->paper_journal_source_name ?? null),
                'research_field' => $this->nullableString($row->paper_research_field ?? null),
                'year' => $this->nullableInt($row->paper_year ?? null),
                'volume' => $this->nullableString($row->paper_volume ?? null),
                'issue' => $this->nullableString($row->paper_issue ?? null),
                'page_start' => $this->nullableInt($row->paper_page_start ?? null),
                'page_end' => $this->nullableInt($row->paper_page_end ?? null),
                'doi' => $this->nullableString($row->paper_doi ?? null),
                'article_url' => $this->nullableUrl($row->paper_article_url ?? null),
            ],
            'project' => [
                'project_code' => $this->nullableString($row->project_code ?? null),
                'management_level' => $this->nullableString($row->activity_type_name ?? null),
                'project_category' => $this->nullableString($row->project_category ?? null),
                'research_field' => $this->nullableString($row->project_research_field ?? null),
                'objectives' => $this->nullableString($row->project_objectives ?? null),
                'content_summary' => $this->nullableString($row->project_content_summary ?? null),
                'start_month' => $this->nullableDate($row->project_start_month ?? null),
                'end_month' => $this->nullableDate($row->project_end_month ?? null),
                'decision_no' => $this->nullableString($row->project_decision_no ?? null),
                'decision_date' => $this->nullableDate($row->project_decision_date ?? null),
            ],
            'book' => [
                'publisher' => $this->nullableString($row->book_publisher ?? null),
                'isbn' => $this->nullableString($row->book_isbn ?? null),
                'year' => $this->nullableInt($row->book_year ?? null),
                'book_type' => $this->nullableString($row->activity_type_name ?? null),
                'research_field' => null,
                'approval_decision_no' => $this->nullableString($row->book_approval_decision_no ?? null),
                'approval_decision_date' => $this->nullableDate($row->book_approval_decision_date ?? null),
            ],
            'conference' => [
                'conference_name' => $this->nullableString($row->conference_name ?? null),
                'held_on' => $this->nullableDate($row->conference_held_on ?? null),
                'location' => $this->nullableString($row->conference_location ?? null),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'item' => [
                    'id' => (int)$row->id,
                    'activity_code' => (string)($row->activity_code ?? ''),
                    'title' => (string)$row->title,
                    'abstract' => (string)($row->abstract ?? ''),


                    'lecturer_id' => (int)$row->lecturer_id,
                    'lecturer_code' => (string)$row->lecturer_code,
                    'lecturer_name' => (string)$row->lecturer_name,

                    'faculty_id' => (int)($row->faculty_id ?? 0),
                    'faculty_name' => (string)($row->faculty_name ?? '—'),

                    'work_type' => $workType,

                    'academic_year_id' => (int)($row->academic_year_id ?? 0),
                    'academic_year_code' => (string)($row->academic_year_code ?? '—'),

                    'approval_status' => 'APPROVED',

                    'pdf_url' => $pdfUrl,
                    'cover_url' => null,
                    'keywords' => $keywords,

                    // ✅ quan trọng: đồng tác giả
                    'participants' => $participants,

                    // public attachments (chỉ các URL công khai hợp lệ)
                    'evidence_files' => $evidenceLinks,
                    'display_meta' => $displayMeta,
                ],
            ],
        ], Response::HTTP_OK);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $text = $this->nullableString($value);
        if ($text === null) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) === 1 ? $text : $text;
    }

    private function nullableUrl(mixed $value): ?string
    {
        $text = $this->nullableString($value);
        if ($text === null) {
            return null;
        }

        return filter_var($text, FILTER_VALIDATE_URL) ? $text : null;
    }

    private function parseKeywords(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[,;]+/', $raw) ?: [])
            ->map(fn($part) => trim((string)$part))
            ->filter(fn($part) => $part !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function loadPublicEvidenceLinks(int $activityId, string $kindCode): array
    {
        $allowedCodes = self::PUBLIC_EVIDENCE_TYPE_CODES_BY_KIND[$kindCode] ?? [];
        if ($allowedCodes === []) {
            return [];
        }

        $rows = DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->whereIn('eft.code', $allowedCodes)
            ->select([
                'eft.code as file_type_code',
                'eft.name as file_type_name',
                'ef.original_name',
                'ef.disk',
                'ef.path',
                'ef.mime_type',
                'ef.id',
            ])
            ->orderByDesc('ef.id')
            ->get();

        $priority = array_flip($allowedCodes);

        return collect($rows)
            ->map(function (object $row) use ($activityId) {
                $disk = strtolower(trim((string) ($row->disk ?? '')));
                $rawPath = trim((string) ($row->path ?? ''));
                $url = $rawPath;
                if ($disk !== 'evidence_link') {
                    $url = route('public.research.evidence.preview', [
                        'activityId' => $activityId,
                        'evidenceId' => (int) $row->id,
                    ]);
                }

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return null;
                }

                $mime = strtolower(trim((string) ($row->mime_type ?? '')));
                $name = strtolower(trim((string) ($row->original_name ?? '')));
                $isPdf = str_contains($mime, 'pdf') || str_ends_with($name, '.pdf') || str_contains($url, '.pdf?') || str_ends_with($url, '.pdf');

                return [
                    'label' => trim((string) ($row->file_type_name ?? '')) !== ''
                        ? (string) $row->file_type_name
                        : (trim((string)($row->original_name ?? '')) !== '' ? (string) $row->original_name : 'Link minh chứng'),
                    'url' => $url,
                    'is_pdf' => $isPdf,
                    'file_type_code' => (string) ($row->file_type_code ?? ''),
                ];
            })
            ->filter()
            ->sortBy(fn(array $item) => $priority[$item['file_type_code']] ?? PHP_INT_MAX)
            ->map(function (array $item) {
                unset($item['file_type_code']);
                return $item;
            })
            ->values()
            ->all();
    }
}
