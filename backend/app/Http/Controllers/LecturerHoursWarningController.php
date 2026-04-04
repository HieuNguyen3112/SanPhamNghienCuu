<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerHoursWarningIndexRequest;
use App\Http\Requests\Lecturer\LecturerHoursWarningSeenRequest;
use App\Support\AcademicYearResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerHoursWarningController extends Controller
{
    public function index(LecturerHoursWarningIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();
        $academicYear = $this->resolveAcademicYear($filters['academic_year_id'] ?? null);
        if (! $academicYear) {
            return response()->json(['message' => 'academic year not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $requiredHours = $this->requiredHours((int) $academicYear->id);
        $hoursStageId = $this->resolveStageId('hours');
        $assistantStageId = $this->resolveStageId('assistant');
        $managerStageId = $this->resolveStageId('manager');

        $totals = $hoursStageId
            ? $this->summaryTotals($lecturer->id, (int) $academicYear->id, $hoursStageId)
            : ['approved_hours' => 0.0, 'pending_hours' => 0.0, 'rejected_hours' => 0.0];

        $deadlineDate = $academicYear->end_date ? Carbon::parse($academicYear->end_date)->toDateString() : null;
        $daysRemaining = $deadlineDate
            ? now()->startOfDay()->diffInDays(Carbon::parse($deadlineDate)->startOfDay(), false)
            : null;

        $eligibleStats = $this->eligibleStats(
            $lecturer->id,
            (int) $academicYear->id,
            $assistantStageId,
            $managerStageId,
            $hoursStageId
        );

        $warnings = $this->buildWarnings(
            $academicYear->code,
            $requiredHours,
            $totals,
            $eligibleStats,
            $deadlineDate,
            $daysRemaining
        );

        $warnings = $this->attachStates($lecturer->id, (int) $academicYear->id, $warnings);
        $warnings = $this->appendResolvedWarnings($lecturer->id, (int) $academicYear->id, $warnings);
        $tabCounts = $this->tabCounts($warnings);

        $tab = $filters['tab'] ?? 'all';
        $filtered = $this->applyTabFilter($warnings, $tab);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));
        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'summary' => [
                    'academic_year_id' => (int) $academicYear->id,
                    'academic_year_code' => $academicYear->code,
                    'required_hours' => $requiredHours,
                    'approved_hours' => $totals['approved_hours'],
                    'pending_hours' => $totals['pending_hours'],
                    'rejected_hours' => $totals['rejected_hours'],
                    'total_hours_current' => $totals['approved_hours'] + $totals['pending_hours'],
                    'shortage_hours' => max($requiredHours - $totals['approved_hours'], 0.0),
                    'deadline_date' => $deadlineDate,
                    'days_remaining' => $daysRemaining,
                ],
                'tab_counts' => $tabCounts,
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
                'suggestions' => $this->buildSuggestions($warnings),
            ],
        ], Response::HTTP_OK);
    }

    public function markSeen(LecturerHoursWarningSeenRequest $request, int $warningId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $warning = DB::table('lecturer_hour_warnings')
            ->where('id', $warningId)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $warning) {
            return response()->json(['message' => 'warning not found'], Response::HTTP_NOT_FOUND);
        }

        $now = now();
        $status = $warning->status_key;
        if ($status !== 'resolved' && $status !== 'seen') {
            DB::table('lecturer_hour_warnings')
                ->where('id', $warningId)
                ->update([
                    'status_key' => 'seen',
                    'seen_at' => $now,
                    'updated_at' => $now,
                ]);
            $status = 'seen';
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'id' => $warningId,
                'status_key' => $status,
                'seen_at' => $warning->seen_at ?? $now->toDateTimeString(),
            ],
        ], Response::HTTP_OK);
    }

    public function markResolved(Request $request, int $warningId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $warning = DB::table('lecturer_hour_warnings')
            ->where('id', $warningId)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $warning) {
            return response()->json(['message' => 'warning not found'], Response::HTTP_NOT_FOUND);
        }

        if ($warning->status_key !== 'resolved') {
            $now = now();
            DB::table('lecturer_hour_warnings')
                ->where('id', $warningId)
                ->update([
                    'status_key' => 'resolved',
                    'resolved_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'id' => $warningId,
                'status_key' => 'resolved',
            ],
        ], Response::HTTP_OK);
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function resolveAcademicYear(?int $academicYearId)
    {
        return AcademicYearResolver::resolve($academicYearId);
    }

    private function requiredHours(int $academicYearId): float
    {
        $value = DB::table('workload_quotas')
            ->where('academic_year_id', $academicYearId)
            ->value('required_hours');

        return $value !== null ? (float) $value : 0.0;
    }

    private function resolveStageId(string $code): ?int
    {
        $id = DB::table('approval_stages')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function summaryTotals(int $lecturerId, int $academicYearId, int $hoursStageId): array
    {
        $row = $this->hoursApprovalQuery($lecturerId, $academicYearId, $hoursStageId)
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(ama.status, aa.status) = 'approved' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as approved_hours")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(ama.status, aa.status) = 'pending' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as pending_hours")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(ama.status, aa.status) = 'rejected' THEN COALESCE(ram.hours_assigned, 0) ELSE 0 END), 0) as rejected_hours")
            ->first();

        return [
            'approved_hours' => $row?->approved_hours !== null ? (float) $row->approved_hours : 0.0,
            'pending_hours' => $row?->pending_hours !== null ? (float) $row->pending_hours : 0.0,
            'rejected_hours' => $row?->rejected_hours !== null ? (float) $row->rejected_hours : 0.0,
        ];
    }

    private function hoursApprovalQuery(int $lecturerId, int $academicYearId, int $hoursStageId)
    {
        return DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->leftJoin('activity_member_approvals as ama', function ($join) use ($lecturerId, $hoursStageId) {
                $join->on('ama.activity_id', '=', 'ra.id')
                    ->where('ama.lecturer_id', '=', $lecturerId)
                    ->where('ama.stage_id', '=', $hoursStageId);
            })
            ->where('aa.stage_id', $hoursStageId)
            ->where('ra.academic_year_id', $academicYearId);
    }

    private function eligibleStats(int $lecturerId, int $academicYearId, ?int $assistantStageId, ?int $managerStageId, ?int $hoursStageId): array
    {
        if (! $assistantStageId || ! $managerStageId || ! $hoursStageId) {
            return [
                'not_submitted' => 0,
                'pending' => 0,
                'rejected' => 0,
            ];
        }

        $base = $this->eligibleWorkQuery($lecturerId, $academicYearId, $assistantStageId, $managerStageId, $hoursStageId);

        $notSubmitted = (clone $base)
            ->whereRaw('COALESCE(ama_hours.status, aa_hours.status) IS NULL')
            ->distinct()
            ->count('ra.id');

        $pending = (clone $base)
            ->whereRaw("COALESCE(ama_hours.status, aa_hours.status) = 'pending'")
            ->distinct()
            ->count('ra.id');

        $rejected = (clone $base)
            ->whereRaw("COALESCE(ama_hours.status, aa_hours.status) = 'rejected'")
            ->distinct()
            ->count('ra.id');

        return [
            'not_submitted' => (int) $notSubmitted,
            'pending' => (int) $pending,
            'rejected' => (int) $rejected,
        ];
    }

    private function eligibleWorkQuery(int $lecturerId, int $academicYearId, int $assistantStageId, int $managerStageId, int $hoursStageId)
    {
        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($assistantStageId) {
                $join->on('aa_assistant.activity_id', '=', 'ra.id')
                    ->where('aa_assistant.stage_id', '=', $assistantStageId);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($managerStageId) {
                $join->on('aa_manager.activity_id', '=', 'ra.id')
                    ->where('aa_manager.stage_id', '=', $managerStageId);
            })
            ->leftJoin('activity_approvals as aa_hours', function ($join) use ($hoursStageId) {
                $join->on('aa_hours.activity_id', '=', 'ra.id')
                    ->where('aa_hours.stage_id', '=', $hoursStageId);
            })
            ->leftJoin('activity_member_approvals as ama_hours', function ($join) use ($lecturerId, $hoursStageId) {
                $join->on('ama_hours.activity_id', '=', 'ra.id')
                    ->where('ama_hours.lecturer_id', '=', $lecturerId)
                    ->where('ama_hours.stage_id', '=', $hoursStageId);
            })
            ->where('ram.lecturer_id', $lecturerId)
            ->where('ra.academic_year_id', $academicYearId)
            ->where('aa_assistant.status', 'approved')
            ->where('aa_manager.status', 'approved');
    }



    private function buildWarnings(
        string $academicYearCode,
        float $requiredHours,
        array $totals,
        array $eligibleStats,
        ?string $deadlineDate,
        ?int $daysRemaining
    ): array {
        $warnings = [];
        $shortage = max($requiredHours - $totals['approved_hours'], 0.0);
        $nowIso = now()->toDateTimeString();

        if ($requiredHours > 0 && $shortage > 0) {
            $warnings[] = [
                'type_key' => 'missing_hours',
                'severity_key' => 'danger',
                'title' => 'Thiếu giờ NCKH',
                'message' => 'Bạn còn thiếu ' . (int) round($shortage) . ' giờ NCKH để đạt định mức năm học ' . $academicYearCode . '.',
                'updated_at' => $nowIso,
                'deadline_at' => $deadlineDate,
                'action' => [
                    'label' => 'Tính giờ NCKH',
                    'route_path' => '/hours/calculate',
                    'external_url' => null,
                ],
            ];
        }

        if ($deadlineDate && $daysRemaining !== null) {
            if ($daysRemaining < 0) {
                $warnings[] = [
                    'type_key' => 'deadline_passed',
                    'severity_key' => 'danger',
                    'title' => 'Đã hết hạn kê khai giờ NCKH',
                    'message' => 'Hạn kê khai đã kết thúc vào ngày ' . $deadlineDate . '.',
                    'updated_at' => $nowIso,
                    'deadline_at' => $deadlineDate,
                    'action' => [
                        'label' => 'Xem công trình cá nhân',
                        'route_path' => '/works/personal',
                        'external_url' => null,
                    ],
                ];
            } elseif ($daysRemaining <= 10) {
                $warnings[] = [
                    'type_key' => 'deadline_near',
                    'severity_key' => 'warning',
                    'title' => 'Sắp hết hạn kê khai giờ NCKH',
                    'message' => 'Thời hạn kê khai còn ' . $daysRemaining . ' ngày (đến ' . $deadlineDate . ').',
                    'updated_at' => $nowIso,
                    'deadline_at' => $deadlineDate,
                    'action' => [
                        'label' => 'Kê khai công trình',
                        'route_path' => '/works/personal',
                        'external_url' => null,
                    ],
                ];
            }
        }

        if ($eligibleStats['not_submitted'] > 0) {
            $warnings[] = [
                'type_key' => 'approved_not_submitted',
                'severity_key' => 'warning',
                'title' => 'Công trình chưa gửi duyệt giờ',
                'message' => 'Có ' . $eligibleStats['not_submitted'] . ' công trình đã duyệt nội dung nhưng chưa gửi xét duyệt giờ.',
                'updated_at' => $nowIso,
                'deadline_at' => null,
                'action' => [
                    'label' => 'Tính giờ NCKH',
                    'route_path' => '/hours/calculate',
                    'external_url' => null,
                ],
            ];
        }

        if ($eligibleStats['pending'] > 0) {
            $warnings[] = [
                'type_key' => 'hours_pending',
                'severity_key' => 'info',
                'title' => 'Đang chờ duyệt giờ NCKH',
                'message' => 'Bạn có ' . $eligibleStats['pending'] . ' công trình đang chờ duyệt giờ NCKH.',
                'updated_at' => $nowIso,
                'deadline_at' => null,
                'action' => [
                    'label' => 'Xem lịch sử xét duyệt',
                    'route_path' => '/hours/personal',
                    'external_url' => null,
                ],
            ];
        }

        if ($eligibleStats['rejected'] > 0) {
            $warnings[] = [
                'type_key' => 'hours_rejected',
                'severity_key' => 'warning',
                'title' => 'Có công trình bị từ chối',
                'message' => 'Có ' . $eligibleStats['rejected'] . ' công trình bị từ chối duyệt giờ NCKH.',
                'updated_at' => $nowIso,
                'deadline_at' => null,
                'action' => [
                    'label' => 'Xem lịch sử xét duyệt',
                    'route_path' => '/hours/personal',
                    'external_url' => null,
                ],
            ];
        }

        return $warnings;
    }
    private function attachStates(int $lecturerId, int $academicYearId, array $warnings): array
    {
        if (empty($warnings)) {
            return [];
        }

        $typeKeys = array_values(array_unique(array_map(fn($w) => $w['type_key'], $warnings)));
        $existing = DB::table('lecturer_hour_warnings')
            ->where('lecturer_id', $lecturerId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('type_key', $typeKeys)
            ->get()
            ->keyBy('type_key');

        $now = now();
        $stateMap = [];

        foreach ($warnings as $warning) {
            $state = $existing[$warning['type_key']] ?? null;
            if (! $state) {
                $id = DB::table('lecturer_hour_warnings')->insertGetId([
                    'lecturer_id' => $lecturerId,
                    'academic_year_id' => $academicYearId,
                    'type_key' => $warning['type_key'],
                    'status_key' => 'unseen',
                    'seen_at' => null,
                    'resolved_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $state = (object) [
                    'id' => $id,
                    'status_key' => 'unseen',
                    'seen_at' => null,
                    'resolved_at' => null,
                ];
            }

            $stateMap[$warning['type_key']] = $state;
        }

        return array_map(function ($warning) use ($stateMap) {
            $state = $stateMap[$warning['type_key']] ?? null;
            $statusKey = $state?->status_key ?? 'unseen';

            return [
                'id' => $state?->id ?? 0,
                'type_key' => $warning['type_key'],
                'severity_key' => $warning['severity_key'],
                'title' => $warning['title'],
                'message' => $warning['message'],
                'status_key' => $statusKey,
                'updated_at' => $warning['updated_at'],
                'deadline_at' => $warning['deadline_at'],
                'action' => $warning['action'],
            ];
        }, $warnings);
    }

    private function tabCounts(array $warnings): array
    {
        $danger = 0;
        $warningCount = 0;
        $done = 0;

        foreach ($warnings as $warning) {
            if ($warning['severity_key'] === 'danger') {
                $danger++;
            }
            if ($warning['severity_key'] === 'warning') {
                $warningCount++;
            }
            if (($warning['status_key'] ?? 'unseen') === 'resolved') {
                $done++;
            }
        }

        return [
            'all' => count($warnings),
            'danger' => $danger,
            'warning' => $warningCount,
            'done' => $done,
        ];
    }

    private function applyTabFilter(array $warnings, string $tab): array
    {
        if ($tab === 'danger') {
            return array_values(array_filter($warnings, fn($w) => $w['severity_key'] === 'danger' && ($w['status_key'] ?? 'unseen') !== 'resolved'));
        }
        if ($tab === 'warning') {
            return array_values(array_filter($warnings, fn($w) => $w['severity_key'] === 'warning' && ($w['status_key'] ?? 'unseen') !== 'resolved'));
        }
        if ($tab === 'done') {
            return array_values(array_filter($warnings, fn($w) => ($w['status_key'] ?? 'unseen') === 'resolved'));
        }
        return $warnings;
    }

    private function buildSuggestions(array $warnings): array
    {
        $suggestions = [];
        $routes = [];

        foreach ($warnings as $warning) {
            $route = $warning['action']['route_path'] ?? null;
            if (! $route || in_array($route, $routes, true)) {
                continue;
            }
            $routes[] = $route;

            $suggestions[] = [
                'id' => count($suggestions) + 1,
                'title' => $warning['title'],
                'description' => $warning['message'],
                'cta_label' => $warning['action']['label'] ?? null,
                'cta_to' => $route,
            ];
        }

        return $suggestions;
    }

    private function appendResolvedWarnings(int $lecturerId, int $academicYearId, array $warnings): array
    {
        $activeTypes = array_values(array_unique(array_map(fn($w) => $w['type_key'], $warnings)));

        $stored = DB::table('lecturer_hour_warnings')
            ->where('lecturer_id', $lecturerId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy('type_key');

        if ($stored->isEmpty()) {
            return $warnings;
        }

        $now = now();
        $resolvedItems = [];

        foreach ($stored as $typeKey => $row) {
            if (in_array($typeKey, $activeTypes, true)) {
                continue;
            }

            if ($row->status_key !== 'resolved') {
                DB::table('lecturer_hour_warnings')
                    ->where('id', $row->id)
                    ->update([
                        'status_key' => 'resolved',
                        'resolved_at' => $row->resolved_at ?? $now,
                        'updated_at' => $now,
                    ]);
                $row->status_key = 'resolved';
            }

            $meta = $this->warningMeta($typeKey);
            $resolvedItems[] = [
                'id' => (int) $row->id,
                'type_key' => $typeKey,
                'severity_key' => $meta['severity_key'],
                'title' => $meta['title'],
                'message' => $meta['resolved_message'],
                'status_key' => 'resolved',
                'updated_at' => $row->updated_at ?? $now->toDateTimeString(),
                'deadline_at' => null,
                'action' => $meta['action'],
            ];
        }

        return array_values(array_merge($warnings, $resolvedItems));
    }



    private function warningMeta(string $typeKey): array
    {
        return match ($typeKey) {
            'missing_hours' => [
                'severity_key' => 'danger',
                'title' => 'Thiếu giờ NCKH',
                'resolved_message' => 'Cảnh báo thiếu giờ NCKH đã được xử lý.',
                'action' => [
                    'label' => 'Tính giờ NCKH',
                    'route_path' => '/hours/calculate',
                    'external_url' => null,
                ],
            ],
            'deadline_near' => [
                'severity_key' => 'warning',
                'title' => 'Sắp hết hạn kê khai giờ NCKH',
                'resolved_message' => 'Cảnh báo sắp hết hạn kê khai đã được xử lý.',
                'action' => [
                    'label' => 'Kê khai công trình',
                    'route_path' => '/works/personal',
                    'external_url' => null,
                ],
            ],
            'deadline_passed' => [
                'severity_key' => 'danger',
                'title' => 'Đã hết hạn kê khai giờ NCKH',
                'resolved_message' => 'Cảnh báo hết hạn kê khai đã được xử lý.',
                'action' => [
                    'label' => 'Xem công trình cá nhân',
                    'route_path' => '/works/personal',
                    'external_url' => null,
                ],
            ],
            'approved_not_submitted' => [
                'severity_key' => 'warning',
                'title' => 'Công trình chưa gửi duyệt giờ',
                'resolved_message' => 'Cảnh báo công trình chưa gửi duyệt giờ đã được xử lý.',
                'action' => [
                    'label' => 'Tính giờ NCKH',
                    'route_path' => '/hours/calculate',
                    'external_url' => null,
                ],
            ],
            'hours_pending' => [
                'severity_key' => 'info',
                'title' => 'Đang chờ duyệt giờ NCKH',
                'resolved_message' => 'Cảnh báo chờ duyệt giờ đã được xử lý.',
                'action' => [
                    'label' => 'Xem lịch sử xét duyệt',
                    'route_path' => '/hours/personal',
                    'external_url' => null,
                ],
            ],
            'hours_rejected' => [
                'severity_key' => 'warning',
                'title' => 'Có công trình bị từ chối',
                'resolved_message' => 'Cảnh báo công trình bị từ chối đã được xử lý.',
                'action' => [
                    'label' => 'Xem lịch sử xét duyệt',
                    'route_path' => '/hours/personal',
                    'external_url' => null,
                ],
            ],
            default => [
                'severity_key' => 'info',
                'title' => 'Cảnh báo giờ NCKH',
                'resolved_message' => 'Cảnh báo giờ NCKH đã được xử lý.',
                'action' => [
                    'label' => null,
                    'route_path' => null,
                    'external_url' => null,
                ],
            ],
        };
    }
}
