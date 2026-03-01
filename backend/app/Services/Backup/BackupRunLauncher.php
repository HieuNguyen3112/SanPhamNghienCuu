<?php

namespace App\Services\Backup;

class BackupRunLauncher
{
    public function launch(string $runId, int $initiatedByUserId, string $trigger = 'manual'): void
    {
        $this->assertSafeRunId($runId);
        $this->assertSafeTrigger($trigger);

        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));

        $args = [
            'spnc:backup:run',
            '--run-id=' . escapeshellarg($runId),
            '--trigger=' . escapeshellarg($trigger),
            '--initiated-by=' . (int) $initiatedByUserId,
            '--no-interaction',
        ];

        $argString = implode(' ', $args);

        if (DIRECTORY_SEPARATOR === '\\') {
            $cmd = 'start "" /B ' . $php . ' ' . $artisan . ' ' . $argString . ' > NUL 2>&1';
            $handle = @popen('cmd /C ' . $cmd, 'r');
            if ($handle === false) {
                throw new BackupRuntimeException('Không thể khởi chạy tác vụ backup nền.');
            }
            pclose($handle);
            return;
        }

        $cmd = $php . ' ' . $artisan . ' ' . $argString . ' > /dev/null 2>&1 &';
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
}

