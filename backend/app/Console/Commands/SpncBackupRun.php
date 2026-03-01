<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
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

    public function __construct(
        ResticBackupManager $backupManager,
        BackupRunStateStore $stateStore
    ) {
        parent::__construct();
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
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
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
            ]);
        } else {
            $this->stateStore->update($runId, [
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
            ]);
        }

        $lock = Cache::lock('spnc:backup:run', 21600);
        if (! $lock->get()) {
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'finished_at' => now()->toIso8601String(),
                'error_message' => 'Đang có một tiến trình backup khác chạy. Vui lòng thử lại sau.',
            ]);
            $this->error('Đang có tiến trình backup khác chạy.');
            return self::FAILURE;
        }

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'started_at' => now()->toIso8601String(),
                'step' => 'running_backup',
                'message' => 'Đang tạo snapshot backup...',
            ]);

            $result = $this->backupManager->runBackup($runId, $trigger, $initiatedBy);

            $pruneResult = null;
            if (! $skipPrune) {
                $this->stateStore->update($runId, [
                    'step' => 'pruning',
                    'message' => 'Đang dọn snapshot cũ theo retention policy...',
                ]);
                $pruneResult = $this->backupManager->pruneBackups();
            }

            $payload = [
                'status' => 'success',
                'step' => 'completed',
                'message' => 'Backup hoàn tất.',
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'snapshot' => $result['snapshot'] ?? null,
                    'summary' => $result['summary'] ?? null,
                    'manifest_relative_path' => $result['manifest_relative_path'] ?? null,
                    'db_dump_relative_path' => $result['db_dump_relative_path'] ?? null,
                    'workspace_relative_path' => $result['workspace_relative_path'] ?? null,
                    'check' => $result['check'] ?? null,
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
                'step' => 'failed',
                'message' => 'Backup thất bại.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);

            $this->error('Backup thất bại: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}
