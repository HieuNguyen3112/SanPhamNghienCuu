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

        $data = $query->orderBy('name')->get();

        return response()->json(['data' => $data], Response::HTTP_OK);
    }
}
