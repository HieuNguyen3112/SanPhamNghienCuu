<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpncBackupCheck extends Command
{
    protected $signature = 'spnc:backup:check
        {--run-id= : Mã run để theo dõi trạng thái}
        {--trigger=manual : Nguồn kích hoạt}';

    protected $description = 'Chạy restic check ngoài hot path backup.';

    public function __construct(
        private ResticBackupManager $backupManager,
        private BackupRunStateStore $stateStore
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        if ($trigger === '') {
            $trigger = 'manual';
        }

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
                'operation' => 'backup_check',
                'trigger' => $trigger,
                'requested_at' => now()->toIso8601String(),
            ]);
        }

        $conflict = $this->stateStore->latestActive(['backup', 'prune', 'restore', 'forget']);
        if (is_array($conflict)) {
            $message = 'Bỏ qua kiểm tra repository vì đang có tiến trình backup khác sử dụng repository.';
            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'backup_check',
                'step' => 'skipped_conflict',
                'finished_at' => now()->toIso8601String(),
                'message' => $message,
                'result' => [
                    'skipped' => true,
                    'reason' => 'active_repository_operation',
                    'conflict_run_id' => $conflict['run_id'] ?? null,
                ],
            ]);
            $this->stateStore->appendLog($runId, $message, 'info');

            return self::SUCCESS;
        }

        $lock = Cache::lock('spnc:backup:check', 7200);
        if (! $lock->get()) {
            $message = 'Đang có tiến trình kiểm tra repository backup.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup_check',
                'step' => 'failed',
                'finished_at' => now()->toIso8601String(),
                'message' => $message,
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            return self::FAILURE;
        }

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'backup_check',
                'started_at' => now()->toIso8601String(),
                'step' => 'checking_repository',
                'message' => 'Đang kiểm tra tính toàn vẹn repository backup...',
            ]);
            $this->stateStore->appendLog($runId, 'Bắt đầu restic check ngoài hot path.');

            $result = $this->backupManager->runRepositoryCheck();

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'backup_check',
                'step' => 'completed',
                'finished_at' => now()->toIso8601String(),
                'message' => 'Kiểm tra repository backup hoàn tất.',
                'result' => [
                    'check' => $result,
                ],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.check_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup_check',
                'step' => 'failed',
                'finished_at' => now()->toIso8601String(),
                'message' => 'Kiểm tra repository backup thất bại.',
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'restic check thất bại: ' . $exception->getMessage(), 'error');

            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}
