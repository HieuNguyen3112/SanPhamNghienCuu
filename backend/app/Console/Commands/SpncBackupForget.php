<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpncBackupForget extends Command
{
    protected $signature = 'spnc:backup:forget
        {--run-id= : Mã run để theo dõi trạng thái}
        {--trigger=manual : Nguồn kích hoạt forget}
        {--initiated-by= : user_id kích hoạt}
        {--snapshot-id=* : Danh sách snapshot id cần xóa}';

    protected $description = 'Xóa snapshot backup theo danh sách chọn trước.';

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
        $snapshotIds = $this->resolveSnapshotIds(
            (array) $this->option('snapshot-id'),
            is_array($existing) ? $existing : null
        );
        if ($snapshotIds === []) {
            $message = 'Danh sách snapshot cần xóa không hợp lệ.';
            if (is_array($existing)) {
                $this->stateStore->update($runId, [
                    'status' => 'failed',
                    'operation' => 'forget',
                    'step' => 'failed',
                    'message' => $message,
                    'finished_at' => now()->toIso8601String(),
                    'error_message' => $message,
                ]);
            }

            $this->error($message);
            return self::FAILURE;
        }

        if (! $existing) {
            $this->stateStore->initialize($runId, [
                'status' => 'queued',
                'operation' => 'forget',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'snapshot_ids' => $snapshotIds,
                'message' => 'Đã xếp lịch xóa snapshot đã chọn.',
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'forget',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
                'snapshot_ids' => $snapshotIds,
            ]);
        }

        $this->stateStore->appendLog(
            $runId,
            'Bắt đầu xóa snapshot theo danh sách chọn: ' . implode(', ', $snapshotIds)
        );

        $lock = Cache::lock('spnc:backup:forget', 7200);
        if (! $lock->get()) {
            $message = 'Đang có tiến trình xóa snapshot khác chạy.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'forget',
                'step' => 'failed',
                'message' => $message,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            $this->error($message);
            return self::FAILURE;
        }

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'forget',
                'step' => 'forgetting',
                'started_at' => now()->toIso8601String(),
                'message' => 'Đang xóa bản sao lưu...',
            ]);
            $this->stateStore->appendLog($runId, 'Đang chạy restic forget...');

            $forgetResult = $this->backupManager->forgetSnapshots($snapshotIds);
            $cache = $this->snapshotStore->removeBySnapshotIds($snapshotIds);
            $this->stateStore->appendLog($runId, 'Xóa snapshot thành công, đã cập nhật cache cục bộ.');
            if (! empty($forgetResult['stdout'])) {
                $this->stateStore->appendLog($runId, 'forget.stdout: ' . (string) $forgetResult['stdout']);
            }
            if (! empty($forgetResult['stderr'])) {
                $this->stateStore->appendLog($runId, 'forget.stderr: ' . (string) $forgetResult['stderr'], 'warning');
            }

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'forget',
                'step' => 'completed',
                'message' => 'Đã xóa bản sao lưu thành công.',
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'forget' => $forgetResult,
                    'snapshot_count' => count((array) ($cache['items'] ?? [])),
                ],
            ]);

            $this->info('Forget hoàn tất. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $userMessage = $this->toUserFacingFailureMessage($exception);
            Log::error('backup.forget_command_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'snapshot_ids' => $snapshotIds,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'forget',
                'step' => 'failed',
                'message' => $userMessage,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, $userMessage . ': ' . $exception->getMessage(), 'error');

            $this->error('Xóa snapshot thất bại: ' . $exception->getMessage());
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
            return 'Tiến trình xóa bản sao lưu bị quá thời gian. Vui lòng thử lại.';
        }

        if (
            str_contains($raw, 'unable to create lock')
            || str_contains($raw, 'repository is already locked')
            || str_contains($raw, 'already locked by pid')
            || str_contains($raw, 'đang bị khóa')
        ) {
            return 'Bản sao lưu đang bị khóa bởi tiến trình khác. Vui lòng đợi rồi thử lại.';
        }

        return 'Không thể xóa bản sao lưu. Vui lòng thử lại.';
    }

    private function normalizeSnapshotIds(array $snapshotIds): array
    {
        $normalized = [];
        foreach ($snapshotIds as $snapshotId) {
            $id = Str::lower(trim((string) $snapshotId));
            if ($id === '') {
                continue;
            }

            try {
                $this->backupManager->assertValidSnapshotId($id);
            } catch (\Throwable) {
                continue;
            }
            $normalized[] = $id;
        }

        return array_values(array_unique($normalized));
    }

    private function resolveSnapshotIds(array $optionSnapshotIds, ?array $existing): array
    {
        $fromOption = $this->normalizeSnapshotIds($optionSnapshotIds);
        if ($fromOption !== []) {
            return $fromOption;
        }

        if (! is_array($existing)) {
            return [];
        }

        return $this->normalizeSnapshotIds((array) ($existing['snapshot_ids'] ?? []));
    }
}
