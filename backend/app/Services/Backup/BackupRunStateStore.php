<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
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

        return $decoded;
    }

    public function listRecent(int $limit = 30): array
    {
        $files = File::glob($this->dirPath() . DIRECTORY_SEPARATOR . '*.json') ?: [];
        usort($files, static function (string $a, string $b): int {
            return File::lastModified($b) <=> File::lastModified($a);
        });

        $items = [];
        foreach (array_slice($files, 0, max(1, $limit)) as $path) {
            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded)) {
                continue;
            }
            $items[] = $decoded;
        }

        return $items;
    }

    public function assertValidRunId(string $runId): void
    {
        if (! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $runId)) {
            throw new InvalidArgumentException('Mã run backup không hợp lệ.');
        }
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
}

