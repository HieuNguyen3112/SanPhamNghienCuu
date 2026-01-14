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
            ['kind' => 'paper', 'type' => 'hdgsnn_900', 'hours' => 40],
            ['kind' => 'paper', 'type' => 'hdgsnn_600', 'hours' => 30],
            ['kind' => 'book', 'type' => 'textbook', 'hours' => 80],
            ['kind' => 'project', 'type' => 'bo', 'hours' => 120],
            ['kind' => 'conference', 'type' => 'report', 'hours' => 10],
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
                        'distribution_strategy' => 'equal_all_members',
                        'hours_total_per_activity' => $rule['hours'],
                        'hours_per_occurrence' => null,
                        'principal_fraction' => null,
                        'others_fraction_total' => null,
                        'max_occurrences_per_year' => null,
                        'effective_to' => $year->end_date,
                        'is_active' => (bool) $year->is_active,
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
                    'required_hours' => 300,
                    'notes' => 'Default quota',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
