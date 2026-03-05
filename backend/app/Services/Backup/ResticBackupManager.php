<?php

namespace App\Services\Backup;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

class ResticBackupManager
{
    private const DB_DUMP_RELATIVE_PATH = 'db/mysql.sql';
    private const MANIFEST_FILENAME = 'manifest.json';

    private const EXPORT_METADATA_FILENAME = 'thong-tin-sao-luu.json';
    private const EXPORT_SUMMARY_FILENAME = 'bao-cao-tom-tat.json';
    private const EXPORT_DB_DUMP_FILENAME = 'co-so-du-lieu.sql.gz';
    private const EXPORT_FILES_ARCHIVE_FILENAME = 'minh-chung.zip';
    private const EXPORT_MANIFEST_FILENAME = 'danh-sach-tep.json';
    private const EXPORT_INTERNAL_DIR = '__internal';
    private const EXPORT_BUNDLE_FILENAME = 'goi-sao-luu.zip';

    private const LEGACY_EXPORT_METADATA_FILENAME = 'metadata.json';
    private const LEGACY_EXPORT_SUMMARY_FILENAME = 'summary_report.json';
    private const LEGACY_EXPORT_DB_DUMP_FILENAME = 'database.sql.gz';
    private const LEGACY_EXPORT_FILES_ARCHIVE_FILENAME = 'evidence_files.zip';

    private ?array $exportIndexCache = null;

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
                $payload = $this->applyVerificationFromRunState($payload, $state);
            } else {
                $payload['run_state'] = null;
                $payload['size_bytes'] = null;
            }
            $payload['status'] = $payload['run_state'] ?? 'unknown';
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

        $this->ensureIncludedPathsExist();
        $includePaths = $this->resolveIncludedPathsForSnapshot();
        if ($includePaths === []) {
            throw new BackupRuntimeException(
                'Không tìm thấy thư mục dữ liệu cần backup. Vui lòng kiểm tra SPNC_BACKUP_INCLUDE_PATHS.'
            );
        }
        $excludePaths = $this->resolveExcludedPaths();

        File::ensureDirectoryExists(dirname($dbDumpAbsolute));
        File::ensureDirectoryExists($workspaceAbsolute);

        try {
            $this->dumpDatabase($dbDumpAbsolute);
            $dbDumpSize = $this->resolveFileSize($dbDumpAbsolute);
            if ($dbDumpSize <= 0) {
                throw new BackupRuntimeException('DB dump rỗng, backup bị hủy để tránh tạo snapshot không hợp lệ.');
            }

            $includedPathStats = $this->buildIncludedPathStats($includePaths);
            $manifest = $this->buildManifest(
                $runId,
                $trigger,
                $includePaths,
                $excludePaths,
                $workspaceRelative,
                $dbDumpRelative,
                $dbDumpSize,
                $includedPathStats,
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
            $snapshotId = trim((string) (($snapshot['snapshot_id'] ?? $snapshot['snapshot_id_full'] ?? '')));

            $checkResult = null;
            if ((bool) config('backup.restic.check_after_backup', true)) {
                $checkResult = $this->runRestic([
                    'check',
                    '--read-data-subset',
                    '1/20',
                ], false, 1800);
            }

            $export = null;
            if ((bool) config('backup.exports.enabled', true)) {
                if ($snapshotId === '') {
                    $export = [
                        'available' => false,
                        'error' => 'Không xác định được snapshot ID để tạo export dễ đọc.',
                    ];
                } else {
                    try {
                        $export = $this->createReadableExport(
                            $snapshotId,
                            $runId,
                            $trigger,
                            $workspaceAbsolute,
                            $dbDumpAbsolute,
                            $manifestAbsolute,
                            $includePaths,
                            $excludePaths,
                            $includedPathStats,
                            $initiatedBy,
                            $summary
                        );
                    } catch (\Throwable $exception) {
                        $export = [
                            'available' => false,
                            'snapshot_id' => $snapshotId,
                            'error' => $exception->getMessage(),
                        ];
                    }
                }
            }

            return [
                'snapshot' => $snapshot,
                'summary' => $summary,
                'manifest_relative_path' => $manifestRelative,
                'db_dump_relative_path' => $dbDumpRelative,
                'workspace_relative_path' => $workspaceRelative,
                'verification' => [
                    'contains_db_dump' => true,
                    'contains_files' => $includedPathStats['file_count'] > 0,
                    'db_dump_size_bytes' => $dbDumpSize,
                    'included_file_count' => $includedPathStats['file_count'],
                    'included_directory_count' => $includedPathStats['directory_count'],
                ],
                'check' => [
                    'ok' => $checkResult ? $checkResult['successful'] : true,
                    'stderr' => $checkResult ? trim((string) $checkResult['stderr']) : null,
                ],
                'export' => $export,
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
        $this->assertValidSnapshotId($snapshotId);

        if (! in_array($scope, ['db_only', 'files_only', 'full'], true)) {
            throw new BackupRuntimeException('Phạm vi khôi phục không hợp lệ.');
        }
        if (! in_array($target, ['staging', 'current'], true)) {
            throw new BackupRuntimeException('Đích khôi phục không hợp lệ.');
        }

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
            if (
                app()->environment('production')
                && ! (bool) config('backup.restore.allow_live_restore_in_production', false)
            ) {
                throw new BackupRuntimeException('Khôi phục trực tiếp trên production đang bị khóa.');
            }
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

    public function buildExportOverview(): array
    {
        $repository = trim((string) config('backup.restic.repository', ''));
        $destination = $this->deriveExportDestination($repository);

        return [
            'enabled' => (bool) config('backup.exports.enabled', true),
            'repository' => $repository,
            'repository_type' => $destination['repository_type'],
            'export_root' => $destination['display_root'],
            'export_folder_name' => (string) config('backup.exports.folder_name', 'exports'),
            'note' => 'Thư mục restic-repo chứa dữ liệu kỹ thuật (dedupe + mã hóa). Tệp dễ đọc nằm ở exports/<dd-mm-yyyy_HH-mm-ss_Sao-luu>.',
        ];
    }

    public function getSnapshotExportMetadata(string $snapshotId, ?array $snapshotHint = null): array
    {
        $this->assertValidSnapshotId($snapshotId);

        $snapshot = $this->resolveSnapshotForDetail($snapshotId, $snapshotHint);

        $metadata = $this->resolveExportMetadata($snapshotId);
        if (! $metadata) {
            throw new BackupRuntimeException('Snapshot chưa có export dễ đọc.');
        }

        return array_merge($metadata, [
            'snapshot_id' => $snapshotId,
            'snapshot_created_at' => $snapshot['created_at'] ?? null,
            'snapshot_backup_type' => $snapshot['backup_type'] ?? null,
        ]);
    }

    public function getSnapshotDetails(string $snapshotId, ?array $snapshotHint = null): array
    {
        $this->assertValidSnapshotId($snapshotId);

        $snapshot = $this->resolveSnapshotForDetail($snapshotId, $snapshotHint);

        $export = $this->resolveExportMetadata($snapshotId);
        $artifacts = array_values((array) ($export['artifacts'] ?? []));
        $visibleArtifacts = array_values(array_filter($artifacts, static function ($name): bool {
            $normalized = str_replace('\\', '/', trim((string) $name));
            return $normalized !== '' && ! str_starts_with($normalized, self::EXPORT_INTERNAL_DIR . '/');
        }));
        $technicalArtifacts = array_values(array_filter($artifacts, static function ($name): bool {
            $normalized = str_replace('\\', '/', trim((string) $name));
            return $normalized !== '' && str_starts_with($normalized, self::EXPORT_INTERNAL_DIR . '/');
        }));

        $filesArchivedCount = (int) ($export['stats']['files_archived_count'] ?? 0);
        $hasEvidenceArchive = $this->artifactExists($artifacts, [
            self::EXPORT_FILES_ARCHIVE_FILENAME,
            self::LEGACY_EXPORT_FILES_ARCHIVE_FILENAME,
        ]);
        $containsEvidenceFiles = (bool) ($snapshot['contains_files'] ?? false) || $filesArchivedCount > 0 || $hasEvidenceArchive;

        return [
            'snapshot' => $snapshot,
            'includes' => [
                'database_dump' => (bool) ($snapshot['contains_db_dump'] ?? false),
                'evidence_files' => $containsEvidenceFiles,
                'summary' => $this->artifactExists($artifacts, [
                    self::EXPORT_SUMMARY_FILENAME,
                    self::LEGACY_EXPORT_SUMMARY_FILENAME,
                ]),
                'metadata' => $this->artifactExists($artifacts, [
                    self::EXPORT_METADATA_FILENAME,
                    self::LEGACY_EXPORT_METADATA_FILENAME,
                ]),
            ],
            'export' => [
                'available' => is_array($export) && (bool) ($export['available'] ?? false),
                'drive_path' => is_array($export) ? ($export['drive_path'] ?? null) : null,
                'export_path' => is_array($export) ? ($export['export_path'] ?? null) : null,
                'bundle_filename' => is_array($export) ? ($export['bundle_filename'] ?? null) : null,
                'generated_at' => is_array($export) ? ($export['generated_at'] ?? null) : null,
                'folder_name' => is_array($export) ? ($export['folder_name'] ?? null) : null,
                'artifacts' => $visibleArtifacts,
                'technical_artifacts' => $technicalArtifacts,
                'stats' => is_array($export) ? ($export['stats'] ?? null) : null,
            ],
            'messages' => [
                'safe' => 'Hệ thống đã sao lưu an toàn.',
                'drive' => 'Bạn có thể mở thư mục Backup trên Google Drive để xem bản sao lưu dễ đọc.',
                'restore' => 'Khi cần khôi phục, vui lòng dùng chức năng Khôi phục trong hệ thống.',
            ],
        ];
    }

    public function prepareExportBundleDownload(string $snapshotId): array
    {
        $metadata = $this->getSnapshotExportMetadata($snapshotId);
        $relativePath = trim((string) ($metadata['local_bundle_relative_path'] ?? ''));
        if ($relativePath === '') {
            throw new BackupRuntimeException('Không tìm thấy đường dẫn gói export.');
        }

        $absolutePath = $this->basePathFromRelative($relativePath);
        if (! File::exists($absolutePath)) {
            throw new BackupRuntimeException('Không tìm thấy tệp export trên máy chủ.');
        }

        return [
            'absolute_path' => $absolutePath,
            'filename' => (string) ($metadata['bundle_filename'] ?? self::EXPORT_BUNDLE_FILENAME),
            'metadata' => $metadata,
        ];
    }

    public function forgetSnapshots(array $snapshotIds): array
    {
        $this->assertConfigured();
        $this->ensureRepositoryReady();

        $normalized = [];
        foreach ($snapshotIds as $snapshotId) {
            $id = trim((string) $snapshotId);
            if ($id === '') {
                continue;
            }
            $this->assertValidSnapshotId($id);
            $normalized[] = Str::lower($id);
        }

        $normalized = array_values(array_unique($normalized));
        if ($normalized === []) {
            throw new BackupRuntimeException('Danh sách snapshot cần xóa không hợp lệ.');
        }

        $args = ['forget', '--tag', 'spnc_backup'];
        foreach ($normalized as $snapshotId) {
            $args[] = $snapshotId;
        }

        $result = $this->runRestic($args, false, 3600);
        $this->removeExportMetadata($normalized);

        return [
            'snapshot_ids' => $normalized,
            'deleted_count' => count($normalized),
            'stdout' => trim((string) ($result['stdout'] ?? '')),
            'stderr' => trim((string) ($result['stderr'] ?? '')),
        ];
    }

    public function unlockStaleRepositoryLocks(): array
    {
        $this->assertConfigured();

        $result = $this->runRestic(['unlock'], true, 180);
        if (! ($result['successful'] ?? false)) {
            throw new BackupRuntimeException(
                'Không thể gỡ khóa stale của repository backup.'
            );
        }

        return [
            'successful' => true,
            'stdout' => trim((string) ($result['stdout'] ?? '')),
            'stderr' => trim((string) ($result['stderr'] ?? '')),
        ];
    }

    public function buildScheduleMeta(): array
    {
        $days = $this->normalizedScheduleDays();
        $time = (string) config('backup.schedule.time', '02:00');
        $timezone = (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh');
        $nextRunAt = $this->computeNextRunAt($days, $time, $timezone);
        $description = $this->buildScheduleDescriptionVi($days, $time);

        return [
            'mode' => 'fixed',
            'interval_minutes' => null,
            'interval_weeks' => 1,
            'weekday' => $days[0] ?? 1,
            'days' => $days,
            'time' => $time,
            'timezone' => $timezone,
            'next_run_at' => $nextRunAt?->toIso8601String(),
            'description_vi' => $description,
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

    public function buildDoctorReport(int $snapshotLimit = 10): array
    {
        $snapshotLimit = max(1, min(50, $snapshotLimit));
        $resticEnv = $this->resticEnv();

        return [
            'generated_at' => now()->toIso8601String(),
            'runtime' => $this->doctorRuntimeContext(),
            'backup' => $this->doctorBackupConfig($resticEnv),
            'proxy' => [
                'configured' => [
                    'HTTP_PROXY' => $this->sanitizeProxyValue((string) config('backup.network.http_proxy', '')),
                    'HTTPS_PROXY' => $this->sanitizeProxyValue((string) config('backup.network.https_proxy', '')),
                    'NO_PROXY' => $this->normalizeNoProxy((string) config('backup.network.no_proxy', '')),
                ],
                'runtime_env' => [
                    'HTTP_PROXY' => $this->sanitizeProxyValue((string) (getenv('HTTP_PROXY') ?: getenv('http_proxy') ?: '')),
                    'HTTPS_PROXY' => $this->sanitizeProxyValue((string) (getenv('HTTPS_PROXY') ?: getenv('https_proxy') ?: '')),
                    'NO_PROXY' => $this->normalizeNoProxy((string) (getenv('NO_PROXY') ?: getenv('no_proxy') ?: '')),
                ],
            ],
            'network' => $this->doctorNetworkDiagnostics(),
            'smoke_tests' => $this->doctorSmokeTests($snapshotLimit),
        ];
    }

    public function assertValidSnapshotId(string $snapshotId): void
    {
        if (! preg_match('/^[A-Fa-f0-9]{6,64}$/', trim($snapshotId))) {
            throw new BackupRuntimeException('Mã snapshot không hợp lệ.');
        }
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

        $blockedDatabases = ['mysql', 'information_schema', 'performance_schema', 'sys'];
        if (in_array(Str::lower($database), $blockedDatabases, true)) {
            throw new BackupRuntimeException('Từ chối import vào database hệ thống không an toàn.');
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

        $process = new Process($command, base_path(), $this->mergeProcessEnvironment($env), null, 3600);
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
        $this->assertValidSnapshotId($snapshotId);

        $result = $this->runRestic([
            'snapshots',
            '--json',
            '--tag',
            'spnc_backup',
            $snapshotId,
        ], true, 180);

        if ($result['successful']) {
            $decoded = json_decode((string) $result['stdout'], true);
            $snapshots = is_array($decoded) ? $decoded : [];
            foreach ($snapshots as $snapshot) {
                if (! is_array($snapshot)) {
                    continue;
                }

                $payload = $this->mapSnapshotPayload($snapshot);
                $id = (string) ($payload['snapshot_id'] ?? '');
                if ($id === $snapshotId || str_starts_with($id, $snapshotId)) {
                    $runId = (string) ($payload['run_id'] ?? '');
                    if ($runId !== '') {
                        $state = app(BackupRunStateStore::class)->get($runId);
                        $payload['run_state'] = $state['status'] ?? null;
                        $payload['size_bytes'] = $this->resolveRunSizeBytes($state);
                        $payload['status'] = $payload['run_state'] ?? 'unknown';
                        $payload = $this->applyVerificationFromRunState($payload, $state);
                    }
                    return $payload;
                }
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
        $snapshotId = (string) ($snapshot['id'] ?? '');
        $shortId = (string) (($snapshot['short_id'] ?? null) ?: Str::substr($snapshotId, 0, 8));
        $createdAt = (string) ($snapshot['time'] ?? '');
        $containsDbDump = $workspacePath !== null;
        $containsFiles = $this->snapshotContainsIncludedFiles((array) ($snapshot['paths'] ?? []), $workspacePath);
        $export = $snapshotId !== '' ? $this->resolveExportMetadata($snapshotId) : null;

        return [
            'snapshot_id' => $snapshotId,
            'snapshot_id_full' => $snapshotId,
            'short_id' => $shortId,
            'backup_name' => $this->buildSnapshotDisplayName($createdAt, $shortId),
            'created_at' => $createdAt,
            'hostname' => (string) ($snapshot['hostname'] ?? ''),
            'paths' => array_values((array) ($snapshot['paths'] ?? [])),
            'tags' => $tags,
            'run_id' => $runId,
            'trigger' => $this->extractTriggerFromTags($tags),
            'workspace_path' => $workspacePath,
            'contains_db_dump' => $containsDbDump,
            'contains_files' => $containsFiles,
            'backup_type' => $this->resolveBackupType($containsDbDump, $containsFiles),
            'export_available' => is_array($export) && (bool) ($export['available'] ?? false),
            'export_path' => is_array($export) ? ($export['export_path'] ?? null) : null,
            'export_drive_path' => is_array($export) ? ($export['drive_path'] ?? null) : null,
            'export_generated_at' => is_array($export) ? ($export['generated_at'] ?? null) : null,
            'export_bundle_filename' => is_array($export) ? ($export['bundle_filename'] ?? null) : null,
            'export_artifacts' => is_array($export) ? array_values((array) ($export['artifacts'] ?? [])) : [],
        ];
    }

    private function resolveSnapshotForDetail(string $snapshotId, ?array $snapshotHint = null): array
    {
        if (is_array($snapshotHint)) {
            $hintId = trim((string) ($snapshotHint['snapshot_id'] ?? ''));
            if ($hintId !== '' && ($hintId === $snapshotId || str_starts_with($hintId, $snapshotId))) {
                return $snapshotHint;
            }
        }

        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('Không tìm thấy snapshot.');
        }

        return $snapshot;
    }

    private function artifactExists(array $artifacts, array $candidates): bool
    {
        if ($artifacts === [] || $candidates === []) {
            return false;
        }

        $normalizedArtifacts = array_map(static function ($value): string {
            return Str::lower(str_replace('\\', '/', trim((string) $value)));
        }, $artifacts);
        $normalizedCandidates = array_map(static function ($value): string {
            return Str::lower(str_replace('\\', '/', trim((string) $value)));
        }, $candidates);

        foreach ($normalizedArtifacts as $artifact) {
            if ($artifact === '') {
                continue;
            }
            if (in_array($artifact, $normalizedCandidates, true)) {
                return true;
            }
        }

        return false;
    }
    private function applyVerificationFromRunState(array $payload, ?array $state): array
    {
        $verification = is_array($state['result']['verification'] ?? null)
            ? $state['result']['verification']
            : null;
        if (! is_array($verification)) {
            return $payload;
        }

        if (array_key_exists('contains_db_dump', $verification)) {
            $payload['contains_db_dump'] = (bool) $verification['contains_db_dump'];
        }
        if (array_key_exists('contains_files', $verification)) {
            $payload['contains_files'] = (bool) $verification['contains_files'];
        }

        $payload['backup_type'] = $this->resolveBackupType(
            (bool) ($payload['contains_db_dump'] ?? false),
            (bool) ($payload['contains_files'] ?? false)
        );

        return $payload;
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
        $existing = [];
        foreach ($this->configuredIncludePaths() as $clean) {
            $absolute = $this->basePathFromRelative($clean);
            if (File::exists($absolute)) {
                $existing[] = $clean;
            }
        }

        return array_values(array_unique($existing));
    }

    private function resolveExcludedPaths(): array
    {
        $configured = (array) config('backup.paths.exclude', []);
        $paths = [];
        foreach ($configured as $relativePath) {
            $clean = str_replace('\\', '/', trim((string) $relativePath, "/\\"));
            if ($clean === '') {
                continue;
            }
            if (! $this->isSafeRelativePath($clean)) {
                continue;
            }
            $paths[] = $clean;
        }

        return array_values(array_unique($paths));
    }

    private function ensureIncludedPathsExist(): void
    {
        $configured = (array) config('backup.paths.include', []);
        foreach ($configured as $relativePath) {
            $clean = str_replace('\\', '/', trim((string) $relativePath, "/\\"));
            if ($clean === '' || ! $this->isSafeRelativePath($clean)) {
                continue;
            }

            $absolute = $this->basePathFromRelative($clean);
            if (File::exists($absolute)) {
                continue;
            }

            // Chỉ tự tạo thư mục trong storage/app để tránh thao tác ngoài phạm vi ứng dụng.
            if (! str_starts_with($clean, 'storage/app/')) {
                continue;
            }

            File::makeDirectory($absolute, 0755, true);
        }
    }

    private function resolveFileSize(string $absolutePath): int
    {
        if (! File::exists($absolutePath)) {
            return 0;
        }

        $size = @filesize($absolutePath);
        if ($size === false || $size < 0) {
            return 0;
        }

        return (int) $size;
    }

    private function buildIncludedPathStats(array $includePaths): array
    {
        $fileCount = 0;
        $directoryCount = 0;

        foreach ($includePaths as $relativePath) {
            $absolute = $this->basePathFromRelative((string) $relativePath);
            if (! File::exists($absolute)) {
                continue;
            }

            if (File::isDirectory($absolute)) {
                $directoryCount++;
                $files = File::allFiles($absolute);
                $fileCount += count($files);
                continue;
            }

            $fileCount++;
        }

        return [
            'file_count' => $fileCount,
            'directory_count' => $directoryCount,
        ];
    }

    private function isSafeRelativePath(string $relativePath): bool
    {
        $path = trim(str_replace('\\', '/', $relativePath));
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path)) {
            return false;
        }

        if (str_contains($path, '../') || str_contains($path, '/..') || str_contains($path, '..\\')) {
            return false;
        }

        return ! str_contains($path, "\0");
    }

    private function buildManifest(
        string $runId,
        string $trigger,
        array $includePaths,
        array $excludePaths,
        string $workspaceRelative,
        string $dbDumpRelative,
        int $dbDumpSize,
        array $includedPathStats,
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
                'verification' => [
                    'db_dump_size_bytes' => $dbDumpSize,
                    'included_file_count' => (int) ($includedPathStats['file_count'] ?? 0),
                    'included_directory_count' => (int) ($includedPathStats['directory_count'] ?? 0),
                ],
            ],
            'restore' => [
                'confirm_phrase' => (string) config('backup.restore.confirm_phrase', 'RESTORE'),
                'available_scopes' => ['db_only', 'files_only', 'full'],
                'available_targets' => ['staging', 'current'],
            ],
        ];
    }

    private function createReadableExport(
        string $snapshotId,
        string $runId,
        string $trigger,
        string $workspaceAbsolute,
        string $dbDumpAbsolute,
        string $manifestAbsolute,
        array $includePaths,
        array $excludePaths,
        array $includedPathStats,
        ?int $initiatedBy,
        ?array $backupSummary
    ): array {
        $destination = $this->deriveExportDestination((string) config('backup.restic.repository', ''));
        $generatedAt = now();
        $folderName = $this->buildFriendlyExportFolderName($generatedAt, $snapshotId);

        $localRootRelative = $this->exportRelativePath($snapshotId);
        $localRootAbsolute = $this->basePathFromRelative($localRootRelative);

        if (File::exists($localRootAbsolute)) {
            File::deleteDirectory($localRootAbsolute);
        }
        File::ensureDirectoryExists($localRootAbsolute);

        $dbDumpExportAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_DB_DUMP_FILENAME;
        $this->gzipFile($dbDumpAbsolute, $dbDumpExportAbsolute);

        $manifestRelativePath = self::EXPORT_INTERNAL_DIR . '/' . self::EXPORT_MANIFEST_FILENAME;
        $manifestExportAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $manifestRelativePath);
        File::ensureDirectoryExists(dirname($manifestExportAbsolute));
        if (File::exists($manifestAbsolute)) {
            File::copy($manifestAbsolute, $manifestExportAbsolute);
        } else {
            File::put($manifestExportAbsolute, "{}\n");
        }

        $filesArchiveAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_FILES_ARCHIVE_FILENAME;
        $filesArchiveStats = $this->buildFilesArchive($filesArchiveAbsolute, $includePaths);

        $summaryPayload = $this->buildSummaryReportData();
        $summaryPayload['backup_context'] = [
            'snapshot_id' => $snapshotId,
            'run_id' => $runId,
            'trigger' => $trigger,
            'workspace_absolute' => $workspaceAbsolute,
            'included_paths' => array_values($includePaths),
            'excluded_paths' => array_values($excludePaths),
            'verification' => [
                'included_file_count' => (int) ($includedPathStats['file_count'] ?? 0),
                'included_directory_count' => (int) ($includedPathStats['directory_count'] ?? 0),
            ],
            'restic_summary' => $backupSummary,
        ];
        $summaryAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_SUMMARY_FILENAME;
        $this->writeJsonFile($summaryAbsolute, $summaryPayload);

        $bundleFilename = self::EXPORT_BUNDLE_FILENAME;
        $bundleAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . $bundleFilename;

        $metadata = [
            'snapshot_id' => $snapshotId,
            'run_id' => $runId,
            'trigger' => $trigger,
            'created_at' => $generatedAt->toIso8601String(),
            'generated_by_user_id' => $initiatedBy,
            'folder_name' => $folderName,
            'description_vi' => 'Bản sao lưu 2 lớp: lớp an toàn (restic) và lớp dễ đọc (exports).',
            'friendly_messages' => [
                'safe' => 'Hệ thống đã sao lưu an toàn.',
                'drive' => 'Bạn có thể mở thư mục Backup trên Google Drive để xem bản sao lưu dễ đọc.',
                'restore' => 'Khi cần khôi phục, vui lòng dùng chức năng Khôi phục trong hệ thống.',
            ],
            'repository' => trim((string) config('backup.restic.repository', '')),
            'export_destination' => [
                'repository_type' => $destination['repository_type'],
                'target_type' => $destination['target_type'],
                'target_root' => $destination['display_root'],
            ],
            'artifacts' => [
                'metadata' => self::EXPORT_METADATA_FILENAME,
                'database_dump' => self::EXPORT_DB_DUMP_FILENAME,
                'files_archive' => self::EXPORT_FILES_ARCHIVE_FILENAME,
                'summary_report' => self::EXPORT_SUMMARY_FILENAME,
                'bundle' => $bundleFilename,
                'internal_manifest' => $manifestRelativePath,
            ],
            'visible_artifacts' => [
                self::EXPORT_METADATA_FILENAME,
                self::EXPORT_DB_DUMP_FILENAME,
                self::EXPORT_FILES_ARCHIVE_FILENAME,
                self::EXPORT_SUMMARY_FILENAME,
                $bundleFilename,
            ],
            'technical_artifacts' => [$manifestRelativePath],
            'stats' => [
                'database_dump_gzip_bytes' => $this->resolveFileSize($dbDumpExportAbsolute),
                'files_archive_bytes' => $this->resolveFileSize($filesArchiveAbsolute),
                'files_archived_count' => (int) ($filesArchiveStats['file_count'] ?? 0),
                'paths_missing_from_archive' => $filesArchiveStats['missing_paths'] ?? [],
            ],
        ];
        $metadataAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_METADATA_FILENAME;
        $this->writeJsonFile($metadataAbsolute, $metadata);

        $this->buildExportBundle($localRootAbsolute, $bundleAbsolute);

        $sync = $this->syncExportToDestination($destination, $localRootAbsolute, $folderName, $bundleFilename);
        $payload = [
            'available' => true,
            'snapshot_id' => $snapshotId,
            'folder_name' => $folderName,
            'export_path' => (string) ($sync['export_path'] ?? ''),
            'drive_path' => (string) ($sync['drive_path'] ?? ''),
            'remote_bundle_path' => $sync['remote_bundle_path'] ?? null,
            'bundle_filename' => $bundleFilename,
            'local_root_relative_path' => $localRootRelative,
            'local_bundle_relative_path' => str_replace('\\', '/', $localRootRelative . '/' . $bundleFilename),
            'generated_at' => $generatedAt->toIso8601String(),
            'artifacts' => [
                self::EXPORT_METADATA_FILENAME,
                self::EXPORT_DB_DUMP_FILENAME,
                self::EXPORT_FILES_ARCHIVE_FILENAME,
                self::EXPORT_SUMMARY_FILENAME,
                $manifestRelativePath,
                $bundleFilename,
            ],
            'visible_artifacts' => $metadata['visible_artifacts'],
            'technical_artifacts' => $metadata['technical_artifacts'],
            'stats' => $metadata['stats'],
        ];

        $this->storeExportMetadata($snapshotId, $payload);

        return $payload;
    }

    private function buildSummaryReportData(): array
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'tables' => [],
        ];

        $report['tables']['research_activities'] = $this->buildTableSummary(
            'research_activities',
            ['id', 'academic_year_id', 'owner_lecturer_id', 'created_at']
        );
        $report['tables']['research_activity_members'] = $this->buildTableSummary(
            'research_activity_members',
            ['id', 'activity_id', 'lecturer_id', 'role', 'created_at']
        );
        $report['tables']['evidence_files'] = $this->buildTableSummary(
            'evidence_files',
            ['id', 'activity_id', 'disk', 'path', 'original_name', 'size_bytes', 'uploaded_at', 'created_at']
        );
        $report['tables']['lecturer_yearly_hours'] = $this->buildTableSummary(
            'lecturer_yearly_hours',
            ['id', 'lecturer_id', 'academic_year_id', 'created_at']
        );

        return $report;
    }

    private function buildTableSummary(string $table, array $preferredColumns, int $previewLimit = 200): array
    {
        if (! Schema::hasTable($table)) {
            return [
                'exists' => false,
                'total_rows' => 0,
                'preview_columns' => [],
                'preview_rows' => [],
            ];
        }

        $columns = Schema::getColumnListing($table);
        $selected = array_values(array_filter(
            $preferredColumns,
            static fn (string $column): bool => in_array($column, $columns, true)
        ));

        if ($selected === []) {
            $selected = array_slice($columns, 0, 8);
        }

        $query = DB::table($table)->select($selected);
        if (in_array('id', $columns, true)) {
            $query->orderByDesc('id');
        } elseif (in_array('created_at', $columns, true)) {
            $query->orderByDesc('created_at');
        }

        $rows = $query->limit($previewLimit)->get()->map(static function ($row): array {
            $payload = [];
            foreach ((array) $row as $key => $value) {
                if ($value instanceof \DateTimeInterface) {
                    $payload[(string) $key] = $value->format(DATE_ATOM);
                    continue;
                }
                $payload[(string) $key] = $value;
            }
            return $payload;
        })->all();

        return [
            'exists' => true,
            'total_rows' => (int) DB::table($table)->count(),
            'preview_columns' => $selected,
            'preview_rows' => $rows,
        ];
    }

    private function buildFilesArchive(string $targetZipAbsolutePath, array $includePaths): array
    {
        if (File::exists($targetZipAbsolutePath)) {
            File::delete($targetZipAbsolutePath);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($targetZipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new BackupRuntimeException('Không thể tạo tệp nén evidence_files.zip.');
        }

        $fileCount = 0;
        $missingPaths = [];
        foreach ($includePaths as $relativePath) {
            $relative = str_replace('\\', '/', trim((string) $relativePath, "/\\"));
            if ($relative === '' || ! $this->isSafeRelativePath($relative)) {
                continue;
            }

            $absolute = $this->basePathFromRelative($relative);
            if (! File::exists($absolute)) {
                $missingPaths[] = $relative;
                continue;
            }

            if (File::isDirectory($absolute)) {
                foreach (File::allFiles($absolute) as $file) {
                    $relativeInDir = str_replace('\\', '/', $file->getRelativePathname());
                    $archivePath = trim($relative . '/' . $relativeInDir, '/');
                    if ($zip->addFile($file->getPathname(), $archivePath)) {
                        $fileCount++;
                    }
                }
                continue;
            }

            if ($zip->addFile($absolute, $relative)) {
                $fileCount++;
            }
        }

        $zip->close();

        return [
            'file_count' => $fileCount,
            'missing_paths' => array_values(array_unique($missingPaths)),
        ];
    }

    private function buildExportBundle(string $exportRootAbsolutePath, string $bundleAbsolutePath): void
    {
        if (File::exists($bundleAbsolutePath)) {
            File::delete($bundleAbsolutePath);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($bundleAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new BackupRuntimeException('Không thể tạo gói export ZIP.');
        }

        $allowedFiles = [
            self::EXPORT_METADATA_FILENAME,
            self::EXPORT_DB_DUMP_FILENAME,
            self::EXPORT_FILES_ARCHIVE_FILENAME,
            self::EXPORT_SUMMARY_FILENAME,
            self::EXPORT_BUNDLE_FILENAME,
            self::EXPORT_INTERNAL_DIR . '/' . self::EXPORT_MANIFEST_FILENAME,
        ];

        foreach ($allowedFiles as $name) {
            $normalized = str_replace('\\', '/', $name);
            if ($normalized === self::EXPORT_BUNDLE_FILENAME) {
                continue;
            }
            $absolute = $exportRootAbsolutePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
            if (! File::exists($absolute)) {
                continue;
            }
            $zip->addFile($absolute, $normalized);
        }

        $zip->close();
    }

    private function gzipFile(string $sourceAbsolutePath, string $targetAbsolutePath): void
    {
        if (! File::exists($sourceAbsolutePath)) {
            throw new BackupRuntimeException('Không tìm thấy DB dump để tạo export.');
        }

        $input = fopen($sourceAbsolutePath, 'rb');
        if (! is_resource($input)) {
            throw new BackupRuntimeException('Không thể đọc DB dump để nén export.');
        }

        $output = gzopen($targetAbsolutePath, 'wb9');
        if (! is_resource($output)) {
            fclose($input);
            throw new BackupRuntimeException('Không thể tạo database.sql.gz cho export.');
        }

        while (! feof($input)) {
            $chunk = fread($input, 1024 * 1024);
            if ($chunk === false) {
                gzclose($output);
                fclose($input);
                throw new BackupRuntimeException('Lỗi đọc DB dump khi nén export.');
            }
            if ($chunk !== '') {
                gzwrite($output, $chunk);
            }
        }

        gzclose($output);
        fclose($input);
    }

    private function writeJsonFile(string $absolutePath, array $payload): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new BackupRuntimeException('Không thể mã hóa JSON export.');
        }

        File::put($absolutePath, $encoded . PHP_EOL);
    }

    private function syncExportToDestination(
        array $destination,
        string $localRootAbsolute,
        string $folderName,
        string $bundleFilename
    ): array {
        if (! (bool) config('backup.exports.sync_to_drive', true)) {
            return [
                'export_path' => $localRootAbsolute,
                'drive_path' => null,
                'remote_bundle_path' => null,
            ];
        }

        if (($destination['target_type'] ?? null) === 'rclone') {
            $targetRoot = rtrim((string) ($destination['target_root'] ?? ''), '/');
            if ($targetRoot === '') {
                throw new BackupRuntimeException('Không xác định được đích rclone cho export.');
            }

            $remoteDir = $targetRoot . '/' . $folderName;
            $this->runRclone(['mkdir', $remoteDir], true, 120);

            foreach (File::allFiles($localRootAbsolute) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $remoteFile = $remoteDir . '/' . $relative;
                $this->runRclone(['copyto', $file->getPathname(), $remoteFile], false, 3600);
            }

            return [
                'export_path' => $remoteDir,
                'drive_path' => 'rclone:' . $remoteDir,
                'remote_bundle_path' => $remoteDir . '/' . $bundleFilename,
            ];
        }

        if (($destination['target_type'] ?? null) === 'local') {
            $targetRoot = (string) ($destination['target_root'] ?? '');
            if ($targetRoot === '') {
                throw new BackupRuntimeException('Không xác định được thư mục local đích cho export.');
            }

            $targetDir = rtrim($targetRoot, '/\\') . DIRECTORY_SEPARATOR . $folderName;
            if (File::exists($targetDir)) {
                File::deleteDirectory($targetDir);
            }
            File::ensureDirectoryExists(dirname($targetDir));
            File::copyDirectory($localRootAbsolute, $targetDir);

            $bundlePath = $targetDir . DIRECTORY_SEPARATOR . $bundleFilename;
            return [
                'export_path' => str_replace('\\', '/', $targetDir),
                'drive_path' => str_replace('\\', '/', $targetDir),
                'remote_bundle_path' => str_replace('\\', '/', $bundlePath),
            ];
        }

        return [
            'export_path' => $localRootAbsolute,
            'drive_path' => null,
            'remote_bundle_path' => null,
        ];
    }

    private function runRclone(array $args, bool $allowFailure = false, int $timeout = 600): array
    {
        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        if ($rcloneBinary === '') {
            throw new BackupRuntimeException('Thiếu cấu hình rclone binary để đồng bộ export.');
        }

        return $this->runProcess(
            array_merge([$rcloneBinary], $args),
            $this->resticEnv(),
            $allowFailure,
            $timeout
        );
    }

    private function deriveExportDestination(string $repository): array
    {
        $folderName = trim((string) config('backup.exports.folder_name', 'exports'), "/\\");
        if ($folderName === '') {
            $folderName = 'exports';
        }

        $configuredTarget = trim((string) config('backup.exports.target', ''));
        if ($configuredTarget !== '') {
            if (str_starts_with(Str::lower($configuredTarget), 'rclone:')) {
                $raw = trim((string) Str::after($configuredTarget, 'rclone:'));
                return [
                    'repository_type' => str_starts_with(Str::lower($repository), 'rclone:') ? 'rclone' : 'local',
                    'target_type' => 'rclone',
                    'target_root' => $raw,
                    'display_root' => 'rclone:' . $raw,
                ];
            }

            $absolute = $this->resolveAbsolutePath($configuredTarget);
            return [
                'repository_type' => str_starts_with(Str::lower($repository), 'rclone:') ? 'rclone' : 'local',
                'target_type' => 'local',
                'target_root' => $absolute,
                'display_root' => str_replace('\\', '/', $absolute),
            ];
        }

        if (str_starts_with(Str::lower($repository), 'rclone:')) {
            $withoutPrefix = trim((string) Str::after($repository, 'rclone:'));
            $parts = explode(':', $withoutPrefix, 2);
            $remoteName = trim((string) ($parts[0] ?? ''));
            $remotePath = trim((string) ($parts[1] ?? ''), '/');
            if ($remoteName !== '') {
                $parentPath = $remotePath;
                if ($parentPath !== '' && str_contains($parentPath, '/')) {
                    $parentPath = (string) Str::beforeLast($parentPath, '/');
                } elseif ($parentPath !== '') {
                    $parentPath = '';
                }

                $targetPath = trim(($parentPath !== '' ? $parentPath . '/' : '') . $folderName, '/');
                $root = $remoteName . ':' . $targetPath;

                return [
                    'repository_type' => 'rclone',
                    'target_type' => 'rclone',
                    'target_root' => $root,
                    'display_root' => 'rclone:' . $root,
                ];
            }
        }

        $resolvedRepo = $this->resolveAbsolutePath($repository);
        $repoParent = dirname(rtrim($resolvedRepo, '/\\'));
        $targetRoot = rtrim($repoParent, '/\\') . DIRECTORY_SEPARATOR . $folderName;

        return [
            'repository_type' => 'local',
            'target_type' => 'local',
            'target_root' => $targetRoot,
            'display_root' => str_replace('\\', '/', $targetRoot),
        ];
    }

    private function resolveExportMetadata(string $snapshotId): ?array
    {
        $index = $this->readExportIndex();
        foreach ($index as $id => $metadata) {
            $normalizedId = trim((string) $id);
            if ($normalizedId === '') {
                continue;
            }
            if ($normalizedId === $snapshotId || str_starts_with($normalizedId, $snapshotId)) {
                return is_array($metadata) ? $metadata : null;
            }
        }

        return null;
    }

    private function storeExportMetadata(string $snapshotId, array $payload): void
    {
        $index = $this->readExportIndex();
        $index[$snapshotId] = $payload;
        $this->writeExportIndex($index);
    }

    private function removeExportMetadata(array $snapshotIds): void
    {
        if ($snapshotIds === []) {
            return;
        }

        $index = $this->readExportIndex();
        foreach ($snapshotIds as $snapshotId) {
            $snapshotId = trim((string) $snapshotId);
            if ($snapshotId === '') {
                continue;
            }

            foreach (array_keys($index) as $key) {
                $normalized = trim((string) $key);
                if ($normalized === $snapshotId || str_starts_with($normalized, $snapshotId)) {
                    $metadata = is_array($index[$key] ?? null) ? $index[$key] : null;
                    $this->deleteExportDestination($metadata);
                    unset($index[$key]);
                    $localPath = $this->basePathFromRelative($this->exportRelativePath($normalized));
                    if (File::exists($localPath)) {
                        File::deleteDirectory($localPath);
                    }
                }
            }
        }

        $this->writeExportIndex($index);
    }

    private function deleteExportDestination(?array $metadata): void
    {
        if (! is_array($metadata)) {
            return;
        }

        $exportPath = trim((string) ($metadata['export_path'] ?? ''));
        if ($exportPath === '') {
            return;
        }

        if ($this->isAbsolutePath($exportPath)) {
            if (File::exists($exportPath)) {
                File::deleteDirectory($exportPath);
            }
            return;
        }

        if (preg_match('/^[A-Za-z0-9_.-]+:.+$/', $exportPath)) {
            try {
                $this->runRclone(['purge', $exportPath], true, 900);
            } catch (\Throwable) {
                // Không chặn thao tác forget nếu dọn export remote thất bại.
            }
        }
    }

    private function readExportIndex(): array
    {
        if (is_array($this->exportIndexCache)) {
            return $this->exportIndexCache;
        }

        $path = $this->exportIndexAbsolutePath();
        if (! File::exists($path)) {
            $this->exportIndexCache = [];
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (! is_array($decoded)) {
            $this->exportIndexCache = [];
            return [];
        }

        $this->exportIndexCache = $decoded;
        return $decoded;
    }

    private function writeExportIndex(array $index): void
    {
        $path = $this->exportIndexAbsolutePath();
        File::ensureDirectoryExists(dirname($path));
        $encoded = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new BackupRuntimeException('Không thể ghi chỉ mục export backup.');
        }

        File::put($path, $encoded . PHP_EOL);
        $this->exportIndexCache = $index;
    }

    private function exportIndexAbsolutePath(): string
    {
        $configured = trim((string) config('backup.exports.index_file', 'backup-exports/index.json'), "/\\");
        $relative = str_replace('\\', '/', 'storage/app/' . $configured);
        return $this->basePathFromRelative($relative);
    }

    private function buildFriendlyExportFolderName(\DateTimeInterface $generatedAt, string $snapshotId): string
    {
        $prefix = $generatedAt->format('d-m-Y_H-i-s') . '_Sao-luu';
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $prefix);
        $safe = trim((string) $safe, '-_.');
        if ($safe === '') {
            $safe = 'backup';
        }

        $suffix = Str::lower(Str::substr(trim($snapshotId), 0, 8));
        if ($suffix === '') {
            return $safe;
        }

        return $safe . '-' . $suffix;
    }
    private function exportRelativePath(string $snapshotId): string
    {
        $dir = trim((string) config('backup.exports.local_dir', 'backup-exports/items'), "/\\");
        return str_replace('\\', '/', 'storage/app/' . $dir . '/' . $snapshotId);
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
            if (File::isDirectory($source)) {
                if (File::exists($target)) {
                    File::deleteDirectory($target);
                }
                File::ensureDirectoryExists(dirname($target));
                File::copyDirectory($source, $target);
            } else {
                File::ensureDirectoryExists(dirname($target));
                File::copy($source, $target);
            }
            $mirrored[] = $relativePath;
        }

        return $mirrored;
    }

    private function restoreSingleFile(string $snapshotId, string $relativePath, string $token): array
    {
        if (! $this->isSafeRelativePath($relativePath)) {
            throw new BackupRuntimeException('Đường dẫn tệp trong snapshot không hợp lệ.');
        }

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
                    return $this->isSafeRelativePath($expected) ? $expected : null;
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
            $relative = substr($path, $pos);
            return $this->isSafeRelativePath($relative) ? $relative : null;
        }

        return null;
    }

    private function snapshotContainsIncludedFiles(array $paths, ?string $workspacePath): bool
    {
        $normalizedPaths = array_values(array_filter(array_map(
            static fn ($path): string => str_replace('\\', '/', trim((string) $path)),
            $paths
        )));

        $included = $this->configuredIncludePaths();
        if ($included === []) {
            return false;
        }

        foreach ($normalizedPaths as $path) {
            if ($workspacePath !== null && ($path === $workspacePath || str_starts_with($path, $workspacePath . '/'))) {
                continue;
            }

            foreach ($included as $includedPath) {
                if ($path === $includedPath || str_starts_with($path, rtrim($includedPath, '/') . '/')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function configuredIncludePaths(): array
    {
        $configured = (array) config('backup.paths.include', []);
        $normalized = [];
        foreach ($configured as $relativePath) {
            $clean = str_replace('\\', '/', trim((string) $relativePath, "/\\"));
            if ($clean === '' || ! $this->isSafeRelativePath($clean)) {
                continue;
            }
            $normalized[] = $clean;
        }

        return array_values(array_unique($normalized));
    }

    private function resolveBackupType(bool $containsDbDump, bool $containsFiles): string
    {
        if ($containsDbDump && $containsFiles) {
            return 'full';
        }
        if ($containsDbDump) {
            return 'db_only';
        }
        if ($containsFiles) {
            return 'files_only';
        }

        return 'unknown';
    }

    private function buildSnapshotDisplayName(string $createdAt, string $shortId): string
    {
        try {
            $date = Carbon::parse($createdAt);
            return 'Backup ' . $date->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i:s');
        } catch (\Throwable) {
            return 'Backup ' . $shortId;
        }
    }

    private function computeNextRunAt(array $days, string $time, string $timezone): ?Carbon
    {
        $now = Carbon::now($timezone);
        [$hour, $minute] = $this->parseTime($time);
        $days = array_values(array_unique(array_filter(array_map(
            static fn ($day): int => (int) $day,
            $days
        ), static fn (int $day): bool => $day >= 0 && $day <= 6)));
        if ($days === []) {
            $days = [1, 4];
        }

        for ($i = 0; $i < 14; $i++) {
            $candidateDay = $now->copy()->startOfDay()->addDays($i);
            if (! in_array($candidateDay->dayOfWeek, $days, true)) {
                continue;
            }

            $candidate = $candidateDay->copy()->setTime($hour, $minute);
            if ($candidate->lessThanOrEqualTo($now)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function normalizedScheduleDays(): array
    {
        $days = array_values(array_filter(array_map(
            static fn ($value): int => (int) trim((string) $value),
            (array) config('backup.schedule.days', [1, 4])
        ), static fn (int $day): bool => $day >= 0 && $day <= 6));
        if ($days === []) {
            return [1, 4];
        }

        return array_values(array_unique($days));
    }

    private function buildScheduleDescriptionVi(array $days, string $time): string
    {
        $timePart = $this->normalizeScheduleTimeForDisplay($time);
        $labels = array_values(array_filter(array_map(
            fn (int $day): string => $this->scheduleWeekdayLabelVi($day),
            $days
        )));
        if ($labels === []) {
            return 'Tự động: lịch cố định theo cấu hình hệ thống.';
        }

        $dayPart = count($labels) === 1
            ? $labels[0]
            : (implode(', ', array_slice($labels, 0, -1)) . ' và ' . $labels[count($labels) - 1]);

        return $timePart !== null
            ? "Tự động: {$dayPart} lúc {$timePart}"
            : "Tự động: {$dayPart}";
    }

    private function normalizeScheduleTimeForDisplay(string $time): ?string
    {
        [$hour, $minute] = $this->parseTime($time);
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $minute, 2, '0', STR_PAD_LEFT);
    }

    private function scheduleWeekdayLabelVi(int $weekday): string
    {
        $map = [
            0 => 'Chủ nhật',
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
        ];

        return $map[$weekday] ?? 'Thứ 2';
    }

    private function parseTime(string $time): array
    {
        $parts = explode(':', $time);
        $hour = isset($parts[0]) ? max(0, min(23, (int) $parts[0])) : 2;
        $minute = isset($parts[1]) ? max(0, min(59, (int) $parts[1])) : 0;
        return [$hour, $minute];
    }

    private function doctorRuntimeContext(): array
    {
        return [
            'app_env' => (string) config('app.env', app()->environment()),
            'php_sapi' => php_sapi_name(),
            'php_version' => PHP_VERSION,
            'php_binary' => PHP_BINARY,
            'script_user' => get_current_user() ?: null,
            'os_user' => $this->detectWhoAmI(),
            'username_env' => getenv('USERNAME') ?: getenv('USER') ?: null,
            'working_directory' => getcwd() ?: null,
            'base_path' => base_path(),
            'config_cached' => app()->configurationIsCached(),
        ];
    }

    private function doctorBackupConfig(array $resticEnv): array
    {
        $resticBinary = trim((string) config('backup.restic.binary', 'restic'));
        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        $repository = trim((string) config('backup.restic.repository', ''));
        $rcloneConfig = trim((string) config('backup.restic.rclone_config_path', ''));

        $proxyKeys = ['HTTP_PROXY', 'HTTPS_PROXY', 'NO_PROXY', 'http_proxy', 'https_proxy', 'no_proxy'];
        $envPreview = [];
        foreach ($proxyKeys as $key) {
            $value = (string) ($resticEnv[$key] ?? '');
            if ($value === '') {
                continue;
            }
            $envPreview[$key] = str_ends_with(Str::lower($key), 'no_proxy')
                ? $this->normalizeNoProxy($value)
                : $this->sanitizeProxyValue($value);
        }

        return [
            'engine' => (string) config('backup.engine', 'restic'),
            'repository' => $repository,
            'restic_password_set' => trim((string) config('backup.restic.password', '')) !== '',
            'restic_binary' => $this->inspectBinaryConfig($resticBinary),
            'rclone_binary' => $this->inspectBinaryConfig($rcloneBinary, $this->resolveRcloneCandidateDirectories()),
            'rclone_program' => $this->resolveRcloneProgram(),
            'rclone_config' => $this->inspectOptionalPath($rcloneConfig),
            'process_env' => [
                'RCLONE_CONFIG' => $this->inspectOptionalPath((string) ($resticEnv['RCLONE_CONFIG'] ?? '')),
                'PATH_overridden' => isset($resticEnv['PATH']) || isset($resticEnv['Path']),
                'proxy_overrides' => $envPreview,
            ],
        ];
    }

    private function doctorNetworkDiagnostics(): array
    {
        $host = 'www.googleapis.com';

        $dnsRecords = [];
        $dnsError = null;
        if (function_exists('dns_get_record')) {
            set_error_handler(static function (int $severity, string $message): bool {
                throw new \ErrorException($message, 0, $severity);
            });
            try {
                $records = dns_get_record($host, DNS_A + DNS_AAAA);
                if (is_array($records)) {
                    $dnsRecords = $this->parseDnsRecords($records);
                }
            } catch (\Throwable $exception) {
                $dnsError = $exception->getMessage();
            } finally {
                restore_error_handler();
            }
        }

        $resolvedByName = gethostbyname($host);
        $resolved = $resolvedByName !== $host ? $resolvedByName : null;

        return [
            'dns_get_record' => [
                'records' => $dnsRecords,
                'error' => $dnsError,
            ],
            'gethostbyname' => [
                'host' => $host,
                'result' => $resolvedByName,
                'resolved' => $resolved !== null,
            ],
            'tcp_connect_443' => $this->probeTcpHost($host, 443, 3.0),
        ];
    }

    private function doctorSmokeTests(int $snapshotLimit): array
    {
        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        $repository = trim((string) config('backup.restic.repository', ''));
        $rcloneRemoteRoot = $this->parseRcloneRemoteRoot($repository);
        $rcloneProgram = $this->resolveRcloneProgram();

        $tests = [
            'snapshot_limit_requested' => $snapshotLimit,
            'rclone_version' => $this->doctorRunCommand([$rcloneBinary, 'version'], [], 20),
            'restic_snapshots' => $this->doctorRunCommand(
                array_merge(
                    [(string) config('backup.restic.binary', 'restic')],
                    $rcloneProgram !== null
                        ? ['-o', 'rclone.program=' . $rcloneProgram]
                        : [],
                    ['snapshots', '--json', '--tag', 'spnc_backup']
                ),
                $this->resticEnv(),
                90
            ),
        ];

        if ($rcloneRemoteRoot !== null) {
            $tests['rclone_lsd'] = $this->doctorRunCommand(
                [$rcloneBinary, 'lsd', $rcloneRemoteRoot],
                $this->resticEnv(),
                30
            );
        } else {
            $tests['rclone_lsd'] = [
                'ok' => false,
                'skipped' => true,
                'reason' => 'Repository không dùng backend rclone hoặc không đọc được remote.',
            ];
        }

        return $tests;
    }

    private function doctorRunCommand(array $command, array $env = [], int $timeout = 60): array
    {
        try {
            $result = $this->runProcess($command, $env, true, $timeout);
            return $this->formatDoctorProcessResult($result);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'exit_code' => null,
                'command' => implode(' ', array_map(static fn ($part): string => (string) $part, $command)),
                'stdout_preview' => null,
                'stderr_preview' => null,
                'exception' => $exception->getMessage(),
            ];
        }
    }

    private function formatDoctorProcessResult(array $result): array
    {
        return [
            'ok' => (bool) ($result['successful'] ?? false),
            'exit_code' => $result['exit_code'] ?? null,
            'command' => (string) ($result['command'] ?? ''),
            'stdout_preview' => $this->trimDoctorOutput((string) ($result['stdout'] ?? ''), 12, 3000),
            'stderr_preview' => $this->trimDoctorOutput((string) ($result['stderr'] ?? ''), 12, 3000),
        ];
    }

    private function trimDoctorOutput(string $output, int $maxLines = 12, int $maxChars = 3000): ?string
    {
        $normalized = trim($output);
        if ($normalized === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $normalized) ?: [];
        $originalLineCount = count($lines);
        $visibleLines = array_slice($lines, 0, max(1, $maxLines));
        $trimmed = implode(PHP_EOL, $visibleLines);

        $truncatedByChar = false;
        if (strlen($trimmed) > $maxChars) {
            $trimmed = substr($trimmed, 0, max(1, $maxChars));
            $truncatedByChar = true;
        }

        if ($originalLineCount > $maxLines || $truncatedByChar) {
            $trimmed .= PHP_EOL . '[output truncated]';
        }

        return $trimmed;
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
        $process = new Process($command, base_path(), $this->mergeProcessEnvironment($env), null, $timeout);
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
            $currentPath = $this->resolveCurrentPathValue();
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
                $mergedPath = implode($pathSeparator, $prepend)
                    . ($currentPath !== '' ? $pathSeparator . $currentPath : '');
                $env['PATH'] = $mergedPath;
                if (DIRECTORY_SEPARATOR === '\\') {
                    $env['Path'] = $mergedPath;
                }
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

    private function inspectBinaryConfig(string $configured, ?array $searchDirs = null): array
    {
        $configured = trim($configured);
        if ($configured === '') {
            return [
                'configured' => '',
                'resolved' => null,
                'exists' => false,
            ];
        }

        $resolved = null;
        if ($this->isAbsolutePath($configured) || str_contains($configured, '/') || str_contains($configured, '\\')) {
            $resolved = $this->resolveAbsolutePath($configured);
        } else {
            $finder = new ExecutableFinder();
            $found = $finder->find($configured, null, $searchDirs ?: null);
            if (is_string($found) && trim($found) !== '') {
                $resolved = $this->resolveAbsolutePath($found);
            }
        }

        if ($resolved === null && $this->isAbsolutePath($configured)) {
            $resolved = $this->resolveAbsolutePath($configured);
        }

        return [
            'configured' => $configured,
            'resolved' => $resolved,
            'exists' => $resolved !== null ? is_file($resolved) : false,
        ];
    }

    private function inspectOptionalPath(string $path): array
    {
        $configured = trim($path);
        if ($configured === '') {
            return [
                'configured' => null,
                'resolved' => null,
                'exists' => false,
                'readable' => false,
            ];
        }

        $resolved = $this->resolveAbsolutePath($configured);

        return [
            'configured' => $configured,
            'resolved' => $resolved,
            'exists' => is_file($resolved),
            'readable' => is_readable($resolved),
        ];
    }

    private function resolveAbsolutePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return $trimmed;
        }

        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);
        if ($this->isAbsolutePath($normalized)) {
            return $normalized;
        }

        return base_path($normalized);
    }

    private function isAbsolutePath(string $path): bool
    {
        $value = trim($path);
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z]:[\/\\\\]/', $value)
            || str_starts_with($value, '/')
            || str_starts_with($value, '\\\\');
    }

    private function parseRcloneRemoteRoot(string $repository): ?string
    {
        $repository = trim($repository);
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            return null;
        }

        $withoutPrefix = trim((string) Str::after($repository, 'rclone:'));
        if ($withoutPrefix === '') {
            return null;
        }

        $parts = explode(':', $withoutPrefix, 2);
        $remoteName = trim((string) ($parts[0] ?? ''));
        if ($remoteName === '') {
            return null;
        }

        return $remoteName . ':';
    }

    private function normalizeNoProxy(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $items = array_values(array_filter(array_map(
            static fn ($part): string => trim((string) $part),
            explode(',', $trimmed)
        )));
        if ($items === []) {
            return null;
        }

        return implode(',', array_slice($items, 0, 20));
    }

    private function sanitizeProxyValue(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $hostPort = $this->extractProxyHostPort($trimmed);
        if ($hostPort === null) {
            return '[set]';
        }

        $hasScheme = str_contains($trimmed, '://');
        if ($hasScheme) {
            $scheme = (string) parse_url($trimmed, PHP_URL_SCHEME);
            if ($scheme !== '') {
                return $scheme . '://' . $hostPort;
            }
        }

        return $hostPort;
    }

    private function extractProxyHostPort(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (str_contains($trimmed, '://')) {
            $host = (string) parse_url($trimmed, PHP_URL_HOST);
            $port = parse_url($trimmed, PHP_URL_PORT);
            if ($host === '') {
                return null;
            }
            return $port ? $host . ':' . $port : $host;
        }

        $withoutAuth = $trimmed;
        $atPos = strrpos($withoutAuth, '@');
        if ($atPos !== false) {
            $withoutAuth = substr($withoutAuth, $atPos + 1);
        }

        return $withoutAuth !== '' ? $withoutAuth : null;
    }

    private function resolveCurrentPathValue(): string
    {
        $systemEnv = $this->systemEnvironment();
        if (isset($systemEnv['PATH']) && trim((string) $systemEnv['PATH']) !== '') {
            return (string) $systemEnv['PATH'];
        }
        if (isset($systemEnv['Path']) && trim((string) $systemEnv['Path']) !== '') {
            return (string) $systemEnv['Path'];
        }

        return (string) (getenv('PATH') ?: getenv('Path') ?: '');
    }

    private function mergeProcessEnvironment(array $overrides): array
    {
        $env = $this->systemEnvironment();

        foreach ($overrides as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            if ($value === null) {
                unset($env[$key]);
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $env[$key] = (string) $value;
        }

        return $env;
    }

    private function systemEnvironment(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $collected = [];
        $sources = [getenv(), $_ENV, $_SERVER];
        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }
            foreach ($source as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }
                if ($value === null || is_array($value) || is_object($value)) {
                    continue;
                }
                $collected[$key] = (string) $value;
            }
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach (['SystemRoot', 'SYSTEMROOT', 'WINDIR', 'COMSPEC', 'PATHEXT'] as $requiredKey) {
                if (isset($collected[$requiredKey]) && trim((string) $collected[$requiredKey]) !== '') {
                    continue;
                }
                $value = getenv($requiredKey);
                if (is_string($value) && trim($value) !== '') {
                    $collected[$requiredKey] = $value;
                }
            }
        }

        $cached = $collected;

        return $cached;
    }

    private function detectWhoAmI(): ?string
    {
        try {
            $result = $this->runProcess(['whoami'], [], true, 10);
            if (! ($result['successful'] ?? false)) {
                return null;
            }
            $value = trim((string) ($result['stdout'] ?? ''));
            return $value !== '' ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDnsRecords(array $records): array
    {
        $output = [];
        foreach (array_slice($records, 0, 12) as $record) {
            if (! is_array($record)) {
                continue;
            }
            $entry = [];
            foreach (['type', 'host', 'ip', 'ipv6', 'target'] as $key) {
                if (! isset($record[$key])) {
                    continue;
                }
                $entry[$key] = (string) $record[$key];
            }
            if ($entry !== []) {
                $output[] = $entry;
            }
        }

        return $output;
    }

    private function probeTcpHost(string $host, int $port, float $timeoutSeconds): array
    {
        $start = microtime(true);
        $errorNumber = 0;
        $errorMessage = '';
        $stream = @fsockopen($host, $port, $errorNumber, $errorMessage, $timeoutSeconds);
        $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        $success = is_resource($stream);

        if ($success) {
            fclose($stream);
        }

        return [
            'host' => $host,
            'port' => $port,
            'success' => $success,
            'latency_ms' => $elapsedMs,
            'error_number' => $errorNumber !== 0 ? $errorNumber : null,
            'error_message' => $errorMessage !== '' ? $errorMessage : null,
        ];
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


