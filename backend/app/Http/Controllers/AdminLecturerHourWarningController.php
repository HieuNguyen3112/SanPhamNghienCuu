<?php

namespace App\Http\Controllers;

use App\Support\AcademicYearResolver;
use App\Support\AuditLogger;
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
        $approvedHoursByLecturer = $this->approvedHoursByLecturerYearQuery(
            (int) $year['id'],
            $facultyId,
            $hoursStageId
        );

        $rows = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoinSub($approvedHoursByLecturer, 'hours_src', function ($join) {
                $join->on('hours_src.lecturer_id', '=', 'l.id');
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
                DB::raw('COALESCE(hours_src.approved_hours, 0) as approved_hours'),
                'lyh.last_notified_at',
            ])
            ->get();

        $entries = collect($rows)->map(function (object $row) use ($requiredHours, $year) {
            $currentHours = (float) ($row->approved_hours ?? 0);
            $remaining = max($requiredHours - $currentHours, 0);
            $severity = $this->resolveSeverity($remaining, $requiredHours);

            $hasRequested = $this->hasRequestedWarning($row->last_notified_at ?? null);
            $lastRequestedAt = $hasRequested ? $row->last_notified_at : null;

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
                    'last_notified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        } else {
            DB::table('lecturer_yearly_hours')
                ->where('id', $existing->id)
                ->update([
                    'last_notified_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        $lecturer = DB::table('lecturers')->where('id', $lecturerId)->select(['id', 'code', 'full_name'])->first();
        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'HOURS_WARNING_SENT',
            'action_label' => 'Truong gui canh bao gio nghien cuu',
            'target_type' => 'lecturer_yearly_hours',
            'target_id' => $lecturerId,
            'target_display' => trim(((string) ($lecturer->code ?? '')) . ' - ' . ((string) ($lecturer->full_name ?? ''))),
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'academic_year_identifier' => $year['code'],
                'reason_code' => $validated['reason_code'],
                'reason_note' => $validated['reason_note'] ?? null,
            ],
        ], $request->user());

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

    private function hasRequestedWarning($lastNotifiedAt): bool
    {
        return ! empty($lastNotifiedAt);
    }

    private function approvedHoursByLecturerYearQuery(int $academicYearId, ?int $facultyId, ?int $hoursStageId)
    {
        if (! $hoursStageId) {
            return DB::table('research_activities as ra')
                ->whereRaw('1 = 0')
                ->selectRaw('0 as lecturer_id, 0 as approved_hours');
        }

        $query = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ra.id', '=', 'ram.activity_id')
            ->leftJoin('activity_approvals as aa_hours', function ($join) use ($hoursStageId) {
                $join->on('aa_hours.activity_id', '=', 'ra.id')
                    ->where('aa_hours.stage_id', '=', $hoursStageId);
            })
            ->leftJoin('activity_member_approvals as ama_hours', function ($join) use ($hoursStageId) {
                $join->on('ama_hours.activity_id', '=', 'ra.id')
                    ->on('ama_hours.lecturer_id', '=', 'ram.lecturer_id')
                    ->where('ama_hours.stage_id', '=', $hoursStageId);
            })
            ->join('lecturers as l', 'l.id', '=', 'ram.lecturer_id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ra.academic_year_id', $academicYearId)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->whereRaw("COALESCE(ama_hours.status, aa_hours.status) = 'approved'");

        if ($facultyId) {
            $query->where('d.faculty_id', $facultyId);
        }

        return $query
            ->selectRaw('ram.lecturer_id as lecturer_id')
            ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as approved_hours')
            ->groupBy('ram.lecturer_id');
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
