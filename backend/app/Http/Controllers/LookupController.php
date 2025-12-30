<?php

namespace App\Http\Controllers;

use App\Models\AcademicRank;
use App\Models\Degree;
use App\Models\Department;
use Illuminate\Http\Request;
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
}
