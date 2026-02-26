<?php

namespace App\Services\Hours;

class HoursAllocator
{
    public function allocateByRule(?object $rule, ?int $quantity, array $members): array
    {
        $normalizedQuantity = max(1, (int) ($quantity ?? 1));
        $memberCount = count($members);
        if (! $rule || $memberCount === 0) {
            return [
                'total_hours_activity' => null,
                'members' => [],
                'formula' => [
                    'mode' => 'missing_rule',
                    'base_hours' => null,
                    'quantity' => $normalizedQuantity,
                    'member_count' => $memberCount,
                ],
            ];
        }

        $strategy = (string) $rule->distribution_strategy;
        $memberHours = array_fill(0, $memberCount, null);
        $totalHours = null;
        $formula = [
            'mode' => $strategy,
            'base_hours' => null,
            'quantity' => $normalizedQuantity,
            'member_count' => $memberCount,
        ];

        if ($strategy === 'per_lecturer_fixed') {
            $hoursPerOccurrence = $rule->hours_per_occurrence !== null ? (float) $rule->hours_per_occurrence : null;
            if ($hoursPerOccurrence !== null) {
                $effectiveOccurrences = $normalizedQuantity;
                if ($rule->max_occurrences_per_year !== null) {
                    $effectiveOccurrences = min($effectiveOccurrences, (int) $rule->max_occurrences_per_year);
                }

                $hoursPerMember = round($hoursPerOccurrence * $effectiveOccurrences, 2);
                $memberHours = array_fill(0, $memberCount, $hoursPerMember);
                $totalHours = round($hoursPerMember * $memberCount, 2);

                $formula['base_hours'] = $hoursPerOccurrence;
                $formula['effective_occurrences'] = $effectiveOccurrences;
                $formula['max_occurrences_per_year'] = $rule->max_occurrences_per_year !== null
                    ? (int) $rule->max_occurrences_per_year
                    : null;
            }
        } elseif ($this->isProjectPoolRule($rule)) {
            $leaderHoursTotal = round((float) $rule->hours_total_per_activity * $normalizedQuantity, 2);
            $memberPoolTotal = round((float) $rule->hours_per_occurrence * $normalizedQuantity, 2);

            $principalIndexes = $this->principalIndexes($members);
            $principalCount = count($principalIndexes);
            if ($principalCount === 0) {
                $fallbackOwnerIndexes = $this->ownerIndexes($members);
                if (count($fallbackOwnerIndexes) > 0) {
                    $principalIndexes = $fallbackOwnerIndexes;
                    $principalCount = count($principalIndexes);
                }
            }

            $nonPrincipalIndexes = array_values(array_diff(range(0, $memberCount - 1), $principalIndexes));
            $nonPrincipalCount = count($nonPrincipalIndexes);
            $appliedMemberPoolTotal = $nonPrincipalCount > 0 ? $memberPoolTotal : 0.0;
            $totalHours = round($leaderHoursTotal + $appliedMemberPoolTotal, 2);

            if ($principalCount === 0) {
                $equalShare = round($totalHours / $memberCount, 2);
                $memberHours = array_fill(0, $memberCount, $equalShare);
            } else {
                $principalShare = round($leaderHoursTotal / $principalCount, 2);
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

            $formula['mode'] = 'project_leader_and_member_pool';
            $formula['leader_hours_total'] = $leaderHoursTotal;
            $formula['member_pool_total'] = $memberPoolTotal;
            $formula['member_pool_applied_total'] = $appliedMemberPoolTotal;
            $formula['principal_count'] = $principalCount;
            $formula['non_principal_count'] = $nonPrincipalCount;
        } else {
            $baseHours = $rule->hours_total_per_activity !== null ? (float) $rule->hours_total_per_activity : null;
            if ($baseHours !== null) {
                $totalHours = round($baseHours * $normalizedQuantity, 2);

                if ($strategy === 'principal_fraction_others_equal') {
                    $principalIndexes = $this->principalIndexes($members);
                    $principalCount = count($principalIndexes);

                    $principalFraction = $rule->principal_fraction !== null ? (float) $rule->principal_fraction : 0.2;
                    $othersFractionTotal = $rule->others_fraction_total !== null
                        ? (float) $rule->others_fraction_total
                        : max(0.0, 1 - $principalFraction);

                    if ($principalCount === 0) {
                        $equalShare = round($totalHours / $memberCount, 2);
                        $memberHours = array_fill(0, $memberCount, $equalShare);
                    } else {
                        $sharedPerMember = round(($totalHours * $othersFractionTotal) / $memberCount, 2);
                        $principalBonusPool = $totalHours * $principalFraction;
                        $principalBonusPerMember = round($principalBonusPool / $principalCount, 2);

                        foreach ($members as $index => $member) {
                            $hours = $sharedPerMember;
                            if (in_array($index, $principalIndexes, true)) {
                                $hours = round($hours + $principalBonusPerMember, 2);
                            }
                            $memberHours[$index] = $hours;
                        }
                    }

                    $formula['principal_fraction'] = $principalFraction;
                    $formula['others_fraction_total'] = $othersFractionTotal;
                    $formula['principal_count'] = $principalCount;
                } else {
                    $equalShare = round($totalHours / $memberCount, 2);
                    $memberHours = array_fill(0, $memberCount, $equalShare);
                }

                $formula['base_hours'] = $baseHours;
            }
        }

        $assignedMembers = [];
        foreach ($members as $index => $member) {
            $hours = $memberHours[$index];
            $share = null;
            if ($totalHours !== null && $totalHours > 0 && $hours !== null) {
                $share = round($hours / $totalHours, 4);
            }

            $assignedMembers[] = [
                'member_row_id' => (int) $member->id,
                'lecturer_id' => (int) $member->lecturer_id,
                'member_role_code' => $member->member_role_code ? (string) $member->member_role_code : null,
                'hours_assigned' => $hours,
                'contribution_share' => $share,
            ];
        }

        return [
            'total_hours_activity' => $totalHours,
            'members' => $assignedMembers,
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

    private function principalIndexes(array $members): array
    {
        $indexes = [];
        foreach ($members as $index => $member) {
            if ($this->isPrincipalRole($member->member_role_code ?? null)) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function ownerIndexes(array $members): array
    {
        $indexes = [];
        foreach ($members as $index => $member) {
            $ownerLecturerId = isset($member->owner_lecturer_id) ? (int) $member->owner_lecturer_id : null;
            if ($ownerLecturerId === null) {
                continue;
            }

            if ((int) $member->lecturer_id === $ownerLecturerId) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function isProjectPoolRule(object $rule): bool
    {
        $kindCode = strtolower((string) ($rule->kind_code ?? ''));
        $typeCode = strtolower((string) ($rule->type_code ?? ''));

        return $kindCode === 'project'
            && in_array($typeCode, ['bo', 'coso'], true)
            && (string) $rule->distribution_strategy === 'principal_fraction_others_equal'
            && $rule->hours_total_per_activity !== null
            && $rule->hours_per_occurrence !== null;
    }
}
