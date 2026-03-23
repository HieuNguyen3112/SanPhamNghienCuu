<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $paperKindId = DB::table('activity_kinds')
            ->where('code', 'paper')
            ->value('id');

        if (! $paperKindId) {
            return;
        }

        DB::table('activity_kinds')
            ->where('id', $paperKindId)
            ->update([
                'name' => 'Bài báo / Báo cáo khoa học',
                'updated_at' => $now,
            ]);

        $scientificReportTypeId = DB::table('activity_types')
            ->where('code', 'scientific_report')
            ->value('id');

        if ($scientificReportTypeId) {
            DB::table('activity_types')
                ->where('id', $scientificReportTypeId)
                ->update([
                    'kind_id' => (int) $paperKindId,
                    'name' => 'Báo cáo khoa học (300 giờ)',
                    'updated_at' => $now,
                ]);
        } else {
            $scientificReportTypeId = DB::table('activity_types')->insertGetId([
                'kind_id' => (int) $paperKindId,
                'code' => 'scientific_report',
                'name' => 'Báo cáo khoa học (300 giờ)',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $academicYears = DB::table('academic_years')
            ->select(['start_date', 'end_date'])
            ->get();

        foreach ($academicYears as $year) {
            DB::table('hour_rules')->updateOrInsert(
                [
                    'kind_id' => (int) $paperKindId,
                    'type_id' => (int) $scientificReportTypeId,
                    'version' => 1,
                    'effective_from' => $year->start_date,
                ],
                [
                    'distribution_strategy' => 'equal_all_members',
                    'hours_total_per_activity' => 300,
                    'hours_per_occurrence' => null,
                    'principal_fraction' => null,
                    'others_fraction_total' => null,
                    'max_occurrences_per_year' => null,
                    'effective_to' => $year->end_date,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $now = now();

        $paperKindId = DB::table('activity_kinds')
            ->where('code', 'paper')
            ->value('id');

        if ($paperKindId) {
            DB::table('activity_kinds')
                ->where('id', $paperKindId)
                ->update([
                    'name' => 'Bài báo khoa học',
                    'updated_at' => $now,
                ]);
        }

        $scientificReportTypeId = DB::table('activity_types')
            ->where('code', 'scientific_report')
            ->value('id');

        if (! $scientificReportTypeId) {
            return;
        }

        DB::table('hour_rules')
            ->where('type_id', (int) $scientificReportTypeId)
            ->delete();

        DB::table('activity_types')
            ->where('id', (int) $scientificReportTypeId)
            ->delete();
    }
};
