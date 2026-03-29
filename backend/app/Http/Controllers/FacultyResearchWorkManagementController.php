<?php

namespace App\Http\Controllers;

use App\Exports\AdminResearchWorksSummaryExport;
use App\Http\Requests\Faculty\FacultyResearchWorkLecturerWorksRequest;
use App\Http\Requests\Faculty\FacultyResearchWorkSummaryRequest;
use App\Services\Evidence\ResearchEvidenceStorageService;
use App\Support\StorageDownload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class FacultyResearchWorkManagementController extends Controller
{
    private const MANAGEMENT_VISIBLE_STATUS_CODES = [
        'submitted',
        'pending_faculty_review',
        'approved',
        'rejected',
        'member_rejected',
    ];

    public function __construct(
        private ResearchEvidenceStorageService $evidenceStorageService
    ) {}

    public function lookups(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $academicYears = DB::table('academic_years')
            ->select(['id', 'code', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'is_active' => (bool) $row->is_active,
            ])
            ->all();

        $statusOptions = $this->statusOptions();

        return response()->json([
            'data' => [
                'academic_years' => $academicYears,
                'statuses' => $statusOptions,
                'faculty' => [
                    'id' => $scope['faculty_id'],
                    'name' => $scope['faculty_name'],
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function lecturerSummary(FacultyResearchWorkSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $filters = $this->normalizeSummaryFilters($request->validated(), $scope['faculty_id']);
        $query = $this->buildSummaryQuery(
            $scope['faculty_id'],
            $filters['academic_year_id'],
            $filters['q']
        );

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $paginator = $query->orderBy('l.full_name')->paginate($perPage, ['*'], 'page', $page);
        $rows = collect($paginator->items())
            ->map(function ($row) use ($filters) {
                return $this->applyCountMode((array) $row, $filters['count_status']);
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'filters' => $filters,
                'faculty' => [
                    'id' => $scope['faculty_id'],
                    'name' => $scope['faculty_name'],
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function lecturerWorks(FacultyResearchWorkLecturerWorksRequest $request, int $lecturer)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        if (! $this->lecturerInFaculty($lecturer, $scope['faculty_id'])) {
            return response()->json(['message' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $filters = $this->normalizeLecturerWorksFilters($request->validated());

        $query = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('research_activity_members as ram', function ($join) use ($lecturer) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturer);
            })
            ->where(function ($q) use ($lecturer) {
                $q->where('ra.owner_lecturer_id', $lecturer)
                    ->orWhereNotNull('ram.lecturer_id');
            })
            ->whereIn('ast.code', self::MANAGEMENT_VISIBLE_STATUS_CODES)
            ->when($filters['academic_year_id'], function ($q, $yearId) {
                $q->where('ra.academic_year_id', $yearId);
            })
            ->when($filters['status'], function ($q, $status) {
                if ($status === 'pending' || $status === 'submitted') {
                    $q->whereIn('ast.code', ['pending_faculty_review', 'submitted']);
                    return;
                }
                if ($status === 'rejected') {
                    $q->whereIn('ast.code', ['rejected', 'member_rejected']);
                    return;
                }
                $q->where('ast.code', $status);
            })
            ->when($filters['q'], function ($q, $keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('ra.title', 'like', '%' . $keyword . '%')
                        ->orWhere('ra.activity_code', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('ra.approved_at')
            ->orderByDesc('ra.updated_at')
            ->orderByDesc('ra.id')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ast.code as status_code',
                'ast.name as status_name',
                'ra.kind_id',
                'ak.name as kind_name',
                'ra.type_id',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                'ra.submitted_at',
                'ra.approved_at',
            ]);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 8)));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function approvedDetail(Request $request, int $activity)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
        ]);

        $lecturerId = isset($validated['lecturer_id']) ? (int) $validated['lecturer_id'] : null;
        if ($lecturerId !== null && ! $this->lecturerInFaculty($lecturerId, $scope['faculty_id'])) {
            return response()->json(['message' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $activityQuery = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('paper_details as pd', 'pd.activity_id', '=', 'ra.id')
            ->leftJoin('book_details as bd', 'bd.activity_id', '=', 'ra.id')
            ->leftJoin('conference_details as cd', 'cd.activity_id', '=', 'ra.id')
            ->leftJoin('project_details as prd', 'prd.activity_id', '=', 'ra.id');

        $this->applyActivityFacultyScope($activityQuery, $scope['faculty_id']);

        $activityRow = $activityQuery
            ->where('ra.id', $activity)
            ->where('ast.code', 'approved')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.owner_lecturer_id',
                'ra.title',
                'ra.abstract',
                'ra.kind_id',
                'ak.name as kind_name',
                'ra.type_id',
                'at.name as type_name',
                'ra.academic_year_id',
                'ay.code as academic_year_code',
                DB::raw($this->activityYearExpression() . ' as work_year'),
                DB::raw('COALESCE(pd.journal_name, bd.publisher, cd.conference_name, prd.project_code) as venue_name'),
                'ra.submitted_at',
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
            ->get()
            ->map(function ($row) {
                return [
                    'evidence_file_id' => (int) $row->evidence_file_id,
                    'file_type_id' => (int) $row->file_type_id,
                    'file_type_name' => $row->file_type_name,
                    'disk' => $row->disk,
                    'path' => $row->path,
                    'original_name' => $row->original_name,
                    'mime_type' => $row->mime_type,
                    'size_bytes' => $row->size_bytes !== null ? (int) $row->size_bytes : null,
                    'uploaded_at' => $row->uploaded_at,
                    'preview_url' => route('faculty.works.evidence.preview', ['evidence' => $row->evidence_file_id], false),
                    'download_url' => route('faculty.works.evidence.download', ['evidence' => $row->evidence_file_id], false),
                    'url' => route('faculty.works.evidence.preview', ['evidence' => $row->evidence_file_id], false),
                ];
            })
            ->all();

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

        $memberSnapshot = null;
        if ($lecturerId !== null) {
            $memberSnapshot = DB::table('research_activity_members as ram')
                ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
                ->where('ram.activity_id', $activity)
                ->where('ram.lecturer_id', $lecturerId)
                ->select([
                    'mr.name as member_role_name',
                    'ram.hours_assigned as lecturer_hours',
                ])
                ->first();

            if (! $memberSnapshot && (int) ($activityRow->owner_lecturer_id ?? 0) === $lecturerId) {
                $memberSnapshot = (object) [
                    'member_role_name' => 'Tác giả chính',
                    'lecturer_hours' => null,
                ];
            }
        }

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
                'work_year' => $activityRow->work_year !== null ? (int) $activityRow->work_year : null,
                'venue_name' => $activityRow->venue_name,
                'submitted_at' => $activityRow->submitted_at,
                'member_role_name' => $memberSnapshot?->member_role_name,
                'lecturer_hours' => $memberSnapshot?->lecturer_hours !== null
                    ? number_format((float) $memberSnapshot->lecturer_hours, 2, '.', '')
                    : null,
                'approved_at' => $activityRow->approved_at,
                'authors' => $authors,
                'evidence_items' => $evidenceItems,
                'final_approval' => $finalApproval,
            ],
        ], Response::HTTP_OK);
    }

    public function exportSummaryExcel(FacultyResearchWorkSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $filters = $this->normalizeSummaryFilters($request->validated(), $scope['faculty_id']);
        $rows = $this->summaryRows($scope['faculty_id'], $filters['academic_year_id'], $filters['q'], $filters['count_status']);

        $filename = $this->buildExportFilename('xlsx');
        return Excel::download(new AdminResearchWorksSummaryExport($rows), $filename);
    }

    public function exportSummaryPdf(FacultyResearchWorkSummaryRequest $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $filters = $this->normalizeSummaryFilters($request->validated(), $scope['faculty_id']);
        $rows = $this->summaryRows($scope['faculty_id'], $filters['academic_year_id'], $filters['q'], $filters['count_status']);
        $filename = $this->buildExportFilename('pdf');

        return Pdf::loadView('exports.admin_works_summary', [
            'rows' => $rows,
            'filters' => $this->buildFilterPayload($filters, $scope['faculty_id']),
        ])->setPaper('A4', 'landscape')->download($filename);
    }

    public function downloadEvidence(Request $request, int $evidence)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $fileQuery = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ef.activity_id', '=', 'ra.id')
            ->where('ef.id', $evidence);

        $this->applyActivityFacultyScope($fileQuery, $scope['faculty_id']);

        $file = $fileQuery
            ->select([
                'ef.id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
            ])
            ->first();

        if (! $file) {
            return response()->json(['message' => 'evidence not found'], Response::HTTP_NOT_FOUND);
        }

        $disk = $file->disk ?: 'public';
        $path = $file->path;
        $filename = $file->original_name ?: ('evidence-' . $file->id . '.pdf');

        if ($this->evidenceStorageService->isRcloneDisk((string) $disk)) {
            try {
                return $this->evidenceStorageService->streamDownload(
                    $request,
                    (string) $disk,
                    (string) $path,
                    (string) $filename,
                    (string) ($file->mime_type ?? 'application/octet-stream')
                );
            } catch (RuntimeException $exception) {
                Log::error('faculty_research_work.evidence_download_failed', [
                    'evidence_id' => $evidence,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Không thể tải tệp minh chứng. Vui lòng thử lại.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return StorageDownload::stream($disk, $path, $filename, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function previewEvidence(Request $request, int $evidence)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $fileQuery = DB::table('evidence_files as ef')
            ->join('research_activities as ra', 'ef.activity_id', '=', 'ra.id')
            ->where('ef.id', $evidence);

        $this->applyActivityFacultyScope($fileQuery, $scope['faculty_id']);

        $file = $fileQuery
            ->select([
                'ef.id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
            ])
            ->first();

        if (! $file) {
            return response()->json(['message' => 'evidence not found'], Response::HTTP_NOT_FOUND);
        }

        $filename = $file->original_name ?: ('evidence-' . $file->id . '.pdf');

        try {
            return $this->evidenceStorageService->streamPreview(
                $request,
                (string) ($file->disk ?? 'local'),
                (string) ($file->path ?? ''),
                (string) $filename,
                (string) ($file->mime_type ?? 'application/pdf')
            );
        } catch (RuntimeException $exception) {
            Log::error('faculty_research_work.evidence_preview_failed', [
                'evidence_id' => $evidence,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể xem trước tệp minh chứng. Vui lòng thử lại.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function resolveFacultyScope(Request $request): ?array
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;
        if (! $lecturer || ! $lecturer->department_id) {
            return null;
        }

        $faculty = DB::table('departments as d')
            ->join('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('d.id', $lecturer->department_id)
            ->select(['f.id', 'f.name'])
            ->first();

        if (! $faculty) {
            return null;
        }

        return [
            'faculty_id' => (int) $faculty->id,
            'faculty_name' => $faculty->name,
        ];
    }

    private function lecturerInFaculty(int $lecturerId, int $facultyId): bool
    {
        return DB::table('lecturers as l')
            ->join('departments as d', 'l.department_id', '=', 'd.id')
            ->where('l.id', $lecturerId)
            ->where('d.faculty_id', $facultyId)
            ->exists();
    }

    private function normalizeSummaryFilters(array $validated, int $facultyId): array
    {
        $status = $validated['count_status'] ?? 'all';
        if ($status === 'submitted') {
            $status = 'pending';
        }

        return [
            'faculty_id' => $facultyId,
            'academic_year_id' => $validated['academic_year_id'] ?? $this->activeAcademicYearId(),
            'q' => trim((string) ($validated['q'] ?? '')),
            'count_status' => $status ?: 'all',
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 12,
        ];
    }

    private function normalizeLecturerWorksFilters(array $validated): array
    {
        $status = $validated['status'] ?? 'approved';
        if ($status === 'submitted') {
            $status = 'pending';
        }

        return [
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'status' => $status ?: null,
            'q' => trim((string) ($validated['q'] ?? '')),
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 8,
        ];
    }

    private function buildSummaryQuery(int $facultyId, ?int $academicYearId, string $search)
    {
        $activityLecturers = $this->activityLecturerSubquery();

        $activityAgg = DB::query()
            ->fromSub($activityLecturers, 'al')
            ->join('research_activities as ra', 'al.activity_id', '=', 'ra.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->whereIn('ast.code', self::MANAGEMENT_VISIBLE_STATUS_CODES)
            ->when($academicYearId, function ($query, $academicYearId) {
                $query->where('ra.academic_year_id', $academicYearId);
            })
            ->groupBy('al.lecturer_id')
            ->select([
                'al.lecturer_id',
                DB::raw('COUNT(*) as total_declared_research_work_count'),
                DB::raw("SUM(CASE WHEN ast.code = 'approved' THEN 1 ELSE 0 END) as approved_research_work_count"),
                DB::raw("SUM(CASE WHEN ast.code IN ('submitted','pending_faculty_review') THEN 1 ELSE 0 END) as pending_research_work_count"),
                DB::raw("SUM(CASE WHEN ast.code IN ('rejected','member_rejected') THEN 1 ELSE 0 END) as rejected_research_work_count"),
            ]);

        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->leftJoinSub($activityAgg, 'agg', 'agg.lecturer_id', '=', 'l.id')
            ->where('f.id', $facultyId)
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

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('l.full_name', 'like', '%' . $search . '%')
                    ->orWhere('l.code', 'like', '%' . $search . '%')
                    ->orWhere('l.email', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function summaryRows(int $facultyId, ?int $academicYearId, string $search, string $countStatus): array
    {
        $query = $this->buildSummaryQuery($facultyId, $academicYearId, $search);
        $rows = $query->orderBy('l.full_name')->get()->all();

        return collect($rows)
            ->map(function ($row) use ($countStatus) {
                return $this->applyCountMode((array) $row, $countStatus);
            })
            ->values()
            ->all();
    }

    private function applyCountMode(array $row, string $mode): array
    {
        $payload = $row;
        $payload['total_declared_research_work_count'] = (int) ($row['total_declared_research_work_count'] ?? 0);
        $payload['approved_research_work_count'] = (int) ($row['approved_research_work_count'] ?? 0);
        $payload['pending_research_work_count'] = (int) ($row['pending_research_work_count'] ?? 0);
        $payload['rejected_research_work_count'] = (int) ($row['rejected_research_work_count'] ?? 0);

        if ($mode === 'approved') {
            $payload['total_declared_research_work_count'] = (int) ($row['approved_research_work_count'] ?? 0);
            $payload['pending_research_work_count'] = 0;
            $payload['rejected_research_work_count'] = 0;
        } elseif ($mode === 'pending') {
            $payload['total_declared_research_work_count'] = (int) ($row['pending_research_work_count'] ?? 0);
            $payload['approved_research_work_count'] = 0;
            $payload['rejected_research_work_count'] = 0;
        } elseif ($mode === 'rejected') {
            $payload['total_declared_research_work_count'] = (int) ($row['rejected_research_work_count'] ?? 0);
            $payload['approved_research_work_count'] = 0;
            $payload['pending_research_work_count'] = 0;
        }

        return $payload;
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

    private function applyActivityFacultyScope($query, int $facultyId): void
    {
        $query->where(function ($scope) use ($facultyId) {
            $scope->whereExists(function ($sub) use ($facultyId) {
                $sub->select(DB::raw(1))
                    ->from('research_activity_members as ram')
                    ->join('lecturers as lm', 'ram.lecturer_id', '=', 'lm.id')
                    ->join('departments as dm', 'lm.department_id', '=', 'dm.id')
                    ->whereColumn('ram.activity_id', 'ra.id')
                    ->where('dm.faculty_id', $facultyId);
            })->orWhereExists(function ($sub) use ($facultyId) {
                $sub->select(DB::raw(1))
                    ->from('lecturers as lo')
                    ->join('departments as d', 'lo.department_id', '=', 'd.id')
                    ->whereColumn('lo.id', 'ra.owner_lecturer_id')
                    ->where('d.faculty_id', $facultyId);
            });
        });
    }

    private function statusOptions(): array
    {
        $rows = DB::table('activity_statuses')
            ->whereIn('code', ['approved', 'submitted', 'rejected'])
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $code = $row->code === 'submitted' ? 'pending' : $row->code;
                return [
                    'code' => $code,
                    'name' => $row->name,
                ];
            })
            ->values()
            ->all();

        array_unshift($rows, [
            'code' => 'all',
            'name' => 'Tất cả',
        ]);

        return $rows;
    }

    private function activeAcademicYearId(): ?int
    {
        $row = DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->select(['id'])
            ->first();

        return $row?->id ? (int) $row->id : null;
    }

    private function buildExportFilename(string $ext): string
    {
        return 'faculty_works_summary_' . now()->format('Ymd_His') . '.' . $ext;
    }

    private function activityYearExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'COALESCE(pd.year, bd.year, CAST(EXTRACT(YEAR FROM prd.start_month) AS INTEGER), CAST(EXTRACT(YEAR FROM cd.held_on) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.approved_at) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.submitted_at) AS INTEGER))',
            'sqlite' => "COALESCE(pd.year, bd.year, CAST(strftime('%Y', prd.start_month) AS INTEGER), CAST(strftime('%Y', cd.held_on) AS INTEGER), CAST(strftime('%Y', ra.approved_at) AS INTEGER), CAST(strftime('%Y', ra.submitted_at) AS INTEGER))",
            default => 'COALESCE(pd.year, bd.year, YEAR(cd.held_on), YEAR(prd.start_month), YEAR(ra.approved_at), YEAR(ra.submitted_at))',
        };
    }

    private function buildFilterPayload(array $filters, int $facultyId): array
    {
        $facultyName = DB::table('faculties')->where('id', $facultyId)->value('name');
        $academicYearCode = $filters['academic_year_id']
            ? DB::table('academic_years')->where('id', $filters['academic_year_id'])->value('code')
            : 'Tất cả';
        $statusLabel = match ($filters['count_status'] ?? 'all') {
            'approved' => 'Đã duyệt',
            'pending' => 'Chờ duyệt',
            'rejected' => 'Từ chối',
            default => 'Tất cả',
        };

        return [
            'faculty' => $facultyName ?: 'Tất cả',
            'department' => 'Tất cả',
            'academic_year' => $academicYearCode ?: 'Tất cả',
            'status' => $statusLabel,
            'keyword' => $filters['q'] ?: 'Tất cả',
        ];
    }
}
