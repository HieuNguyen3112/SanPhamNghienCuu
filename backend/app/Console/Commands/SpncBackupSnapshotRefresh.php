<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpncBackupSnapshotRefresh extends Command
{
    protected $signature = 'spnc:backup:snapshots:refresh
        {--run-id= : Mã run theo dõi refresh}
        {--trigger=manual : Nguồn kích hoạt}
        {--initiated-by= : user_id kích hoạt}';

    protected $description = 'Làm mới cache danh sách snapshot backup từ repository restic.';

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
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;

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
                'operation' => 'snapshot_refresh',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'message' => 'Đã xếp lịch làm mới danh sách snapshot.',
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'snapshot_refresh',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
            ]);
        }
        $this->snapshotStore->markRefreshing($runId);

        $lock = Cache::lock('spnc:backup:snapshots:refresh', 1800);
        if (! $lock->get()) {
            $message = 'Đang có một tiến trình làm mới snapshot khác chạy.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'snapshot_refresh',
                'step' => 'failed',
                'message' => $message,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $message,
            ]);
            $this->snapshotStore->markRefreshFailed($message, $runId);
            $this->error($message);
            return self::FAILURE;
        }

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'loading_snapshots',
                'started_at' => now()->toIso8601String(),
                'message' => 'Đang đồng bộ danh sách bản sao lưu...',
            ]);

            $limit = max(10, (int) config('backup.snapshot_cache.max_items', 200));
            $snapshots = $this->backupManager->listSnapshots($limit);
            $cache = $this->snapshotStore->replace($snapshots, $runId);

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'snapshot_refresh',
                'step' => 'completed',
                'message' => 'Làm mới danh sách snapshot hoàn tất.',
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'snapshot_count' => count($snapshots),
                    'refreshed_at' => $cache['refreshed_at'] ?? null,
                ],
            ]);

            $this->info('Snapshot cache refreshed. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $userMessage = $this->toUserFacingFailureMessage($exception);
            Log::error('backup.snapshot_refresh_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'snapshot_refresh',
                'step' => 'failed',
                'message' => $userMessage,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->snapshotStore->markRefreshFailed($userMessage, $runId);

            $this->error('Snapshot refresh failed: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }

    private function toUserFacingFailureMessage(\Throwable $exception): string
    {
        $raw = Str::lower(trim((string) $exception->getMessage()));
        if (
            str_contains($raw, 'timed out')
            || str_contains($raw, 'timeout')
            || str_contains($raw, 'exceeded the timeout')
            || str_contains($raw, 'quá thời gian')
        ) {
            return 'Đồng bộ danh sách bị quá thời gian. Vui lòng thử lại.';
        }

        return 'Đồng bộ danh sách thất bại. Vui lòng thử lại.';
    }
}
