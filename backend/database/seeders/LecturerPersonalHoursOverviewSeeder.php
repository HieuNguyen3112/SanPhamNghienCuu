<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LecturerPersonalHoursOverviewSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'gv@local.test')->first();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return;
        }

        $academicYearId = DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('id');

        if (! $academicYearId) {
            return;
        }

        $now = now();
        $hoursStageId = DB::table('approval_stages')->where('code', 'hours')->value('id');
        if (! $hoursStageId) {
            DB::table('approval_stages')->updateOrInsert(
                ['code' => 'hours'],
                [
                    'name' => 'Khoa duyệt giờ NCKH',
                    'order_no' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $hoursStageId = DB::table('approval_stages')->where('code', 'hours')->value('id');
        }

        if (! $hoursStageId) {
            return;
        }

        $adminUserId = DB::table('users')->where('email', 'admin@local.test')->value('id');

        $seedActivities = [
            ['code' => 'RA-PAPER-001', 'status' => 'approved', 'days_ago' => 12],
            ['code' => 'RA-BOOK-001', 'status' => 'approved', 'days_ago' => 12],
        ];

        foreach ($seedActivities as $seed) {
            $activityId = DB::table('research_activities')
                ->where('activity_code', $seed['code'])
                ->value('id');

            if (! $activityId) {
                continue;
            }

            $memberExists = DB::table('research_activity_members')
                ->where('activity_id', $activityId)
                ->where('lecturer_id', $lecturer->id)
                ->exists();

            if (! $memberExists) {
                continue;
            }

            $submittedAt = $now->copy()->subDays($seed['days_ago']);
            $decidedAt = $seed['status'] === 'pending'
                ? null
                : $submittedAt->copy()->addDays(2);

            $note = null;
            if ($seed['status'] === 'rejected') {
                $note = json_encode([
                    'reason_code' => 'missing_evidence',
                    'reason_detail' => 'Thiếu minh chứng',
                ], JSON_UNESCAPED_UNICODE);
            }

            DB::table('activity_approvals')->updateOrInsert(
                [
                    'activity_id' => $activityId,
                    'stage_id' => $hoursStageId,
                ],
                [
                    'status' => $seed['status'],
                    'decided_by_user_id' => $seed['status'] === 'pending' ? null : $adminUserId,
                    'decided_at' => $decidedAt,
                    'note' => $note,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            );
        }
    }
}
