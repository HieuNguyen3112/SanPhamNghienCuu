<?php

namespace App\Services\Backup;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ResticBackupManager
{
    private const DB_DUMP_RELATIVE_PATH = 'db/mysql.sql';
    private const MANIFEST_FILENAME = 'manifest.json';

    public function listSnapshots(int $limit = 50): array
    {
        $this->assertConfigured();
        $this->ensureRepositoryReady();

        $result = $this->runRestic([
            'snapshots',
            '--json',
            '--tag',
            'spnc_backup',
        ], false, 300);

        $decoded = json_decode((string) $result['stdout'], true);
        $snapshots = is_array($decoded) ? $decoded : [];

        usort($snapshots, static function (array $a, array $b): int {
            return strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? ''));
        });

        $stateStore = app(BackupRunStateStore::class);
        $items = [];
        foreach (array_slice($snapshots, 0, max(1, $limit)) as $snapshot) {
            $payload = $this->mapSnapshotPayload($snapshot);
            $runId = $payload['run_id'] ?? null;
            if ($runId) {
                $state = $stateStore->get($runId);
                $payload['run_state'] = $state['status'] ?? null;
                $payload['size_bytes'] = $this->resolveRunSizeBytes($state);
                $payload['trigger'] = $state['trigger'] ?? $payload['trigger'];
            } else {
                $payload['run_state'] = null;
                $payload['size_bytes'] = null;
            }
            $items[] = $payload;
        }

        return $items;
    }

    public function runBackup(string $runId, string $trigger = 'manual', ?int $initiatedBy = null): array
    {
        $this->assertConfigured();
        $this->ensureRepositoryReady();

        $workspaceRelative = $this->workspaceRelativePath($runId);
        $workspaceAbsolute = $this->basePathFromRelative($workspaceRelative);
        $dbDumpRelative = $workspaceRelative . '/' . self::DB_DUMP_RELATIVE_PATH;
        $dbDumpAbsolute = $this->basePathFromRelative($dbDumpRelative);
        $manifestRelative = $workspaceRelative . '/' . self::MANIFEST_FILENAME;
        $manifestAbsolute = $this->basePathFromRelative($manifestRelative);

        $includePaths = $this->resolveIncludedPathsForSnapshot();
        $excludePaths = $this->resolveExcludedPaths();

        File::ensureDirectoryExists(dirname($dbDumpAbsolute));
        File::ensureDirectoryExists($workspaceAbsolute);

        try {
            $this->dumpDatabase($dbDumpAbsolute);
            $manifest = $this->buildManifest(
                $runId,
                $trigger,
                $includePaths,
                $excludePaths,
                $workspaceRelative,
                $dbDumpRelative,
                $initiatedBy
            );
            File::put(
                $manifestAbsolute,
                json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
            );

            $backupArgs = [
                'backup',
                $workspaceRelative,
                '--json',
                '--compression',
                (string) config('backup.restic.compression', 'auto'),
                '--tag',
                'spnc_backup',
                '--tag',
                'run_id:' . $runId,
                '--tag',
                'trigger:' . $trigger,
                '--tag',
                'env:' . app()->environment(),
            ];

            foreach ($includePaths as $path) {
                $backupArgs[] = $path;
            }

            foreach ($excludePaths as $path) {
                $backupArgs[] = '--exclude';
                $backupArgs[] = $path;
            }

            $backupResult = $this->runRestic($backupArgs, false, 7200);
            $summary = $this->extractBackupSummary($backupResult['stdout']);
            $snapshot = $this->findLatestSnapshotForRunId($runId);

            $checkResult = null;
            if ((bool) config('backup.restic.check_after_backup', true)) {
                $checkResult = $this->runRestic([
                    'check',
                    '--read-data-subset',
                    '1/20',
                ], false, 1800);
            }

            return [
                'snapshot' => $snapshot,
                'summary' => $summary,
                'manifest_relative_path' => $manifestRelative,
                'db_dump_relative_path' => $dbDumpRelative,
                'workspace_relative_path' => $workspaceRelative,
                'check' => [
                    'ok' => $checkResult ? $checkResult['successful'] : true,
                    'stderr' => $checkResult ? trim((string) $checkResult['stderr']) : null,
                ],
            ];
        } finally {
            // Không giữ DB dump tạm tại local sau khi đã snapshot thành công/thất bại.
            if (File::exists($workspaceAbsolute)) {
                File::deleteDirectory($workspaceAbsolute);
            }
        }
    }

    public function pruneBackups(): array
    {
        $this->assertConfigured();
        $this->ensureRepositoryReady();

        $keepLast = max(1, (int) config('backup.retention.keep_last', 12));
        $keepWeekly = max(1, (int) config('backup.retention.keep_weekly', 8));
        $keepMonthly = max(1, (int) config('backup.retention.keep_monthly', 6));

        $result = $this->runRestic([
            'forget',
            '--prune',
            '--tag',
            'spnc_backup',
            '--keep-last',
            (string) $keepLast,
            '--keep-weekly',
            (string) $keepWeekly,
            '--keep-monthly',
            (string) $keepMonthly,
        ], false, 5400);

        return [
            'successful' => (bool) $result['successful'],
            'stdout' => trim((string) $result['stdout']),
            'stderr' => trim((string) $result['stderr']),
            'retention' => [
                'keep_last' => $keepLast,
                'keep_weekly' => $keepWeekly,
                'keep_monthly' => $keepMonthly,
            ],
        ];
    }

    public function restoreSnapshot(
        string $snapshotId,
        string $scope,
        string $target,
        string $restoreRunId
    ): array {
        $this->assertConfigured();
        $this->ensureRepositoryReady();

        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('Không tìm thấy snapshot cần khôi phục.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('Snapshot không chứa metadata workspace để khôi phục.');
        }

        $restoreRelativeRoot = $this->restoreRelativePath($restoreRunId);
        $restoreAbsoluteRoot = $this->basePathFromRelative($restoreRelativeRoot);
        if (File::exists($restoreAbsoluteRoot)) {
            File::deleteDirectory($restoreAbsoluteRoot);
        }
        File::ensureDirectoryExists($restoreAbsoluteRoot);

        $includePaths = $this->resolveRestoreIncludePaths($scope, $workspacePath);
        $args = [
            'restore',
            $snapshotId,
            '--target',
            $restoreAbsoluteRoot,
        ];
        foreach ($includePaths as $includePath) {
            $args[] = '--include';
            $args[] = $includePath;
        }

        $restoreResult = $this->runRestic($args, false, 7200);

        $dbImported = false;
        $fileMirrors = [];
        if ($target === 'current') {
            if (! (bool) config('backup.restore.allow_live_restore', false)) {
                throw new BackupRuntimeException('Khôi phục trực tiếp bị tắt trong cấu hình hệ thống.');
            }

            if (in_array($scope, ['db_only', 'full'], true)) {
                $dumpPath = $this->basePathFromRestoredRoot(
                    $restoreAbsoluteRoot,
                    $workspacePath . '/' . self::DB_DUMP_RELATIVE_PATH
                );
                $this->importDatabaseDump($dumpPath);
                $dbImported = true;
            }

            if (in_array($scope, ['files_only', 'full'], true)) {
                $fileMirrors = $this->applyRestoredFilesToCurrent($restoreAbsoluteRoot);
            }
        }

        return [
            'snapshot_id' => $snapshotId,
            'scope' => $scope,
            'target' => $target,
            'restore_root' => $restoreRelativeRoot,
            'db_imported' => $dbImported,
            'mirrored_paths' => $fileMirrors,
            'stdout' => trim((string) $restoreResult['stdout']),
            'stderr' => trim((string) $restoreResult['stderr']),
        ];
    }

    public function extractManifestFromSnapshot(string $snapshotId): array
    {
        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('Không tìm thấy snapshot.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('Không tìm thấy đường dẫn manifest trong snapshot.');
        }

        $relativePath = $workspacePath . '/' . self::MANIFEST_FILENAME;
        return $this->restoreSingleFile($snapshotId, $relativePath, 'manifest_' . Str::uuid());
    }

    public function extractDatabaseDumpFromSnapshot(string $snapshotId): array
    {
        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('Không tìm thấy snapshot.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('Không tìm thấy đường dẫn DB dump trong snapshot.');
        }

        $relativePath = $workspacePath . '/' . self::DB_DUMP_RELATIVE_PATH;
        return $this->restoreSingleFile($snapshotId, $relativePath, 'dbdump_' . Str::uuid());
    }

    public function buildScheduleMeta(): array
    {
        $days = $this->normalizedScheduleDays();
        $time = (string) config('backup.schedule.time', '02:00');
        $timezone = (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh');
        $nextRunAt = $this->computeNextRunAt($days, $time, $timezone);

        return [
            'days' => $days,
            'time' => $time,
            'timezone' => $timezone,
            'next_run_at' => $nextRunAt?->toIso8601String(),
        ];
    }

    public function buildRetentionMeta(): array
    {
        return [
            'keep_last' => max(1, (int) config('backup.retention.keep_last', 12)),
            'keep_weekly' => max(1, (int) config('backup.retention.keep_weekly', 8)),
            'keep_monthly' => max(1, (int) config('backup.retention.keep_monthly', 6)),
        ];
    }

    private function assertConfigured(): void
    {
        if ((string) config('backup.engine', 'restic') !== 'restic') {
            throw new BackupRuntimeException('Hệ thống hiện chỉ hỗ trợ engine restic.');
        }

        $repository = trim((string) config('backup.restic.repository', ''));
        $password = trim((string) config('backup.restic.password', ''));
        if ($repository === '' || $password === '') {
            throw new BackupRuntimeException(
                'Thiếu cấu hình backup. Vui lòng khai báo SPNC_BACKUP_REPOSITORY và SPNC_BACKUP_PASSWORD.'
            );
        }
    }

    private function ensureRepositoryReady(): void
    {
        $probe = $this->runRestic([
            'snapshots',
            '--json',
            '--tag',
            'spnc_backup',
        ], true, 180);

        if ($probe['successful']) {
            return;
        }

        $stderr = Str::lower((string) $probe['stderr']);
        $repositoryMissing = str_contains($stderr, 'unable to open config file')
            || str_contains($stderr, 'is there a repository')
            || str_contains($stderr, 'config file does not exist');

        if (! $repositoryMissing) {
            throw new BackupRuntimeException(
                'Không thể truy cập repository backup: ' . trim((string) $probe['stderr'])
            );
        }

        $init = $this->runRestic(['init'], true, 300);
        if ($init['successful']) {
            return;
        }

        $initErr = Str::lower((string) $init['stderr']);
        if (str_contains($initErr, 'already initialized')) {
            return;
        }

        throw new BackupRuntimeException('Khởi tạo repository backup thất bại: ' . trim((string) $init['stderr']));
    }

    private function dumpDatabase(string $outputAbsolutePath): void
    {
        $mysql = config('database.connections.mysql');
        if (! is_array($mysql)) {
            throw new BackupRuntimeException('Không tìm thấy cấu hình database mysql.');
        }

        $database = (string) ($mysql['database'] ?? '');
        $username = (string) ($mysql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiếu cấu hình database để tạo dump backup.');
        }

        $command = [
            (string) config('backup.mysql.mysqldump_binary', 'mysqldump'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--host=' . (string) ($mysql['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($mysql['port'] ?? 3306),
            '--user=' . $username,
            '--result-file=' . $outputAbsolutePath,
            $database,
        ];

        $env = [];
        $password = (string) ($mysql['password'] ?? '');
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $result = $this->runProcess($command, $env, false, 1800);
        if (! $result['successful']) {
            throw new BackupRuntimeException('Tạo DB dump thất bại: ' . trim((string) $result['stderr']));
        }
    }

    private function importDatabaseDump(string $dumpAbsolutePath): void
    {
        if (! File::exists($dumpAbsolutePath)) {
            throw new BackupRuntimeException('Không tìm thấy tệp DB dump để khôi phục.');
        }

        $mysql = config('database.connections.mysql');
        if (! is_array($mysql)) {
            throw new BackupRuntimeException('Không tìm thấy cấu hình mysql để khôi phục dữ liệu.');
        }

        $database = (string) ($mysql['database'] ?? '');
        $username = (string) ($mysql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiếu cấu hình database để import bản sao lưu.');
        }

        $command = [
            (string) config('backup.mysql.mysql_binary', 'mysql'),
            '--default-character-set=utf8mb4',
            '--host=' . (string) ($mysql['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($mysql['port'] ?? 3306),
            '--user=' . $username,
            $database,
        ];

        $env = [];
        $password = (string) ($mysql['password'] ?? '');
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($command, base_path(), $env ?: null, null, 3600);
        $stream = fopen($dumpAbsolutePath, 'rb');
        if ($stream === false) {
            throw new BackupRuntimeException('Không thể đọc tệp DB dump để import.');
        }
        $process->setInput($stream);
        $process->run();
        fclose($stream);

        if (! $process->isSuccessful()) {
            throw new BackupRuntimeException('Khôi phục database thất bại: ' . trim((string) $process->getErrorOutput()));
        }
    }

    private function findLatestSnapshotForRunId(string $runId): ?array
    {
        $result = $this->runRestic([
            'snapshots',
            '--json',
            '--tag',
            'run_id:' . $runId,
        ], false, 300);

        $decoded = json_decode((string) $result['stdout'], true);
        $items = is_array($decoded) ? $decoded : [];
        if ($items === []) {
            return null;
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? ''));
        });

        return $this->mapSnapshotPayload($items[0]);
    }

    private function findSnapshotById(string $snapshotId): ?array
    {
        $snapshots = $this->listSnapshots(200);
        foreach ($snapshots as $item) {
            $id = (string) ($item['snapshot_id'] ?? '');
            if ($id === $snapshotId || str_starts_with($id, $snapshotId)) {
                return $item;
            }
        }

        return null;
    }

    private function mapSnapshotPayload(array $snapshot): array
    {
        $tags = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) ($snapshot['tags'] ?? [])
        )));
        $runId = $this->extractRunIdFromTags($tags);
        $workspacePath = $this->extractWorkspacePath((array) ($snapshot['paths'] ?? []), $runId);

        return [
            'snapshot_id' => (string) ($snapshot['id'] ?? ''),
            'short_id' => (string) (($snapshot['short_id'] ?? null) ?: Str::substr((string) ($snapshot['id'] ?? ''), 0, 8)),
            'created_at' => (string) ($snapshot['time'] ?? ''),
            'hostname' => (string) ($snapshot['hostname'] ?? ''),
            'paths' => array_values((array) ($snapshot['paths'] ?? [])),
            'tags' => $tags,
            'run_id' => $runId,
            'trigger' => $this->extractTriggerFromTags($tags),
            'workspace_path' => $workspacePath,
        ];
    }

    private function resolveRunSizeBytes(?array $state): ?int
    {
        if (! $state || ! is_array($state)) {
            return null;
        }

        $summary = $state['result']['summary'] ?? null;
        if (! is_array($summary)) {
            return null;
        }

        foreach (['data_added', 'total_bytes_processed'] as $key) {
            if (isset($summary[$key]) && is_numeric($summary[$key])) {
                return (int) $summary[$key];
            }
        }

        return null;
    }

    private function extractBackupSummary(string $stdout): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', $stdout) ?: [];
        $lastSummary = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $decoded = json_decode($trimmed, true);
            if (! is_array($decoded)) {
                continue;
            }

            if (($decoded['message_type'] ?? null) === 'summary') {
                $lastSummary = $decoded;
            }
        }

        return $lastSummary;
    }

    private function resolveIncludedPathsForSnapshot(): array
    {
        $configured = (array) config('backup.paths.include', []);
        $existing = [];
        foreach ($configured as $relativePath) {
            $clean = trim((string) $relativePath, "/\\");
            if ($clean === '') {
                continue;
            }
            $absolute = $this->basePathFromRelative($clean);
            if (File::exists($absolute)) {
                $existing[] = str_replace('\\', '/', $clean);
            }
        }

        return array_values(array_unique($existing));
    }

    private function resolveExcludedPaths(): array
    {
        $configured = (array) config('backup.paths.exclude', []);
        $paths = [];
        foreach ($configured as $relativePath) {
            $clean = trim((string) $relativePath, "/\\");
            if ($clean === '') {
                continue;
            }
            $paths[] = str_replace('\\', '/', $clean);
        }

        return array_values(array_unique($paths));
    }

    private function buildManifest(
        string $runId,
        string $trigger,
        array $includePaths,
        array $excludePaths,
        string $workspaceRelative,
        string $dbDumpRelative,
        ?int $initiatedBy
    ): array {
        return [
            'run_id' => $runId,
            'trigger' => $trigger,
            'created_at' => now()->toIso8601String(),
            'app' => [
                'name' => (string) config('app.name', 'SPNC'),
                'environment' => (string) app()->environment(),
                'url' => (string) config('app.url', ''),
                'version' => (string) env('APP_VERSION', ''),
                'git_commit' => (string) env('APP_GIT_COMMIT', ''),
            ],
            'operator' => [
                'initiated_by_user_id' => $initiatedBy,
            ],
            'backup' => [
                'engine' => 'restic',
                'repository' => (string) config('backup.restic.repository', ''),
                'encryption' => 'restic client-side encryption (repository password)',
                'compression' => (string) config('backup.restic.compression', 'auto'),
                'workspace_relative_path' => $workspaceRelative,
                'manifest_relative_path' => $workspaceRelative . '/' . self::MANIFEST_FILENAME,
                'db_dump_relative_path' => $dbDumpRelative,
                'included_paths' => $includePaths,
                'excluded_paths' => $excludePaths,
            ],
            'restore' => [
                'confirm_phrase' => (string) config('backup.restore.confirm_phrase', 'KHOI_PHUC_DU_LIEU'),
                'available_scopes' => ['db_only', 'files_only', 'full'],
                'available_targets' => ['staging', 'current'],
            ],
        ];
    }

    private function resolveRestoreIncludePaths(string $scope, string $workspacePath): array
    {
        $scope = trim($scope);
        $includes = [];

        if (in_array($scope, ['db_only', 'full'], true)) {
            $includes[] = $workspacePath . '/' . self::DB_DUMP_RELATIVE_PATH;
            $includes[] = $workspacePath . '/' . self::MANIFEST_FILENAME;
        }

        if (in_array($scope, ['files_only', 'full'], true)) {
            foreach ($this->resolveIncludedPathsForSnapshot() as $path) {
                $includes[] = $path;
            }
        }

        $includes = array_values(array_unique(array_filter($includes)));
        if ($includes === []) {
            throw new BackupRuntimeException('Không có dữ liệu phù hợp với phạm vi khôi phục đã chọn.');
        }

        return $includes;
    }

    private function applyRestoredFilesToCurrent(string $restoreAbsoluteRoot): array
    {
        $mirrored = [];
        foreach ($this->resolveIncludedPathsForSnapshot() as $relativePath) {
            $source = $this->basePathFromRestoredRoot($restoreAbsoluteRoot, $relativePath);
            if (! File::exists($source)) {
                continue;
            }

            $target = $this->basePathFromRelative($relativePath);
            File::ensureDirectoryExists($target);
            File::copyDirectory($source, $target);
            $mirrored[] = $relativePath;
        }

        return $mirrored;
    }

    private function restoreSingleFile(string $snapshotId, string $relativePath, string $token): array
    {
        $downloadRelativeRoot = $this->downloadRelativePath($token);
        $downloadAbsoluteRoot = $this->basePathFromRelative($downloadRelativeRoot);
        if (File::exists($downloadAbsoluteRoot)) {
            File::deleteDirectory($downloadAbsoluteRoot);
        }
        File::ensureDirectoryExists($downloadAbsoluteRoot);

        $result = $this->runRestic([
            'restore',
            $snapshotId,
            '--target',
            $downloadAbsoluteRoot,
            '--include',
            $relativePath,
        ], false, 3600);

        $extractedAbsolutePath = $this->basePathFromRestoredRoot($downloadAbsoluteRoot, $relativePath);
        if (! File::exists($extractedAbsolutePath)) {
            throw new BackupRuntimeException('Không tìm thấy tệp sau khi giải snapshot.');
        }

        return [
            'absolute_path' => $extractedAbsolutePath,
            'relative_root' => $downloadRelativeRoot,
            'stdout' => trim((string) $result['stdout']),
            'stderr' => trim((string) $result['stderr']),
        ];
    }

    private function extractRunIdFromTags(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (! str_starts_with((string) $tag, 'run_id:')) {
                continue;
            }
            $value = trim((string) Str::after((string) $tag, 'run_id:'));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractTriggerFromTags(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (! str_starts_with((string) $tag, 'trigger:')) {
                continue;
            }
            $value = trim((string) Str::after((string) $tag, 'trigger:'));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractWorkspacePath(array $paths, ?string $runId): ?string
    {
        $normalizedPaths = array_values(array_filter(array_map(
            static fn ($path): string => str_replace('\\', '/', trim((string) $path)),
            $paths
        )));

        if ($runId) {
            $expected = $this->workspaceRelativePath($runId);
            foreach ($normalizedPaths as $path) {
                if ($path === $expected || str_ends_with($path, '/' . trim($expected, '/'))) {
                    return $expected;
                }
            }
        }

        $workspaceDir = trim((string) config('backup.paths.workspace_dir', 'backup-workspace'), "/\\");
        $needle = 'storage/app/' . $workspaceDir . '/';
        foreach ($normalizedPaths as $path) {
            $pos = strpos($path, $needle);
            if ($pos === false) {
                continue;
            }
            return substr($path, $pos);
        }

        return null;
    }

    private function computeNextRunAt(array $days, string $time, string $timezone): ?Carbon
    {
        $now = Carbon::now($timezone);
        [$hour, $minute] = $this->parseTime($time);

        for ($i = 0; $i < 14; $i++) {
            $candidate = $now->copy()->startOfDay()->addDays($i)->setTime($hour, $minute);
            if (! in_array($candidate->dayOfWeek, $days, true)) {
                continue;
            }
            if ($candidate->lessThanOrEqualTo($now)) {
                continue;
            }
            return $candidate;
        }

        return null;
    }

    private function normalizedScheduleDays(): array
    {
        $days = (array) config('backup.schedule.days', [1, 4]);
        $normalized = [];
        foreach ($days as $day) {
            $value = (int) $day;
            if ($value < 0 || $value > 6) {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized !== [] ? array_values(array_unique($normalized)) : [1, 4];
    }

    private function parseTime(string $time): array
    {
        $parts = explode(':', $time);
        $hour = isset($parts[0]) ? max(0, min(23, (int) $parts[0])) : 2;
        $minute = isset($parts[1]) ? max(0, min(59, (int) $parts[1])) : 0;
        return [$hour, $minute];
    }

    private function runRestic(array $args, bool $allowFailure = false, int $timeout = 600): array
    {
        $command = [(string) config('backup.restic.binary', 'restic')];

        $rcloneProgram = $this->resolveRcloneProgram();
        if ($rcloneProgram !== null) {
            $command[] = '-o';
            $command[] = 'rclone.program=' . $rcloneProgram;
        }

        $command = array_merge($command, $args);
        return $this->runProcess($command, $this->resticEnv(), $allowFailure, $timeout);
    }

    private function runProcess(
        array $command,
        array $env = [],
        bool $allowFailure = false,
        int $timeout = 600
    ): array {
        $process = new Process($command, base_path(), $env ?: null, null, $timeout);
        $process->run();

        $result = [
            'successful' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'command' => $process->getCommandLine(),
        ];

        if (! $allowFailure && ! $result['successful']) {
            throw new BackupRuntimeException(
                'Lệnh backup thất bại: ' . trim((string) $result['stderr'])
            );
        }

        return $result;
    }

    private function resticEnv(): array
    {
        $env = [
            'RESTIC_REPOSITORY' => (string) config('backup.restic.repository', ''),
            'RESTIC_PASSWORD' => (string) config('backup.restic.password', ''),
        ];

        $rcloneConfig = trim((string) config('backup.restic.rclone_config_path', ''));
        if ($rcloneConfig !== '') {
            $env['RCLONE_CONFIG'] = $rcloneConfig;
        }

        $httpProxy = trim((string) config('backup.network.http_proxy', ''));
        $httpsProxy = trim((string) config('backup.network.https_proxy', ''));
        $noProxy = trim((string) config('backup.network.no_proxy', ''));

        if ($httpProxy !== '') {
            $env['HTTP_PROXY'] = $httpProxy;
            $env['http_proxy'] = $httpProxy;
        }
        if ($httpsProxy !== '') {
            $env['HTTPS_PROXY'] = $httpsProxy;
            $env['https_proxy'] = $httpsProxy;
        }
        if ($noProxy !== '') {
            $env['NO_PROXY'] = $noProxy;
            $env['no_proxy'] = $noProxy;
        }

        $candidateDirs = $this->resolveRcloneCandidateDirectories();
        if ($candidateDirs !== []) {
            $pathSeparator = DIRECTORY_SEPARATOR === '\\' ? ';' : ':';
            $currentPath = (string) (getenv('PATH') ?: '');
            $pathItems = array_values(array_filter(explode($pathSeparator, $currentPath)));
            $normalizedCurrent = array_map(static function (string $item): string {
                return rtrim(str_replace('\\', '/', $item), '/');
            }, $pathItems);

            $prepend = [];
            foreach ($candidateDirs as $dir) {
                $normalizedDir = rtrim(str_replace('\\', '/', $dir), '/');
                if (! in_array($normalizedDir, $normalizedCurrent, true)) {
                    $prepend[] = $dir;
                }
            }

            if ($prepend !== []) {
                $env['PATH'] = implode($pathSeparator, $prepend)
                    . ($currentPath !== '' ? $pathSeparator . $currentPath : '');
            }
        }

        return $env;
    }

    private function resolveRcloneProgram(): ?string
    {
        $repository = trim((string) config('backup.restic.repository', ''));
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            return null;
        }

        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        if ($rcloneBinary === '') {
            return null;
        }

        if (str_contains($rcloneBinary, '/') || str_contains($rcloneBinary, '\\')) {
            return $this->normalizeProgramPath($rcloneBinary);
        }

        $binaryNames = [$rcloneBinary];
        if (DIRECTORY_SEPARATOR === '\\' && ! str_ends_with(Str::lower($rcloneBinary), '.exe')) {
            $binaryNames[] = $rcloneBinary . '.exe';
        }

        foreach ($this->resolveRcloneCandidateDirectories() as $dir) {
            foreach ($binaryNames as $binaryName) {
                $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binaryName;
                if (is_file($candidate)) {
                    return $this->normalizeProgramPath($candidate);
                }
            }
        }

        return $rcloneBinary;
    }

    private function resolveRcloneCandidateDirectories(): array
    {
        $dirs = [];

        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        if (str_contains($rcloneBinary, '/') || str_contains($rcloneBinary, '\\')) {
            $dirs[] = dirname(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rcloneBinary));
        }

        $resticBinary = trim((string) config('backup.restic.binary', 'restic'));
        if (str_contains($resticBinary, '/') || str_contains($resticBinary, '\\')) {
            $dirs[] = dirname(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $resticBinary));
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $dirs[] = 'C:' . DIRECTORY_SEPARATOR . 'Tools' . DIRECTORY_SEPARATOR . 'rclone';
        }

        $normalized = [];
        foreach ($dirs as $dir) {
            $clean = trim((string) $dir);
            if ($clean === '' || ! is_dir($clean)) {
                continue;
            }
            $key = Str::lower(rtrim(str_replace('\\', '/', $clean), '/'));
            $normalized[$key] = $clean;
        }

        return array_values($normalized);
    }

    private function normalizeProgramPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return $trimmed;
        }

        return str_replace('\\', '/', $trimmed);
    }

    private function workspaceRelativePath(string $runId): string
    {
        $dir = trim((string) config('backup.paths.workspace_dir', 'backup-workspace'), "/\\");
        return str_replace('\\', '/', 'storage/app/' . $dir . '/' . $runId);
    }

    private function restoreRelativePath(string $runId): string
    {
        $dir = trim((string) config('backup.paths.restore_dir', 'backup-restores'), "/\\");
        return str_replace('\\', '/', 'storage/app/' . $dir . '/' . $runId);
    }

    private function downloadRelativePath(string $token): string
    {
        $dir = trim((string) config('backup.paths.download_dir', 'backup-downloads'), "/\\");
        return str_replace('\\', '/', 'storage/app/' . $dir . '/' . $token);
    }

    private function basePathFromRelative(string $relativePath): string
    {
        return base_path(str_replace('/', DIRECTORY_SEPARATOR, trim($relativePath, "/\\")));
    }

    private function basePathFromRestoredRoot(string $restoredRoot, string $relativePath): string
    {
        $relative = str_replace('/', DIRECTORY_SEPARATOR, trim($relativePath, "/\\"));
        return rtrim($restoredRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
    }
}
