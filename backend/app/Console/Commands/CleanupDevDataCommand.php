<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDevDataCommand extends Command
{
    protected $signature = 'spnc:cleanup-dev-data {--execute : Thực thi xóa dữ liệu} {--limit=200 : Giới hạn số công trình xử lý}';

    protected $description = 'Dọn dữ liệu dev/test rõ ràng theo pattern an toàn (không chạy tự động).';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $limit = max(1, (int) $this->option('limit'));

        $candidates = $this->findCandidateActivities($limit);
        if ($candidates->isEmpty()) {
            $this->info('Không tìm thấy dữ liệu dev/test phù hợp để dọn.');
            return self::SUCCESS;
        }

        $this->warn('Danh sách công trình ứng viên (dev/test):');
        foreach ($candidates as $row) {
            $this->line(sprintf(
                ' - #%d [%s] %s (owner: %s)',
                (int) $row->id,
                (string) $row->activity_code,
                (string) $row->title,
                (string) ($row->owner_email ?? 'unknown')
            ));
        }

        if (! $execute) {
            $this->info('Đang ở chế độ an toàn (dry-run). Thêm --execute để thực thi xóa.');
            return self::SUCCESS;
        }

        if (! app()->environment('local')) {
            $this->error('Chỉ cho phép chạy --execute ở môi trường local.');
            return self::FAILURE;
        }

        $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
        DB::transaction(function () use ($ids) {
            foreach ([
                'activity_approvals',
                'activity_status_histories',
                'research_activity_members',
                'evidence_files',
                'calculation_logs',
                'paper_details',
                'book_details',
                'project_details',
                'conference_details',
            ] as $table) {
                DB::table($table)->whereIn('activity_id', $ids)->delete();
            }

            DB::table('research_activities')->whereIn('id', $ids)->delete();
        });

        $this->info('Đã xóa ' . count($ids) . ' công trình dev/test.');

        return self::SUCCESS;
    }

    private function findCandidateActivities(int $limit)
    {
        return DB::table('research_activities as ra')
            ->leftJoin('lecturers as l', 'l.id', '=', 'ra.owner_lecturer_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->select(['ra.id', 'ra.activity_code', 'ra.title', 'u.email as owner_email'])
            ->where(function ($query) {
                $query->where('ra.title', 'like', 'Test%')
                    ->orWhere('ra.title', 'like', 'TEST%')
                    ->orWhere('ra.title', 'like', 'Draft %')
                    ->orWhere('ra.title', 'like', 'Bài báo bản nháp%')
                    ->orWhere('ra.activity_code', 'like', 'TEST-%')
                    ->orWhere('ra.activity_code', 'like', 'DRAFT-%')
                    ->orWhere('ra.notes', 'like', '%seed%');
            })
            ->where(function ($query) {
                $query->where('u.email', 'like', '%@local.test')
                    ->orWhere('u.email', 'like', '%+seed@%');
            })
            ->orderByDesc('ra.id')
            ->limit($limit)
            ->get();
    }
}
