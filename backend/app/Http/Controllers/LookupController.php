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
        $today = Carbon::now()->toDateString();

        $data = DB::table('academic_years')
            ->select(['id', 'code', 'start_date', 'end_date', 'is_active'])
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($row) use ($today) {
                return [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'is_active' => (bool) $row->is_active,
                    'is_current' => $row->start_date <= $today && $row->end_date >= $today,
                ];
            });

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

        $types = $query->orderBy('name')->get();

        $typeIds = $types
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $researchHoursByTypeId = [];
        $maxOccurrencesByTypeId = [];
        if (! empty($typeIds)) {
            $today = Carbon::now()->toDateString();

            $rules = DB::table('hour_rules')
                ->whereIn('type_id', $typeIds)
                ->where('is_active', 1)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->orderByDesc('effective_from')
                ->orderByDesc('version')
                ->orderByDesc('id')
                ->get(['type_id', 'hours_total_per_activity', 'hours_per_occurrence', 'max_occurrences_per_year']);

            foreach ($rules as $rule) {
                $typeId = (int) ($rule->type_id ?? 0);
                if ($typeId <= 0 || array_key_exists($typeId, $researchHoursByTypeId)) {
                    continue;
                }

                $hours = $rule->hours_total_per_activity ?? $rule->hours_per_occurrence;
                if ($hours === null) {
                    continue;
                }

                $researchHoursByTypeId[$typeId] = (float) $hours;
                $maxOccurrencesByTypeId[$typeId] = $rule->max_occurrences_per_year !== null
                    ? (int) $rule->max_occurrences_per_year
                    : null;
            }
        }

        $data = $types->map(function ($row) use ($researchHoursByTypeId, $maxOccurrencesByTypeId) {
            $typeId = (int) $row->id;

            return [
                'id' => $typeId,
                'kind_id' => (int) $row->kind_id,
                'code' => $row->code,
                'name' => $row->name,
                'research_hours' => $researchHoursByTypeId[$typeId] ?? null,
                'max_occurrences_per_year' => $maxOccurrencesByTypeId[$typeId] ?? null,
            ];
        });

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
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->select([
                'l.id',
                'l.code',
                'l.full_name',
                'l.department_id',
                'd.name as department_name',
                'd.faculty_id',
                'f.name as faculty_name',
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

        $query = DB::table('journals as j')
            ->select([
                'j.id',
                'j.name',
                'j.address',
                'j.issn',
                'j.country',
                'j.notes',
                'j.source_name',
                'j.point_min',
                'j.point_max',
                'j.classification',
                'j.research_hours',
                'j.is_active',
            ])
            ->when($activeOnly, fn($q) => $q->where('j.is_active', 1))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('j.name', 'like', $like)
                        ->orWhere('j.issn', 'like', $like);
                });
            })
            ->orderBy('j.name')
            ->limit(50);

        return response()->json(['data' => $query->get()], Response::HTTP_OK);
    }
}
