<?php

namespace App\Services\Backup;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BackupRunStateStore
{
    public function generateRunId(): string
    {
        return (string) Str::uuid();
    }

    public function initialize(string $runId, array $payload): array
    {
        $this->assertValidRunId($runId);
        $state = array_merge([
            'run_id' => $runId,
            'status' => 'queued',
            'trigger' => 'manual',
            'requested_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'logs' => [],
        ], $payload);

        $this->write($runId, $state);
        return $state;
    }

    public function update(string $runId, array $patch): array
    {
        $this->assertValidRunId($runId);
        $current = $this->get($runId) ?? ['run_id' => $runId];
        $next = array_merge($current, $patch, [
            'run_id' => $runId,
            'updated_at' => now()->toIso8601String(),
        ]);
        $this->write($runId, $next);
        return $next;
    }

    public function get(string $runId): ?array
    {
        $this->assertValidRunId($runId);
        $path = $this->pathOf($runId);
        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizeState($decoded, true);
    }

    public function listRecent(int $limit = 30): array
    {
        $items = [];
        foreach ($this->recentStateFiles($limit) as $path) {
            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded)) {
                continue;
            }
            $items[] = $this->normalizeState($decoded, true);
        }

        return $items;
    }

    public function latestActive(array $operations = []): ?array
    {
        $allowedOperations = array_values(array_filter(array_map(
            static fn ($value): string => Str::lower(trim((string) $value)),
            $operations
        )));

        foreach ($this->recentStateFiles(120) as $path) {
            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded)) {
                continue;
            }
            $decoded = $this->normalizeState($decoded, true);

            $status = Str::lower(trim((string) ($decoded['status'] ?? '')));
            if (! in_array($status, ['queued', 'running'], true)) {
                continue;
            }

            if ($allowedOperations !== []) {
                $operation = Str::lower(trim((string) ($decoded['operation'] ?? '')));
                if (! in_array($operation, $allowedOperations, true)) {
                    continue;
                }
            }

            return $this->summarizeRun($decoded);
        }

        return null;
    }

    public function latestSuccessfulBackup(): ?array
    {
        foreach ($this->recentStateFiles(160) as $path) {
            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded)) {
                continue;
            }

            $status = Str::lower(trim((string) ($decoded['status'] ?? '')));
            if ($status !== 'success') {
                continue;
            }

            $operation = Str::lower(trim((string) ($decoded['operation'] ?? 'backup')));
            if (! in_array($operation, ['backup', ''], true)) {
                continue;
            }

            return $this->summarizeRun($decoded);
        }

        return null;
    }

    public function assertValidRunId(string $runId): void
    {
        if (! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $runId)) {
            throw new InvalidArgumentException('Mã run backup không hợp lệ.');
        }
    }

    public function appendLog(string $runId, string $message, string $level = 'info'): array
    {
        $this->assertValidRunId($runId);
        $cleanMessage = $this->sanitizeLogMessage($message);
        if ($cleanMessage === '') {
            return $this->get($runId) ?? ['run_id' => $runId, 'logs' => []];
        }

        $state = $this->get($runId) ?? ['run_id' => $runId];
        $logs = array_values(array_filter((array) ($state['logs'] ?? []), static fn ($item): bool => is_array($item)));
        $logs[] = [
            'at' => now()->toIso8601String(),
            'level' => $this->sanitizeLogLevel($level),
            'message' => $cleanMessage,
        ];

        $maxLines = max(10, (int) config('backup.runs.log_max_lines', 80));
        if (count($logs) > $maxLines) {
            $logs = array_slice($logs, -$maxLines);
        }

        return $this->update($runId, [
            'logs' => $logs,
            'last_log_at' => now()->toIso8601String(),
        ]);
    }

    public function pathOf(string $runId): string
    {
        return $this->dirPath() . DIRECTORY_SEPARATOR . $runId . '.json';
    }

    private function write(string $runId, array $payload): void
    {
        $this->ensureDir();
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new InvalidArgumentException('Không thể mã hóa trạng thái backup.');
        }

        File::put($this->pathOf($runId), $encoded . PHP_EOL, true);
    }

    private function dirPath(): string
    {
        $dir = trim((string) config('backup.paths.state_dir', 'backup-runs'), "/\\");
        return storage_path('app' . DIRECTORY_SEPARATOR . $dir);
    }

    private function ensureDir(): void
    {
        $dir = $this->dirPath();
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    private function recentStateFiles(int $limit): array
    {
        $files = File::glob($this->dirPath() . DIRECTORY_SEPARATOR . '*.json') ?: [];
        usort($files, static function (string $a, string $b): int {
            return File::lastModified($b) <=> File::lastModified($a);
        });

        return array_slice($files, 0, max(1, $limit));
    }

    private function summarizeRun(array $state): array
    {
        return [
            'run_id' => $state['run_id'] ?? null,
            'operation' => $state['operation'] ?? null,
            'status' => $state['status'] ?? null,
            'trigger' => $state['trigger'] ?? null,
            'step' => $state['step'] ?? null,
            'message' => $state['message'] ?? null,
            'requested_by_user_id' => $state['requested_by_user_id'] ?? null,
            'requested_at' => $state['requested_at'] ?? null,
            'started_at' => $state['started_at'] ?? null,
            'finished_at' => $state['finished_at'] ?? null,
            'error_message' => $state['error_message'] ?? null,
            'snapshot_id' => $state['snapshot_id'] ?? null,
        ];
    }

    private function normalizeState(array $state, bool $persist): array
    {
        $status = Str::lower(trim((string) ($state['status'] ?? '')));
        if (! in_array($status, ['queued', 'running'], true)) {
            return $state;
        }

        if (! $this->isActiveRunStale($state, $status)) {
            return $state;
        }

        $runId = trim((string) ($state['run_id'] ?? ''));
        if ($runId === '' || ! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $runId)) {
            return $state;
        }

        $operation = Str::lower(trim((string) ($state['operation'] ?? '')));
        $timeoutSeconds = $this->resolveActiveRunTimeoutSeconds($operation, $status);
        $message = match ($operation) {
            'snapshot_refresh' => 'Đồng bộ danh sách snapshot quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
            'forget' => 'Tiến trình xóa snapshot bị quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
            'prune' => 'Tiến trình dọn snapshot bị quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
            'backup' => 'Tiến trình sao lưu bị quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
            'restore' => 'Tiến trình khôi phục bị quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
            default => 'Tiến trình nền quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.',
        };
        $errorMessage = trim((string) ($state['error_message'] ?? ''));

        $normalized = array_merge($state, [
            'status' => 'failed',
            'step' => 'timeout',
            'message' => $message,
            'error_message' => $errorMessage !== ''
                ? $errorMessage
                : ('Run timed out after ' . $timeoutSeconds . ' seconds.'),
            'finished_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if ($persist) {
            $this->write($runId, $normalized);
            Log::warning('backup.run_marked_stale', [
                'run_id' => $runId,
                'operation' => $operation !== '' ? $operation : null,
                'previous_status' => $status,
                'timeout_seconds' => $timeoutSeconds,
            ]);
        }

        return $normalized;
    }

    private function isActiveRunStale(array $state, string $status): bool
    {
        $operation = Str::lower(trim((string) ($state['operation'] ?? '')));
        $timeoutSeconds = $this->resolveActiveRunTimeoutSeconds($operation, $status);
        $reference = $this->resolveActiveRunReferenceTime($state, $status);

        if (! $reference) {
            return true;
        }

        $graceSeconds = max(0, (int) config('backup.runs.stale_grace_seconds', 30));
        return $reference->lt(now()->subSeconds($timeoutSeconds + $graceSeconds));
    }

    private function resolveActiveRunTimeoutSeconds(string $operation, string $status): int
    {
        if ($status === 'queued') {
            $timeouts = (array) config('backup.runs.queue_stale_after_by_operation', []);
            if (isset($timeouts[$operation])) {
                return max(30, (int) $timeouts[$operation]);
            }

            return max(30, (int) config('backup.runs.queue_stale_after_seconds', 300));
        }

        $timeouts = (array) config('backup.runs.running_stale_after_by_operation', []);
        if (isset($timeouts[$operation])) {
            return max(60, (int) $timeouts[$operation]);
        }

        if ($operation === 'snapshot_refresh') {
            return max(60, (int) config('backup.snapshot_cache.refresh_timeout_seconds', 900));
        }

        return max(120, (int) config('backup.runs.running_stale_after_seconds', 14400));
    }

    private function resolveActiveRunReferenceTime(array $state, string $status): ?Carbon
    {
        $candidates = $status === 'running'
            ? ['started_at', 'updated_at', 'requested_at']
            : ['updated_at', 'requested_at', 'started_at'];

        foreach ($candidates as $field) {
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

    private function sanitizeLogLevel(string $level): string
    {
        $normalized = strtolower(trim($level));
        if (! in_array($normalized, ['debug', 'info', 'warning', 'error'], true)) {
            return 'info';
        }

        return $normalized;
    }

    private function sanitizeLogMessage(string $message): string
    {
        $output = trim($message);
        if ($output === '') {
            return '';
        }

        $secrets = [
            (string) config('backup.restic.password', ''),
            (string) config('database.connections.mysql.password', ''),
        ];

        foreach ($secrets as $secret) {
            $secret = trim($secret);
            if ($secret === '') {
                continue;
            }
            $output = str_replace($secret, '***', $output);
        }

        $output = preg_replace('/(RESTIC_PASSWORD|MYSQL_PWD)=([^\\s]+)/i', '$1=***', $output) ?: $output;

        $maxChars = max(80, (int) config('backup.runs.log_max_chars', 500));
        if (strlen($output) > $maxChars) {
            $output = substr($output, 0, $maxChars) . '...';
        }

        return $output;
    }
}
