<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $paperKindId = DB::table('activity_kinds')->where('code', 'paper')->value('id');
        if (! $paperKindId) {
            return;
        }

        $types = [
            'scientific_report_900' => ['name' => 'Báo cáo khoa học (900 giờ)', 'hours' => 900],
            'scientific_report_600' => ['name' => 'Báo cáo khoa học (600 giờ)', 'hours' => 600],
            'scientific_report' => ['name' => 'Báo cáo khoa học (300 giờ)', 'hours' => 300],
        ];

        $typeIds = [];
        foreach ($types as $code => $meta) {
            $existingId = DB::table('activity_types')->where('code', $code)->value('id');
            if ($existingId) {
                DB::table('activity_types')
                    ->where('id', (int) $existingId)
                    ->update([
                        'kind_id' => (int) $paperKindId,
                        'name' => $meta['name'],
                        'updated_at' => $now,
                    ]);
                $typeIds[$code] = (int) $existingId;
                continue;
            }

            $typeIds[$code] = (int) DB::table('activity_types')->insertGetId([
                'kind_id' => (int) $paperKindId,
                'code' => $code,
                'name' => $meta['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $academicYears = DB::table('academic_years')->select(['start_date', 'end_date'])->get();
        foreach ($academicYears as $year) {
            foreach ($types as $code => $meta) {
                DB::table('hour_rules')->updateOrInsert(
                    [
                        'kind_id' => (int) $paperKindId,
                        'type_id' => $typeIds[$code],
                        'version' => 1,
                        'effective_from' => $year->start_date,
                    ],
                    [
                        'distribution_strategy' => 'equal_all_members',
                        'hours_total_per_activity' => $meta['hours'],
                        'hours_per_occurrence' => null,
                        'principal_fraction' => null,
                        'others_fraction_total' => null,
                        'max_occurrences_per_year' => null,
                        'effective_to' => $year->end_date,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = ['scientific_report_900', 'scientific_report_600'];
        $ids = DB::table('activity_types')->whereIn('code', $codes)->pluck('id')->all();

        if (! empty($ids)) {
            DB::table('hour_rules')->whereIn('type_id', $ids)->delete();
            DB::table('activity_types')->whereIn('id', $ids)->delete();
        }
    }
};
