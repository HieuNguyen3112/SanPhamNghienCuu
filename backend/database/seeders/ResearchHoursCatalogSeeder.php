<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResearchHoursCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $academicYears = [
            ['code' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-08-31', 'is_active' => false],
            ['code' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-08-31', 'is_active' => false],
            ['code' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-08-31', 'is_active' => true],
        ];

        foreach ($academicYears as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['code' => $year['code']],
                array_merge($year, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $years = DB::table('academic_years')
            ->select(['id', 'code', 'start_date', 'end_date', 'is_active'])
            ->whereIn('code', array_column($academicYears, 'code'))
            ->get()
            ->keyBy('code');

        $kindIds = DB::table('activity_kinds')->pluck('id', 'code')->all();
        $typeIds = DB::table('activity_types')->pluck('id', 'code')->all();

        if (empty($kindIds)) {
            return;
        }

        $ruleTemplates = [
            [
                'kind' => 'paper',
                'type' => 'hdgsnn_900',
                'type_aliases' => ['hdgsnn_900'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 900,
            ],
            [
                'kind' => 'paper',
                'type' => 'hdgsnn_600',
                'type_aliases' => ['hdgsnn_600'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 600,
            ],
            [
                'kind' => 'paper',
                'type' => 'hdgsnn_300',
                'type_aliases' => ['hdgsnn_300', 'issn_isbn'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 300,
            ],
            [
                'kind' => 'paper',
                'type' => 'scientific_report',
                'type_aliases' => ['scientific_report', 'paper_report', 'bao_cao_khoa_hoc'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 300,
            ],
            [
                'kind' => 'paper',
                'type' => 'scientific_report_600',
                'type_aliases' => ['scientific_report_600', 'paper_report_600', 'bao_cao_khoa_hoc_600'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 600,
            ],
            [
                'kind' => 'paper',
                'type' => 'scientific_report_900',
                'type_aliases' => ['scientific_report_900', 'paper_report_900', 'bao_cao_khoa_hoc_900'],
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 900,
            ],
            [
                'kind' => 'book',
                'type' => 'textbook',
                'type_aliases' => ['textbook', 'giao_trinh', 'book_textbook'],
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 900,
                'principal_fraction' => 0.2,
                'others_fraction_total' => 0.8,
            ],
            [
                'kind' => 'book',
                'type' => 'reference',
                'type_aliases' => ['reference', 'tai_lieu', 'tham_khao', 'book_reference'],
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 600,
                'principal_fraction' => 0.2,
                'others_fraction_total' => 0.8,
            ],
            [
                'kind' => 'conference',
                'type' => 'report',
                'type_aliases' => ['report', 'bao_cao', 'conference_report', 'presentation'],
                'distribution_strategy' => 'per_lecturer_fixed',
                'hours_per_occurrence' => 40,
            ],
            [
                'kind' => 'conference',
                'type' => 'attend',
                'type_aliases' => ['attend', 'tham_du', 'conference_attend'],
                'distribution_strategy' => 'per_lecturer_fixed',
                'hours_per_occurrence' => 4,
                'max_occurrences_per_year' => 40,
            ],
            [
                'kind' => 'project',
                'type' => 'bo',
                'type_aliases' => ['bo', 'ministry', 'cap_bo', 'project_bo'],
                'distribution_strategy' => 'principal_fraction_others_equal',
                // Quy tắc đề tài cấp Bộ: Chủ nhiệm 720h + quỹ giờ thành viên 480h.
                'hours_total_per_activity' => 720,
                'hours_per_occurrence' => 480,
            ],
            [
                'kind' => 'project',
                'type' => 'coso',
                'type_aliases' => ['coso', 'co_so', 'university', 'cap_truong', 'project_university'],
                'distribution_strategy' => 'principal_fraction_others_equal',
                // Quy tắc đề tài cấp cơ sở: Chủ nhiệm 600h + quỹ giờ thành viên 240h.
                'hours_total_per_activity' => 600,
                'hours_per_occurrence' => 240,
            ],
        ];

        foreach ($years as $year) {
            foreach ($ruleTemplates as $rule) {
                $kindId = $kindIds[$rule['kind']] ?? null;
                $typeId = $this->resolveTypeId(
                    $typeIds,
                    (int) $kindId,
                    array_merge([$rule['type']], $rule['type_aliases'] ?? [])
                );

                if (! $kindId || ! $typeId) {
                    continue;
                }

                DB::table('hour_rules')->updateOrInsert(
                    [
                        'kind_id' => $kindId,
                        'type_id' => $typeId,
                        'version' => 1,
                        'effective_from' => $year->start_date,
                    ],
                    [
                        'distribution_strategy' => $rule['distribution_strategy'],
                        'hours_total_per_activity' => $rule['hours_total_per_activity'] ?? null,
                        'hours_per_occurrence' => $rule['hours_per_occurrence'] ?? null,
                        'principal_fraction' => $rule['principal_fraction'] ?? null,
                        'others_fraction_total' => $rule['others_fraction_total'] ?? null,
                        'max_occurrences_per_year' => $rule['max_occurrences_per_year'] ?? null,
                        'effective_to' => $year->end_date,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        foreach ($years as $year) {
            DB::table('workload_quotas')->updateOrInsert(
                ['academic_year_id' => $year->id],
                [
                    'required_hours' => 600,
                    'notes' => 'Định mức mặc định',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function resolveTypeId(array $typeIds, int $kindId, array $aliases): ?int
    {
        if ($kindId <= 0) {
            return null;
        }

        foreach ($aliases as $alias) {
            $alias = trim((string) $alias);
            if ($alias !== '' && isset($typeIds[$alias])) {
                return (int) $typeIds[$alias];
            }
        }

        $normalizedAliases = collect($aliases)
            ->map(fn($alias) => $this->normalizeToken((string) $alias))
            ->filter()
            ->values()
            ->all();

        if ($normalizedAliases === []) {
            return null;
        }

        $row = DB::table('activity_types')
            ->where('kind_id', $kindId)
            ->select(['id', 'code'])
            ->get()
            ->first(function ($type) use ($normalizedAliases) {
                $code = $this->normalizeToken((string) $type->code);
                foreach ($normalizedAliases as $alias) {
                    if ($alias === $code || str_contains($code, $alias) || str_contains($alias, $code)) {
                        return true;
                    }
                }

                return false;
            });

        return $row ? (int) $row->id : null;
    }

    private function normalizeToken(string $value): string
    {
        $ascii = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $ascii);

        return trim((string) $normalized, '_');
    }
}
