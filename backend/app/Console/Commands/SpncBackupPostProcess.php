<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpncBackupPostProcess extends Command
{
    protected $signature = 'spnc:backup:post-process
        {--run-id= : Mã run để theo dõi trạng thái}
        {--snapshot-id= : Snapshot ID cần tạo readable export}
        {--trigger=manual : Nguồn kích hoạt}
        {--initiated-by= : user_id kích hoạt}';

    protected $description = 'Hoàn thiện export readable và đồng bộ Drive sau khi snapshot backup đã an toàn.';

    public function __construct(
        private ResticBackupManager $backupManager,
        private BackupRunStateStore $stateStore,
        private BackupSnapshotStore $snapshotStore
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $snapshotId = trim((string) ($this->option('snapshot-id') ?: ''));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;

        if ($trigger === '') {
            $trigger = 'manual';
        }

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
                'operation' => 'backup_postprocess',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'snapshot_id' => $snapshotId,
            ]);
        }

        $lock = Cache::lock('spnc:backup:post-process:' . $snapshotId, 10800);
        if (! $lock->get()) {
            $message = 'Đang có tiến trình hoàn thiện export cho snapshot này.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup_postprocess',
                'step' => 'failed',
                'snapshot_id' => $snapshotId,
                'finished_at' => now()->toIso8601String(),
                'message' => $message,
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            $this->error($message);
            return self::FAILURE;
        }

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'backup_postprocess',
                'started_at' => now()->toIso8601String(),
                'step' => 'building_export',
                'snapshot_id' => $snapshotId,
                'message' => 'Đang tạo readable export và đồng bộ lên Drive...',
            ]);
            $this->stateStore->appendLog($runId, 'Bắt đầu tạo readable export cho snapshot ' . $snapshotId . '.');

            $export = $this->backupManager->generateReadableExportForSnapshot(
                $snapshotId,
                $runId,
                $trigger,
                $initiatedBy
            );

            $this->snapshotStore->mergeBySnapshotId($snapshotId, [
                'export_available' => (bool) ($export['available'] ?? false),
                'export_path' => $export['export_path'] ?? null,
                'export_drive_path' => $export['drive_path'] ?? null,
                'export_generated_at' => $export['generated_at'] ?? null,
                'export_bundle_filename' => $export['bundle_filename'] ?? null,
                'export_artifacts' => array_values((array) ($export['artifacts'] ?? [])),
            ], $runId);

            $this->stateStore->appendLog(
                $runId,
                'Readable export đã sẵn sàng tại ' . (string) ($export['export_path'] ?? 'không rõ đường dẫn') . '.'
            );
            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'backup_postprocess',
                'step' => 'completed',
                'snapshot_id' => $snapshotId,
                'finished_at' => now()->toIso8601String(),
                'message' => 'Hoàn thiện readable export thành công.',
                'result' => [
                    'export' => $export,
                ],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.postprocess_failed', [
                'run_id' => $runId,
                'snapshot_id' => $snapshotId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup_postprocess',
                'step' => 'failed',
                'snapshot_id' => $snapshotId,
                'finished_at' => now()->toIso8601String(),
                'message' => 'Không thể hoàn thiện readable export.',
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Hậu xử lý export thất bại: ' . $exception->getMessage(), 'error');

            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}
