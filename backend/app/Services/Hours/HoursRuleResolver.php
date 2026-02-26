<?php

namespace App\Services\Hours;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HoursRuleResolver
{
    public function resolveForActivity(int $kindId, ?int $typeId, ?int $academicYearId = null): ?object
    {
        [$windowStart, $windowEnd] = $this->resolveRuleWindow($academicYearId);
        $requestedTypeCode = $this->resolveTypeCode($typeId);

        $candidateTypeIds = $this->buildCandidateTypeIds($kindId, $typeId, $requestedTypeCode);

        foreach ($candidateTypeIds as $candidateTypeId) {
            $rule = $this->findExactRule($kindId, $candidateTypeId, $windowStart, $windowEnd);
            if ($rule) {
                return $rule;
            }
        }

        $rule = $this->findGenericRule($kindId, $windowStart, $windowEnd);
        if ($rule) {
            return $rule;
        }

        foreach ($candidateTypeIds as $candidateTypeId) {
            $rule = $this->findExactRule($kindId, $candidateTypeId);
            if ($rule) {
                return $rule;
            }
        }

        return $this->findGenericRule($kindId);
    }

    public function formatRuleSummary(?object $rule): string
    {
        if (! $rule) {
            return 'Chưa có quy tắc quy đổi cho công trình này. Vui lòng liên hệ Phòng Quản lý khoa học để cấu hình.';
        }

        $parts = [
            'chiến lược=' . $rule->distribution_strategy,
        ];

        if ($rule->hours_total_per_activity !== null) {
            $parts[] = 'tổng giờ=' . $rule->hours_total_per_activity;
        }

        if ($rule->hours_per_occurrence !== null) {
            if ($this->isProjectPoolRule($rule)) {
                $parts[] = 'quỹ giờ thành viên=' . $rule->hours_per_occurrence;
            } else {
                $parts[] = 'giờ mỗi lần=' . $rule->hours_per_occurrence;
            }
        }

        if ($rule->principal_fraction !== null) {
            $parts[] = 'tỷ lệ chủ nhiệm/chủ biên=' . $rule->principal_fraction;
        }

        if ($rule->others_fraction_total !== null) {
            $parts[] = 'tỷ lệ nhóm thành viên=' . $rule->others_fraction_total;
        }

        if ($rule->max_occurrences_per_year !== null) {
            $parts[] = 'số lần tối đa=' . $rule->max_occurrences_per_year;
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

    private function buildCandidateTypeIds(int $kindId, ?int $typeId, ?string $requestedTypeCode): array
    {
        $ids = [];
        if ($typeId !== null) {
            $ids[] = (int) $typeId;
        }

        foreach ($this->resolveMappedTypeIds($kindId, $requestedTypeCode) as $mappedTypeId) {
            if (! in_array($mappedTypeId, $ids, true)) {
                $ids[] = $mappedTypeId;
            }
        }

        return $ids;
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

    private function findGenericRule(
        int $kindId,
        ?string $windowStart = null,
        ?string $windowEnd = null
    ): ?object {
        $query = $this->baseRuleQuery($kindId, null, false)
            ->whereNull('hr.type_id');

        if ($windowStart && $windowEnd) {
            $this->applyRuleWindowFilter($query, $windowStart, $windowEnd);
        }

        return $query->first();
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

        return (string) $code;
    }

    private function resolveMappedTypeIds(int $kindId, ?string $typeCode): array
    {
        if ($typeCode === null || trim($typeCode) === '') {
            return [];
        }

        $kindCode = strtolower((string) DB::table('activity_kinds')->where('id', $kindId)->value('code'));
        if ($kindCode === '') {
            return [];
        }

        $normalized = $this->normalizeToken($typeCode);
        if ($normalized === '') {
            return [];
        }

        $aliasGroups = [];

        if ($kindCode === 'project') {
            if ($this->containsAny($normalized, ['ministry', 'bo', 'cap_bo'])) {
                $aliasGroups[] = ['bo', 'ministry', 'cap_bo', 'project_bo', 'project_ministry'];
            }
            if ($this->containsAny($normalized, ['university', 'coso', 'co_so', 'cap_truong'])) {
                $aliasGroups[] = ['coso', 'university', 'co_so', 'cap_truong', 'project_university'];
            }
        }

        if ($kindCode === 'conference') {
            if ($this->containsAny($normalized, ['attend', 'tham_du', 'thamdu', 'participant'])) {
                $aliasGroups[] = ['attend', 'tham_du', 'conference_attend'];
            }
            if ($this->containsAny($normalized, ['report', 'bao_cao', 'presentation', 'present'])) {
                $aliasGroups[] = ['report', 'bao_cao', 'conference_report', 'presentation'];
            }
        }

        if ($kindCode === 'book') {
            if ($this->containsAny($normalized, ['textbook', 'giao_trinh'])) {
                $aliasGroups[] = ['textbook', 'giao_trinh', 'book_textbook'];
            }
            if ($this->containsAny($normalized, ['reference', 'tham_khao', 'tai_lieu'])) {
                $aliasGroups[] = ['reference', 'tham_khao', 'tai_lieu', 'book_reference'];
            }
        }

        if ($aliasGroups === []) {
            $aliasGroups[] = [$normalized];
        }

        $typeIds = [];
        foreach ($aliasGroups as $aliases) {
            foreach ($this->findTypeIdsByAliases($kindId, $aliases) as $aliasTypeId) {
                if (! in_array($aliasTypeId, $typeIds, true)) {
                    $typeIds[] = $aliasTypeId;
                }
            }
        }

        return $typeIds;
    }

    private function findTypeIdsByAliases(int $kindId, array $aliases): array
    {
        $normalizedAliases = array_values(array_unique(array_filter(array_map(
            fn ($alias) => $this->normalizeToken((string) $alias),
            $aliases
        ))));

        if ($normalizedAliases === []) {
            return [];
        }

        return DB::table('activity_types')
            ->where('kind_id', $kindId)
            ->where(function ($query) use ($normalizedAliases) {
                foreach ($normalizedAliases as $alias) {
                    $query->orWhereRaw('LOWER(code) = ?', [strtolower($alias)])
                        ->orWhereRaw('LOWER(code) LIKE ?', ['%' . strtolower($alias) . '%']);
                }
            })
            ->orderByDesc('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function normalizeToken(string $value): string
    {
        $ascii = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $ascii);
        return trim((string) $normalized, '_');
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}

