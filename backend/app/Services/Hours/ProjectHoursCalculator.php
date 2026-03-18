<?php

namespace App\Services\Hours;

class ProjectHoursCalculator
{
    private const RULES = [
        'bo' => [
            'level_label' => 'Đề tài cấp Bộ (2 năm)',
            'leader_hours' => 720.0,
            'member_pool_hours' => 480.0,
        ],
        'coso' => [
            'level_label' => 'Đề tài cấp cơ sở (1 năm)',
            'leader_hours' => 600.0,
            'member_pool_hours' => 240.0,
        ],
    ];

    public function supports(?string $typeCode): bool
    {
        return $this->normalizeTypeCode($typeCode) !== null;
    }

    public function calculate(
        ?string $typeCode,
        int $quantity,
        array $members,
        int $ownerLecturerId
    ): ?array {
        $normalizedTypeCode = $this->normalizeTypeCode($typeCode);
        if (! $normalizedTypeCode) {
            return null;
        }

        $rule = self::RULES[$normalizedTypeCode];
        $normalizedQuantity = max(1, $quantity);
        $leaderRuleHoursTotal = round($rule['leader_hours'] * $normalizedQuantity, 2);
        $memberPoolTotal = round($rule['member_pool_hours'] * $normalizedQuantity, 2);

        $normalizedMembers = [];
        foreach ($members as $index => $member) {
            $lecturerId = isset($member['lecturer_id']) ? (int) $member['lecturer_id'] : 0;
            if ($lecturerId <= 0) {
                continue;
            }

            $normalizedMembers[] = [
                'index' => $index,
                'lecturer_id' => $lecturerId,
                'lecturer_full_name' => (string) ($member['lecturer_full_name'] ?? ('GV #' . $lecturerId)),
                'member_role_code' => isset($member['member_role_code']) ? strtolower((string) $member['member_role_code']) : null,
                'member_role_name' => $member['member_role_name'] ?? null,
            ];
        }

        if (empty($normalizedMembers)) {
            return [
                'level_code' => $normalizedTypeCode,
                'level_label' => $rule['level_label'],
                'leader_hours' => $leaderRuleHoursTotal,
                'member_pool_hours' => $memberPoolTotal,
                'member_pool_count' => 0,
                'member_pool_each' => 0.0,
                'total_hours_allocated' => $leaderRuleHoursTotal,
                'progress_multiplier_applied' => false,
                'progress_supported' => false,
                'progress_note' => 'Chưa áp dụng % tiến độ do chưa có trường dữ liệu trong hệ thống.',
                'formula_rows' => $this->buildFormulaRows($leaderRuleHoursTotal, $memberPoolTotal, 0, 0.0),
                'members' => [],
                'rule_summary' => $this->buildRuleSummary($rule, 0, 0.0),
            ];
        }

        $leaderIndexes = [];
        foreach ($normalizedMembers as $member) {
            if ($this->isLeaderRole($member['member_role_code'])) {
                $leaderIndexes[] = $member['index'];
            }
        }

        if (empty($leaderIndexes)) {
            foreach ($normalizedMembers as $member) {
                if ($member['lecturer_id'] === $ownerLecturerId) {
                    $leaderIndexes[] = $member['index'];
                }
            }
        }

        if (empty($leaderIndexes)) {
            $leaderIndexes[] = $normalizedMembers[0]['index'];
        }

        $leaderIndexes = array_values(array_unique($leaderIndexes));
        if (count($leaderIndexes) > 1) {
            $leaderIndexes = [$leaderIndexes[0]];
        }
        $leaderCount = count($leaderIndexes);

        $allIndexes = array_column($normalizedMembers, 'index');
        $memberPoolIndexes = array_values(array_diff($allIndexes, $leaderIndexes));
        $memberPoolCount = count($memberPoolIndexes);
        $memberPoolAppliedTotal = $memberPoolCount > 0 ? $memberPoolTotal : 0.0;
        $leaderHoursTotal = $memberPoolCount > 0
            ? round(max(0.0, $leaderRuleHoursTotal - $memberPoolTotal), 2)
            : $leaderRuleHoursTotal;
        $memberPoolEach = $memberPoolCount > 0
            ? round($memberPoolAppliedTotal / $memberPoolCount, 2)
            : 0.0;
        $leaderEach = $leaderCount > 0
            ? round($leaderHoursTotal / $leaderCount, 2)
            : 0.0;

        $hoursByIndex = [];
        foreach ($leaderIndexes as $index) {
            $hoursByIndex[$index] = $leaderEach;
        }
        foreach ($memberPoolIndexes as $index) {
            $hoursByIndex[$index] = $memberPoolEach;
        }

        $totalHoursAllocated = round($leaderHoursTotal + $memberPoolAppliedTotal, 2);
        $membersPayload = [];
        foreach ($normalizedMembers as $member) {
            $roleCode = $member['member_role_code'];
            $membersPayload[] = [
                'lecturer_id' => $member['lecturer_id'],
                'lecturer_full_name' => $member['lecturer_full_name'],
                'member_role_code' => $roleCode,
                'member_role_name' => $this->resolveRoleLabel($roleCode, $member['member_role_name']),
                'hours_assigned' => isset($hoursByIndex[$member['index']]) ? (float) $hoursByIndex[$member['index']] : 0.0,
                'is_leader' => in_array($member['index'], $leaderIndexes, true),
            ];
        }

        return [
            'level_code' => $normalizedTypeCode,
            'level_label' => $rule['level_label'],
            'leader_hours' => $leaderHoursTotal,
            'member_pool_hours' => $memberPoolTotal,
            'member_pool_count' => $memberPoolCount,
            'member_pool_each' => $memberPoolEach,
            'total_hours_allocated' => $totalHoursAllocated,
            'progress_multiplier_applied' => false,
            'progress_supported' => false,
            'progress_note' => 'Chưa áp dụng % tiến độ do chưa có trường dữ liệu trong hệ thống.',
            'formula_rows' => $this->buildFormulaRows(
                $leaderHoursTotal,
                $memberPoolTotal,
                $memberPoolCount,
                $memberPoolEach
            ),
            'members' => $membersPayload,
            'rule_summary' => $this->buildRuleSummary($rule, $memberPoolCount, $memberPoolEach),
        ];
    }

    private function normalizeTypeCode(?string $typeCode): ?string
    {
        if ($typeCode === null) {
            return null;
        }

        $normalized = strtolower(trim($typeCode));

        if ($normalized === 'ministry') {
            $normalized = 'bo';
        } elseif ($normalized === 'university') {
            $normalized = 'coso';
        }

        return array_key_exists($normalized, self::RULES) ? $normalized : null;
    }

    private function isLeaderRole(?string $memberRoleCode): bool
    {
        if (! $memberRoleCode) {
            return false;
        }

        return strtolower($memberRoleCode) === 'principal';
    }

    private function resolveRoleLabel(?string $memberRoleCode, ?string $fallback): string
    {
        $normalized = $memberRoleCode ? strtolower($memberRoleCode) : null;

        return match ($normalized) {
            'principal' => 'Chủ nhiệm',
            'secretary' => 'Thư ký',
            'member' => 'Thành viên',
            'coauthor' => 'Đồng tác giả',
            'corresponding_author' => 'Tác giả chính',
            'chief_editor' => 'Chủ biên',
            default => $fallback ? (string) $fallback : 'Thành viên',
        };
    }

    private function buildFormulaRows(
        float $leaderHoursTotal,
        float $memberPoolTotal,
        int $memberPoolCount,
        float $memberPoolEach
    ): array {
        return [
            [
                'role_label' => 'Chủ nhiệm',
                'total_hours' => $leaderHoursTotal,
                'formula_text' => round($leaderHoursTotal, 2) . ' giờ (100%)',
            ],
            [
                'role_label' => 'Nhóm thành viên',
                'total_hours' => $memberPoolTotal,
                'formula_text' => $memberPoolCount > 0
                    ? round($memberPoolTotal, 2) . ' / ' . $memberPoolCount . ' = ' . round($memberPoolEach, 2) . ' giờ/người'
                    : round($memberPoolTotal, 2) . ' / 0 = 0 giờ/người (chưa có thành viên)',
            ],
        ];
    }

    private function buildRuleSummary(array $rule, int $memberPoolCount, float $memberPoolEach): string
    {
        $leaderHours = $memberPoolCount > 0
            ? round(max(0.0, $rule['leader_hours'] - $rule['member_pool_hours']), 2)
            : round($rule['leader_hours'], 2);

        return $rule['level_label']
            . ': Chủ nhiệm '
            . $leaderHours
            . ' giờ, Nhóm thành viên '
            . round($rule['member_pool_hours'], 2)
            . ' / '
            . $memberPoolCount
            . ' = '
            . round($memberPoolEach, 2)
            . ' giờ/người.';
    }
}

