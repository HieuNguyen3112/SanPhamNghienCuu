<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeVietnameseLabelsCommand extends Command
{
    protected $signature = 'spnc:normalize-vietnamese-labels {--dry-run : Chỉ hiển thị thay đổi}';

    protected $description = 'Chuẩn hóa nhãn danh mục sang tiếng Việt có dấu (idempotent, không đổi code/key).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $totalUpdated = 0;

        $labelMappings = [
            'activity_kinds' => [
                'paper' => 'Bài báo khoa học',
                'book' => 'Sách, giáo trình',
                'project' => 'Đề tài KH&CN',
                'conference' => 'Hội nghị, hội thảo',
            ],
            'activity_types' => [
                'hdgsnn_900' => 'Bài báo HDGSNN 1-2 điểm (900 giờ)',
                'hdgsnn_600' => 'Bài báo HDGSNN <= 1 điểm (600 giờ)',
                'hdgsnn_300' => 'Bài báo có ISSN/ISBN (300 giờ)',
                'textbook' => 'Giáo trình',
                'reference' => 'Tài liệu tham khảo',
                'bo' => 'Đề tài cấp Bộ (2 năm)',
                'coso' => 'Đề tài cấp Trường (1 năm)',
                'report' => 'Báo cáo hội thảo',
                'attend' => 'Tham dự hội thảo',
            ],
            'member_roles' => [
                'principal' => 'Chủ nhiệm',
                'member' => 'Thành viên',
                'secretary' => 'Thư ký',
                'corresponding_author' => 'Tác giả chính',
                'coauthor' => 'Đồng tác giả',
                'chief_editor' => 'Chủ biên',
            ],
            'activity_statuses' => [
                'draft' => 'Bản nháp',
                'pending_member_confirm' => 'Chờ thành viên xác nhận',
                'member_rejected' => 'Thành viên từ chối',
                'pending_faculty_review' => 'Chờ khoa duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
            ],
            'approval_stages' => [
                'assistant' => 'Khoa duyệt nội dung',
                'hours' => 'Khoa duyệt giờ NCKH',
            ],
            'evidence_file_types' => [
                'content' => 'Toàn văn',
                'cover' => 'Trang bìa',
                'toc' => 'Mục lục',
                'acceptance_decision' => 'Quyết định nghiệm thu',
                'publication_decision' => 'Quyết định xuất bản',
            ],
        ];

        DB::transaction(function () use ($labelMappings, $dryRun, &$totalUpdated) {
            foreach ($labelMappings as $table => $mapping) {
                foreach ($mapping as $code => $targetName) {
                    $row = DB::table($table)
                        ->select(['id', 'name'])
                        ->where('code', $code)
                        ->first();

                    if (! $row) {
                        continue;
                    }
                    if ((string) $row->name === (string) $targetName) {
                        continue;
                    }

                    $this->line("[{$table}] code={$code}: '{$row->name}' => '{$targetName}'");
                    $totalUpdated++;

                    if (! $dryRun) {
                        $updatePayload = ['name' => $targetName];
                        if ($this->hasColumn($table, 'updated_at')) {
                            $updatePayload['updated_at'] = now();
                        }
                        DB::table($table)->where('id', $row->id)->update($updatePayload);
                    }
                }
            }

            $this->normalizeProjectRules($dryRun, $totalUpdated);
        });

        $this->info(
            'Hoàn tất chuẩn hóa nhãn tiếng Việt'
                . ($dryRun ? ' (dry-run)' : '')
                . '. Số bản ghi thay đổi: ' . $totalUpdated
        );

        return self::SUCCESS;
    }

    private function normalizeProjectRules(bool $dryRun, int &$totalUpdated): void
    {
        $projectKindId = DB::table('activity_kinds')->where('code', 'project')->value('id');
        if (! $projectKindId) {
            return;
        }

        $typeIds = DB::table('activity_types')
            ->whereIn('code', ['bo', 'coso'])
            ->pluck('id', 'code')
            ->all();
        if (empty($typeIds)) {
            return;
        }

        $targets = [
            'bo' => ['leader' => 720.0, 'member_pool' => 480.0],
            'coso' => ['leader' => 600.0, 'member_pool' => 240.0],
        ];

        foreach ($targets as $typeCode => $target) {
            $typeId = $typeIds[$typeCode] ?? null;
            if (! $typeId) {
                continue;
            }

            $rows = DB::table('hour_rules')
                ->where('kind_id', $projectKindId)
                ->where('type_id', $typeId)
                ->get(['id', 'distribution_strategy', 'hours_total_per_activity', 'hours_per_occurrence']);

            foreach ($rows as $row) {
                $sameStrategy = (string) $row->distribution_strategy === 'principal_fraction_others_equal';
                $sameLeader = (float) $row->hours_total_per_activity === (float) $target['leader'];
                $samePool = (float) $row->hours_per_occurrence === (float) $target['member_pool'];
                if ($sameStrategy && $sameLeader && $samePool) {
                    continue;
                }

                $this->line(
                    '[hour_rules] id=' . $row->id
                        . ' (type=' . $typeCode . '): '
                        . 'strategy=' . $row->distribution_strategy
                        . ', total=' . $row->hours_total_per_activity
                        . ', pool=' . $row->hours_per_occurrence
                        . ' => strategy=principal_fraction_others_equal'
                        . ', total=' . $target['leader']
                        . ', pool=' . $target['member_pool']
                );
                $totalUpdated++;

                if (! $dryRun) {
                    DB::table('hour_rules')
                        ->where('id', $row->id)
                        ->update([
                            'distribution_strategy' => 'principal_fraction_others_equal',
                            'hours_total_per_activity' => $target['leader'],
                            'hours_per_occurrence' => $target['member_pool'],
                            'principal_fraction' => null,
                            'others_fraction_total' => null,
                            'max_occurrences_per_year' => null,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, DB::getSchemaBuilder()->getColumnListing($table), true);
    }
}
