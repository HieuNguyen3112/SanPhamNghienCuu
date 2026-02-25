<?php

namespace App\Services\Hours;

use Illuminate\Support\Facades\DB;

class HoursRecomputeService
{
    private HoursRuleResolver $ruleResolver;
    private HoursAllocator $allocator;

    public function __construct(
        HoursRuleResolver $ruleResolver,
        HoursAllocator $allocator
    ) {
        $this->ruleResolver = $ruleResolver;
        $this->allocator = $allocator;
    }

    public function recomputeActivity(int $activityId, $executedAt = null, bool $persist = true): array
    {
        $timestamp = $executedAt ?? now();

        $activity = DB::table('research_activities as ra')
            ->join('activity_kinds as ak', 'ak.id', '=', 'ra.kind_id')
            ->leftJoin('activity_types as at', 'at.id', '=', 'ra.type_id')
            ->where('ra.id', $activityId)
            ->select([
                'ra.id',
                'ra.kind_id',
                'ra.type_id',
                'ra.academic_year_id',
                'ra.quantity',
                'ak.code as kind_code',
                'at.code as type_code',
            ])
            ->first();

        if (! $activity) {
            return [
                'rule_id' => null,
                'rule_summary' => $this->ruleResolver->formatRuleSummary(null),
                'total_hours_activity' => null,
                'members' => [],
                'formula' => null,
            ];
        }

        $rule = $this->ruleResolver->resolveForActivity(
            (int) $activity->kind_id,
            $activity->type_id ? (int) $activity->type_id : null,
            $activity->academic_year_id ? (int) $activity->academic_year_id : null
        );

        if ($rule) {
            // Prefer type/kind codes from activity to avoid stale/null code joins in some older rows.
            $rule->kind_code = $rule->kind_code ?? $activity->kind_code;
            $rule->type_code = $rule->type_code ?? $activity->type_code;
        }

        $members = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activityId)
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->select([
                'ram.id',
                'ram.lecturer_id',
                'mr.code as member_role_code',
            ])
            ->orderBy('ram.id')
            ->get();

        if ($members->isEmpty()) {
            return [
                'rule_id' => $rule?->id ? (int) $rule->id : null,
                'rule_summary' => $this->ruleResolver->formatRuleSummary($rule),
                'total_hours_activity' => null,
                'members' => [],
                'formula' => null,
            ];
        }

        $calculated = $this->allocator->allocateByRule(
            $rule,
            $activity->quantity ? (int) $activity->quantity : 1,
            $members->all()
        );

        if ($persist && $calculated['total_hours_activity'] !== null) {
            DB::table('research_activities')
                ->where('id', $activityId)
                ->update([
                    'total_hours_calc' => $calculated['total_hours_activity'],
                    'updated_at' => $timestamp,
                ]);

            foreach ($calculated['members'] as $memberUpdate) {
                DB::table('research_activity_members')
                    ->where('id', (int) $memberUpdate['member_row_id'])
                    ->update([
                        'hours_assigned' => $memberUpdate['hours_assigned'],
                        'contribution_share' => $memberUpdate['contribution_share'],
                        'updated_at' => $timestamp,
                    ]);
            }

            DB::table('calculation_logs')->insert([
                'activity_id' => $activityId,
                'executed_at' => $timestamp,
                'rule_id' => $rule ? (int) $rule->id : null,
                'input_snapshot' => json_encode([
                    'kind_code' => $activity->kind_code,
                    'type_code' => $activity->type_code,
                    'distribution_strategy' => $rule?->distribution_strategy,
                    'quantity' => max(1, (int) ($activity->quantity ?? 1)),
                    'member_count' => $members->count(),
                    'members' => array_map(static fn ($member) => [
                        'member_row_id' => (int) $member->id,
                        'lecturer_id' => (int) $member->lecturer_id,
                        'member_role_code' => $member->member_role_code ? (string) $member->member_role_code : null,
                    ], $members->all()),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'result_snapshot' => json_encode([
                    'total_hours_activity' => $calculated['total_hours_activity'],
                    'members' => $calculated['members'],
                    'formula' => $calculated['formula'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'total_hours' => $calculated['total_hours_activity'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return [
            'rule_id' => $rule?->id ? (int) $rule->id : null,
            'rule_summary' => $this->ruleResolver->formatRuleSummary($rule),
            'total_hours_activity' => $calculated['total_hours_activity'],
            'members' => $calculated['members'],
            'formula' => $calculated['formula'] ?? null,
        ];
    }

    public function backfillMissingHoursForLecturer(
        int $lecturerId,
        int $approvedStatusId,
        ?int $academicYearId = null,
        int $limit = 200
    ): array {
        $activityIds = $this->collectBackfillActivityIds($lecturerId, $approvedStatusId, $academicYearId, $limit);

        $recomputed = 0;
        $skipped = 0;
        $processedIds = [];

        foreach ($activityIds as $activityId) {
            $result = $this->recomputeActivity($activityId, now(), true);
            if ($result['total_hours_activity'] === null) {
                $skipped++;
                continue;
            }

            $recomputed++;
            $processedIds[] = $activityId;
        }

        return [
            'recomputed' => $recomputed,
            'skipped' => $skipped,
            'activity_ids' => $processedIds,
        ];
    }

    public function recomputeApprovedActivitiesForLecturer(
        int $lecturerId,
        int $approvedStatusId,
        ?int $academicYearId = null,
        int $limit = 200
    ): array {
        $query = DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram_self', function ($join) use ($lecturerId) {
                $join->on('ram_self.activity_id', '=', 'ra.id')
                    ->where('ram_self.lecturer_id', '=', $lecturerId);
            })
            ->where('ra.status_id', $approvedStatusId)
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram_self.confirmation_status', 'accepted');
            });

        if ($academicYearId) {
            $query->where('ra.academic_year_id', $academicYearId);
        }

        $activityIds = $query
            ->orderByDesc('ra.updated_at')
            ->limit(max(1, $limit))
            ->pluck('ra.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($activityIds)) {
            return [
                'recomputed' => 0,
                'activity_ids' => [],
            ];
        }

        $results = $this->recomputeActivities($activityIds, now());
        $recomputed = 0;
        foreach ($results as $result) {
            if (($result['total_hours_activity'] ?? null) !== null) {
                $recomputed++;
            }
        }

        return [
            'recomputed' => $recomputed,
            'activity_ids' => $activityIds,
        ];
    }

    public function recomputeActivities(array $activityIds, $executedAt = null): array
    {
        $timestamp = $executedAt ?? now();
        $results = [];
        foreach (array_values(array_unique(array_map('intval', $activityIds))) as $activityId) {
            $results[$activityId] = $this->recomputeActivity($activityId, $timestamp, true);
        }

        return $results;
    }

    private function collectBackfillActivityIds(
        int $lecturerId,
        int $approvedStatusId,
        ?int $academicYearId,
        int $limit
    ): array {
        $query = DB::table('research_activities as ra')
            ->leftJoin('research_activity_members as ram_self', function ($join) use ($lecturerId) {
                $join->on('ram_self.activity_id', '=', 'ra.id')
                    ->where('ram_self.lecturer_id', '=', $lecturerId);
            })
            ->where('ra.status_id', $approvedStatusId)
            ->where(function ($query) use ($lecturerId) {
                $query->where('ra.owner_lecturer_id', $lecturerId)
                    ->orWhere('ram_self.confirmation_status', 'accepted');
            });

        if ($academicYearId) {
            $query->where('ra.academic_year_id', $academicYearId);
        }

        $query->where(function ($query) {
            $query->whereNull('ra.total_hours_calc')
                ->orWhereExists(function ($missingMember) {
                    $missingMember->from('research_activity_members as ram_missing')
                        ->join('research_activities as ra_missing', 'ram_missing.activity_id', '=', 'ra_missing.id')
                        ->whereColumn('ram_missing.activity_id', 'ra.id')
                        ->where(function ($eligible) {
                            $eligible->where('ram_missing.confirmation_status', 'accepted')
                                ->orWhereColumn('ram_missing.lecturer_id', 'ra_missing.owner_lecturer_id');
                        })
                        ->whereNull('ram_missing.hours_assigned');
                });
        });

        return $query
            ->orderByDesc('ra.updated_at')
            ->limit(max(1, $limit))
            ->pluck('ra.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
