<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchHoursCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $academicYears = [
            ['code' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-08-31', 'is_active' => false],
            ['code' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-08-31', 'is_active' => true],
            ['code' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-08-31', 'is_active' => false],
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
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 900,
            ],
            [
                'kind' => 'paper',
                'type' => 'hdgsnn_600',
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 600,
            ],
            [
                'kind' => 'paper',
                'type' => 'hdgsnn_300',
                'distribution_strategy' => 'equal_all_members',
                'hours_total_per_activity' => 300,
            ],
            [
                'kind' => 'book',
                'type' => 'textbook',
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 900,
                'principal_fraction' => 0.2,
                'others_fraction_total' => 0.8,
            ],
            [
                'kind' => 'book',
                'type' => 'reference',
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 600,
                'principal_fraction' => 0.2,
                'others_fraction_total' => 0.8,
            ],
            [
                'kind' => 'conference',
                'type' => 'report',
                'distribution_strategy' => 'per_lecturer_fixed',
                'hours_per_occurrence' => 40,
            ],
            [
                'kind' => 'conference',
                'type' => 'attend',
                'distribution_strategy' => 'per_lecturer_fixed',
                'hours_per_occurrence' => 4,
                'max_occurrences_per_year' => 40,
            ],
            [
                'kind' => 'project',
                'type' => 'bo',
                'distribution_strategy' => 'principal_fraction_others_equal',
                // Quy tắc đề tài cấp Bộ: Chủ nhiệm 720h + quỹ giờ thành viên 480h.
                'hours_total_per_activity' => 720,
                'hours_per_occurrence' => 480,
            ],
            [
                'kind' => 'project',
                'type' => 'coso',
                'distribution_strategy' => 'principal_fraction_others_equal',
                // Quy tắc đề tài cấp Trường: Chủ nhiệm 600h + quỹ giờ thành viên 240h.
                'hours_total_per_activity' => 600,
                'hours_per_occurrence' => 240,
            ],
        ];

        foreach ($years as $year) {
            foreach ($ruleTemplates as $rule) {
                $kindId = $kindIds[$rule['kind']] ?? null;
                $typeId = $typeIds[$rule['type']] ?? null;
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
}
