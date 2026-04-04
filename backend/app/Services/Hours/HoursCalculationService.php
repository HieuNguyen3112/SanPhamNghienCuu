<?php

namespace App\Services\Hours;

use Illuminate\Support\Carbon;

class HoursCalculationService
{
    public function calculate(?object $rule, ?int $quantity, array $members, array $context = []): array
    {
        $normalizedQuantity = max(1, (int) ($quantity ?? 1));
        $memberCount = count($members);

        if (! $rule || $memberCount === 0) {
            return [
                'total_hours_activity' => null,
                'calculated_total_hours' => null,
                'total_hours_to_add' => null,
                'members' => [],
                'formula' => [
                    'mode' => 'missing_rule',
                    'base_hours' => null,
                    'quantity' => $normalizedQuantity,
                    'member_count' => $memberCount,
                    'progress_multiplier' => 1.0,
                    'progress_applied' => false,
                ],
            ];
        }

        $baseAllocation = $this->allocateBaseHoursByStrategy($rule, $normalizedQuantity, $members);
        $baseMemberHours = $baseAllocation['member_hours'];
        $baseTotalHours = $baseAllocation['total_hours'];

        if ($baseTotalHours === null) {
            $membersPayload = [];
            foreach ($members as $member) {
                $membersPayload[] = [
                    'member_row_id' => $this->memberInt($member, 'id'),
                    'lecturer_id' => $this->memberInt($member, 'lecturer_id'),
                    'member_role_code' => $this->memberString($member, 'member_role_code'),
                    'role_weight' => null,
                    'calculated_hours' => null,
                    'hours_claimed_before' => round($this->memberClaimedBefore($member), 2),
                    'hours_to_add' => null,
                    'hours_assigned' => null,
                    'contribution_share' => null,
                ];
            }

            return [
                'total_hours_activity' => null,
                'calculated_total_hours' => null,
                'total_hours_to_add' => null,
                'members' => $membersPayload,
                'formula' => [
                    'mode' => (string) ($rule->distribution_strategy ?? 'unknown'),
                    'base_hours' => $baseAllocation['base_hours'],
                    'quantity' => $normalizedQuantity,
                    'member_count' => $memberCount,
                    'base_total_hours' => null,
                    'progress_multiplier' => 1.0,
                    'progress_applied' => false,
                    'progress_reason' => 'missing_rule_parameters',
                ],
            ];
        }

        $kindCode = strtolower((string) ($rule->kind_code ?? ($context['kind_code'] ?? '')));
        $progressEnabled = $this->shouldApplyProgress($rule, $kindCode);
        $progress = $this->resolveProgressMultiplier(
            $progressEnabled,
            $context['start_date'] ?? null,
            $context['end_date'] ?? null,
            $context['executed_at'] ?? null
        );

        $memberPayload = [];
        $calculatedTotalHours = 0.0;
        $hoursToAddTotal = 0.0;
        $roleWeights = $this->deriveRoleWeights($baseMemberHours, $baseTotalHours);

        foreach ($members as $index => $member) {
            $baseHours = $baseMemberHours[$index] ?? 0.0;
            $calculatedHours = round($baseHours * $progress['multiplier'], 2);
            $claimedBefore = round($this->memberClaimedBefore($member), 2);
            $hoursToAdd = round($calculatedHours - $claimedBefore, 2);

            $calculatedTotalHours = round($calculatedTotalHours + $calculatedHours, 2);
            $hoursToAddTotal = round($hoursToAddTotal + $hoursToAdd, 2);

            $memberPayload[] = [
                'member_row_id' => $this->memberInt($member, 'id'),
                'lecturer_id' => $this->memberInt($member, 'lecturer_id'),
                'member_role_code' => $this->memberString($member, 'member_role_code'),
                'role_weight' => $roleWeights[$index] ?? null,
                'calculated_hours' => $calculatedHours,
                'hours_claimed_before' => $claimedBefore,
                'hours_to_add' => $hoursToAdd,
                // Keep legacy contract: existing flows read hours_assigned as effective value.
                'hours_assigned' => $hoursToAdd,
                'contribution_share' => null,
            ];
        }

        if (abs($hoursToAddTotal) > 0.000001) {
            foreach ($memberPayload as $idx => $payload) {
                $memberPayload[$idx]['contribution_share'] = round(
                    ((float) $payload['hours_assigned']) / $hoursToAddTotal,
                    4
                );
            }
        }

        $formula = array_merge(
            [
                'mode' => (string) ($rule->distribution_strategy ?? 'unknown'),
                'base_hours' => $baseAllocation['base_hours'],
                'quantity' => $normalizedQuantity,
                'member_count' => $memberCount,
                'base_total_hours' => $baseTotalHours,
                'progress_multiplier' => $progress['multiplier'],
                'progress_applied' => $progressEnabled,
                'progress_reason' => $progress['reason'],
                'progress_window' => [
                    'start_date' => $progress['start_date'],
                    'end_date' => $progress['end_date'],
                    'evaluated_at' => $progress['evaluated_at'],
                ],
                'calculated_total_hours' => $calculatedTotalHours,
                'total_hours_to_add' => $hoursToAddTotal,
            ],
            $baseAllocation['details']
        );

        return [
            'total_hours_activity' => $hoursToAddTotal,
            'calculated_total_hours' => $calculatedTotalHours,
            'total_hours_to_add' => $hoursToAddTotal,
            'members' => $memberPayload,
            'formula' => $formula,
        ];
    }

    public function isPrincipalRole(?string $memberRoleCode): bool
    {
        if (! $memberRoleCode) {
            return false;
        }

        return in_array(strtolower($memberRoleCode), ['principal', 'chief_editor'], true);
    }

    public function isProjectPoolRule(?object $rule): bool
    {
        if (! $rule) {
            return false;
        }

        $kindCode = strtolower((string) ($rule->kind_code ?? ''));
        $typeCode = strtolower((string) ($rule->type_code ?? ''));

        return $kindCode === 'project'
            && in_array($typeCode, ['bo', 'coso'], true)
            && (string) $rule->distribution_strategy === 'principal_fraction_others_equal'
            && $rule->hours_total_per_activity !== null
            && $rule->hours_per_occurrence !== null;
    }

    private function allocateBaseHoursByStrategy(object $rule, int $quantity, array $members): array
    {
        $memberCount = count($members);
        $strategy = (string) $rule->distribution_strategy;
        $memberHours = array_fill(0, $memberCount, null);
        $baseHours = null;
        $details = [
            'distribution_strategy' => $strategy,
        ];

        if ($strategy === 'per_lecturer_fixed') {
            $hoursPerOccurrence = $rule->hours_per_occurrence !== null ? (float) $rule->hours_per_occurrence : null;
            if ($hoursPerOccurrence !== null) {
                $effectiveOccurrences = $quantity;
                if ($rule->max_occurrences_per_year !== null) {
                    $effectiveOccurrences = min($effectiveOccurrences, (int) $rule->max_occurrences_per_year);
                }

                $perMember = round($hoursPerOccurrence * $effectiveOccurrences, 2);
                $memberHours = array_fill(0, $memberCount, $perMember);
                $baseHours = $hoursPerOccurrence;
                $details['effective_occurrences'] = $effectiveOccurrences;
                $details['max_occurrences_per_year'] = $rule->max_occurrences_per_year !== null
                    ? (int) $rule->max_occurrences_per_year
                    : null;

                return [
                    'member_hours' => $memberHours,
                    'total_hours' => round(array_sum($memberHours), 2),
                    'base_hours' => $baseHours,
                    'details' => $details,
                ];
            }

            return [
                'member_hours' => $memberHours,
                'total_hours' => null,
                'base_hours' => $baseHours,
                'details' => $details,
            ];
        }

        if ($this->isProjectPoolRule($rule)) {
            $leaderRuleTotal = round((float) $rule->hours_total_per_activity * $quantity, 2);
            $memberPoolTotal = round((float) $rule->hours_per_occurrence * $quantity, 2);

            $principalIndexes = $this->principalIndexes($members);
            if (count($principalIndexes) === 0) {
                $principalIndexes = $this->ownerIndexes($members);
            }

            $principalCount = count($principalIndexes);
            $nonPrincipalIndexes = array_values(array_diff(range(0, $memberCount - 1), $principalIndexes));
            $nonPrincipalCount = count($nonPrincipalIndexes);

            $appliedMemberPoolTotal = $nonPrincipalCount > 0 ? $memberPoolTotal : 0.0;
            $appliedLeaderHoursTotal = $nonPrincipalCount > 0
                ? round(max(0.0, $leaderRuleTotal - $memberPoolTotal), 2)
                : $leaderRuleTotal;

            if ($principalCount === 0) {
                $equalShare = $memberCount > 0 ? round(($appliedLeaderHoursTotal + $appliedMemberPoolTotal) / $memberCount, 2) : 0.0;
                $memberHours = array_fill(0, $memberCount, $equalShare);
            } else {
                $principalShare = round($appliedLeaderHoursTotal / $principalCount, 2);
                foreach ($principalIndexes as $index) {
                    $memberHours[$index] = $principalShare;
                }

                if ($nonPrincipalCount > 0) {
                    $memberShare = round($appliedMemberPoolTotal / $nonPrincipalCount, 2);
                    foreach ($nonPrincipalIndexes as $index) {
                        $memberHours[$index] = $memberShare;
                    }
                }
            }

            $baseHours = (float) $rule->hours_total_per_activity;
            $details['leader_rule_hours_total'] = $leaderRuleTotal;
            $details['leader_hours_total'] = $appliedLeaderHoursTotal;
            $details['member_pool_total'] = $memberPoolTotal;
            $details['member_pool_applied_total'] = $appliedMemberPoolTotal;
            $details['principal_count'] = $principalCount;
            $details['non_principal_count'] = $nonPrincipalCount;

            return [
                'member_hours' => $memberHours,
                'total_hours' => round(array_sum($memberHours), 2),
                'base_hours' => $baseHours,
                'details' => $details,
            ];
        }

        $totalByRule = $rule->hours_total_per_activity !== null
            ? round((float) $rule->hours_total_per_activity * $quantity, 2)
            : null;
        $baseHours = $rule->hours_total_per_activity !== null ? (float) $rule->hours_total_per_activity : null;

        if ($totalByRule === null || $memberCount === 0) {
            return [
                'member_hours' => $memberHours,
                'total_hours' => null,
                'base_hours' => $baseHours,
                'details' => $details,
            ];
        }

        if ($strategy === 'principal_fraction_others_equal') {
            $principalIndexes = $this->principalIndexes($members);
            $principalCount = count($principalIndexes);

            $principalFraction = $rule->principal_fraction !== null ? (float) $rule->principal_fraction : 0.2;
            $othersFractionTotal = $rule->others_fraction_total !== null
                ? (float) $rule->others_fraction_total
                : max(0.0, 1 - $principalFraction);

            if ($principalCount === 0) {
                $equalShare = round($totalByRule / $memberCount, 2);
                $memberHours = array_fill(0, $memberCount, $equalShare);
            } else {
                $sharedPerMember = round(($totalByRule * $othersFractionTotal) / $memberCount, 2);
                $principalBonusPool = $totalByRule * $principalFraction;
                $principalBonusPerMember = round($principalBonusPool / $principalCount, 2);

                foreach (array_keys($memberHours) as $index) {
                    $hours = $sharedPerMember;
                    if (in_array($index, $principalIndexes, true)) {
                        $hours = round($hours + $principalBonusPerMember, 2);
                    }
                    $memberHours[$index] = $hours;
                }
            }

            $details['principal_fraction'] = $principalFraction;
            $details['others_fraction_total'] = $othersFractionTotal;
            $details['principal_count'] = $principalCount;
        } else {
            $equalShare = round($totalByRule / $memberCount, 2);
            $memberHours = array_fill(0, $memberCount, $equalShare);
        }

        return [
            'member_hours' => $memberHours,
            'total_hours' => round(array_sum($memberHours), 2),
            'base_hours' => $baseHours,
            'details' => $details,
        ];
    }

    private function deriveRoleWeights(array $baseMemberHours, float $baseTotalHours): array
    {
        $weights = [];
        if ($baseTotalHours <= 0) {
            foreach (array_keys($baseMemberHours) as $idx) {
                $weights[$idx] = null;
            }
            return $weights;
        }

        foreach ($baseMemberHours as $idx => $hours) {
            $weights[$idx] = round(((float) $hours) / $baseTotalHours, 6);
        }

        return $weights;
    }

    private function shouldApplyProgress(object $rule, string $kindCode): bool
    {
        if (property_exists($rule, 'use_progress_multiplier') && $rule->use_progress_multiplier !== null) {
            return (bool) $rule->use_progress_multiplier;
        }

        return $kindCode === 'project';
    }

    private function resolveProgressMultiplier(bool $enabled, $startDate, $endDate, $evaluatedAt): array
    {
        $evaluated = $this->toCarbon($evaluatedAt) ?? now();
        $start = $this->toCarbon($startDate);
        $end = $this->toCarbon($endDate);

        if (! $enabled) {
            return [
                'multiplier' => 1.0,
                'reason' => 'progress_disabled',
                'start_date' => $start?->toDateString(),
                'end_date' => $end?->toDateString(),
                'evaluated_at' => $evaluated->toDateString(),
            ];
        }

        if (! $start || ! $end) {
            return [
                'multiplier' => 1.0,
                'reason' => 'missing_dates_fallback_full_hours',
                'start_date' => $start?->toDateString(),
                'end_date' => $end?->toDateString(),
                'evaluated_at' => $evaluated->toDateString(),
            ];
        }

        if ($start->gt($evaluated)) {
            return [
                'multiplier' => 0.0,
                'reason' => 'start_date_in_future',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'evaluated_at' => $evaluated->toDateString(),
            ];
        }

        if ($end->lt($evaluated)) {
            return [
                'multiplier' => 1.0,
                'reason' => 'already_finished',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'evaluated_at' => $evaluated->toDateString(),
            ];
        }

        $duration = $end->getTimestamp() - $start->getTimestamp();
        if ($duration <= 0) {
            return [
                'multiplier' => 1.0,
                'reason' => 'invalid_duration_fallback_full_hours',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'evaluated_at' => $evaluated->toDateString(),
            ];
        }

        $elapsed = $evaluated->getTimestamp() - $start->getTimestamp();
        $progress = max(0.0, min(1.0, $elapsed / $duration));

        return [
            'multiplier' => round($progress, 4),
            'reason' => 'time_based_progress',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'evaluated_at' => $evaluated->toDateString(),
        ];
    }

    private function principalIndexes(array $members): array
    {
        $indexes = [];
        foreach ($members as $index => $member) {
            if ($this->isPrincipalRole($this->memberString($member, 'member_role_code'))) {
                $indexes[] = (int) $index;
            }
        }

        return $indexes;
    }

    private function ownerIndexes(array $members): array
    {
        $indexes = [];
        foreach ($members as $index => $member) {
            $ownerLecturerId = $this->memberInt($member, 'owner_lecturer_id');
            $lecturerId = $this->memberInt($member, 'lecturer_id');
            if ($ownerLecturerId > 0 && $lecturerId > 0 && $ownerLecturerId === $lecturerId) {
                $indexes[] = (int) $index;
            }
        }

        return $indexes;
    }

    private function memberClaimedBefore($member): float
    {
        return $this->memberFloat($member, 'hours_claimed_before');
    }

    private function memberInt($member, string $key): int
    {
        if (is_array($member)) {
            return isset($member[$key]) ? (int) $member[$key] : 0;
        }

        return isset($member->{$key}) ? (int) $member->{$key} : 0;
    }

    private function memberFloat($member, string $key): float
    {
        if (is_array($member)) {
            return isset($member[$key]) ? (float) $member[$key] : 0.0;
        }

        return isset($member->{$key}) ? (float) $member->{$key} : 0.0;
    }

    private function memberString($member, string $key): ?string
    {
        if (is_array($member)) {
            return isset($member[$key]) ? (string) $member[$key] : null;
        }

        return isset($member->{$key}) ? (string) $member->{$key} : null;
    }

    private function toCarbon($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
