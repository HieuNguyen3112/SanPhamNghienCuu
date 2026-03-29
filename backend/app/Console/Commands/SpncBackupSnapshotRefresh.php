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
        {--run-id= : Ma run theo doi refresh}
        {--trigger=manual : Nguon kich hoat}
        {--initiated-by= : user_id kich hoat}';

    protected $description = 'Lam moi cache danh sach snapshot backup tu repository restic.';

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
                'message' => 'Da xep lich lam moi danh sach snapshot.',
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
            $message = 'Dang co mot tien trinh lam moi snapshot khac chay.';
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
                'step' => 'probing_drive',
                'started_at' => now()->toIso8601String(),
                'message' => 'Dang kiem tra Google Drive backup...',
            ]);

            $this->backupManager->assertDriveReadiness('snapshot_refresh');

            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'opening_repository',
                'message' => 'Dang mo repository backup...',
            ]);

            $limit = max(10, (int) config('backup.snapshot_cache.max_items', 200));
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'listing_snapshots',
                'message' => 'Dang dong bo danh sach ban sao luu...',
            ]);
            $snapshots = $this->backupManager->listSnapshots($limit);
            $cache = $this->snapshotStore->replace($snapshots, $runId);

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'snapshot_refresh',
                'step' => 'ready',
                'message' => 'Lam moi danh sach snapshot hoan tat.',
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
        if (str_contains($raw, 'service_account_invalid') || str_contains($raw, 'service_account_required')) {
            return 'Google Drive backup chua san sang vi service account chua duoc nap dung. Vui long kiem tra cau hinh production.';
        }

        if (str_contains($raw, 'rclone_service_account_required') || str_contains($raw, 'drive_auth_invalid')) {
            return 'Production chi ho tro Google Drive backup bang service account. Hay kiem tra lai remote spnc_gdrive.';
        }

        if (str_contains($raw, 'drive_remote_inaccessible')) {
            return 'Khong the truy cap thu muc Google Drive backup. Vui long kiem tra root_folder_id va quyen chia se cho service account.';
        }

        if (str_contains($raw, 'repository_access_failed')) {
            return 'Google Drive da truy cap duoc nhung repository backup chua mo duoc. Vui long kiem tra restic repository.';
        }

        if (
            str_contains($raw, 'timed out')
            || str_contains($raw, 'timeout')
            || str_contains($raw, 'exceeded the timeout')
            || str_contains($raw, 'qua thoi gian')
        ) {
            return 'Dong bo danh sach bi qua thoi gian. Vui long thu lai.';
        }

        return 'Dong bo danh sach that bai. Vui long thu lai.';
    }
}
