<?php

namespace App\Services\Backup;

class BackupRunLauncher
{
    public function launch(string $runId, int $initiatedByUserId, string $trigger = 'manual'): void
    {
        $this->launchBackup($runId, $initiatedByUserId, $trigger);
    }

    public function launchBackup(string $runId, int $initiatedByUserId, string $trigger = 'manual'): void
    {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);
        $this->runDetached('spnc:backup:run', [
            '--run-id' => $runId,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    public function launchPrune(string $runId, int $initiatedByUserId, string $trigger = 'manual'): void
    {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);
        $this->runDetached('spnc:backup:prune', [
            '--run-id' => $runId,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    public function launchRestore(
        string $runId,
        int $initiatedByUserId,
        string $snapshotId,
        string $scope,
        string $target,
        string $trigger = 'manual'
    ): void {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);
        $this->assertSafeSnapshotId($snapshotId);
        $this->runDetached('spnc:backup:restore', [
            '--run-id' => $runId,
            '--snapshot-id' => $snapshotId,
            '--scope' => $scope,
            '--target' => $target,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    public function launchSnapshotRefresh(string $runId, int $initiatedByUserId, string $trigger = 'manual'): void
    {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);
        $this->runDetached('spnc:backup:snapshots:refresh', [
            '--run-id' => $runId,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    public function launchBackupPostProcess(
        string $runId,
        int $initiatedByUserId,
        string $snapshotId,
        string $trigger = 'manual'
    ): void {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);
        $this->assertSafeSnapshotId($snapshotId);
        $this->runDetached('spnc:backup:post-process', [
            '--run-id' => $runId,
            '--snapshot-id' => $snapshotId,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    public function launchForget(
        string $runId,
        int $initiatedByUserId,
        array $snapshotIds,
        string $trigger = 'manual'
    ): void {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);

        $normalizedSnapshotIds = [];
        foreach ($snapshotIds as $snapshotId) {
            $id = trim((string) $snapshotId);
            if ($id === '') {
                continue;
            }

            $this->assertSafeSnapshotId($id);
            $normalizedSnapshotIds[] = strtolower($id);
        }

        $normalizedSnapshotIds = array_values(array_unique($normalizedSnapshotIds));
        if ($normalizedSnapshotIds === []) {
            throw new BackupRuntimeException('Danh sách snapshot cần xóa không hợp lệ.');
        }

        // Không truyền danh sách snapshot qua CLI khi chạy detached để tránh lỗi parse tham số
        // trên môi trường Windows; command sẽ đọc lại snapshot_ids từ run-state đã được API ghi sẵn.
        $this->runDetached('spnc:backup:forget', [
            '--run-id' => $runId,
            '--trigger' => $trigger,
            '--initiated-by' => (string) $initiatedByUserId,
            '--no-interaction' => true,
        ]);
    }

    private function runDetached(string $artisanCommand, array $options): void
    {
        if (! preg_match('/^[a-z0-9:\-]+$/', $artisanCommand)) {
            throw new BackupRuntimeException('Lệnh backup nền không hợp lệ.');
        }

        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));
        $parts = [$php, $artisan, escapeshellarg($artisanCommand)];

        foreach ($options as $key => $value) {
            $option = trim((string) $key);
            if (! preg_match('/^--[a-z0-9\-]+$/', $option)) {
                throw new BackupRuntimeException('Tham số chạy nền không hợp lệ.');
            }

            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $option;
                }
                continue;
            }

            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || is_array($item) || is_object($item)) {
                        continue;
                    }
                    $parts[] = $option . '=' . escapeshellarg((string) $item);
                }
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $parts[] = $option . '=' . escapeshellarg((string) $value);
        }

        $argString = implode(' ', $parts);
        if (DIRECTORY_SEPARATOR === '\\') {
            $cmd = 'start "" /B ' . $argString . ' > NUL 2>&1';
            $handle = @popen('cmd /C ' . $cmd, 'r');
            if ($handle === false) {
                throw new BackupRuntimeException('Không thể khởi chạy tác vụ backup nền.');
            }
            pclose($handle);
            return;
        }

        $cmd = $argString . ' > /dev/null 2>&1 &';
        @exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new BackupRuntimeException('Không thể khởi chạy tác vụ backup nền.');
        }
    }

    private function assertSafeRunId(string $runId): void
    {
        if (! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $runId)) {
            throw new BackupRuntimeException('Mã run backup không hợp lệ.');
        }
    }

    private function assertSafeTrigger(string $trigger): void
    {
        if (! preg_match('/^[a-z_]{3,32}$/', $trigger)) {
            throw new BackupRuntimeException('Nguồn kích hoạt backup không hợp lệ.');
        }
    }

    private function assertSafeSnapshotId(string $snapshotId): void
    {
        if (! preg_match('/^[A-Fa-f0-9]{6,64}$/', $snapshotId)) {
            throw new BackupRuntimeException('Mã snapshot không hợp lệ.');
        }
    }
}
