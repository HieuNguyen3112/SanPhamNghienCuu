<?php

namespace App\Http\Controllers;

use App\Models\AcademicRank;
use App\Models\Degree;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LookupController extends Controller
{
    public function degrees()
    {
        $data = Degree::query()
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function academicRanks()
    {
        $data = AcademicRank::query()
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function faculties()
    {
        $data = DB::table('faculties')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function departments(Request $request)
    {
        $query = Department::query()->select(['id', 'faculty_id', 'code', 'name']);

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->input('faculty_id'));
        }

        $data = $query
            ->orderBy('name')
            ->get()
            ->map(function (Department $department) {
                return [
                    'id' => (int) $department->id,
                    'faculty_id' => (int) $department->faculty_id,
                    'code' => $department->code,
                    'name' => $department->name,
                ];
            });

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function academicYears()
    {
        $data = DB::table('academic_years')
            ->select(['id', 'code', 'start_date', 'end_date', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function activityKinds()
    {
        $data = DB::table('activity_kinds')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function activityTypes(Request $request)
    {
        $query = DB::table('activity_types')
            ->select(['id', 'kind_id', 'code', 'name']);

        if ($request->filled('kind_id')) {
            $query->where('kind_id', $request->input('kind_id'));
        }

        $data = $query->orderBy('name')->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function memberRoles()
    {
        $data = DB::table('member_roles')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function evidenceFileTypes()
    {
        $data = DB::table('evidence_file_types')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function activityStatuses()
    {
        $data = DB::table('activity_statuses')
            ->select(['id', 'code', 'name'])
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function lecturers(Request $request)
    {
        $query = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->select([
                'l.id',
                'l.code',
                'l.full_name',
                'l.department_id',
                'd.name as department_name',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('l.full_name', 'like', '%' . $search . '%')
                    ->orWhere('l.code', 'like', '%' . $search . '%');
            });
        }

        $data = $query
            ->orderBy('l.full_name')
            ->limit(50)
            ->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function journals(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $activeOnly = $request->boolean('active', true);

        $today = Carbon::now()->toDateString();
        $latestRanking = DB::table('journal_rankings')
            ->select('journal_id', DB::raw('MAX(effective_from) as effective_from'))
            ->where('effective_from', '<=', $today)
            ->groupBy('journal_id');

        $query = DB::table('journals as j')
            ->leftJoinSub($latestRanking, 'lr', 'lr.journal_id', '=', 'j.id')
            ->leftJoin('journal_rankings as jr', function ($join) {
                $join->on('jr.journal_id', '=', 'j.id')
                    ->on('jr.effective_from', '=', 'lr.effective_from');
            })
            ->select([
                'j.id',
                'j.name',
                'j.address',
                'j.issn',
                'j.is_active',
                'jr.rank as current_rank',
                'jr.effective_from as current_rank_effective_from',
            ])
            ->when($activeOnly, function ($q) {
                $q->where('j.is_active', 1);
            })
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('j.name', 'like', $like)
                        ->orWhere('j.issn', 'like', $like);
                });
            })
            ->orderBy('j.name')
            ->limit(50);

        $data = $query->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }
}
