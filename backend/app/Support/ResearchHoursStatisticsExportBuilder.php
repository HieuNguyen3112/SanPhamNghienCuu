<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ResearchHoursStatisticsExportBuilder
{
    public static function resolveAcademicYearId(?int $requestedAcademicYearId, ?int $facultyId = null): int
    {
        if ($requestedAcademicYearId && $requestedAcademicYearId > 0) {
            return $requestedAcademicYearId;
        }

        $currentAcademicYearId = AcademicYearResolver::currentId();
        if ($currentAcademicYearId) {
            return (int) $currentAcademicYearId;
        }

        $query = DB::table('activity_approvals as aa')
            ->join('approval_stages as st', 'aa.stage_id', '=', 'st.id')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->where('st.code', 'hours')
            ->where('aa.status', 'approved')
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($builder) {
                $builder->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->orderByDesc('ay.is_active')
            ->orderByDesc('ay.start_date');

        if ($facultyId) {
            $query->where('d.faculty_id', $facultyId);
        }

        $academicYearId = $query->value('ra.academic_year_id');
        if ($academicYearId) {
            return (int) $academicYearId;
        }

        return (int) (DB::table('academic_years')->orderByDesc('id')->value('id') ?? 0);
    }

    public static function resolveAcademicYearCode(int $academicYearId): string
    {
        if ($academicYearId <= 0) {
            return '';
        }

        return (string) (DB::table('academic_years')->where('id', $academicYearId)->value('code') ?? '');
    }

    public static function build(array $tableRows, int $academicYearId, ?int $facultyId = null): array
    {
        $normalizedRows = collect($tableRows)
            ->map(static function (array $row) use ($academicYearId) {
                return [
                    'lecturer_id' => (int) ($row['lecturer_id'] ?? 0),
                    'lecturer_code' => (string) ($row['lecturer_code'] ?? ''),
                    'lecturer_full_name' => (string) ($row['lecturer_full_name'] ?? $row['lecturer_name'] ?? ''),
                    'faculty_name' => (string) ($row['faculty_name'] ?? ''),
                    'department_name' => (string) ($row['department_name'] ?? ''),
                    'academic_year_code' => (string) ($row['academic_year_code'] ?? self::resolveAcademicYearCode($academicYearId)),
                    'hours_total' => (float) ($row['hours_total'] ?? $row['total_hours'] ?? 0),
                ];
            })
            ->all();

        $lecturerIds = collect($normalizedRows)
            ->pluck('lecturer_id')
            ->filter(static fn ($id) => (int) $id > 0)
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($normalizedRows === [] || $lecturerIds === [] || $academicYearId <= 0) {
            return LecturerHoursSummaryReportBuilder::build($normalizedRows, []);
        }

        $activityRows = DB::table('activity_approvals as aa')
            ->join('approval_stages as st', 'aa.stage_id', '=', 'st.id')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', 'ram.activity_id', '=', 'ra.id')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('st.code', 'hours')
            ->where('aa.status', 'approved')
            ->where('ra.academic_year_id', $academicYearId)
            ->whereIn('ram.lecturer_id', $lecturerIds)
            ->where(function ($builder) {
                $builder->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->when($facultyId, static function ($query, int $facultyId) {
                $query->where('d.faculty_id', $facultyId);
            })
            ->select([
                'ram.lecturer_id',
                'ra.id as activity_id',
                'ak.code as kind_code',
                'at.code as type_code',
                'mr.code as member_role_code',
                'ram.hours_assigned',
            ])
            ->get()
            ->map(static function ($row) {
                return [
                    'lecturer_id' => (int) $row->lecturer_id,
                    'activity_id' => (int) $row->activity_id,
                    'kind_code' => (string) ($row->kind_code ?? ''),
                    'type_code' => (string) ($row->type_code ?? ''),
                    'member_role_code' => $row->member_role_code ? (string) $row->member_role_code : null,
                    'hours_assigned' => $row->hours_assigned !== null ? (float) $row->hours_assigned : 0.0,
                ];
            })
            ->all();

        return LecturerHoursSummaryReportBuilder::build(
            $normalizedRows,
            $activityRows,
            count($activityRows) > 0
        );
    }
}
