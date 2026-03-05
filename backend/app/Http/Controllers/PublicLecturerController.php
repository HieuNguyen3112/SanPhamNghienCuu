<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PublicLecturerController extends Controller
{
    // GET /api/public/lecturers?q=&department_id=&academic_year_id=&page=&per_page=
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $departmentId = $request->query('department_id');
        $academicYearId = $request->query('academic_year_id');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(24, (int) $request->query('per_page', 12)));

        // ====== Derived table: activity_id + lecturer_id (owner + members) ======
        $owners = DB::table('research_activities as ra')
            ->select([
                'ra.id as activity_id',
                'ra.owner_lecturer_id as lecturer_id',
            ]);

        $members = DB::table('research_activity_members as ram')
            ->select([
                'ram.activity_id',
                'ram.lecturer_id',
            ]);

        // union all rồi distinct ở ngoài để tránh trùng
        $activityLecturers = DB::query()
            ->fromSub($owners->unionAll($members), 'al')
            ->select('al.activity_id', 'al.lecturer_id')
            ->distinct();

        // ====== Base activities approved ======
        $activityAgg = DB::query()
            ->fromSub($activityLecturers, 'al')
            ->join('research_activities as ra', 'al.activity_id', '=', 'ra.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->when($academicYearId, fn($qq) => $qq->where('ra.academic_year_id', (int)$academicYearId))
            ->where('ast.code', 'approved')
            ->groupBy('al.lecturer_id')
            ->select([
                'al.lecturer_id',
                DB::raw("SUM(CASE WHEN ak.code = 'paper' THEN 1 ELSE 0 END) AS paper"),
                DB::raw("SUM(CASE WHEN ak.code = 'project' THEN 1 ELSE 0 END) AS project"),
                DB::raw("SUM(CASE WHEN ak.code = 'conference' THEN 1 ELSE 0 END) AS conference"),
                // book_only = book nhưng type != textbook
                DB::raw("SUM(CASE WHEN ak.code = 'book' AND (at.code IS NULL OR LOWER(at.code) <> 'textbook') THEN 1 ELSE 0 END) AS book_only"),
                // textbook = book nhưng type = textbook
                DB::raw("SUM(CASE WHEN ak.code = 'book' AND LOWER(at.code) = 'textbook' THEN 1 ELSE 0 END) AS textbook"),
                DB::raw("COUNT(DISTINCT ra.id) AS total"),
            ]);

        // ====== Join lecturers + profile + counts ======
        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('degrees as deg', 'l.degree_id', '=', 'deg.id')
            ->leftJoin('academic_ranks as ar', 'l.academic_rank_id', '=', 'ar.id')
            ->leftJoin('lecturer_profiles as lp', 'lp.lecturer_id', '=', 'l.id')
            ->leftJoinSub($activityAgg, 'rw', 'rw.lecturer_id', '=', 'l.id')
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%' . $q . '%';
                $qq->where(function ($sub) use ($like) {
                    $sub->where('l.full_name', 'like', $like)
                        ->orWhere('l.code', 'like', $like);
                });
            })
            ->when($departmentId, fn($qq) => $qq->where('l.department_id', (int)$departmentId))
            ->where('l.active', true)
            ->orderBy('l.full_name')
            ->select([
                'l.id',
                'l.code',
                'l.full_name',
                'd.name as department_name',
                'deg.name as degree_name',
                'ar.name as academic_rank_name',
                // profile fields (public)
                'lp.current_unit',
                'lp.current_position',
                'lp.teaching_specialization',
                'lp.research_area',
                // counts
                DB::raw("COALESCE(rw.paper, 0) as paper"),
                DB::raw("COALESCE(rw.project, 0) as project"),
                DB::raw("COALESCE(rw.conference, 0) as conference"),
                DB::raw("COALESCE(rw.book_only, 0) as book_only"),
                DB::raw("COALESCE(rw.textbook, 0) as textbook"),
                DB::raw("COALESCE(rw.total, 0) as total"),
            ]);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'code' => $row->code,
                'full_name' => $row->full_name,
                'department_name' => $row->department_name,
                'degree_name' => $row->degree_name,
                'academic_rank_name' => $row->academic_rank_name,
                'profile' => [
                    'current_unit' => $row->current_unit,
                    'current_position' => $row->current_position,
                    'teaching_specialization' => $row->teaching_specialization,
                    'research_area' => $row->research_area,
                ],
                'research_works' => [
                    'counts_by_kind' => [
                        'paper' => (int) $row->paper,
                        'project' => (int) $row->project,
                        'conference' => (int) $row->conference,
                        'book_only' => (int) $row->book_only,
                        'textbook' => (int) $row->textbook,
                        'total' => (int) $row->total,
                    ],
                ],
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    // GET /api/public/lecturers/{code}
    public function show(Request $request, Lecturer $lecturer)
{
    $lecturer->load(['department', 'degree', 'academicRank', 'profile']);

    $counts = $this->approvedCountsByKind($lecturer->id);

    $workHistories = $lecturer->workHistories()
        ->orderByDesc('start_date')
        ->get()
        ->map(fn($h) => [
            'organization' => $h->organization,
            'position' => $h->position,
            'department' => $h->department,
            'workplace' => $h->workplace,
            'start_date' => $h->start_date?->toDateString(),
            'end_date' => $h->end_date?->toDateString(),
            'is_current' => (bool) $h->is_current,
            'employment_type' => $h->employment_type,
            'notes' => $h->notes,
        ])
        ->values()
        ->all();

    $educations = $lecturer->trainingHistories()
        ->with('degree')
        ->orderByDesc('start_date')
        ->get()
        ->map(fn($h) => [
            'degree_name' => $h->degree?->name,
            'degree_title' => $h->degree_title,
            'major' => $h->major,
            'institution' => $h->institution,
            'country' => $h->country,
            'city' => $h->city,
            'start_date' => $h->start_date?->toDateString(),
            'end_date' => $h->end_date?->toDateString(),
            'is_current' => (bool) $h->is_current,
            'training_form' => $h->training_form,
            'notes' => $h->notes,
        ])
        ->values()
        ->all();

    return response()->json([
        'success' => true,
        'data' => [
            'lecturer' => [
                'id' => $lecturer->id,
                'code' => $lecturer->code,
                'full_name' => $lecturer->full_name,
                'department_name' => $lecturer->department?->name,
                'degree_name' => $lecturer->degree?->name,
                'academic_rank_name' => $lecturer->academicRank?->name,
                'email' => $lecturer->email,
                'phone' => $lecturer->phone,
            ],
            'profile' => $lecturer->profile ? [
                'gender' => $lecturer->profile->gender,
                'date_of_birth' => $lecturer->profile->date_of_birth,
                'address' => $lecturer->profile->address,
                'current_unit' => $lecturer->profile->current_unit,
                'current_position' => $lecturer->profile->current_position,
                'teaching_specialization' => $lecturer->profile->teaching_specialization,
                'research_area' => $lecturer->profile->research_area,
                'personal_email' => $lecturer->profile->personal_email,
            ] : null,

            'research_works' => [
                'counts_by_kind' => $counts,
            ],

            'work_histories' => $workHistories,
            'educations' => $educations,
        ],
    ], Response::HTTP_OK);
}

/**
 * Đếm công trình approved theo kind, tách giáo trình theo activity_types.code = textbook.
 */
private function approvedCountsByKind(int $lecturerId): array
{
    $row = DB::table('research_activities as ra')
        ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
        ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
        ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
        ->leftJoin('research_activity_members as ram', function ($join) use ($lecturerId) {
            $join->on('ra.id', '=', 'ram.activity_id')
                ->where('ram.lecturer_id', '=', $lecturerId);
        })
        ->where('ast.code', 'approved')
        ->where(function ($q) use ($lecturerId) {
            $q->where('ra.owner_lecturer_id', $lecturerId)
              ->orWhereNotNull('ram.lecturer_id');
        })
        ->selectRaw("SUM(CASE WHEN ak.code = 'paper' THEN 1 ELSE 0 END) AS paper")
        ->selectRaw("SUM(CASE WHEN ak.code = 'project' THEN 1 ELSE 0 END) AS project")
        ->selectRaw("SUM(CASE WHEN ak.code = 'conference' THEN 1 ELSE 0 END) AS conference")
        ->selectRaw("SUM(CASE WHEN ak.code = 'book' AND (at.code IS NULL OR LOWER(at.code) <> 'textbook') THEN 1 ELSE 0 END) AS book_only")
        ->selectRaw("SUM(CASE WHEN ak.code = 'book' AND LOWER(at.code) = 'textbook' THEN 1 ELSE 0 END) AS textbook")
        ->selectRaw("COUNT(DISTINCT ra.id) AS total")
        ->first();

    return [
        'paper' => (int)($row->paper ?? 0),
        'project' => (int)($row->project ?? 0),
        'conference' => (int)($row->conference ?? 0),
        'book_only' => (int)($row->book_only ?? 0),
        'textbook' => (int)($row->textbook ?? 0),
        'total' => (int)($row->total ?? 0),
    ];
}
}