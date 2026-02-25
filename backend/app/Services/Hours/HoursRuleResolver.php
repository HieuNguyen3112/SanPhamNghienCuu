<?php

namespace App\Services\Hours;

use Illuminate\Support\Facades\DB;

class HoursRuleResolver
{
    public function resolveForActivity(int $kindId, ?int $typeId, ?int $academicYearId = null): ?object
    {
        [$windowStart, $windowEnd] = $this->resolveRuleWindow($academicYearId);
        $requestedTypeCode = $this->resolveTypeCode($typeId);

        $rule = $this->findExactRule($kindId, $typeId, $windowStart, $windowEnd);
        if ($rule) {
            return $this->normalizeFixedPaperRule($rule, $requestedTypeCode);
        }

        $fallbackRule = $this->findExactRule($kindId, $typeId);
        if ($fallbackRule) {
            return $this->normalizeFixedPaperRule($fallbackRule, $requestedTypeCode);
        }

        $paperFallbackRule = $this->findPaperFamilyFallbackRule(
            $kindId,
            $typeId,
            $requestedTypeCode,
            $windowStart,
            $windowEnd
        );

        return $this->normalizeFixedPaperRule($paperFallbackRule, $requestedTypeCode);
    }

    public function formatRuleSummary(?object $rule): string
    {
        if (! $rule) {
            return 'Chưa có quy tắc quy đổi cho loại công trình này. Vui lòng liên hệ Phòng quản lý khoa học để cấu hình.';
        }

        $parts = [
            'chien_luoc=' . $rule->distribution_strategy,
        ];

        if ($rule->hours_total_per_activity !== null) {
            $parts[] = 'tong_gio=' . $rule->hours_total_per_activity;
        }

        if ($rule->hours_per_occurrence !== null) {
            if ($this->isProjectPoolRule($rule)) {
                $parts[] = 'quy_gio_thanh_vien=' . $rule->hours_per_occurrence;
            } else {
                $parts[] = 'gio_moi_lan=' . $rule->hours_per_occurrence;
            }
        }

        if ($rule->principal_fraction !== null) {
            $parts[] = 'ty_le_chu_nhiem_chu_bien=' . $rule->principal_fraction;
        }

        if ($rule->others_fraction_total !== null) {
            $parts[] = 'ty_le_nhom_thanh_vien=' . $rule->others_fraction_total;
        }

        if ($rule->max_occurrences_per_year !== null) {
            $parts[] = 'so_lan_toi_da=' . $rule->max_occurrences_per_year;
        }

        return 'Quy tắc quy đổi: ' . implode(', ', $parts);
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
            && $rule->distribution_strategy === 'principal_fraction_others_equal'
            && $rule->hours_total_per_activity !== null
            && $rule->hours_per_occurrence !== null;
    }

    private function resolveRuleWindow(?int $academicYearId): array
    {
        if (! $academicYearId) {
            return [null, null];
        }

        $row = DB::table('academic_years')
            ->where('id', $academicYearId)
            ->select(['start_date', 'end_date'])
            ->first();

        if (! $row) {
            return [null, null];
        }

        return [$row->start_date, $row->end_date];
    }

    private function findExactRule(
        int $kindId,
        ?int $typeId,
        ?string $windowStart = null,
        ?string $windowEnd = null
    ): ?object {
        $query = $this->baseRuleQuery($kindId, $typeId);

        if ($windowStart && $windowEnd) {
            $this->applyRuleWindowFilter($query, $windowStart, $windowEnd);
        }

        return $query->first();
    }

    private function findPaperFamilyFallbackRule(
        int $kindId,
        ?int $typeId,
        ?string $requestedTypeCode,
        ?string $windowStart,
        ?string $windowEnd
    ): ?object {
        if (! $typeId || ! $requestedTypeCode) {
            return null;
        }

        if (! in_array($requestedTypeCode, ['hdgsnn_900', 'hdgsnn_600', 'hdgsnn_300'], true)) {
            return null;
        }

        $kindCode = DB::table('activity_kinds')->where('id', $kindId)->value('code');
        if (strtolower((string) $kindCode) !== 'paper') {
            return null;
        }

        $query = $this->baseRuleQuery($kindId, null, false);

        if ($windowStart && $windowEnd) {
            $this->applyRuleWindowFilter($query, $windowStart, $windowEnd);
        }

        $row = $query->first();
        if (! $row) {
            $row = $this->baseRuleQuery($kindId, null, false)->first();
        }

        if (! $row) {
            return null;
        }

        $row->type_id = $typeId;
        $row->type_code = $requestedTypeCode;

        return $row;
    }

    private function baseRuleQuery(int $kindId, ?int $typeId, bool $withTypeFilter = true)
    {
        $query = DB::table('hour_rules as hr')
            ->join('activity_kinds as ak', 'ak.id', '=', 'hr.kind_id')
            ->leftJoin('activity_types as at', 'at.id', '=', 'hr.type_id')
            ->where('hr.kind_id', $kindId)
            ->where('hr.is_active', 1)
            ->select([
                'hr.*',
                'ak.code as kind_code',
                'at.code as type_code',
            ])
            ->orderByDesc('hr.effective_from')
            ->orderByDesc('hr.version')
            ->orderByDesc('hr.id');

        if ($withTypeFilter) {
            $query->where(function ($sub) use ($typeId) {
                if ($typeId === null) {
                    $sub->whereNull('hr.type_id');
                    return;
                }

                $sub->where('hr.type_id', $typeId);
            });
        }

        return $query;
    }

    private function applyRuleWindowFilter($query, string $windowStart, string $windowEnd): void
    {
        $query->whereDate('hr.effective_from', '<=', $windowEnd)
            ->where(function ($sub) use ($windowStart) {
                $sub->whereNull('hr.effective_to')
                    ->orWhereDate('hr.effective_to', '>=', $windowStart);
            });
    }

    private function resolveTypeCode(?int $typeId): ?string
    {
        if (! $typeId) {
            return null;
        }

        $code = DB::table('activity_types')->where('id', $typeId)->value('code');
        if (! $code) {
            return null;
        }

        return strtolower((string) $code);
    }

    private function normalizeFixedPaperRule(?object $rule, ?string $requestedTypeCode = null): ?object
    {
        if (! $rule) {
            return null;
        }

        $kindCode = strtolower((string) ($rule->kind_code ?? ''));
        $typeCode = strtolower((string) ($rule->type_code ?? $requestedTypeCode ?? ''));
        if ($kindCode !== 'paper') {
            return $rule;
        }

        $fixedHours = match ($typeCode) {
            'hdgsnn_900' => 900.0,
            'hdgsnn_600' => 600.0,
            'hdgsnn_300' => 300.0,
            default => null,
        };

        if ($fixedHours === null) {
            return $rule;
        }

        $rule->distribution_strategy = 'equal_all_members';
        $rule->hours_total_per_activity = $fixedHours;
        $rule->hours_per_occurrence = null;
        $rule->principal_fraction = null;
        $rule->others_fraction_total = null;
        $rule->max_occurrences_per_year = null;
        $rule->type_code = $typeCode;

        return $rule;
    }
}
