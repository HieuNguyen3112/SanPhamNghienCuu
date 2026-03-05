<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use App\Support\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpncBackupRestore extends Command
{
    protected $signature = 'spnc:backup:restore
        {--run-id= : Mã run để theo dõi trạng thái}
        {--snapshot-id= : Mã snapshot cần khôi phục}
        {--scope=full : db_only/files_only/full}
        {--target=staging : staging/current}
        {--trigger=manual : Nguồn kích hoạt}
        {--initiated-by= : user_id kích hoạt}';

    protected $description = 'Khôi phục snapshot backup theo chế độ bất đồng bộ.';

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
        $snapshotId = trim((string) $this->option('snapshot-id'));
        $scope = trim((string) $this->option('scope'));
        $target = trim((string) $this->option('target'));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;

        try {
            $this->stateStore->assertValidRunId($runId);
            $this->backupManager->assertValidSnapshotId($snapshotId);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $existing = $this->stateStore->get($runId);
        if (! $existing) {
            $this->stateStore->initialize($runId, [
                'status' => 'queued',
                'operation' => 'restore',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'snapshot_id' => $snapshotId,
                'scope' => $scope,
                'target' => $target,
                'message' => 'Đã xếp lịch khôi phục snapshot.',
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'restore',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
                'snapshot_id' => $snapshotId,
                'scope' => $scope,
                'target' => $target,
            ]);
        }
        $this->stateStore->appendLog($runId, 'Bắt đầu tiến trình restore snapshot ' . $snapshotId . '.');

        $lock = Cache::lock('spnc:backup:restore', 21600);
        if (! $lock->get()) {
            $message = 'Đang có tiến trình restore khác chạy.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'restore',
                'step' => 'failed',
                'message' => $message,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            $this->error($message);
            return self::FAILURE;
        }

        $maintenanceEnabled = false;
        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'restore',
                'step' => 'restoring_snapshot',
                'started_at' => now()->toIso8601String(),
                'message' => 'Đang khôi phục dữ liệu từ snapshot...',
            ]);

            if ($target === 'current') {
                Artisan::call('down', ['--retry' => 60]);
                $maintenanceEnabled = true;
                $this->stateStore->appendLog($runId, 'Đã bật maintenance mode để khôi phục trực tiếp.');
            }

            $result = $this->backupManager->restoreSnapshot($snapshotId, $scope, $target, $runId);
            $this->stateStore->appendLog($runId, 'Khôi phục snapshot hoàn tất, đang cập nhật cache snapshot...');
            if (! empty($result['stdout'])) {
                $this->stateStore->appendLog($runId, 'restore.stdout: ' . (string) $result['stdout']);
            }
            if (! empty($result['stderr'])) {
                $this->stateStore->appendLog($runId, 'restore.stderr: ' . (string) $result['stderr'], 'warning');
            }

            $limit = max(10, (int) config('backup.snapshot_cache.max_items', 200));
            $snapshots = $this->backupManager->listSnapshots($limit);
            $this->snapshotStore->replace($snapshots, $runId);

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'restore',
                'step' => 'completed',
                'message' => 'Khôi phục dữ liệu hoàn tất.',
                'finished_at' => now()->toIso8601String(),
                'result' => $result,
            ]);
            $this->stateStore->appendLog($runId, 'Khôi phục thành công.');

            $this->writeAudit(
                $initiatedBy,
                'BACKUP_RESTORE_COMPLETED',
                'Khôi phục sao lưu hệ thống thành công',
                'success',
                $snapshotId,
                [
                    'scope' => $scope,
                    'target' => $target,
                    'run_id' => $runId,
                ]
            );

            $this->info('Restore hoàn tất. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.restore_command_failed', [
                'run_id' => $runId,
                'snapshot_id' => $snapshotId,
                'scope' => $scope,
                'target' => $target,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'restore',
                'step' => 'failed',
                'message' => 'Khôi phục dữ liệu thất bại.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Khôi phục thất bại: ' . $exception->getMessage(), 'error');

            $this->writeAudit(
                $initiatedBy,
                'BACKUP_RESTORE_FAILED',
                'Khôi phục sao lưu hệ thống thất bại',
                'failure',
                $snapshotId,
                [
                    'scope' => $scope,
                    'target' => $target,
                    'run_id' => $runId,
                ],
                $exception->getMessage()
            );

            $this->error('Restore thất bại: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            if ($maintenanceEnabled) {
                Artisan::call('up');
            }
            optional($lock)->release();
        }
    }

    private function writeAudit(
        ?int $initiatedBy,
        string $actionCode,
        string $actionLabel,
        string $resultStatus,
        string $snapshotId,
        array $changes = [],
        ?string $errorMessage = null
    ): void {
        $actor = null;
        if ($initiatedBy && $initiatedBy > 0) {
            $actor = User::query()->find($initiatedBy);
        }

        AuditLogger::log(null, [
            'action_group' => 'security',
            'action_code' => $actionCode,
            'action_label' => $actionLabel,
            'target_type' => 'backup_snapshot',
            'target_id' => $snapshotId,
            'target_display' => 'Snapshot ' . $snapshotId,
            'result_status' => $resultStatus,
            'result_error_message' => $errorMessage,
            'changes' => $changes,
            'request_path' => '/api/admin/backups/' . $snapshotId . '/restore',
            'request_method' => 'POST',
            'note' => 'background_restore=true',
            'actor_user_id' => $initiatedBy,
        ], $actor);
    }
}
