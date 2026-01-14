<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchHoursUsageSeeder extends Seeder
{
    public function run(): void
    {
        $activityId = DB::table('research_activities')->value('id');
        $ruleId = DB::table('hour_rules')->value('id');

        if (! $activityId || ! $ruleId) {
            return;
        }

        $exists = DB::table('calculation_logs')
            ->where('activity_id', $activityId)
            ->where('rule_id', $ruleId)
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();
        DB::table('calculation_logs')->insert([
            'activity_id' => $activityId,
            'executed_at' => $now,
            'rule_id' => $ruleId,
            'input_snapshot' => json_encode([
                'members' => [],
                'quantity' => 1,
            ], JSON_UNESCAPED_UNICODE),
            'result_snapshot' => json_encode([
                'total_hours' => 10,
            ], JSON_UNESCAPED_UNICODE),
            'total_hours' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
