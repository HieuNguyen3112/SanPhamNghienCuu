<?php

namespace App\Http\Controllers;

use App\Support\WorkflowNotification;
use App\Support\AcademicYearResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FacultyLecturerHourWarningController extends Controller
{
    public function index(Request $request)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'faculty_identifier' => ['nullable', 'string', 'max:50'],
            'academic_year_identifier' => ['nullable', 'string', 'max:20'],
            'severity_filter' => ['nullable', 'string', 'in:ALL,MILD,MODERATE,SEVERE'],
            'notification_state_filter' => ['nullable', 'string', 'in:ALL,NOT_REQUESTED,REQUESTED'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->matchesFacultyIdentifier($validated['faculty_identifier'] ?? null, $scope)) {
            return response()->json(['message' => 'faculty scope mismatch'], Response::HTTP_FORBIDDEN);
        }

        $year = $this->resolveAcademicYear($validated['academic_year_identifier'] ?? null);
        if (! $year) {
            return response()->json([
                'message' => 'academic_year_identifier not found',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $requiredHours = (float) (DB::table('workload_quotas')
            ->where('academic_year_id', $year['id'])
            ->value('required_hours') ?? 600);

        $rows = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->leftJoin('lecturer_yearly_hours as lyh', function ($join) use ($year) {
                $join->on('lyh.lecturer_id', '=', 'l.id')
                    ->where('lyh.academic_year_id', '=', $year['id']);
            })
            ->where('f.id', $scope['faculty_id'])
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
                'lyh.hours_total',
                'lyh.created_at as hours_created_at',
                'lyh.updated_at as hours_updated_at',
            ])
            ->get();

        $entries = collect($rows)->map(function ($row) use ($requiredHours, $year) {
            $currentHours = (float) ($row->hours_total ?? 0);
            $remaining = max($requiredHours - $currentHours, 0);
            $severity = $this->resolveSeverity($remaining, $requiredHours);

            $hasRequested = $this->hasRequestedWarning($row->hours_created_at, $row->hours_updated_at);
            $lastRequestedAt = $hasRequested ? $row->hours_updated_at : null;

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
                'faculty_option_list' => [
                    [
                        'faculty_identifier' => 'FACULTY_' . $scope['faculty_id'],
                        'faculty_short_name' => $scope['faculty_code'],
                        'faculty_full_name' => $scope['faculty_name'],
                    ],
                ],
                'academic_year_option_list' => $this->academicYearOptions(),
                'summary_statistics' => $summary,
                'entry_list' => $entries->values()->all(),
            ],
        ], Response::HTTP_OK);
    }

    public function sendWarning(Request $request, int $lecturerId)
    {
        $scope = $this->resolveFacultyScope($request);
        if (! $scope) {
            return response()->json(['message' => 'faculty scope not found'], Response::HTTP_FORBIDDEN);
        }

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

        $lecturer = DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('l.id', $lecturerId)
            ->select(['l.id', 'f.id as faculty_id'])
            ->first();

        if (! $lecturer || (int) $lecturer->faculty_id !== $scope['faculty_id']) {
            return response()->json(['message' => 'lecturer not in scope'], Response::HTTP_FORBIDDEN);
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

        $reasonLabel = match ($validated['reason_code']) {
            'MISSING_HOURS' => 'thiếu giờ NCKH',
            'DEADLINE_NEAR' => 'sắp đến hạn nộp',
            'DEADLINE_PASSED' => 'đã quá hạn nộp',
            'MISSING_EVIDENCE' => 'thiếu minh chứng',
            default => 'cần rà soát hồ sơ giờ NCKH',
        };

        $detail = trim((string) ($validated['reason_note'] ?? ''));
        $message = 'Khoa đã gửi nhắc nhở giờ NCKH năm học ' . $year['code'] . ' (' . $reasonLabel . ').';
        if ($detail !== '') {
            $message .= ' Ghi chú: ' . $detail;
        }

        WorkflowNotification::notifyLecturer(
            $lecturerId,
            WorkflowNotification::makePayload(
                'hours_warning',
                'Nhắc nhở giờ NCKH',
                $message,
                '/hours/personal_warnings',
                [
                    'lecturer_id' => (int) $lecturerId,
                    'academic_year' => $year['code'],
                    'reason_code' => $validated['reason_code'],
                    'reason_note' => $validated['reason_note'] ?? null,
                ]
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'ok',
        ], Response::HTTP_OK);
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
            ->select(['f.id', 'f.code', 'f.name'])
            ->first();

        if (! $faculty) {
            return null;
        }

        return [
            'faculty_id' => (int) $faculty->id,
            'faculty_code' => $faculty->code ?? '',
            'faculty_name' => $faculty->name,
        ];
    }

    private function matchesFacultyIdentifier(?string $identifier, array $scope): bool
    {
        if (! $identifier || $identifier === 'ALL_FACULTIES') {
            return true;
        }

        if (preg_match('/^FACULTY_(\d+)$/', $identifier, $matches)) {
            return (int) $matches[1] === $scope['faculty_id'];
        }

        return $identifier === $scope['faculty_code'];
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
