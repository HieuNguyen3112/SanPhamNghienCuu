<?php

namespace App\Http\Controllers;

use App\Support\AcademicYearResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminLecturerHourWarningController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'faculty_identifier' => ['nullable', 'string', 'max:50'],
            'academic_year_identifier' => ['nullable', 'string', 'max:20'],
            'severity_filter' => ['nullable', 'string', 'in:ALL,MILD,MODERATE,SEVERE'],
            'notification_state_filter' => ['nullable', 'string', 'in:ALL,NOT_REQUESTED,REQUESTED'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $year = $this->resolveAcademicYear($validated['academic_year_identifier'] ?? null);
        if (! $year) {
            return response()->json([
                'message' => 'academic_year_identifier not found',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $requiredHours = (float) (DB::table('workload_quotas')
            ->where('academic_year_id', $year['id'])
            ->value('required_hours') ?? 600);

        $facultyId = $this->resolveFacultyId($validated['faculty_identifier'] ?? null);
        $hoursStageId = $this->resolveHoursStageId();

        $rows = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('research_activity_members as ram', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('research_activities as ra', function ($join) use ($year) {
                $join->on('ra.id', '=', 'ram.activity_id')
                    ->where('ra.academic_year_id', '=', $year['id']);
            })
            ->leftJoin('activity_approvals as aa', function ($join) use ($hoursStageId) {
                $join->on('aa.activity_id', '=', 'ra.id');
                if ($hoursStageId) {
                    $join->where('aa.stage_id', '=', $hoursStageId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('lecturer_yearly_hours as lyh', function ($join) use ($year) {
                $join->on('lyh.lecturer_id', '=', 'l.id')
                    ->where('lyh.academic_year_id', '=', $year['id']);
            })
            ->when($facultyId, function ($query) use ($facultyId) {
                $query->where('f.id', $facultyId);
            })
            ->when(! empty($validated['keyword']), function ($query) use ($validated) {
                $keyword = trim($validated['keyword']);
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('l.code', 'like', '%' . $keyword . '%')
                        ->orWhere('l.full_name', 'like', '%' . $keyword . '%');
                });
            })
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'f.id as faculty_id',
                'f.code as faculty_code',
                'f.name as faculty_name',
                DB::raw("COALESCE(SUM(CASE WHEN aa.status = 'approved' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as approved_hours"),
                DB::raw("COALESCE(SUM(CASE WHEN aa.status = 'pending' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as pending_hours"),
                DB::raw("COALESCE(SUM(CASE WHEN aa.status = 'rejected' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as rejected_hours"),
                'lyh.created_at as warning_created_at',
                'lyh.updated_at as warning_updated_at',
            ])
            ->groupBy(
                'l.id',
                'l.code',
                'l.full_name',
                'f.id',
                'f.code',
                'f.name',
                'lyh.created_at',
                'lyh.updated_at'
            )
            ->get();

        $entries = collect($rows)->map(function ($row) use ($requiredHours, $year) {
            $currentHours = (float) ($row->approved_hours ?? 0);
            $remaining = max($requiredHours - $currentHours, 0);
            $severity = $this->resolveSeverity($remaining, $requiredHours);

            $hasRequested = $this->hasRequestedWarning($row->warning_created_at, $row->warning_updated_at);
            $lastRequestedAt = $hasRequested ? $row->warning_updated_at : null;

            return [
                'lecturer_identifier' => (string) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'faculty_identifier' => $row->faculty_id ? 'FACULTY_' . (int) $row->faculty_id : 'FACULTY_0',
                'faculty_short_name' => $row->faculty_code ?? '',
                'academic_year_identifier' => $year['code'],
                'required_hours' => $requiredHours,
                'current_hours' => $currentHours,
                'remaining_hours' => $remaining,
                'severity' => $severity,
                'has_requested_warning' => $hasRequested,
                'last_requested_at' => $lastRequestedAt,
                'last_requested_reason_code' => null,
                'last_requested_reason_note' => null,
            ];
        })
            ->filter(function ($row) {
                return $row['remaining_hours'] > 0;
            })
            ->values();

        $entries = $this->applyFilters(
            $entries,
            $validated['severity_filter'] ?? 'ALL',
            $validated['notification_state_filter'] ?? 'ALL'
        );

        $summary = $this->summary($entries, $requiredHours);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'faculty_option_list' => $this->facultyOptions(),
                'academic_year_option_list' => $this->academicYearOptions(),
                'summary_statistics' => $summary,
                'entry_list' => $entries->values()->all(),
            ],
        ], Response::HTTP_OK);
    }

    public function sendWarning(Request $request, int $lecturerId)
    {
        $validated = $request->validate([
            'academic_year_identifier' => ['required', 'string', 'max:20'],
            'reason_code' => ['required', 'string', 'in:MISSING_HOURS,DEADLINE_NEAR,DEADLINE_PASSED,MISSING_EVIDENCE,OTHER'],
            'reason_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['reason_code'] === 'OTHER' && empty($validated['reason_note'])) {
            return response()->json([
                'message' => 'reason_note is required when reason_code is OTHER',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $year = $this->resolveAcademicYear($validated['academic_year_identifier']);
        if (! $year) {
            return response()->json([
                'message' => 'academic_year_identifier not found',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = DB::table('lecturer_yearly_hours')
            ->where('lecturer_id', $lecturerId)
            ->where('academic_year_id', $year['id'])
            ->first();

        $now = now();

        if (! $existing) {
            DB::table('lecturer_yearly_hours')->updateOrInsert(
                [
                    'lecturer_id' => $lecturerId,
                    'academic_year_id' => $year['id'],
                ],
                [
                    'hours_total' => 0,
                    'created_at' => $now->copy()->subMinute(),
                    'updated_at' => $now,
                ]
            );
        } else {
            DB::table('lecturer_yearly_hours')
                ->where('id', $existing->id)
                ->update(['updated_at' => $now]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
        ], Response::HTTP_OK);
    }

    private function resolveAcademicYear(?string $identifier): ?array
    {
        if ($identifier) {
            $row = DB::table('academic_years')->where('code', $identifier)->first();
            if ($row) {
                return ['id' => (int) $row->id, 'code' => $row->code];
            }
        }

        $row = AcademicYearResolver::current();

        if (! $row) {
            return null;
        }

        return ['id' => (int) $row->id, 'code' => $row->code];
    }

    private function resolveFacultyId(?string $identifier): ?int
    {
        if (! $identifier || $identifier === 'ALL_FACULTIES') {
            return null;
        }

        if (preg_match('/^FACULTY_(\d+)$/', $identifier, $matches)) {
            return (int) $matches[1];
        }

        return DB::table('faculties')->where('code', $identifier)->value('id');
    }

    private function resolveHoursStageId(): ?int
    {
        return DB::table('approval_stages')->where('code', 'hours')->value('id');
    }

    private function resolveSeverity(float $remaining, float $required): string
    {
        if ($required <= 0) {
            return 'MILD';
        }

        $ratio = $remaining / $required;
        if ($ratio >= 0.5) {
            return 'SEVERE';
        }
        if ($ratio >= 0.2) {
            return 'MODERATE';
        }
        return 'MILD';
    }

    private function hasRequestedWarning($createdAt, $updatedAt): bool
    {
        if (! $createdAt || ! $updatedAt) {
            return false;
        }

        $created = Carbon::parse($createdAt);
        $updated = Carbon::parse($updatedAt);

        return $updated->gt($created->addMinute());
    }

    private function applyFilters($entries, string $severity, string $notificationState)
    {
        $filtered = $entries;

        if ($severity !== 'ALL') {
            $filtered = $filtered->filter(function ($row) use ($severity) {
                return $row['severity'] === $severity;
            });
        }

        if ($notificationState !== 'ALL') {
            $filtered = $filtered->filter(function ($row) use ($notificationState) {
                if ($notificationState === 'REQUESTED') {
                    return $row['has_requested_warning'] === true;
                }
                return $row['has_requested_warning'] === false;
            });
        }

        return $filtered->values();
    }

    private function summary($entries, float $requiredHours): array
    {
        $total = $entries->count();
        $remaining = $entries->sum('remaining_hours');

        return [
            'total_lecturers_not_meeting_standard' => $total,
            'total_remaining_hours_to_meet_standard' => (float) $remaining,
            'average_remaining_hours_to_meet_standard' => $total === 0 ? 0 : (float) round($remaining / $total, 2),
            'minimum_required_hours' => $requiredHours,
        ];
    }

    private function facultyOptions(): array
    {
        return DB::table('faculties')
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                return [
                    'faculty_identifier' => 'FACULTY_' . (int) $row->id,
                    'faculty_short_name' => $row->code,
                    'faculty_full_name' => $row->name,
                ];
            })
            ->all();
    }

    private function academicYearOptions(): array
    {
        return DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                return [
                    'academic_year_identifier' => $row->code,
                    'label' => $row->code,
                ];
            })
            ->all();
    }
}
