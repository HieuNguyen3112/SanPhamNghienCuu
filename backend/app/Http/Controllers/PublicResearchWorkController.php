<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class PublicResearchWorkController extends Controller
{
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

        // ✅ Select (faculty_name đúng là KHOA)
        $select = [
            'ra.id',
            'ra.title',
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
                'abstract' => '',

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
        ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
        ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
        ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
        ->where('ast.code', 'approved')
        ->where('ra.id', $activityId);

    $hasFacultyJoin = Schema::hasTable('faculties') && Schema::hasColumn('departments', 'faculty_id');
    if ($hasFacultyJoin) {
        $base->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id');
    }

    $row = $base->select([
        'ra.id',
        'ra.activity_code',
        'ra.title',
        'ak.code as kind_code',
        'ay.id as academic_year_id',
        'ay.code as academic_year_code',
        'l.id as lecturer_id',
        'l.code as lecturer_code',
        'l.full_name as lecturer_name',
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
        ->join('lecturers as ml', 'ram.lecturer_id', '=', 'ml.id')
        ->leftJoin('departments as md', 'ml.department_id', '=', 'md.id')
        ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
        ->where('ram.activity_id', $activityId)
        ->where('ram.lecturer_id', '<>', (int)$row->lecturer_id);

    if ($hasFacultyJoin) {
        $membersQ->leftJoin('faculties as mf', 'md.faculty_id', '=', 'mf.id');
    }

    $memberRows = $membersQ->select([
        'ml.id as lecturer_id',
        'ml.code as lecturer_code',
        'ml.full_name as lecturer_name',
        $hasFacultyJoin ? 'mf.name as faculty_name' : 'md.name as faculty_name',
        'mr.name as role_name',
    ])->get();

    foreach ($memberRows as $m) {
        $fallbackRole = $row->kind_code === 'paper' ? 'Đồng tác giả' : 'Thành viên';
        $participants[] = [
            'lecturer_id' => (int) $m->lecturer_id,
            'lecturer_code' => (string) $m->lecturer_code,
            'lecturer_name' => (string) $m->lecturer_name,
            'faculty_name' => (string) ($m->faculty_name ?? '—'),
            'role_name' => $m->role_name ? (string)$m->role_name : $fallbackRole,
        ];
    }

    return response()->json([
        'success' => true,
        'data' => [
            'item' => [
                'id' => (int)$row->id,
                'activity_code' => (string)($row->activity_code ?? ''),
                'title' => (string)$row->title,
                'abstract' => '',

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

                // ✅ quan trọng: đồng tác giả
                'participants' => $participants,

                // public attachments (tạm rỗng)
                'evidence_files' => [],
            ],
        ],
    ], Response::HTTP_OK);
}
}