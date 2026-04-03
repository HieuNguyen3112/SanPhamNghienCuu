<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('activity_statuses')->updateOrInsert(
            ['code' => 'need_revision'],
            [
                'name' => 'Cần chỉnh sửa',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $submittedId = DB::table('activity_statuses')->where('code', 'submitted')->value('id');
        $pendingFacultyReviewId = DB::table('activity_statuses')->where('code', 'pending_faculty_review')->value('id');

        if (! $submittedId || ! $pendingFacultyReviewId) {
            return;
        }

        $submittedId = (int) $submittedId;
        $pendingFacultyReviewId = (int) $pendingFacultyReviewId;

        DB::table('research_activities')
            ->where('status_id', $submittedId)
            ->update([
                'status_id' => $pendingFacultyReviewId,
                'updated_at' => $now,
            ]);

        DB::table('activity_status_histories')
            ->where('from_status_id', $submittedId)
            ->update([
                'from_status_id' => $pendingFacultyReviewId,
                'updated_at' => $now,
            ]);

        DB::table('activity_status_histories')
            ->where('to_status_id', $submittedId)
            ->update([
                'to_status_id' => $pendingFacultyReviewId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $needRevisionId = DB::table('activity_statuses')->where('code', 'need_revision')->value('id');
        if (! $needRevisionId) {
            return;
        }

        $needRevisionId = (int) $needRevisionId;

        $isUsed = DB::table('research_activities')->where('status_id', $needRevisionId)->exists()
            || DB::table('activity_status_histories')->where('from_status_id', $needRevisionId)->exists()
            || DB::table('activity_status_histories')->where('to_status_id', $needRevisionId)->exists();

        if (! $isUsed) {
            DB::table('activity_statuses')->where('id', $needRevisionId)->delete();
        }
    }
};
