<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpncBackupRun extends Command
{
    protected $signature = 'spnc:backup:run
        {--run-id= : Mã run để theo dõi trạng thái}
        {--trigger=manual : Nguồn kích hoạt backup (manual/schedule)}
        {--initiated-by= : user_id kích hoạt}
        {--skip-prune : Bỏ qua bước dọn snapshot cũ}';

    protected $description = 'Chạy backup SPNC (DB + tệp nhạy cảm) bằng restic.';

    private ResticBackupManager $backupManager;
    private BackupRunStateStore $stateStore;
    private BackupSnapshotStore $snapshotStore;

    public function __construct(
        ResticBackupManager $backupManager,
        BackupRunStateStore $stateStore,
        BackupSnapshotStore $snapshotStore
    ) {
        parent::__construct();
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
        $this->snapshotStore = $snapshotStore;
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        if ($trigger === '') {
            $trigger = 'manual';
        }
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;
        $skipPrune = (bool) $this->option('skip-prune');

        try {
            $this->stateStore->assertValidRunId($runId);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $existing = $this->stateStore->get($runId);
        if (! $existing) {
            $this->stateStore->initialize($runId, [
                'status' => 'queued',
                'operation' => 'backup',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'backup',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
            ]);
        }
        $this->stateStore->appendLog($runId, 'Bắt đầu tiến trình backup. trigger=' . $trigger);

        $lock = Cache::lock('spnc:backup:run', 21600);
        if (! $lock->get()) {
            $message = 'Đang có một bản sao lưu đang chạy. Vui lòng đợi hoàn tất.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup',
                'finished_at' => now()->toIso8601String(),
                'message' => $message,
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            $this->error($message);
            return self::FAILURE;
        }

        try {
            if (strtolower($trigger) === 'schedule' && $this->shouldSkipScheduleRun($runId)) {
                $message = 'Bỏ qua backup lịch vì lần chạy gần nhất chưa đạt khoảng cách theo cấu hình.';
                $this->stateStore->update($runId, [
                    'status' => 'success',
                    'operation' => 'backup',
                    'step' => 'skipped_schedule_window',
                    'message' => $message,
                    'finished_at' => now()->toIso8601String(),
                    'result' => [
                        'skipped' => true,
                        'reason' => 'schedule_window_guard',
                    ],
                ]);
                $this->stateStore->appendLog($runId, $message, 'info');
                Log::warning('backup.schedule_run_skipped_frequency_guard', [
                    'run_id' => $runId,
                    'trigger' => $trigger,
                ]);
                $this->info($message . ' run_id=' . $runId);
                return self::SUCCESS;
            }

            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'backup',
                'started_at' => now()->toIso8601String(),
                'step' => 'running_backup',
                'message' => 'Đang tạo snapshot backup...',
            ]);
            $this->stateStore->appendLog($runId, 'Đang chạy restic backup...');

            $result = $this->backupManager->runBackup($runId, $trigger, $initiatedBy);
            $this->stateStore->appendLog($runId, 'Tạo snapshot hoàn tất.');
            $summary = $result['summary'] ?? null;
            if (is_array($summary)) {
                $this->stateStore->appendLog(
                    $runId,
                    'Tóm tắt backup: ' . json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
            $export = $result['export'] ?? null;
            if (is_array($export)) {
                if ((bool) ($export['available'] ?? false)) {
                    $this->stateStore->appendLog(
                        $runId,
                        'Đã tạo export dễ đọc: ' . (string) ($export['export_path'] ?? 'không rõ đường dẫn')
                    );
                } else {
                    $this->stateStore->appendLog(
                        $runId,
                        'Không thể tạo export dễ đọc: ' . (string) ($export['error'] ?? 'lỗi không xác định'),
                        'warning'
                    );
                }
            }

            $pruneResult = null;
            if (! $skipPrune) {
                $this->stateStore->update($runId, [
                    'step' => 'pruning',
                    'message' => 'Đang dọn snapshot cũ theo retention policy...',
                ]);
                $this->stateStore->appendLog($runId, 'Đang chạy prune theo retention policy...');
                $pruneResult = $this->backupManager->pruneBackups();
                $this->stateStore->appendLog($runId, 'Prune hoàn tất.');
            }

            $limit = max(10, (int) config('backup.snapshot_cache.max_items', 200));
            $snapshots = $this->backupManager->listSnapshots($limit);
            $this->snapshotStore->replace($snapshots, $runId);
            $this->stateStore->appendLog($runId, 'Đã đồng bộ cache snapshot sau backup.');

            $payload = [
                'status' => 'success',
                'operation' => 'backup',
                'step' => 'completed',
                'message' => 'Backup hoàn tất.',
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'snapshot' => $result['snapshot'] ?? null,
                    'summary' => $result['summary'] ?? null,
                    'manifest_relative_path' => $result['manifest_relative_path'] ?? null,
                    'db_dump_relative_path' => $result['db_dump_relative_path'] ?? null,
                    'workspace_relative_path' => $result['workspace_relative_path'] ?? null,
                    'verification' => $result['verification'] ?? null,
                    'check' => $result['check'] ?? null,
                    'export' => $result['export'] ?? null,
                    'prune' => $pruneResult,
                ],
            ];
            $this->stateStore->update($runId, $payload);

            $this->info('Backup hoàn tất. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.run_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup',
                'step' => 'failed',
                'message' => 'Backup thất bại.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Backup thất bại: ' . $exception->getMessage(), 'error');

            $this->error('Backup thất bại: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }

    private function shouldSkipScheduleRun(string $runId): bool
    {
        $minGapSeconds = $this->scheduleMinGapSeconds();
        $threshold = now()->subSeconds($minGapSeconds);

        foreach ($this->stateStore->listRecent(240) as $state) {
            if (! is_array($state)) {
                continue;
            }

            $candidateRunId = trim((string) ($state['run_id'] ?? ''));
            if ($candidateRunId === '' || $candidateRunId === $runId) {
                continue;
            }

            $operation = strtolower(trim((string) ($state['operation'] ?? '')));
            if ($operation !== 'backup') {
                continue;
            }

            $trigger = strtolower(trim((string) ($state['trigger'] ?? '')));
            if ($trigger !== 'schedule') {
                continue;
            }

            $status = strtolower(trim((string) ($state['status'] ?? '')));
            if (! in_array($status, ['queued', 'running', 'success', 'failed'], true)) {
                continue;
            }

            $referenceAt = $this->resolveRecentScheduleReferenceAt($state);
            if (! $referenceAt) {
                continue;
            }

            return $referenceAt->greaterThan($threshold);
        }

        return false;
    }

    private function scheduleMinGapSeconds(): int
    {
        $days = array_values(array_unique(array_filter(array_map(
            static fn ($day): int => (int) $day,
            (array) config('backup.schedule.days', [1, 4])
        ), static fn (int $day): bool => $day >= 0 && $day <= 6)));
        if ($days === []) {
            $days = [1, 4];
        }

        sort($days);
        $count = count($days);
        $minDayGap = 7;
        for ($i = 0; $i < $count; $i++) {
            $current = $days[$i];
            $next = $days[($i + 1) % $count];
            $gap = $next - $current;
            if ($gap <= 0) {
                $gap += 7;
            }
            $minDayGap = min($minDayGap, $gap);
        }

        return max(3600, ($minDayGap * 86400) - 600);
    }

    private function resolveRecentScheduleReferenceAt(array $state): ?Carbon
    {
        foreach (['started_at', 'requested_at', 'finished_at', 'updated_at'] as $field) {
            $value = trim((string) ($state[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
