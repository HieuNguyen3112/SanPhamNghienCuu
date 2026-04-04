<?php

namespace App\Services\Hours;

class HoursAllocator
{
    private HoursCalculationService $hoursCalculationService;

    public function __construct(HoursCalculationService $hoursCalculationService)
    {
        $this->hoursCalculationService = $hoursCalculationService;
    }

    public function allocateByRule(?object $rule, ?int $quantity, array $members, array $context = []): array
    {
        return $this->hoursCalculationService->calculate($rule, $quantity, $members, $context);
    }

    public function isPrincipalRole(?string $memberRoleCode): bool
    {
        return $this->hoursCalculationService->isPrincipalRole($memberRoleCode);
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
        return $this->hoursCalculationService->isProjectPoolRule($rule);
    }
}
