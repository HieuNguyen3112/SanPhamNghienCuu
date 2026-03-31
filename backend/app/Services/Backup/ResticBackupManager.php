<?php

namespace App\Services\Backup;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

class ResticBackupManager
{
    private const DB_DUMP_RELATIVE_PATH = 'db/mysql.sql';
    private const MANIFEST_FILENAME = 'manifest.json';

    private const EXPORT_README_FILENAME = 'README.txt';
    private const EXPORT_OVERVIEW_FILENAME = 'tong-quan.json';
    private const EXPORT_DATABASE_DIR = 'database';
    private const EXPORT_DB_DUMP_FILENAME = 'du-lieu.sql.gz';
    private const EXPORT_SYSTEM_DIR = '_he-thong';
    private const EXPORT_STAGING_DIR = '_dang-xu-ly';
    private const EXPORT_MANIFEST_FILENAME = 'danh-sach-tep.json';
    private const EXPORT_BUNDLE_FILENAME = 'goi-sao-luu.zip';
    private const EXPORT_WORKS_DIR = 'cong-trinh';
    private const EXPORT_LECTURERS_DIR = 'giang-vien';
    private const EXPORT_EVIDENCE_DIR = 'minh-chung';
    private const EXPORT_WORK_INFO_FILENAME = 'thong-tin-cong-trinh.json';
    private const EXPORT_WORK_INFO_PDF_FILENAME = 'thong-tin-cong-trinh.pdf';
    private const EXPORT_LECTURER_SUMMARY_FILENAME = 'tong-hop.json';
    private const EXPORT_LECTURER_SUMMARY_PDF_FILENAME = 'tong-hop-giang-vien.pdf';

    private const LEGACY_EXPORT_METADATA_FILENAME = 'thong-tin-sao-luu.json';
    private const LEGACY_EXPORT_SUMMARY_FILENAME = 'bao-cao-tom-tat.json';
    private const LEGACY_EXPORT_DB_DUMP_FILENAME = 'co-so-du-lieu.sql.gz';
    private const LEGACY_EXPORT_FILES_ARCHIVE_FILENAME = 'minh-chung.zip';
    private const LEGACY_EXPORT_INTERNAL_DIR = '__internal';
    private const LEGACY_EXPORT_READABLE_ROOT_DIR = 'readable_exports';
    private const LEGACY_EXPORT_READABLE_INDEX_FILENAME = 'index.json';

    private ?array $exportIndexCache = null;

    public function listSnapshots(int $limit = 50, bool $skipPreflight = false): array
    {
        if (! $skipPreflight) {
            $this->assertConfigured();
            $this->assertDriveReadiness('snapshot_refresh');
            $this->assertRepositoryReady('snapshot_refresh');
        }

        try {
            $result = $this->runRestic([
                'snapshots',
                '--json',
                '--tag',
                'spnc_backup',
            ], false, $this->snapshotListingTimeoutSeconds());
        } catch (\Throwable $exception) {
            $normalizedMessage = Str::lower(trim((string) $exception->getMessage()));
            if (Str::contains($normalizedMessage, ['timed out', 'timeout', 'deadline exceeded'])) {
                throw new BackupRuntimeException('[SNAPSHOT_LIST_TIMEOUT] restic snapshots --json bi qua thoi gian cho khi doc metadata repository.');
            }

            throw $exception;
        }

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
            $payload['status'] = $payload['run_state'] ?? 'success';
            $items[] = $payload;
        }

        return $items;
    }

    public function runBackup(string $runId, string $trigger = 'manual', ?int $initiatedBy = null): array
    {
        $this->assertConfigured();
        $this->assertDriveReadiness('backup');
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
                'KhÃ´ng tÃ¬m tháº¥y thÆ° má»¥c dá»¯ liá»‡u cáº§n backup. Vui lÃ²ng kiá»ƒm tra SPNC_BACKUP_INCLUDE_PATHS.'
            );
        }
        $excludePaths = $this->resolveExcludedPaths();

        File::ensureDirectoryExists(dirname($dbDumpAbsolute));
        File::ensureDirectoryExists($workspaceAbsolute);

        try {
            $this->dumpDatabase($dbDumpAbsolute);
            $dbDumpSize = $this->resolveFileSize($dbDumpAbsolute);
            if ($dbDumpSize <= 0) {
                throw new BackupRuntimeException('DB dump rá»—ng, backup bá»‹ há»§y Ä‘á»ƒ trÃ¡nh táº¡o snapshot khÃ´ng há»£p lá»‡.');
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
            if ($snapshotId === '') {
                throw new BackupRuntimeException('KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c snapshot ID sau khi restic backup hoÃ n táº¥t.');
            }

            return [
                'snapshot' => $snapshot,
                'summary' => $summary,
                'manifest_relative_path' => $manifestRelative,
                'db_dump_relative_path' => $dbDumpRelative,
                'workspace_relative_path' => $workspaceRelative,
                'verification' => [
                    'contains_db_dump' => true,
                    'contains_files' => $includedPathStats['has_included_content'],
                    'db_dump_size_bytes' => $dbDumpSize,
                    'included_file_count' => $includedPathStats['file_count'],
                    'included_directory_count' => $includedPathStats['directory_count'],
                    'included_path_count' => $includedPathStats['path_count'],
                    'stats_are_estimated' => true,
                ],
                'check' => [
                    'scheduled' => (bool) config('backup.verification.enabled', true),
                    'deferred' => true,
                ],
                'export' => [
                    'available' => false,
                    'status' => (bool) config('backup.exports.enabled', true) ? 'queued' : 'disabled',
                    'snapshot_id' => $snapshotId,
                ],
            ];
        } finally {
            // KhÃ´ng giá»¯ DB dump táº¡m táº¡i local sau khi Ä‘Ã£ snapshot thÃ nh cÃ´ng/tháº¥t báº¡i.
            if (File::exists($workspaceAbsolute)) {
                File::deleteDirectory($workspaceAbsolute);
            }
        }
    }

    public function generateReadableExportForSnapshot(
        string $snapshotId,
        string $runId,
        string $trigger = 'manual',
        ?int $initiatedBy = null
    ): array {
        $this->assertConfigured();
        $this->assertDriveReadiness('backup_postprocess');
        $this->ensureRepositoryReady();
        $this->assertValidSnapshotId($snapshotId);

        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y snapshot Ä‘á»ƒ táº¡o export dá»… Ä‘á»c.');
        }

        $manifest = $this->extractManifestFromSnapshot($snapshotId);
        $dbDump = $this->extractDatabaseDumpFromSnapshot($snapshotId);

        try {
            $generatedAt = null;
            $createdAt = trim((string) ($snapshot['created_at'] ?? ''));
            if ($createdAt !== '') {
                try {
                    $generatedAt = Carbon::parse($createdAt);
                } catch (\Throwable) {
                    $generatedAt = null;
                }
            }

            return $this->createReadableExport(
                $snapshotId,
                $runId,
                $trigger,
                (string) $dbDump['absolute_path'],
                (string) $manifest['absolute_path'],
                $initiatedBy,
                $generatedAt
            );
        } finally {
            $manifestRoot = trim((string) ($manifest['relative_root'] ?? ''), "/\\");
            if ($manifestRoot !== '') {
                $manifestAbsoluteRoot = $this->basePathFromRelative($manifestRoot);
                if (File::exists($manifestAbsoluteRoot)) {
                    File::deleteDirectory($manifestAbsoluteRoot);
                }
            }

            $dbDumpRoot = trim((string) ($dbDump['relative_root'] ?? ''), "/\\");
            if ($dbDumpRoot !== '' && $dbDumpRoot !== $manifestRoot) {
                $dbDumpAbsoluteRoot = $this->basePathFromRelative($dbDumpRoot);
                if (File::exists($dbDumpAbsoluteRoot)) {
                    File::deleteDirectory($dbDumpAbsoluteRoot);
                }
            }
        }
    }

    public function runRepositoryCheck(): array
    {
        $this->assertConfigured();
        $this->assertDriveReadiness('backup_check');
        $this->ensureRepositoryReady();

        $subset = trim((string) config('backup.verification.read_data_subset', '1/20'));
        if ($subset === '') {
            $subset = '1/20';
        }

        $result = $this->runRestic([
            'check',
            '--read-data-subset',
            $subset,
        ], false, 1800);

        return [
            'ok' => (bool) $result['successful'],
            'stdout' => trim((string) $result['stdout']),
            'stderr' => trim((string) $result['stderr']),
            'read_data_subset' => $subset,
        ];
    }

    public function pruneBackups(): array
    {
        $this->assertConfigured();
        $this->assertDriveReadiness('prune');
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
        $this->assertDriveReadiness('restore');
        $this->ensureRepositoryReady();
        $this->assertValidSnapshotId($snapshotId);

        if (! in_array($scope, ['db_only', 'files_only', 'full'], true)) {
            throw new BackupRuntimeException('Pháº¡m vi khÃ´i phá»¥c khÃ´ng há»£p lá»‡.');
        }
        if (! in_array($target, ['staging', 'current'], true)) {
            throw new BackupRuntimeException('ÄÃ­ch khÃ´i phá»¥c khÃ´ng há»£p lá»‡.');
        }

        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y snapshot cáº§n khÃ´i phá»¥c.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('Snapshot khÃ´ng chá»©a metadata workspace Ä‘á»ƒ khÃ´i phá»¥c.');
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
                throw new BackupRuntimeException('KhÃ´i phá»¥c trá»±c tiáº¿p trÃªn production Ä‘ang bá»‹ khÃ³a.');
            }
            if (! (bool) config('backup.restore.allow_live_restore', false)) {
                throw new BackupRuntimeException('KhÃ´i phá»¥c trá»±c tiáº¿p bá»‹ táº¯t trong cáº¥u hÃ¬nh há»‡ thá»‘ng.');
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
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y snapshot.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y Ä‘Æ°á»ng dáº«n manifest trong snapshot.');
        }

        $relativePath = $workspacePath . '/' . self::MANIFEST_FILENAME;
        return $this->restoreSingleFile($snapshotId, $relativePath, 'manifest_' . Str::uuid());
    }

    public function extractDatabaseDumpFromSnapshot(string $snapshotId): array
    {
        $snapshot = $this->findSnapshotById($snapshotId);
        if (! $snapshot) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y snapshot.');
        }

        $workspacePath = (string) ($snapshot['workspace_path'] ?? '');
        if ($workspacePath === '') {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y Ä‘Æ°á»ng dáº«n DB dump trong snapshot.');
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
            'export_root' => ($destination['valid'] ?? false) ? ($destination['display_root'] ?? null) : null,
            'export_folder_name' => (string) config('backup.exports.folder_name', 'exports'),
            'available' => (bool) ($destination['valid'] ?? false),
            'error_code' => $destination['error_code'] ?? null,
            'error_message' => $destination['error_message'] ?? null,
            'note' => "Th\u{01B0} m\u{1EE5}c restic-repo gi\u{1EEF} l\u{1EDB}p sao l\u{01B0}u k\u{1EF9} thu\u{1EAD}t. L\u{1EDB}p exports ch\u{1EC9} ch\u{1EE9}a README.txt, tong-quan.json, database/, cong-trinh/, giang-vien/ v\u{00E0} _he-thong/.",
        ];
    }

    public function findExportMetadata(string $snapshotId): ?array
    {
        $this->assertValidSnapshotId($snapshotId);

        return $this->resolveExportMetadata($snapshotId);
    }

    public function hydrateSnapshotExportMetadata(array $snapshot, ?string $runId = null): array
    {
        $snapshotId = trim((string) ($snapshot['snapshot_id'] ?? $snapshot['snapshot_id_full'] ?? ''));
        if ($snapshotId === '') {
            return $snapshot;
        }

        $metadata = $this->resolveExportMetadata($snapshotId);
        if (! is_array($metadata) || ! (bool) ($metadata['available'] ?? false)) {
            return $snapshot;
        }

        $patch = $this->snapshotPatchFromExportMetadata($metadata);
        if ($patch === []) {
            return $snapshot;
        }

        app(BackupSnapshotStore::class)->mergeBySnapshotId(
            $snapshotId,
            $patch,
            $runId ?? trim((string) ($snapshot['run_id'] ?? '')) ?: null
        );

        return array_merge($snapshot, $patch);
    }

    public function getSnapshotExportMetadata(string $snapshotId, ?array $snapshotHint = null): array
    {
        $this->assertValidSnapshotId($snapshotId);

        $snapshot = $this->resolveSnapshotForDetail($snapshotId, $snapshotHint);

        $metadata = $this->resolveExportMetadata($snapshotId);
        if (! $metadata) {
            throw new BackupRuntimeException('Snapshot chÆ°a cÃ³ export dá»… Ä‘á»c.');
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
        $visibleArtifacts = array_values(array_filter($artifacts, fn ($name): bool => ! $this->isSystemArtifactPath((string) $name)));
        $technicalArtifacts = array_values(array_filter($artifacts, fn ($name): bool => $this->isSystemArtifactPath((string) $name)));

        $readableStats = is_array($export['stats']['readable_exports'] ?? null)
            ? $export['stats']['readable_exports']
            : [];
        $totalEvidenceFiles = (int) ($readableStats['total_evidence_files'] ?? 0);
        $hasLegacyEvidenceArchive = $this->artifactExists($artifacts, [self::LEGACY_EXPORT_FILES_ARCHIVE_FILENAME]);
        $containsEvidenceFiles = (bool) ($snapshot['contains_files'] ?? false)
            || $totalEvidenceFiles > 0
            || $hasLegacyEvidenceArchive;

        return [
            'snapshot' => $snapshot,
            'includes' => [
                'database_dump' => (bool) ($snapshot['contains_db_dump'] ?? false),
                'evidence_files' => $containsEvidenceFiles,
                'summary' => $this->artifactExists($artifacts, [
                    self::EXPORT_OVERVIEW_FILENAME,
                    self::LEGACY_EXPORT_SUMMARY_FILENAME,
                ]),
                'metadata' => $this->artifactExists($artifacts, [
                    self::EXPORT_OVERVIEW_FILENAME,
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
                'safe' => 'Há»‡ thá»‘ng Ä‘Ã£ sao lÆ°u an toÃ n.',
                'drive' => 'Báº¡n cÃ³ thá»ƒ má»Ÿ thÆ° má»¥c Backup trÃªn Google Drive Ä‘á»ƒ xem báº£n sao lÆ°u dá»… Ä‘á»c.',
                'restore' => 'Khi cáº§n khÃ´i phá»¥c, vui lÃ²ng dÃ¹ng chá»©c nÄƒng KhÃ´i phá»¥c trong há»‡ thá»‘ng.',
            ],
        ];
    }

    public function prepareExportBundleDownload(string $snapshotId): array
    {
        $metadata = $this->getSnapshotExportMetadata($snapshotId);
        $relativePath = trim((string) ($metadata['local_bundle_relative_path'] ?? ''));
        if ($relativePath !== '') {
            $absolutePath = $this->basePathFromRelative($relativePath);
            if (File::exists($absolutePath)) {
                return [
                    'absolute_path' => $absolutePath,
                    'filename' => (string) ($metadata['bundle_filename'] ?? self::EXPORT_BUNDLE_FILENAME),
                    'metadata' => $metadata,
                ];
            }
        }

        $localRootRelative = trim((string) ($metadata['local_root_relative_path'] ?? ''));
        if ($localRootRelative === '') {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y thÆ° má»¥c export Ä‘á»ƒ Ä‘Ã³ng gÃ³i táº£i xuá»‘ng.');
        }

        $localRootAbsolute = $this->basePathFromRelative($localRootRelative);
        if (! File::isDirectory($localRootAbsolute)) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y thÆ° má»¥c export trÃªn mÃ¡y chá»§.');
        }

        $token = 'export_' . Str::uuid();
        $downloadRelativeRoot = $this->downloadRelativePath($token);
        $downloadAbsoluteRoot = $this->basePathFromRelative($downloadRelativeRoot);
        if (File::exists($downloadAbsoluteRoot)) {
            File::deleteDirectory($downloadAbsoluteRoot);
        }
        File::ensureDirectoryExists($downloadAbsoluteRoot);

        $filename = (string) ($metadata['bundle_filename'] ?? ('backup_export_' . $snapshotId . '.zip'));
        $absolutePath = $downloadAbsoluteRoot . DIRECTORY_SEPARATOR . $filename;
        $this->buildExportBundle($localRootAbsolute, $absolutePath);

        return [
            'absolute_path' => $absolutePath,
            'filename' => $filename,
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
            throw new BackupRuntimeException('Danh sÃ¡ch snapshot cáº§n xÃ³a khÃ´ng há»£p lá»‡.');
        }

        $args = ['forget'];
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
                'KhÃ´ng thá»ƒ gá»¡ khÃ³a stale cá»§a repository backup.'
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

    public function buildReadinessReport(): array
    {
        $repository = trim((string) config('backup.restic.repository', ''));
        $passwordSet = trim((string) config('backup.restic.password', '')) !== '';
        $resticEnv = $this->resticEnv();
        $backupConfig = $this->doctorBackupConfig($resticEnv);
        $destination = $this->deriveExportDestination($repository);

        $repositoryType = (string) ($destination['repository_type'] ?? 'unknown');
        $requiresRclone = $repositoryType === 'rclone' || (($destination['target_type'] ?? null) === 'rclone');
        $databaseRuntime = is_array($backupConfig['database'] ?? null) ? $backupConfig['database'] : [];
        $databaseDriver = Str::lower(trim((string) ($databaseRuntime['driver'] ?? '')));

        $resticBinaryOk = (bool) (($backupConfig['restic_binary']['exists'] ?? false) === true);
        $rcloneBinaryOk = ! $requiresRclone || (bool) (($backupConfig['rclone_binary']['exists'] ?? false) === true);
        $rcloneConfigOk = ! $requiresRclone || (bool) (($backupConfig['rclone_config']['readable'] ?? false) === true);
        $rcloneRemoteOk = ! $requiresRclone || (bool) (($backupConfig['rclone_remote']['defined'] ?? false) === true);
        $mysqlDumpBinaryOk = ! in_array($databaseDriver, ['mysql', 'mariadb'], true)
            || (bool) (($databaseRuntime['mysql_dump_binary']['exists'] ?? false) === true);
        $mysqlRestoreBinaryOk = ! in_array($databaseDriver, ['mysql', 'mariadb'], true)
            || (bool) (($databaseRuntime['mysql_restore_binary']['exists'] ?? false) === true);
        $pgsqlDumpBinaryOk = ! in_array($databaseDriver, ['pgsql', 'postgres', 'postgresql'], true)
            || (bool) (($databaseRuntime['pgsql_dump_binary']['exists'] ?? false) === true);
        $pgsqlRestoreBinaryOk = ! in_array($databaseDriver, ['pgsql', 'postgres', 'postgresql'], true)
            || (bool) (($databaseRuntime['pgsql_restore_binary']['exists'] ?? false) === true);
        $rcloneRemoteAuthMode = (string) ($backupConfig['rclone_remote']['auth_mode'] ?? '');
        $serviceAccountLoaded = (bool) (($backupConfig['rclone_service_account_file']['readable'] ?? false) === true);
        $authReady = ! $requiresRclone
            || $rcloneRemoteAuthMode === 'oauth_token'
            || ($rcloneRemoteAuthMode === 'service_account' && $serviceAccountLoaded);
        $rcloneConfigSource = (string) ($backupConfig['rclone_config']['source'] ?? '');
        $serviceAccountSource = (string) ($backupConfig['rclone_service_account_file']['source'] ?? '');
        $conflictingRemoteAuthFields = (bool) (($backupConfig['rclone_remote']['has_conflicting_auth_fields'] ?? false) === true);
        $productionPathOverridesBase64 = $requiresRclone
            && app()->environment('production')
            && $rcloneConfigSource === 'path'
            && trim((string) config('backup.restic.rclone_config_base64', '')) !== '';
        $repositoryEnvConfigured = $repository !== '' && $passwordSet;
        $runtimeReady = $resticBinaryOk
            && $rcloneBinaryOk
            && $rcloneConfigOk
            && $rcloneRemoteOk
            && $authReady
            && $mysqlDumpBinaryOk
            && $mysqlRestoreBinaryOk
            && $pgsqlDumpBinaryOk
            && $pgsqlRestoreBinaryOk
            && ! $productionPathOverridesBase64
            && ! $conflictingRemoteAuthFields;

        $blockingIssues = [];
        $warnings = [];

        if ($repository === '') {
            $blockingIssues[] = [
                'code' => 'BACKUP_REPOSITORY_MISSING',
                'message' => 'Thiáº¿u SPNC_BACKUP_REPOSITORY nÃªn há»‡ thá»‘ng chÆ°a xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c repository backup.',
            ];
        }

        if (! $passwordSet) {
            $blockingIssues[] = [
                'code' => 'BACKUP_PASSWORD_MISSING',
                'message' => 'Thiáº¿u SPNC_BACKUP_PASSWORD nÃªn khÃ´ng thá»ƒ truy cáº­p repository restic.',
            ];
        }

        if (! $resticBinaryOk) {
            $blockingIssues[] = [
                'code' => 'RESTIC_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y restic binary trong runtime hiá»‡n táº¡i.',
            ];
        }

        if ($requiresRclone && ! $rcloneBinaryOk) {
            $blockingIssues[] = [
                'code' => 'RCLONE_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y rclone binary trong runtime hiá»‡n táº¡i.',
            ];
        }

        if ($requiresRclone && ! $rcloneConfigOk) {
            $blockingIssues[] = [
                'code' => 'RCLONE_CONFIG_INVALID',
                'message' => (string) (($backupConfig['rclone_config']['error'] ?? null)
                    ?: 'KhÃ´ng Ä‘á»c Ä‘Æ°á»£c tá»‡p rclone.conf dÃ¹ng cho backup.'),
            ];
        }

        if ($requiresRclone && ! $rcloneRemoteOk) {
            $blockingIssues[] = [
                'code' => 'RCLONE_REMOTE_UNDEFINED',
                'message' => 'Remote rclone trong SPNC_BACKUP_REPOSITORY chÆ°a Ä‘Æ°á»£c khai bÃ¡o trong rclone config hiá»‡n táº¡i.',
            ];
        }

        if (in_array($databaseDriver, ['pgsql', 'postgres', 'postgresql'], true) && ! $pgsqlDumpBinaryOk) {
            $blockingIssues[] = [
                'code' => 'PG_DUMP_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y pg_dump trong runtime hiá»‡n táº¡i nÃªn chÆ°a thá»ƒ táº¡o PostgreSQL dump cho backup.',
            ];
        }

        if (in_array($databaseDriver, ['pgsql', 'postgres', 'postgresql'], true) && ! $pgsqlRestoreBinaryOk) {
            $blockingIssues[] = [
                'code' => 'PSQL_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y psql trong runtime hiá»‡n táº¡i nÃªn chÆ°a thá»ƒ phá»¥c há»“i PostgreSQL tá»« backup.',
            ];
        }

        if (in_array($databaseDriver, ['mysql', 'mariadb'], true) && ! $mysqlDumpBinaryOk) {
            $blockingIssues[] = [
                'code' => 'MYSQLDUMP_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y mysqldump trong runtime hiá»‡n táº¡i nÃªn chÆ°a thá»ƒ táº¡o database dump cho backup.',
            ];
        }

        if (in_array($databaseDriver, ['mysql', 'mariadb'], true) && ! $mysqlRestoreBinaryOk) {
            $blockingIssues[] = [
                'code' => 'MYSQL_BINARY_INVALID',
                'message' => 'KhÃ´ng tÃ¬m tháº¥y mysql client trong runtime hiá»‡n táº¡i nÃªn chÆ°a thá»ƒ phá»¥c há»“i database tá»« backup.',
            ];
        }

        if ($rcloneRemoteAuthMode === 'service_account' && ! empty($backupConfig['rclone_service_account_file']['error'])) {
            $warnings[] = [
                'code' => 'RCLONE_SERVICE_ACCOUNT_INVALID',
                'message' => (string) $backupConfig['rclone_service_account_file']['error'],
            ];
        }

        if (! ($destination['valid'] ?? false)) {
            $blockingIssues[] = [
                'code' => (string) ($destination['error_code'] ?? 'EXPORT_TARGET_INVALID'),
                'message' => (string) ($destination['error_message'] ?? 'KhÃ´ng suy ra Ä‘Æ°á»£c Ä‘Ã­ch export tá»« cáº¥u hÃ¬nh backup hiá»‡n táº¡i.'),
            ];
        }

        if ($requiresRclone && $rcloneRemoteAuthMode === 'oauth_token') {
            $warnings[] = [
                'code' => 'RCLONE_OAUTH_INTERACTIVE',
                'message' => 'Backup dang dung OAuth token trong rclone.conf. Can theo doi token vi co the phai reconnect lai khi token het han hoac bi revoke.',
            ];
        }

        if ($requiresRclone && $rcloneRemoteAuthMode === 'service_account' && ! $serviceAccountLoaded) {
            $blockingIssues[] = [
                'code' => 'SERVICE_ACCOUNT_INVALID',
                'message' => (string) (($backupConfig['rclone_service_account_file']['error'] ?? null)
                    ?: 'Khong the materialize Google service account JSON cho runtime backup.'),
            ];
        }

        if ($productionPathOverridesBase64) {
            $blockingIssues[] = [
                'code' => 'RCLONE_CONFIG_SOURCE_MISMATCH',
                'message' => 'Production Ä‘ang Æ°u tiÃªn file rclone.conf cÅ© theo path thay vÃ¬ cáº¥u hÃ¬nh base64 tá»« env. HÃ£y Ä‘á»ƒ SPNC_RCLONE_CONFIG rá»—ng.',
            ];
        }

        if ($requiresRclone && $rcloneRemoteAuthMode === '') {
            $blockingIssues[] = [
                'code' => 'RCLONE_REMOTE_AUTH_INVALID',
                'message' => 'Remote backup khong co auth hop le. Hay kiem tra token OAuth hoac service_account_file trong rclone.conf.',
            ];
        }

        if ($conflictingRemoteAuthFields) {
            $blockingIssues[] = [
                'code' => 'RCLONE_REMOTE_AUTH_CONFLICT',
                'message' => 'Remote backup dang tron OAuth va service account trong cung mot cau hinh. Hay giu duy nhat mot auth mode.',
            ];
        }

        $driveProbe = $this->buildOperationReadinessProbe($repository, $requiresRclone);

        return [
            'repository_env_configured' => $repositoryEnvConfigured,
            'runtime_ready' => $runtimeReady,
            'ready_for_operations' => $repositoryEnvConfigured && $runtimeReady && (bool) ($destination['valid'] ?? false) && (bool) ($driveProbe['ok'] ?? true),
            'repository' => [
                'value' => $repository !== '' ? $repository : null,
                'type' => $repositoryType !== 'unknown' ? $repositoryType : null,
                'password_set' => $passwordSet,
            ],
            'runtime' => [
                'restic_binary' => $backupConfig['restic_binary'] ?? null,
                'rclone_binary' => $backupConfig['rclone_binary'] ?? null,
                'rclone_config' => $backupConfig['rclone_config'] ?? null,
                'rclone_program' => $backupConfig['rclone_program'] ?? null,
                'rclone_remote' => $backupConfig['rclone_remote'] ?? null,
                'auth_mode' => $rcloneRemoteAuthMode !== '' ? $rcloneRemoteAuthMode : null,
                'config_source' => $rcloneConfigSource !== '' ? $rcloneConfigSource : null,
                'service_account_source' => $serviceAccountSource !== '' ? $serviceAccountSource : null,
                'service_account_loaded' => $serviceAccountLoaded,
                'drive_probe' => $driveProbe,
                'database' => $backupConfig['database'] ?? null,
            ],
            'export_destination' => [
                'valid' => (bool) ($destination['valid'] ?? false),
                'repository_type' => $destination['repository_type'] ?? null,
                'target_type' => $destination['target_type'] ?? null,
                'display_root' => ($destination['valid'] ?? false) ? ($destination['display_root'] ?? null) : null,
                'error_code' => $destination['error_code'] ?? null,
                'error_message' => $destination['error_message'] ?? null,
            ],
            'blocking_issues' => $blockingIssues,
            'warnings' => $warnings,
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

    public function assertDriveReadiness(string $operation = 'backup'): void
    {
        $this->assertConfigured();

        $repository = trim((string) config('backup.restic.repository', ''));
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            return;
        }

        $probe = $this->buildOperationReadinessProbe($repository, true, false);
        $this->logBackupRuntimeContext($operation, $probe);

        if ((bool) ($probe['ok'] ?? false)) {
            return;
        }

        $errorCode = trim((string) ($probe['error_code'] ?? '')) ?: 'DRIVE_REMOTE_INACCESSIBLE';
        $message = trim((string) ($probe['message'] ?? '')) ?: 'Khï¿½ng th? xï¿½c th?c ho?c truy c?p Google Drive backup.';

        throw new BackupRuntimeException("[{$errorCode}] {$message}");
    }

    public function assertRepositoryReady(string $operation = 'backup'): void
    {
        $this->assertConfigured();
        $this->ensureRepositoryReady($operation);
    }

    public function assertValidSnapshotId(string $snapshotId): void
    {
        if (! preg_match('/^[A-Fa-f0-9]{6,64}$/', trim($snapshotId))) {
            throw new BackupRuntimeException('MÃ£ snapshot khÃ´ng há»£p lá»‡.');
        }
    }

    private function assertConfigured(): void
    {
        if ((string) config('backup.engine', 'restic') !== 'restic') {
            throw new BackupRuntimeException('Há»‡ thá»‘ng hiá»‡n chá»‰ há»— trá»£ engine restic.');
        }

        $repository = trim((string) config('backup.restic.repository', ''));
        $password = trim((string) config('backup.restic.password', ''));
        if ($repository === '' || $password === '') {
            throw new BackupRuntimeException(
                'Thiáº¿u cáº¥u hÃ¬nh backup. Vui lÃ²ng khai bÃ¡o SPNC_BACKUP_REPOSITORY vÃ  SPNC_BACKUP_PASSWORD.'
            );
        }
    }

    private function buildOperationReadinessProbe(
        string $repository,
        bool $requiresRclone,
        bool $includeRepositoryProbe = false
    ): array {
        if (! $requiresRclone) {
            return [
                'ok' => true,
                'remote_probe' => ['ok' => true, 'skipped' => true],
                'repository_probe' => ['ok' => true, 'skipped' => ! $includeRepositoryProbe],
            ];
        }

        $resticEnv = $this->resticEnv();
        $backupConfig = $this->doctorBackupConfig($resticEnv);
        $authMode = trim((string) ($backupConfig['rclone_remote']['auth_mode'] ?? ''));
        $remoteName = trim((string) ($backupConfig['rclone_remote']['name'] ?? ''));
        $configSource = trim((string) ($backupConfig['rclone_config']['source'] ?? ''));
        $serviceAccountLoaded = (bool) (($backupConfig['rclone_service_account_file']['readable'] ?? false) === true);
        $conflictingRemoteAuthFields = (bool) (($backupConfig['rclone_remote']['has_conflicting_auth_fields'] ?? false) === true);

        if ($authMode === '') {
            return [
                'ok' => false,
                'error_code' => 'DRIVE_AUTH_INVALID',
                'message' => 'Khong xac dinh duoc auth mode cho remote backup. Hay kiem tra token OAuth trong rclone.conf hoac service account runtime.',
                'remote_name' => $remoteName !== '' ? $remoteName : null,
                'auth_mode' => null,
                'config_source' => $configSource !== '' ? $configSource : null,
                'service_account_loaded' => $serviceAccountLoaded,
            ];
        }

        if ($authMode === 'service_account' && ! $serviceAccountLoaded) {
            return [
                'ok' => false,
                'error_code' => 'SERVICE_ACCOUNT_INVALID',
                'message' => (string) (($backupConfig['rclone_service_account_file']['error'] ?? null)
                    ?: 'Khong the nap Google service account JSON cho runtime backup.'),
                'remote_name' => $remoteName !== '' ? $remoteName : null,
                'auth_mode' => $authMode,
                'config_source' => $configSource !== '' ? $configSource : null,
                'service_account_loaded' => false,
            ];
        }

        if ($conflictingRemoteAuthFields) {
            return [
                'ok' => false,
                'error_code' => 'RCLONE_REMOTE_AUTH_CONFLICT',
                'message' => 'Remote backup dang tron ca OAuth va service account. Hay giu duy nhat mot auth mode trong runtime backup.',
                'remote_name' => $remoteName !== '' ? $remoteName : null,
                'auth_mode' => $authMode !== '' ? $authMode : null,
                'config_source' => $configSource !== '' ? $configSource : null,
                'service_account_loaded' => $serviceAccountLoaded,
            ];
        }

        $rootTarget = $this->parseRcloneRemoteRoot($repository);
        $remoteProbe = $rootTarget !== null
            ? $this->runRclone(['lsd', $rootTarget], true, $this->driveProbeTimeoutSeconds())
            : ['successful' => false, 'stderr' => 'Khï¿½ng xï¿½c d?nh du?c remote rclone.', 'stdout' => ''];

        if (! (bool) ($remoteProbe['successful'] ?? false)) {
            return [
                'ok' => false,
                'error_code' => $this->detectDriveProbeErrorCode($remoteProbe),
                'message' => $this->extractProcessFailureMessage($remoteProbe, 'Khï¿½ng th? truy c?p Google Drive backup.'),
                'remote_name' => $remoteName !== '' ? $remoteName : null,
                'auth_mode' => $authMode !== '' ? $authMode : null,
                'config_source' => $configSource !== '' ? $configSource : null,
                'service_account_loaded' => $serviceAccountLoaded,
                'remote_probe' => [
                    'ok' => false,
                    'target' => $rootTarget,
                ],
            ];
        }

        $repositoryProbe = [
            'ok' => true,
            'skipped' => ! $includeRepositoryProbe,
            'target' => $repository,
        ];

        if ($includeRepositoryProbe) {
            $resticProbe = $this->runRestic(['cat', 'config'], true, $this->repositoryOpenTimeoutSeconds());
            if (! (bool) ($resticProbe['successful'] ?? false)) {
                return [
                    'ok' => false,
                    'error_code' => $this->detectRepositoryProbeErrorCode($resticProbe),
                    'message' => $this->buildRepositoryAccessFailureMessage($repository, $resticProbe),
                    'remote_name' => $remoteName !== '' ? $remoteName : null,
                    'auth_mode' => $authMode !== '' ? $authMode : null,
                    'config_source' => $configSource !== '' ? $configSource : null,
                    'service_account_loaded' => $serviceAccountLoaded,
                    'remote_probe' => [
                        'ok' => true,
                        'target' => $rootTarget,
                    ],
                    'repository_probe' => [
                        'ok' => false,
                        'target' => $repository,
                    ],
                ];
            }
        }

        return [
            'ok' => true,
            'remote_name' => $remoteName !== '' ? $remoteName : null,
            'auth_mode' => $authMode !== '' ? $authMode : null,
            'config_source' => $configSource !== '' ? $configSource : null,
            'service_account_loaded' => $serviceAccountLoaded,
            'remote_probe' => [
                'ok' => true,
                'target' => $rootTarget,
            ],
            'repository_probe' => $repositoryProbe,
        ];
    }

    private function detectDriveProbeErrorCode(array $probe): string
    {
        $message = Str::lower(trim((string) (($probe['stderr'] ?? '') . ' ' . ($probe['stdout'] ?? ''))));

        if ($message !== '' && Str::contains($message, ['invalid_grant', 'oauth', 'token', 'unauthorized_client'])) {
            return 'DRIVE_AUTH_INVALID';
        }

        if ($message !== '' && Str::contains($message, ['directory not found', 'root folder', 'permission denied', 'not found'])) {
            return 'DRIVE_REMOTE_INACCESSIBLE';
        }

        if ($message !== '' && Str::contains($message, ['timed out', 'timeout', 'deadline exceeded'])) {
            return 'DRIVE_PROBE_TIMEOUT';
        }

        return 'DRIVE_REMOTE_INACCESSIBLE';
    }

    private function detectRepositoryProbeErrorCode(array $probe): string
    {
        $message = Str::lower(trim((string) (($probe['stderr'] ?? '') . ' ' . ($probe['stdout'] ?? ''))));

        if ($message !== '' && Str::contains($message, ['already locked', 'repository is already locked', 'lock was created'])) {
            return 'REPOSITORY_LOCKED';
        }

        if ($message !== '' && Str::contains($message, ['timed out', 'timeout', 'deadline exceeded'])) {
            return 'REPOSITORY_OPEN_TIMEOUT';
        }

        if ($message !== '' && Str::contains($message, ['unable to open config file', 'is there a repository', 'config file does not exist', 'permission denied', 'directory not found', 'not found'])) {
            return 'REPOSITORY_NOT_VISIBLE';
        }

        return 'REPOSITORY_ACCESS_FAILED';
    }

    private function driveProbeTimeoutSeconds(): int
    {
        return max(5, (int) config('backup.timeouts.drive_probe_seconds', 12));
    }

    private function repositoryOpenTimeoutSeconds(): int
    {
        return max(30, (int) config('backup.timeouts.repository_open_seconds', 120));
    }

    private function snapshotListingTimeoutSeconds(): int
    {
        return max(60, (int) config('backup.timeouts.snapshot_listing_seconds', 300));
    }

    private function repositoryOpenReuseSeconds(): int
    {
        return max(0, (int) config('backup.timeouts.repository_open_reuse_seconds', 120));
    }

    private function extractProcessFailureMessage(array $result, string $fallback): string
    {
        $stderr = trim((string) ($result['stderr'] ?? ''));
        if ($stderr !== '') {
            return $stderr;
        }

        $stdout = trim((string) ($result['stdout'] ?? ''));
        if ($stdout !== '') {
            return $stdout;
        }

        return $fallback;
    }

    private function logBackupRuntimeContext(string $operation, array $probe): void
    {
        $resticEnv = $this->resticEnv();
        $backupConfig = $this->doctorBackupConfig($resticEnv);
        $repository = trim((string) config('backup.restic.repository', ''));
        Log::info('backup.runtime_context', [
            'operation' => $operation,
            'repository' => $repository,
            'remote_name' => $probe['remote_name'] ?? $this->extractRcloneRemoteName($repository),
            'auth_mode' => $probe['auth_mode'] ?? null,
            'config_source' => $probe['config_source'] ?? null,
            'service_account_loaded' => (bool) ($probe['service_account_loaded'] ?? false),
            'root_folder_id' => $backupConfig['rclone_remote']['root_folder_id'] ?? null,
            'repository_root_target' => $this->parseRcloneRemoteRoot($repository),
            'repository_parent_target' => $this->buildRcloneRepositoryProbeTarget($repository),
            'repository_target' => $this->buildRcloneRepositoryTarget($repository),
        ]);
    }

    private function ensureRepositoryReady(string $operation = 'backup'): void
    {
        $repository = trim((string) config('backup.restic.repository', ''));
        $cacheKey = $this->repositoryOpenStateCacheKey($repository);
        $reuseSeconds = $this->repositoryOpenReuseSeconds();
        if ($reuseSeconds > 0) {
            $cachedProbe = Cache::get($cacheKey);
            if (is_array($cachedProbe) && ! empty($cachedProbe['opened_at'])) {
                Log::info('backup.repository_open_cache_hit', [
                    'operation' => $operation,
                    'repository' => $repository,
                    'opened_at' => $cachedProbe['opened_at'],
                    'duration_ms' => $cachedProbe['duration_ms'] ?? null,
                    'reuse_seconds' => $reuseSeconds,
                ]);

                return;
            }
        }

        $startedAt = microtime(true);
        try {
            $probe = $this->runRestic(['cat', 'config'], true, $this->repositoryOpenTimeoutSeconds());
        } catch (\Throwable $exception) {
            $normalizedMessage = Str::lower(trim((string) $exception->getMessage()));
            if (Str::contains($normalizedMessage, ['timed out', 'timeout', 'deadline exceeded'])) {
                throw new BackupRuntimeException('[REPOSITORY_OPEN_TIMEOUT] restic cat config bi qua thoi gian cho khi mo repository backup.');
            }

            throw $exception;
        }

        if ($probe['successful']) {
            Cache::put($cacheKey, [
                'opened_at' => now()->toIso8601String(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], now()->addSeconds(max(1, $reuseSeconds)));

            return;
        }

        $stderr = Str::lower((string) $probe['stderr']);
        $repositoryMissing = str_contains($stderr, 'unable to open config file')
            || str_contains($stderr, 'is there a repository')
            || str_contains($stderr, 'config file does not exist');

        if (! $repositoryMissing) {
            throw new BackupRuntimeException(
                'KhÃ´ng thá»ƒ truy cáº­p repository backup: '
                . $this->buildRepositoryAccessFailureMessage($repository, $probe)
            );
        }

        if ($operation !== 'backup') {
            throw new BackupRuntimeException(
                '[REPOSITORY_NOT_VISIBLE] Repository backup khong co config visible trong buoc kiem tra. Hay kiem tra path repository va quyen truy cap noi dung repository.'
            );
        }

        $init = $this->runRestic(['init'], true, 300);
        if ($init['successful']) {
            return;
        }

        $initErr = Str::lower((string) $init['stderr']);
        if (str_contains($initErr, 'already initialized')) {
            Cache::put($cacheKey, [
                'opened_at' => now()->toIso8601String(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], now()->addSeconds(max(1, $reuseSeconds)));

            return;
        }

        throw new BackupRuntimeException('Khá»Ÿi táº¡o repository backup tháº¥t báº¡i: ' . trim((string) $init['stderr']));
    }

    private function dumpDatabase(string $outputAbsolutePath): void
    {
        $databaseConfig = $this->resolveBackupDatabaseConnection();
        $driver = $databaseConfig['driver'];
        $connection = $databaseConfig['config'];

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->dumpMySqlDatabase($connection, $outputAbsolutePath);
            return;
        }

        if (in_array($driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $this->dumpPgSqlDatabase($connection, $outputAbsolutePath);
            return;
        }

        throw new BackupRuntimeException(
            'Backup database hiá»‡n chá»‰ há»— trá»£ mysql/mariadb vÃ  pgsql. Driver hiá»‡n táº¡i: ' . $driver
        );
    }

    private function importDatabaseDump(string $dumpAbsolutePath): void
    {
        if (! File::exists($dumpAbsolutePath)) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y tá»‡p DB dump Ä‘á»ƒ khÃ´i phá»¥c.');
        }

        $databaseConfig = $this->resolveBackupDatabaseConnection();
        $driver = $databaseConfig['driver'];
        $connection = $databaseConfig['config'];

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->importMySqlDatabaseDump($connection, $dumpAbsolutePath);
            return;
        }

        if (in_array($driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $this->importPgSqlDatabaseDump($connection, $dumpAbsolutePath);
            return;
        }

        throw new BackupRuntimeException(
            'KhÃ´i phá»¥c database hiá»‡n chá»‰ há»— trá»£ mysql/mariadb vÃ  pgsql. Driver hiá»‡n táº¡i: ' . $driver
        );
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
                        $payload['status'] = $payload['run_state'] ?? 'success';
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
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y snapshot.');
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

    private function isSystemArtifactPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '') {
            return false;
        }

        return str_starts_with($normalized, self::EXPORT_SYSTEM_DIR . '/')
            || str_starts_with($normalized, self::LEGACY_EXPORT_INTERNAL_DIR . '/');
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

            // Chá»‰ tá»± táº¡o thÆ° má»¥c trong storage/app Ä‘á»ƒ trÃ¡nh thao tÃ¡c ngoÃ i pháº¡m vi á»©ng dá»¥ng.
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
        $pathCount = 0;

        foreach ($includePaths as $relativePath) {
            $absolute = $this->basePathFromRelative((string) $relativePath);
            if (! File::exists($absolute)) {
                continue;
            }

            $pathCount++;
            if (File::isDirectory($absolute)) {
                $directoryCount++;
                continue;
            }

            $fileCount++;
        }

        return [
            'file_count' => $fileCount,
            'directory_count' => $directoryCount,
            'path_count' => $pathCount,
            'has_included_content' => $pathCount > 0,
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
        string $dbDumpAbsolute,
        string $manifestAbsolute,
        ?int $initiatedBy,
        ?\DateTimeInterface $generatedAt = null
    ): array {
        $destination = $this->deriveExportDestination((string) config('backup.restic.repository', ''));
        if (! ($destination['valid'] ?? false)) {
            throw new BackupRuntimeException(
                (string) ($destination['error_message'] ?? 'KhÃ´ng suy ra Ä‘Æ°á»£c Ä‘Ã­ch export tá»« cáº¥u hÃ¬nh backup hiá»‡n táº¡i.')
            );
        }
        $generatedAt ??= now();
        $folderName = $this->buildFriendlyExportFolderName($generatedAt, $snapshotId);

        $localRootRelative = $this->exportRelativePath($snapshotId);
        $localRootAbsolute = $this->basePathFromRelative($localRootRelative);

        if (File::exists($localRootAbsolute)) {
            File::deleteDirectory($localRootAbsolute);
        }
        File::ensureDirectoryExists($localRootAbsolute);

        $databaseRelativePath = self::EXPORT_DATABASE_DIR . '/' . self::EXPORT_DB_DUMP_FILENAME;
        $dbDumpExportAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $databaseRelativePath);
        File::ensureDirectoryExists(dirname($dbDumpExportAbsolute));
        $this->gzipFile($dbDumpAbsolute, $dbDumpExportAbsolute);

        $manifestRelativePath = self::EXPORT_SYSTEM_DIR . '/' . self::EXPORT_MANIFEST_FILENAME;
        $manifestExportAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $manifestRelativePath);
        File::ensureDirectoryExists(dirname($manifestExportAbsolute));
        if (File::exists($manifestAbsolute)) {
            File::copy($manifestAbsolute, $manifestExportAbsolute);
        } else {
            File::put($manifestExportAbsolute, "{}\n");
        }

        $readableExport = $this->buildReadableEvidenceExport($localRootAbsolute);
        $readmeRelativePath = self::EXPORT_README_FILENAME;
        $readmeAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_README_FILENAME;
        $this->writeTextFile($readmeAbsolute, $this->buildExportReadme($snapshotId, $folderName, $generatedAt, $readableExport));

        $overviewRelativePath = self::EXPORT_OVERVIEW_FILENAME;
        $overviewAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_OVERVIEW_FILENAME;
        $overviewPayload = [
            'snapshot_id' => $snapshotId,
            'run_id' => $runId,
            'created_at' => $generatedAt->format(DATE_ATOM),
            'backup_type' => 'full',
            'trigger' => $trigger,
            'generated_by_user_id' => $initiatedBy,
            'folder_name' => $folderName,
            'database' => [
                'path' => $databaseRelativePath,
                'gzip_size_bytes' => $this->resolveFileSize($dbDumpExportAbsolute),
            ],
            'totals' => [
                'cong_trinh' => (int) ($readableExport['stats']['works_count'] ?? 0),
                'giang_vien' => (int) ($readableExport['stats']['lecturers_count'] ?? 0),
                'minh_chung' => (int) ($readableExport['stats']['total_evidence_files'] ?? 0),
                'minh_chung_da_sao_chep' => (int) ($readableExport['stats']['copied_evidence_files'] ?? 0),
                'minh_chung_chi_co_metadata' => (int) ($readableExport['stats']['metadata_only_evidence_files'] ?? 0),
            ],
            'duong_dan' => [
                'cong_trinh' => self::EXPORT_WORKS_DIR,
                'giang_vien' => self::EXPORT_LECTURERS_DIR,
                'he_thong' => self::EXPORT_SYSTEM_DIR,
            ],
            'ghi_chu' => [
                'exports_only' => 'ÄÃ¢y lÃ  lá»›p export dá»… Ä‘á»c Ä‘á»ƒ tra cá»©u nhanh trÃªn Drive.',
                'official_restore' => 'KhÃ´i phá»¥c chÃ­nh thá»©c váº«n pháº£i thá»±c hiá»‡n báº±ng chá»©c nÄƒng KhÃ´i phá»¥c cá»§a há»‡ thá»‘ng.',
                'live_evidence' => 'Tá»‡p PDF dÆ°á»›i cong-trinh/ chá»‰ lÃ  báº£n export dá»… Ä‘á»c, khÃ´ng pháº£i nguá»“n runtime cá»§a chá»©c nÄƒng xem minh chá»©ng.',
            ],
        ];
        $this->writeJsonFile($overviewAbsolute, $overviewPayload);

        $metadata = [
            'snapshot_id' => $snapshotId,
            'run_id' => $runId,
            'trigger' => $trigger,
            'created_at' => $generatedAt->format(DATE_ATOM),
            'generated_by_user_id' => $initiatedBy,
            'folder_name' => $folderName,
            'description_vi' => 'Lá»›p exports Ä‘Ã£ Ä‘Æ°á»£c tá»‘i giáº£n cho má»¥c Ä‘Ã­ch tra cá»©u, cÃ²n khÃ´i phá»¥c chÃ­nh thá»©c váº«n dá»±a vÃ o restic-repo.',
            'friendly_messages' => [
                'safe' => 'Há»‡ thá»‘ng Ä‘Ã£ sao lÆ°u an toÃ n.',
                'drive' => 'Báº¡n cÃ³ thá»ƒ má»Ÿ thÆ° má»¥c Backup trÃªn Google Drive Ä‘á»ƒ xem báº£n sao lÆ°u dá»… Ä‘á»c.',
                'restore' => 'Khi cáº§n khÃ´i phá»¥c, vui lÃ²ng dÃ¹ng chá»©c nÄƒng KhÃ´i phá»¥c trong há»‡ thá»‘ng.',
            ],
            'repository' => trim((string) config('backup.restic.repository', '')),
            'export_destination' => [
                'repository_type' => $destination['repository_type'],
                'target_type' => $destination['target_type'],
                'target_root' => $destination['display_root'],
            ],
            'artifacts' => [
                'readme' => $readmeRelativePath,
                'overview' => $overviewRelativePath,
                'database_dump' => $databaseRelativePath,
                'works_dir' => self::EXPORT_WORKS_DIR . '/',
                'lecturers_dir' => self::EXPORT_LECTURERS_DIR . '/',
                'system_manifest' => $manifestRelativePath,
            ],
            'visible_artifacts' => [
                $readmeRelativePath,
                $overviewRelativePath,
                $databaseRelativePath,
                self::EXPORT_WORKS_DIR . '/',
                self::EXPORT_LECTURERS_DIR . '/',
            ],
            'technical_artifacts' => [$manifestRelativePath],
            'stats' => [
                'database_dump_gzip_bytes' => $this->resolveFileSize($dbDumpExportAbsolute),
                'readable_exports' => $readableExport['stats'],
            ],
            'readable_exports' => [
                'works_dir' => $readableExport['works_relative_path'],
                'lecturers_dir' => $readableExport['lecturers_relative_path'],
                'system_dir' => self::EXPORT_SYSTEM_DIR,
                'strategy' => [
                    'live_evidence_storage_is_unchanged' => true,
                    'works_view_contains_physical_files' => true,
                    'lecturers_view_uses_summary_references_only' => true,
                    'presentation_pdfs_enabled' => (bool) config('backup.exports.pdf_enabled', true),
                    'remote_drive_fetch_is_skipped_during_backup' => true,
                    'zip_bundles_are_not_synced_to_drive' => true,
                ],
            ],
            'bundle' => [
                'filename' => self::EXPORT_BUNDLE_FILENAME,
                'generated_on_demand' => true,
                'synced_to_drive' => false,
            ],
        ];
        $payload = [
            'available' => true,
            'snapshot_id' => $snapshotId,
            'folder_name' => $folderName,
            'export_path' => str_replace('\\', '/', $localRootAbsolute),
            'drive_path' => null,
            'remote_bundle_path' => null,
            'bundle_filename' => self::EXPORT_BUNDLE_FILENAME,
            'local_root_relative_path' => $localRootRelative,
            'local_bundle_relative_path' => null,
            'generated_at' => $generatedAt->format(DATE_ATOM),
            'artifacts' => array_values(array_unique(array_merge(
                $metadata['visible_artifacts'],
                $metadata['technical_artifacts']
            ))),
            'visible_artifacts' => $metadata['visible_artifacts'],
            'technical_artifacts' => $metadata['technical_artifacts'],
            'stats' => $metadata['stats'],
            'sync_status' => (bool) config('backup.exports.sync_to_drive', true) ? 'local_ready' : 'skipped',
            'sync_error' => null,
        ];

        $this->storeExportMetadata($snapshotId, $payload);
        $this->reportPostProcessProgress(
            $runId,
            'syncing_export',
            'Readable export cá»¥c bá»™ Ä‘Ã£ hoÃ n táº¥t. Äang Ä‘á»“ng bá»™ tá»›i Ä‘Ã­ch lÆ°u trá»¯.'
        );

        $sync = $this->syncExportToDestination($destination, $localRootAbsolute, $folderName);
        $payload['export_path'] = (string) ($sync['export_path'] ?? $payload['export_path']);
        $payload['drive_path'] = (string) ($sync['drive_path'] ?? '');
        $payload['sync_status'] = (string) ($sync['sync_status'] ?? 'success');
        $payload['sync_error'] = $sync['sync_error'] ?? null;

        $this->reportPostProcessProgress(
            $runId,
            'publishing_export_metadata',
            'Readable export Ä‘Ã£ Ä‘á»“ng bá»™ xong. Äang cÃ´ng bá»‘ metadata cuá»‘i cÃ¹ng.'
        );
        $this->storeExportMetadata($snapshotId, $payload);

        return $payload;
    }

    private function buildReadableEvidenceExport(string $exportRootAbsolutePath): array
    {
        $dataset = $this->buildReadableExportDataset();
        $pdfRenderer = (bool) config('backup.exports.pdf_enabled', true)
            ? app(ReadableExportPdfRenderer::class)
            : null;
        $worksRootRelative = self::EXPORT_WORKS_DIR;
        $lecturersRootRelative = self::EXPORT_LECTURERS_DIR;
        File::ensureDirectoryExists($exportRootAbsolutePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $worksRootRelative));
        File::ensureDirectoryExists($exportRootAbsolutePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $lecturersRootRelative));

        $processedWorks = [];
        $processedLecturers = [];
        $copiedFiles = 0;
        $metadataOnlyFiles = 0;
        $copiedBytes = 0;
        $totalEvidenceFiles = 0;

        foreach ($dataset['works'] as $work) {
            $workRelativeBase = $worksRootRelative . '/' . $work['folder_name'];
            $workAbsoluteBase = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $workRelativeBase);
            File::ensureDirectoryExists($workAbsoluteBase);

            $evidenceDirRelativePath = $workRelativeBase . '/' . self::EXPORT_EVIDENCE_DIR;
            $evidenceDirAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $evidenceDirRelativePath);
            File::ensureDirectoryExists($evidenceDirAbsolutePath);

            $processedFiles = [];
            foreach ($work['lecturer_groups'] as $group) {
                foreach ($group['files'] as $file) {
                    $totalEvidenceFiles++;
                    $exportRelativePath = null;
                    $exportStatus = 'metadata_only';
                    $exportBytes = 0;

                    if ((bool) ($file['source']['available'] ?? false) && ! empty($file['source']['absolute_path'])) {
                        $exportRelativePath = $evidenceDirRelativePath . '/' . $file['export_filename'];
                        $evidenceAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                            . str_replace('/', DIRECTORY_SEPARATOR, $exportRelativePath);
                        $exportBytes = $this->copyReadableExportFile(
                            (string) $file['source']['absolute_path'],
                            $evidenceAbsolutePath
                        );
                        $exportStatus = 'copied';
                        $copiedFiles++;
                        $copiedBytes += $exportBytes;
                    } else {
                        $metadataOnlyFiles++;
                    }

                    $processedFiles[] = [
                        'evidence_id' => $file['evidence_id'],
                        'file_type' => $file['file_type'],
                        'original_name' => $file['original_name'],
                        'stored_filename' => $file['export_filename'],
                        'disk' => $file['disk'],
                        'path' => $file['path'],
                        'mime_type' => $file['mime_type'],
                        'size_bytes' => $file['size_bytes'],
                        'sha256' => $file['sha256'],
                        'uploaded_at' => $file['uploaded_at'],
                        'uploaded_by_user_id' => $file['uploaded_by_user_id'],
                        'uploaded_by' => [
                            'identity_key' => $group['lecturer']['identity_key'] ?? null,
                            'lecturer_id' => $group['lecturer']['lecturer_id'] ?? null,
                            'code' => $group['lecturer']['code'] ?? null,
                            'full_name' => $group['lecturer']['full_name'] ?? null,
                            'folder_name' => $group['lecturer']['folder_name'] ?? null,
                        ],
                        'export_status' => $exportStatus,
                        'export_relative_path' => $exportRelativePath,
                        'source' => $file['source'],
                        'exported_size_bytes' => $exportBytes > 0 ? $exportBytes : null,
                    ];
                }
            }

            usort($processedFiles, static function (array $left, array $right): int {
                return strcmp(
                    (string) ($left['uploaded_by']['full_name'] ?? ''),
                    (string) ($right['uploaded_by']['full_name'] ?? '')
                );
            });

            $workInfoRelativePath = $workRelativeBase . '/' . self::EXPORT_WORK_INFO_FILENAME;
            $workInfoAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $workInfoRelativePath);
            $workInfoPayload = [
                'snapshot' => [
                    'generated_at' => $dataset['generated_at'],
                ],
                'activity' => $work['activity'],
                'owner' => $work['owner'],
                'participants' => array_values($work['participants']),
                'evidence_files' => $processedFiles,
                'stats' => [
                    'participant_count' => count($work['participants']),
                    'evidence_file_count' => count($processedFiles),
                    'copied_evidence_count' => count(array_filter(
                        $processedFiles,
                        static fn (array $item): bool => ($item['export_status'] ?? '') === 'copied'
                    )),
                    'metadata_only_evidence_count' => count(array_filter(
                        $processedFiles,
                        static fn (array $item): bool => ($item['export_status'] ?? '') !== 'copied'
                    )),
                ],
            ];
            $this->writeJsonFile($workInfoAbsolutePath, $workInfoPayload);

            $workInfoPdfRelativePath = null;
            if ($pdfRenderer instanceof ReadableExportPdfRenderer) {
                $workInfoPdfRelativePath = $workRelativeBase . '/' . self::EXPORT_WORK_INFO_PDF_FILENAME;
                $workInfoPdfAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $workInfoPdfRelativePath);
                $this->writeBinaryFile($workInfoPdfAbsolutePath, $pdfRenderer->renderWorkSummary($workInfoPayload));
            }

            $processedWorks[$work['activity']['activity_id']] = [
                'activity' => $work['activity'],
                'owner' => $work['owner'],
                'folder_name' => $work['folder_name'],
                'relative_base_path' => $workRelativeBase,
                'work_info_relative_path' => $workInfoRelativePath,
                'work_info_pdf_relative_path' => $workInfoPdfRelativePath,
                'participants' => array_values($work['participants']),
                'evidence_files' => $processedFiles,
            ];
        }

        foreach ($dataset['lecturers'] as $lecturer) {
            $lecturerRelativeBase = $lecturersRootRelative . '/' . $lecturer['folder_name'];
            $lecturerAbsoluteBase = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $lecturerRelativeBase);
            File::ensureDirectoryExists($lecturerAbsoluteBase);

            $workEntries = [];
            foreach ($lecturer['works'] as $activityId => $workReference) {
                $processedWork = $processedWorks[$activityId] ?? null;
                if (! is_array($processedWork)) {
                    continue;
                }

                $evidenceReferences = array_values(array_filter(
                    $processedWork['evidence_files'],
                    static fn (array $item): bool => ($item['uploaded_by']['identity_key'] ?? null) === ($lecturer['identity_key'] ?? null)
                ));

                $workEntries[] = [
                    'activity_id' => $processedWork['activity']['activity_id'],
                    'activity_code' => $processedWork['activity']['activity_code'],
                    'title' => $processedWork['activity']['title'],
                    'folder_name' => $processedWork['folder_name'],
                    'cong_trinh_relative_path' => $processedWork['relative_base_path'],
                    'thong_tin_cong_trinh' => $processedWork['work_info_relative_path'],
                    'thong_tin_cong_trinh_tham_chieu' => $this->buildRelativeExportPath(
                        $lecturerRelativeBase . '/' . self::EXPORT_LECTURER_SUMMARY_FILENAME,
                        $processedWork['work_info_relative_path']
                    ),
                    'thong_tin_cong_trinh_pdf' => $processedWork['work_info_pdf_relative_path'],
                    'thong_tin_cong_trinh_pdf_tham_chieu' => is_string($processedWork['work_info_pdf_relative_path'] ?? null)
                        ? $this->buildRelativeExportPath(
                            $lecturerRelativeBase . '/' . self::EXPORT_LECTURER_SUMMARY_FILENAME,
                            $processedWork['work_info_pdf_relative_path']
                        )
                        : null,
                    'participation' => $workReference['participation'],
                    'uploaded_evidence_count' => count($evidenceReferences),
                    'uploaded_evidence_files' => array_map(
                        fn (array $file): array => [
                            'evidence_id' => $file['evidence_id'],
                            'original_name' => $file['original_name'],
                            'stored_filename' => $file['stored_filename'],
                            'file_type' => $file['file_type'],
                            'uploaded_at' => $file['uploaded_at'],
                            'export_status' => $file['export_status'],
                            'work_evidence_relative_path' => $file['export_relative_path'],
                            'work_evidence_reference' => is_string($file['export_relative_path']) && $file['export_relative_path'] !== ''
                                ? $this->buildRelativeExportPath(
                                    $lecturerRelativeBase . '/' . self::EXPORT_LECTURER_SUMMARY_FILENAME,
                                    $file['export_relative_path']
                                )
                                : null,
                        ],
                        $evidenceReferences
                    ),
                ];
            }

            usort($workEntries, static function (array $left, array $right): int {
                return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            });

            $lecturerSummaryRelativePath = $lecturerRelativeBase . '/' . self::EXPORT_LECTURER_SUMMARY_FILENAME;
            $lecturerSummaryAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $lecturerSummaryRelativePath);
            $uploadedEvidenceCount = array_sum(array_map(
                static fn (array $item): int => (int) ($item['uploaded_evidence_count'] ?? 0),
                $workEntries
            ));
            $lecturerSummaryPayload = [
                'lecturer' => $lecturer['lecturer'],
                'snapshot' => [
                    'generated_at' => $dataset['generated_at'],
                    'works_count' => count($workEntries),
                    'uploaded_evidence_count' => $uploadedEvidenceCount,
                ],
                'works' => $workEntries,
            ];
            $this->writeJsonFile($lecturerSummaryAbsolutePath, $lecturerSummaryPayload);

            $lecturerSummaryPdfRelativePath = null;
            if ($pdfRenderer instanceof ReadableExportPdfRenderer) {
                $lecturerSummaryPdfRelativePath = $lecturerRelativeBase . '/' . self::EXPORT_LECTURER_SUMMARY_PDF_FILENAME;
                $lecturerSummaryPdfAbsolutePath = $exportRootAbsolutePath . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $lecturerSummaryPdfRelativePath);
                $this->writeBinaryFile($lecturerSummaryPdfAbsolutePath, $pdfRenderer->renderLecturerSummary($lecturerSummaryPayload));
            }

            $processedLecturers[] = [
                'lecturer_id' => $lecturer['lecturer']['lecturer_id'],
                'code' => $lecturer['lecturer']['code'],
                'full_name' => $lecturer['lecturer']['full_name'],
                'folder_name' => $lecturer['folder_name'],
                'summary_relative_path' => $lecturerSummaryRelativePath,
                'summary_pdf_relative_path' => $lecturerSummaryPdfRelativePath,
                'works_count' => count($workEntries),
                'uploaded_evidence_count' => $uploadedEvidenceCount,
            ];
        }

        usort($processedLecturers, static function (array $left, array $right): int {
            return strcmp((string) ($left['full_name'] ?? ''), (string) ($right['full_name'] ?? ''));
        });

        $workEntries = array_map(static function (array $work): array {
            return [
                'activity_id' => $work['activity']['activity_id'],
                'activity_code' => $work['activity']['activity_code'],
                'title' => $work['activity']['title'],
                'folder_name' => $work['folder_name'],
                'cong_trinh_relative_path' => $work['relative_base_path'],
                'thong_tin_cong_trinh' => $work['work_info_relative_path'],
                'thong_tin_cong_trinh_pdf' => $work['work_info_pdf_relative_path'],
                'participant_count' => count($work['participants']),
                'evidence_file_count' => count($work['evidence_files']),
            ];
        }, array_values($processedWorks));

        usort($workEntries, static function (array $left, array $right): int {
            return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return [
            'works_relative_path' => $worksRootRelative,
            'lecturers_relative_path' => $lecturersRootRelative,
            'works' => $workEntries,
            'lecturers' => $processedLecturers,
            'stats' => [
                'works_count' => count($workEntries),
                'lecturers_count' => count($processedLecturers),
                'total_evidence_files' => $totalEvidenceFiles,
                'copied_evidence_files' => $copiedFiles,
                'metadata_only_evidence_files' => $metadataOnlyFiles,
                'copied_evidence_bytes' => $copiedBytes,
            ],
        ];
    }

    private function buildReadableExportDataset(): array
    {
        $generatedAt = now()->toIso8601String();
        $lecturerRows = $this->fetchReadableLecturers();
        $lecturers = [];
        foreach ($lecturerRows as $row) {
            $descriptor = $this->mapReadableLecturerDescriptor([
                'lecturer_id' => (int) $row->lecturer_id,
                'code' => $row->lecturer_code,
                'full_name' => $row->lecturer_full_name,
                'email' => $row->lecturer_email,
                'department_id' => $row->department_id,
                'department_code' => $row->department_code,
                'department_name' => $row->department_name,
                'faculty_id' => $row->faculty_id,
                'faculty_code' => $row->faculty_code,
                'faculty_name' => $row->faculty_name,
            ]);
            $lecturers[$descriptor['identity_key']] = $this->initializeReadableLecturerPayload($descriptor);
        }

        $activityRows = $this->fetchReadableActivities();
        $works = [];
        foreach ($activityRows as $row) {
            $ownerDescriptor = $this->mapReadableLecturerDescriptor([
                'lecturer_id' => (int) $row->owner_lecturer_id,
                'code' => $row->owner_lecturer_code,
                'full_name' => $row->owner_lecturer_name,
                'email' => $row->owner_lecturer_email,
                'department_id' => $row->owner_department_id,
                'department_code' => $row->owner_department_code,
                'department_name' => $row->owner_department_name,
                'faculty_id' => $row->owner_faculty_id,
                'faculty_code' => $row->owner_faculty_code,
                'faculty_name' => $row->owner_faculty_name,
            ]);

            if (! isset($lecturers[$ownerDescriptor['identity_key']])) {
                $lecturers[$ownerDescriptor['identity_key']] = $this->initializeReadableLecturerPayload($ownerDescriptor);
            }

            $activityId = (int) $row->activity_id;
            $work = [
                'activity' => [
                    'activity_id' => $activityId,
                    'activity_code' => (string) $row->activity_code,
                    'title' => (string) $row->title,
                    'abstract' => $row->abstract,
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'submitted_at' => $row->submitted_at,
                    'approved_at' => $row->approved_at,
                    'total_hours_calc' => $row->total_hours_calc,
                    'academic_year' => [
                        'id' => $row->academic_year_id ? (int) $row->academic_year_id : null,
                        'code' => $row->academic_year_code,
                    ],
                    'kind' => [
                        'id' => $row->kind_id ? (int) $row->kind_id : null,
                        'code' => $row->kind_code,
                        'name' => $row->kind_name,
                    ],
                    'type' => [
                        'id' => $row->type_id ? (int) $row->type_id : null,
                        'code' => $row->type_code,
                        'name' => $row->type_name,
                    ],
                    'status' => [
                        'id' => $row->status_id ? (int) $row->status_id : null,
                        'code' => $row->status_code,
                        'name' => $row->status_name,
                    ],
                ],
                'owner' => $ownerDescriptor,
                'folder_name' => $this->buildReadableFolderName(
                    trim((string) $row->activity_code . ' ' . (string) $row->title),
                    'cong-trinh',
                    $activityId
                ),
                'participants' => [
                    $ownerDescriptor['identity_key'] => $this->buildReadableParticipantEntry(
                        $ownerDescriptor,
                        true,
                        'Chá»§ nhiá»‡m',
                        null,
                        null
                    ),
                ],
                'lecturer_groups' => [],
            ];
            $works[$activityId] = $work;

            $this->attachLecturerWorkReference(
                $lecturers[$ownerDescriptor['identity_key']],
                $work,
                true,
                'Chá»§ nhiá»‡m',
                null,
                null
            );
        }

        if ($works === []) {
            return [
                'generated_at' => $generatedAt,
                'works' => [],
                'lecturers' => array_values($lecturers),
            ];
        }

        $memberRows = $this->fetchReadableActivityMembers(array_keys($works));
        foreach ($memberRows as $row) {
            $descriptor = $this->mapReadableLecturerDescriptor([
                'lecturer_id' => (int) $row->lecturer_id,
                'code' => $row->lecturer_code,
                'full_name' => $row->lecturer_full_name,
                'email' => $row->lecturer_email,
                'department_id' => $row->department_id,
                'department_code' => $row->department_code,
                'department_name' => $row->department_name,
                'faculty_id' => $row->faculty_id,
                'faculty_code' => $row->faculty_code,
                'faculty_name' => $row->faculty_name,
            ]);
            if (! isset($lecturers[$descriptor['identity_key']])) {
                $lecturers[$descriptor['identity_key']] = $this->initializeReadableLecturerPayload($descriptor);
            }

            $activityId = (int) $row->activity_id;
            if (! isset($works[$activityId])) {
                continue;
            }

            if (! isset($works[$activityId]['participants'][$descriptor['identity_key']])) {
                $works[$activityId]['participants'][$descriptor['identity_key']] = $this->buildReadableParticipantEntry(
                    $descriptor,
                    false,
                    $row->member_role_name,
                    $row->contribution_share,
                    $row->hours_assigned
                );
            }

            $this->attachLecturerWorkReference(
                $lecturers[$descriptor['identity_key']],
                $works[$activityId],
                false,
                $row->member_role_name,
                $row->contribution_share,
                $row->hours_assigned
            );
        }

        $evidenceRows = $this->fetchReadableEvidenceFiles(array_keys($works));
        foreach ($evidenceRows as $row) {
            $activityId = (int) $row->activity_id;
            if (! isset($works[$activityId])) {
                continue;
            }

            $uploaderDescriptor = $row->uploader_lecturer_id
                ? $this->mapReadableLecturerDescriptor([
                    'lecturer_id' => (int) $row->uploader_lecturer_id,
                    'code' => $row->uploader_lecturer_code,
                    'full_name' => $row->uploader_lecturer_name,
                    'email' => $row->uploader_lecturer_email,
                    'department_id' => $row->uploader_department_id,
                    'department_code' => $row->uploader_department_code,
                    'department_name' => $row->uploader_department_name,
                    'faculty_id' => $row->uploader_faculty_id,
                    'faculty_code' => $row->uploader_faculty_code,
                    'faculty_name' => $row->uploader_faculty_name,
                ])
                : $this->mapReadableExternalUploaderDescriptor([
                    'uploaded_by_user_id' => (int) $row->uploaded_by_user_id,
                    'name' => $row->uploader_user_name,
                    'email' => $row->uploader_user_email,
                ]);

            if ($uploaderDescriptor['lecturer_id'] !== null && ! isset($lecturers[$uploaderDescriptor['identity_key']])) {
                $lecturers[$uploaderDescriptor['identity_key']] = $this->initializeReadableLecturerPayload($uploaderDescriptor);
            }

            if (! isset($works[$activityId]['lecturer_groups'][$uploaderDescriptor['identity_key']])) {
                $works[$activityId]['lecturer_groups'][$uploaderDescriptor['identity_key']] = [
                    'lecturer' => $uploaderDescriptor,
                    'files' => [],
                ];
            }

            $works[$activityId]['lecturer_groups'][$uploaderDescriptor['identity_key']]['files'][] = [
                'evidence_id' => (int) $row->evidence_id,
                'file_type' => [
                    'id' => $row->file_type_id ? (int) $row->file_type_id : null,
                    'name' => $row->file_type_name,
                ],
                'original_name' => (string) $row->original_name,
                'export_filename' => $this->buildReadableEvidenceFilename(
                    (int) $row->evidence_id,
                    (string) $row->original_name,
                    (string) $row->mime_type
                ),
                'disk' => (string) $row->disk,
                'path' => (string) $row->path,
                'mime_type' => (string) $row->mime_type,
                'size_bytes' => (int) $row->size_bytes,
                'sha256' => (string) $row->sha256,
                'uploaded_at' => $row->uploaded_at,
                'uploaded_by_user_id' => (int) $row->uploaded_by_user_id,
                'source' => $this->resolveEvidenceExportSource((string) $row->disk, (string) $row->path),
            ];

            if ($uploaderDescriptor['lecturer_id'] !== null) {
                $existingParticipation = $lecturers[$uploaderDescriptor['identity_key']]['works'][$activityId]['participation'] ?? [];
                $this->attachLecturerWorkReference(
                    $lecturers[$uploaderDescriptor['identity_key']],
                    $works[$activityId],
                    (bool) ($works[$activityId]['owner']['identity_key'] === $uploaderDescriptor['identity_key']),
                    $existingParticipation['member_role_name'] ?? null,
                    $existingParticipation['contribution_share'] ?? null,
                    $existingParticipation['hours_assigned'] ?? null
                );
            }
        }

        foreach ($works as &$work) {
            uasort($work['participants'], static function (array $left, array $right): int {
                return strcmp((string) ($left['full_name'] ?? ''), (string) ($right['full_name'] ?? ''));
            });
            uasort($work['lecturer_groups'], static function (array $left, array $right): int {
                return strcmp(
                    (string) ($left['lecturer']['full_name'] ?? ''),
                    (string) ($right['lecturer']['full_name'] ?? '')
                );
            });
        }
        unset($work);

        foreach ($lecturers as &$lecturer) {
            uasort($lecturer['works'], static function (array $left, array $right): int {
                return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            });
        }
        unset($lecturer);

        $lecturerValues = array_values($lecturers);
        usort($lecturerValues, static function (array $left, array $right): int {
            return strcmp(
                (string) ($left['lecturer']['full_name'] ?? ''),
                (string) ($right['lecturer']['full_name'] ?? '')
            );
        });

        $workValues = array_values($works);
        usort($workValues, static function (array $left, array $right): int {
            return strcmp((string) ($left['activity']['title'] ?? ''), (string) ($right['activity']['title'] ?? ''));
        });

        return [
            'generated_at' => $generatedAt,
            'works' => $workValues,
            'lecturers' => $lecturerValues,
        ];
    }

    private function fetchReadableLecturers(): array
    {
        return DB::table('lecturers as l')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->orderBy('l.full_name')
            ->orderBy('l.id')
            ->select([
                'l.id as lecturer_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'l.email as lecturer_email',
                'd.id as department_id',
                'd.code as department_code',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.code as faculty_code',
                'f.name as faculty_name',
            ])
            ->get()
            ->all();
    }

    private function fetchReadableActivities(): array
    {
        return DB::table('research_activities as ra')
            ->join('lecturers as owner_l', 'ra.owner_lecturer_id', '=', 'owner_l.id')
            ->leftJoin('departments as owner_d', 'owner_l.department_id', '=', 'owner_d.id')
            ->leftJoin('faculties as owner_f', 'owner_d.faculty_id', '=', 'owner_f.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->leftJoin('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->orderBy('ra.id')
            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.abstract',
                'ra.start_date',
                'ra.end_date',
                'ra.submitted_at',
                'ra.approved_at',
                'ra.total_hours_calc',
                'ay.id as academic_year_id',
                'ay.code as academic_year_code',
                'ak.id as kind_id',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'at.id as type_id',
                'at.code as type_code',
                'at.name as type_name',
                'ast.id as status_id',
                'ast.code as status_code',
                'ast.name as status_name',
                'owner_l.id as owner_lecturer_id',
                'owner_l.code as owner_lecturer_code',
                'owner_l.full_name as owner_lecturer_name',
                'owner_l.email as owner_lecturer_email',
                'owner_d.id as owner_department_id',
                'owner_d.code as owner_department_code',
                'owner_d.name as owner_department_name',
                'owner_f.id as owner_faculty_id',
                'owner_f.code as owner_faculty_code',
                'owner_f.name as owner_faculty_name',
            ])
            ->get()
            ->all();
    }

    private function fetchReadableActivityMembers(array $activityIds): array
    {
        if ($activityIds === []) {
            return [];
        }

        return DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->whereIn('ram.activity_id', $activityIds)
            ->orderBy('ram.activity_id')
            ->orderBy('ram.id')
            ->select([
                'ram.activity_id',
                'ram.lecturer_id',
                'ram.contribution_share',
                'ram.hours_assigned',
                'mr.name as member_role_name',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'l.email as lecturer_email',
                'd.id as department_id',
                'd.code as department_code',
                'd.name as department_name',
                'f.id as faculty_id',
                'f.code as faculty_code',
                'f.name as faculty_name',
            ])
            ->get()
            ->all();
    }

    private function fetchReadableEvidenceFiles(array $activityIds): array
    {
        if ($activityIds === []) {
            return [];
        }

        return DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->leftJoin('users as uploader_u', 'ef.uploaded_by_user_id', '=', 'uploader_u.id')
            ->leftJoin('lecturers as uploader_l', 'uploader_l.user_id', '=', 'uploader_u.id')
            ->leftJoin('departments as uploader_d', 'uploader_l.department_id', '=', 'uploader_d.id')
            ->leftJoin('faculties as uploader_f', 'uploader_d.faculty_id', '=', 'uploader_f.id')
            ->whereIn('ef.activity_id', $activityIds)
            ->orderBy('ef.activity_id')
            ->orderBy('ef.uploaded_at')
            ->orderBy('ef.id')
            ->select([
                'ef.id as evidence_id',
                'ef.activity_id',
                'ef.file_type_id',
                'eft.name as file_type_name',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.sha256',
                'ef.uploaded_at',
                'ef.uploaded_by_user_id',
                'uploader_u.name as uploader_user_name',
                'uploader_u.email as uploader_user_email',
                'uploader_l.id as uploader_lecturer_id',
                'uploader_l.code as uploader_lecturer_code',
                'uploader_l.full_name as uploader_lecturer_name',
                'uploader_l.email as uploader_lecturer_email',
                'uploader_d.id as uploader_department_id',
                'uploader_d.code as uploader_department_code',
                'uploader_d.name as uploader_department_name',
                'uploader_f.id as uploader_faculty_id',
                'uploader_f.code as uploader_faculty_code',
                'uploader_f.name as uploader_faculty_name',
            ])
            ->get()
            ->all();
    }

    private function mapReadableLecturerDescriptor(array $payload): array
    {
        $lecturerId = isset($payload['lecturer_id']) ? (int) $payload['lecturer_id'] : 0;
        $code = trim((string) ($payload['code'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $label = trim($code . ' ' . $fullName);

        return [
            'identity_key' => 'lecturer:' . $lecturerId,
            'lecturer_id' => $lecturerId > 0 ? $lecturerId : null,
            'uploaded_by_user_id' => null,
            'code' => $code !== '' ? $code : ('GV-' . $lecturerId),
            'full_name' => $fullName !== '' ? $fullName : ('Giáº£ng viÃªn #' . $lecturerId),
            'email' => $email !== '' ? $email : null,
            'department' => [
                'id' => ! empty($payload['department_id']) ? (int) $payload['department_id'] : null,
                'code' => $payload['department_code'] ?? null,
                'name' => $payload['department_name'] ?? null,
            ],
            'faculty' => [
                'id' => ! empty($payload['faculty_id']) ? (int) $payload['faculty_id'] : null,
                'code' => $payload['faculty_code'] ?? null,
                'name' => $payload['faculty_name'] ?? null,
            ],
            'folder_name' => $this->buildReadableFolderName($label, 'giang-vien', $lecturerId > 0 ? $lecturerId : sha1($label)),
        ];
    }

    private function mapReadableExternalUploaderDescriptor(array $payload): array
    {
        $userId = isset($payload['uploaded_by_user_id']) ? (int) $payload['uploaded_by_user_id'] : 0;
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $label = $name !== '' ? $name : ('Tai-khoan-' . $userId);

        return [
            'identity_key' => 'user:' . $userId,
            'lecturer_id' => null,
            'uploaded_by_user_id' => $userId > 0 ? $userId : null,
            'code' => 'USER-' . ($userId > 0 ? $userId : 'unknown'),
            'full_name' => $label,
            'email' => $email !== '' ? $email : null,
            'department' => [
                'id' => null,
                'code' => null,
                'name' => null,
            ],
            'faculty' => [
                'id' => null,
                'code' => null,
                'name' => null,
            ],
            'folder_name' => $this->buildReadableFolderName($label, 'tai-khoan', $userId > 0 ? $userId : sha1($label)),
        ];
    }

    private function initializeReadableLecturerPayload(array $descriptor): array
    {
        return [
            'identity_key' => $descriptor['identity_key'],
            'folder_name' => $descriptor['folder_name'],
            'lecturer' => $descriptor,
            'works' => [],
        ];
    }

    private function buildReadableParticipantEntry(
        array $descriptor,
        bool $isOwner,
        ?string $memberRoleName,
        $contributionShare,
        $hoursAssigned
    ): array {
        return [
            'identity_key' => $descriptor['identity_key'],
            'lecturer_id' => $descriptor['lecturer_id'],
            'code' => $descriptor['code'],
            'full_name' => $descriptor['full_name'],
            'email' => $descriptor['email'],
            'department' => $descriptor['department'],
            'faculty' => $descriptor['faculty'],
            'is_owner' => $isOwner,
            'member_role_name' => $memberRoleName !== null ? trim((string) $memberRoleName) : null,
            'contribution_share' => $contributionShare !== null ? (float) $contributionShare : null,
            'hours_assigned' => $hoursAssigned !== null ? (float) $hoursAssigned : null,
        ];
    }

    private function attachLecturerWorkReference(
        array &$lecturer,
        array $work,
        bool $isOwner,
        ?string $memberRoleName,
        $contributionShare,
        $hoursAssigned
    ): void {
        $activityId = (int) ($work['activity']['activity_id'] ?? 0);
        if ($activityId <= 0) {
            return;
        }

        if (! isset($lecturer['works'][$activityId])) {
            $lecturer['works'][$activityId] = [
                'activity_id' => $activityId,
                'activity_code' => $work['activity']['activity_code'] ?? null,
                'title' => $work['activity']['title'] ?? null,
                'folder_name' => $work['folder_name'] ?? null,
                'participation' => [
                    'is_owner' => false,
                    'member_role_name' => null,
                    'contribution_share' => null,
                    'hours_assigned' => null,
                ],
            ];
        }

        $lecturer['works'][$activityId]['participation']['is_owner'] = (bool) (
            ($lecturer['works'][$activityId]['participation']['is_owner'] ?? false) || $isOwner
        );
        if ($memberRoleName !== null && trim($memberRoleName) !== '') {
            $lecturer['works'][$activityId]['participation']['member_role_name'] = trim($memberRoleName);
        }
        if ($contributionShare !== null) {
            $lecturer['works'][$activityId]['participation']['contribution_share'] = (float) $contributionShare;
        }
        if ($hoursAssigned !== null) {
            $lecturer['works'][$activityId]['participation']['hours_assigned'] = (float) $hoursAssigned;
        }
    }

    private function resolveEvidenceExportSource(string $disk, string $path): array
    {
        $normalizedDisk = Str::lower(trim($disk));
        $normalizedPath = trim($path);
        if ($normalizedPath === '') {
            return [
                'available' => false,
                'mode' => 'metadata_only',
                'storage' => $normalizedDisk !== '' ? $normalizedDisk : 'unknown',
                'absolute_path' => null,
                'reason' => 'missing_path',
            ];
        }

        if ($normalizedDisk === 'rclone_drive') {
            $absolutePath = $this->resolveEvidenceHotAbsolutePath($normalizedPath);
            if ($absolutePath !== null && is_file($absolutePath) && is_readable($absolutePath)) {
                return [
                    'available' => true,
                    'mode' => 'copy_from_hot_cache',
                    'storage' => 'rclone_drive',
                    'absolute_path' => $absolutePath,
                    'reason' => null,
                ];
            }

            return [
                'available' => false,
                'mode' => 'metadata_only',
                'storage' => 'rclone_drive',
                'absolute_path' => null,
                'reason' => 'hot_cache_missing',
            ];
        }

        $absolutePath = $this->resolveEvidenceLocalAbsolutePath($disk, $normalizedPath);
        if ($absolutePath !== null && is_file($absolutePath) && is_readable($absolutePath)) {
            return [
                'available' => true,
                'mode' => 'copy_from_local_disk',
                'storage' => $normalizedDisk !== '' ? $normalizedDisk : 'local',
                'absolute_path' => $absolutePath,
                'reason' => null,
            ];
        }

        return [
            'available' => false,
            'mode' => 'metadata_only',
            'storage' => $normalizedDisk !== '' ? $normalizedDisk : 'unknown',
            'absolute_path' => null,
            'reason' => 'local_file_missing',
        ];
    }

    private function resolveEvidenceHotAbsolutePath(string $coldPath): ?string
    {
        $disk = trim((string) config('evidence.storage.hot_disk', 'local'));
        if ($disk === '' || ! config("filesystems.disks.{$disk}")) {
            $disk = 'local';
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);
        if (! method_exists($adapter, 'path')) {
            return null;
        }

        return $adapter->path($this->buildEvidenceHotRelativePathFromColdPath($coldPath));
    }

    private function buildEvidenceHotRelativePathFromColdPath(string $coldPath): string
    {
        $hash = sha1(trim($coldPath));
        $baseDir = trim((string) config('evidence.storage.hot_dir', 'evidence/hot-cache'), "/\\");
        if ($baseDir === '') {
            $baseDir = 'evidence/hot-cache';
        }

        return $baseDir . '/' . substr($hash, 0, 2) . '/' . $hash . '.pdf';
    }

    private function resolveEvidenceLocalAbsolutePath(string $disk, string $path): ?string
    {
        $normalizedDisk = trim($disk);
        if ($normalizedDisk === '' || ! config("filesystems.disks.{$normalizedDisk}")) {
            return null;
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($normalizedDisk);
        if (! method_exists($adapter, 'path')) {
            return null;
        }

        return $adapter->path($path);
    }

    private function copyReadableExportFile(string $sourceAbsolutePath, string $targetAbsolutePath): int
    {
        File::ensureDirectoryExists(dirname($targetAbsolutePath));
        if (File::exists($targetAbsolutePath)) {
            File::delete($targetAbsolutePath);
        }

        if (! File::copy($sourceAbsolutePath, $targetAbsolutePath)) {
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ sao chÃ©p tá»‡p minh chá»©ng vÃ o readable export.');
        }

        return $this->resolveFileSize($targetAbsolutePath);
    }

    private function buildReadableFolderName(string $label, string $fallbackPrefix, $stableId): string
    {
        $normalized = trim($label);
        $slug = Str::slug($normalized, '-');
        if ($slug === '') {
            $slug = Str::slug($fallbackPrefix, '-');
        }
        if ($slug === '') {
            $slug = 'item';
        }

        if (strlen($slug) > 72) {
            $slug = rtrim(substr($slug, 0, 72), '-');
        }

        $suffix = trim((string) $stableId);
        if ($suffix === '') {
            return $slug;
        }

        return $slug . '-' . Str::lower($suffix);
    }

    private function buildReadableEvidenceFilename(int $evidenceId, string $originalName, string $mimeType): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $base = trim($base);
        $slug = Str::slug($base, '-');
        if ($slug === '') {
            $slug = 'minh-chung';
        }
        if (strlen($slug) > 80) {
            $slug = rtrim(substr($slug, 0, 80), '-');
        }

        $extension = Str::lower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = Str::contains(Str::lower($mimeType), 'pdf') ? 'pdf' : 'bin';
        }

        return str_pad((string) $evidenceId, 6, '0', STR_PAD_LEFT) . '_' . $slug . '.' . $extension;
    }

    private function buildRelativeExportPath(string $fromRelativePath, string $toRelativePath): ?string
    {
        $from = trim(str_replace('\\', '/', $fromRelativePath), '/');
        $to = trim(str_replace('\\', '/', $toRelativePath), '/');
        if ($from === '' || $to === '') {
            return null;
        }

        $fromParts = explode('/', $from);
        array_pop($fromParts);
        $toParts = explode('/', $to);

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $prefix = $fromParts === [] ? '' : str_repeat('../', count($fromParts));
        $suffix = implode('/', $toParts);

        return $prefix . $suffix;
    }

    private function buildExportBundle(string $exportRootAbsolutePath, string $bundleAbsolutePath): void
    {
        if (File::exists($bundleAbsolutePath)) {
            File::delete($bundleAbsolutePath);
        }

        File::ensureDirectoryExists(dirname($bundleAbsolutePath));

        $zip = new ZipArchive();
        $openResult = $zip->open($bundleAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ táº¡o gÃ³i export ZIP.');
        }

        foreach (File::allFiles($exportRootAbsolutePath) as $file) {
            $normalized = str_replace('\\', '/', $file->getRelativePathname());
            if ($normalized === self::EXPORT_BUNDLE_FILENAME || str_ends_with($normalized, '/' . self::EXPORT_BUNDLE_FILENAME)) {
                continue;
            }
            $zip->addFile($file->getPathname(), $normalized);
        }

        $zip->close();
    }

    private function gzipFile(string $sourceAbsolutePath, string $targetAbsolutePath): void
    {
        if (! File::exists($sourceAbsolutePath)) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y DB dump Ä‘á»ƒ táº¡o export.');
        }

        $input = fopen($sourceAbsolutePath, 'rb');
        if (! is_resource($input)) {
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ Ä‘á»c DB dump Ä‘á»ƒ nÃ©n export.');
        }

        $output = gzopen($targetAbsolutePath, 'wb9');
        if (! is_resource($output)) {
            fclose($input);
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ táº¡o database.sql.gz cho export.');
        }

        while (! feof($input)) {
            $chunk = fread($input, 1024 * 1024);
            if ($chunk === false) {
                gzclose($output);
                fclose($input);
                throw new BackupRuntimeException('Lá»—i Ä‘á»c DB dump khi nÃ©n export.');
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
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ mÃ£ hÃ³a JSON export.');
        }

        File::put($absolutePath, $encoded . PHP_EOL);
    }

    private function writeTextFile(string $absolutePath, string $content): void
    {
        File::put($absolutePath, rtrim($content) . PHP_EOL);
    }

    private function writeBinaryFile(string $absolutePath, string $content): void
    {
        File::put($absolutePath, $content);
    }

    private function buildExportReadme(
        string $snapshotId,
        string $folderName,
        \DateTimeInterface $generatedAt,
        array $readableExport
    ): string {
        $lines = [
            'SPNC - EXPORT SAO LUU DE DOC',
            '',
            'Thu muc nay la lop export de doc nhanh tren Google Drive.',
            'Khoi phuc chinh thuc van phai thuc hien bang chuc nang Khoi phuc cua he thong va du lieu ky thuat trong restic-repo.',
            '',
            'Thong tin nhanh:',
            '- Snapshot ID: ' . $snapshotId,
            '- Thu muc export: ' . $folderName,
            '- Thoi diem tao: ' . $generatedAt->format('Y-m-d H:i:s'),
            '- So cong trinh: ' . (string) ($readableExport['stats']['works_count'] ?? 0),
            '- So giang vien: ' . (string) ($readableExport['stats']['lecturers_count'] ?? 0),
            '- So minh chung: ' . (string) ($readableExport['stats']['total_evidence_files'] ?? 0),
            '',
            'Cau truc chinh:',
            '- database/du-lieu.sql.gz: ban xuat co so du lieu de doi chieu nhanh.',
            '- cong-trinh/: gom thong-tin-cong-trinh.pdf, thong-tin-cong-trinh.json va thu muc minh-chung/.',
            '- giang-vien/: gom tong-hop-giang-vien.pdf, tong-hop.json va cac tham chieu tong hop.',
            '- _he-thong/: tep ky thuat phuc vu doi chieu/export, khong phai lop runtime.',
        ];

        return implode(PHP_EOL, $lines);
    }

    private function syncExportToDestination(
        array $destination,
        string $localRootAbsolute,
        string $folderName
    ): array {
        if (! (bool) config('backup.exports.sync_to_drive', true)) {
            return [
                'export_path' => $localRootAbsolute,
                'drive_path' => null,
                'sync_status' => 'skipped',
                'sync_error' => null,
            ];
        }

        if (($destination['target_type'] ?? null) === 'rclone') {
            $targetRoot = rtrim((string) ($destination['target_root'] ?? ''), '/');
            if ($targetRoot === '') {
                throw new BackupRuntimeException('KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c Ä‘Ã­ch rclone cho export.');
            }

            $stagingRoot = $targetRoot . '/' . self::EXPORT_STAGING_DIR;
            $stagingDir = $stagingRoot . '/' . $folderName;
            $remoteDir = $targetRoot . '/' . $folderName;
            $this->runRclone(['purge', $stagingDir], true, 1800);
            $this->runRclone([
                'sync',
                $localRootAbsolute,
                $stagingDir,
                '--create-empty-src-dirs',
                '--fast-list',
            ], false, 3600);
            $this->runRclone(['purge', $remoteDir], true, 1800);
            $this->runRclone(['moveto', $stagingDir, $remoteDir, '--fast-list'], false, 1800);

            return [
                'export_path' => $remoteDir,
                'drive_path' => 'rclone:' . $remoteDir,
                'sync_status' => 'success',
                'sync_error' => null,
            ];
        }

        if (($destination['target_type'] ?? null) === 'local') {
            $targetRoot = (string) ($destination['target_root'] ?? '');
            if ($targetRoot === '') {
                throw new BackupRuntimeException('KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c thÆ° má»¥c local Ä‘Ã­ch cho export.');
            }

            $stagingRoot = rtrim($targetRoot, '/\\') . DIRECTORY_SEPARATOR . self::EXPORT_STAGING_DIR;
            $stagingDir = $stagingRoot . DIRECTORY_SEPARATOR . $folderName;
            $targetDir = rtrim($targetRoot, '/\\') . DIRECTORY_SEPARATOR . $folderName;
            if (File::exists($stagingDir)) {
                File::deleteDirectory($stagingDir);
            }
            File::ensureDirectoryExists(dirname($stagingDir));
            File::copyDirectory($localRootAbsolute, $stagingDir);
            if (File::exists($targetDir)) {
                File::deleteDirectory($targetDir);
            }
            File::ensureDirectoryExists(dirname($targetDir));
            if (! File::moveDirectory($stagingDir, $targetDir, true)) {
                throw new BackupRuntimeException('Khï¿½ng th? cï¿½ng b? export local t? thu m?c staging.');
            }

            return [
                'export_path' => str_replace('\\', '/', $targetDir),
                'drive_path' => str_replace('\\', '/', $targetDir),
                'sync_status' => 'success',
                'sync_error' => null,
            ];
        }

        return [
            'export_path' => $localRootAbsolute,
            'drive_path' => null,
            'sync_status' => 'local_only',
            'sync_error' => null,
        ];
    }

    private function runRclone(array $args, bool $allowFailure = false, int $timeout = 600): array
    {
        $rcloneBinary = trim((string) config('backup.restic.rclone_binary', 'rclone'));
        if ($rcloneBinary === '') {
            throw new BackupRuntimeException('Thiáº¿u cáº¥u hÃ¬nh rclone binary Ä‘á»ƒ Ä‘á»“ng bá»™ export.');
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
        $repositoryType = str_starts_with(Str::lower($repository), 'rclone:')
            ? 'rclone'
            : ($repository !== '' ? 'local' : 'unknown');

        if ($configuredTarget !== '') {
            if (str_starts_with(Str::lower($configuredTarget), 'rclone:')) {
                $raw = trim((string) Str::after($configuredTarget, 'rclone:'));
                if ($raw === '') {
                    return [
                        'valid' => false,
                        'repository_type' => $repositoryType,
                        'target_type' => 'rclone',
                        'target_root' => null,
                        'display_root' => null,
                        'error_code' => 'EXPORT_TARGET_INVALID',
                        'error_message' => 'SPNC_BACKUP_EXPORT_TARGET Ä‘ang Ä‘á»ƒ dáº¡ng rclone nhÆ°ng thiáº¿u remote/path.',
                    ];
                }

                return [
                    'valid' => true,
                    'repository_type' => $repositoryType,
                    'target_type' => 'rclone',
                    'target_root' => $raw,
                    'display_root' => 'rclone:' . $raw,
                    'error_code' => null,
                    'error_message' => null,
                ];
            }

            $absolute = $this->resolveAbsolutePath($configuredTarget);
            return [
                'valid' => true,
                'repository_type' => $repositoryType,
                'target_type' => 'local',
                'target_root' => $absolute,
                'display_root' => str_replace('\\', '/', $absolute),
                'error_code' => null,
                'error_message' => null,
            ];
        }

        if ($repository === '') {
            return [
                'valid' => false,
                'repository_type' => 'unknown',
                'target_type' => null,
                'target_root' => null,
                'display_root' => null,
                'error_code' => 'BACKUP_REPOSITORY_MISSING',
                'error_message' => 'Thiáº¿u SPNC_BACKUP_REPOSITORY nÃªn chÆ°a thá»ƒ suy ra thÆ° má»¥c exports.',
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
                    'valid' => true,
                    'repository_type' => 'rclone',
                    'target_type' => 'rclone',
                    'target_root' => $root,
                    'display_root' => 'rclone:' . $root,
                    'error_code' => null,
                    'error_message' => null,
                ];
            }

            return [
                'valid' => false,
                'repository_type' => 'rclone',
                'target_type' => null,
                'target_root' => null,
                'display_root' => null,
                'error_code' => 'RCLONE_REMOTE_INVALID',
                'error_message' => 'SPNC_BACKUP_REPOSITORY Ä‘ang á»Ÿ dáº¡ng rclone nhÆ°ng khÃ´ng chá»©a remote há»£p lá»‡.',
            ];
        }

        $resolvedRepo = $this->resolveAbsolutePath($repository);
        $repoParent = dirname(rtrim($resolvedRepo, '/\\'));
        $targetRoot = rtrim($repoParent, '/\\') . DIRECTORY_SEPARATOR . $folderName;

        return [
            'valid' => true,
            'repository_type' => 'local',
            'target_type' => 'local',
            'target_root' => $targetRoot,
            'display_root' => str_replace('\\', '/', $targetRoot),
            'error_code' => null,
            'error_message' => null,
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

        $recovered = $this->recoverExportMetadataFromLocalSnapshot($snapshotId);
        if (is_array($recovered)) {
            $this->storeExportMetadata($snapshotId, $recovered);

            return $recovered;
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
                // KhÃ´ng cháº·n thao tÃ¡c forget náº¿u dá»n export remote tháº¥t báº¡i.
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
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ ghi chá»‰ má»¥c export backup.');
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

    private function recoverExportMetadataFromLocalSnapshot(string $snapshotId): ?array
    {
        $localRootRelative = $this->exportRelativePath($snapshotId);
        $localRootAbsolute = $this->basePathFromRelative($localRootRelative);
        if (! File::isDirectory($localRootAbsolute)) {
            return null;
        }

        $overviewAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_OVERVIEW_FILENAME;
        $readmeAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_README_FILENAME;
        $dbDumpAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, self::EXPORT_DATABASE_DIR . '/' . self::EXPORT_DB_DUMP_FILENAME);
        $manifestAbsolute = $localRootAbsolute . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, self::EXPORT_SYSTEM_DIR . '/' . self::EXPORT_MANIFEST_FILENAME);

        if (! File::exists($overviewAbsolute) || ! File::exists($readmeAbsolute) || ! File::exists($dbDumpAbsolute)) {
            return null;
        }

        $overview = json_decode((string) File::get($overviewAbsolute), true);
        if (! is_array($overview)) {
            return null;
        }

        $folderName = trim((string) ($overview['folder_name'] ?? ''));
        if ($folderName === '') {
            $folderName = basename($localRootAbsolute);
        }

        $stats = [
            'database_dump_gzip_bytes' => $this->resolveFileSize($dbDumpAbsolute),
            'readable_exports' => [
                'works_count' => (int) ($overview['totals']['cong_trinh'] ?? 0),
                'lecturers_count' => (int) ($overview['totals']['giang_vien'] ?? 0),
                'total_evidence_files' => (int) ($overview['totals']['minh_chung'] ?? 0),
                'copied_evidence_files' => (int) ($overview['totals']['minh_chung_da_sao_chep'] ?? 0),
                'metadata_only_evidence_files' => (int) ($overview['totals']['minh_chung_chi_co_metadata'] ?? 0),
                'copied_evidence_bytes' => null,
            ],
        ];

        return [
            'available' => true,
            'snapshot_id' => $snapshotId,
            'folder_name' => $folderName,
            'export_path' => str_replace('\\', '/', $localRootAbsolute),
            'drive_path' => null,
            'remote_bundle_path' => null,
            'bundle_filename' => self::EXPORT_BUNDLE_FILENAME,
            'local_root_relative_path' => $localRootRelative,
            'local_bundle_relative_path' => null,
            'generated_at' => (string) ($overview['created_at'] ?? null),
            'artifacts' => array_values(array_filter([
                self::EXPORT_README_FILENAME,
                self::EXPORT_OVERVIEW_FILENAME,
                self::EXPORT_DATABASE_DIR . '/' . self::EXPORT_DB_DUMP_FILENAME,
                File::isDirectory($localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_WORKS_DIR) ? self::EXPORT_WORKS_DIR . '/' : null,
                File::isDirectory($localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_LECTURERS_DIR) ? self::EXPORT_LECTURERS_DIR . '/' : null,
                File::exists($manifestAbsolute) ? self::EXPORT_SYSTEM_DIR . '/' . self::EXPORT_MANIFEST_FILENAME : null,
            ])),
            'visible_artifacts' => array_values(array_filter([
                self::EXPORT_README_FILENAME,
                self::EXPORT_OVERVIEW_FILENAME,
                self::EXPORT_DATABASE_DIR . '/' . self::EXPORT_DB_DUMP_FILENAME,
                File::isDirectory($localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_WORKS_DIR) ? self::EXPORT_WORKS_DIR . '/' : null,
                File::isDirectory($localRootAbsolute . DIRECTORY_SEPARATOR . self::EXPORT_LECTURERS_DIR) ? self::EXPORT_LECTURERS_DIR . '/' : null,
            ])),
            'technical_artifacts' => array_values(array_filter([
                File::exists($manifestAbsolute) ? self::EXPORT_SYSTEM_DIR . '/' . self::EXPORT_MANIFEST_FILENAME : null,
            ])),
            'stats' => $stats,
            'sync_status' => 'recovered_local',
            'sync_error' => null,
            'recovered_from_local' => true,
        ];
    }

    private function snapshotPatchFromExportMetadata(array $metadata): array
    {
        return [
            'export_available' => (bool) ($metadata['available'] ?? false),
            'export_path' => $metadata['export_path'] ?? null,
            'export_drive_path' => $metadata['drive_path'] ?? null,
            'export_generated_at' => $metadata['generated_at'] ?? null,
            'export_bundle_filename' => $metadata['bundle_filename'] ?? null,
            'export_artifacts' => array_values((array) ($metadata['artifacts'] ?? [])),
            'export_sync_status' => $metadata['sync_status'] ?? null,
            'export_sync_error' => $metadata['sync_error'] ?? null,
        ];
    }

    private function reportPostProcessProgress(string $runId, string $step, string $message): void
    {
        $runId = trim($runId);
        if ($runId === '' || ! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $runId)) {
            return;
        }

        try {
            $stateStore = app(BackupRunStateStore::class);
            $stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'backup_postprocess',
                'step' => $step,
                'message' => $message,
            ]);
            $stateStore->appendLog($runId, $message);
        } catch (\Throwable) {
            // KhÃ´ng Ä‘á»ƒ lá»—i ghi tráº¡ng thÃ¡i phá»¥ lÃ m há»ng tiáº¿n trÃ¬nh export chÃ­nh.
        }
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
            throw new BackupRuntimeException('KhÃ´ng cÃ³ dá»¯ liá»‡u phÃ¹ há»£p vá»›i pháº¡m vi khÃ´i phá»¥c Ä‘Ã£ chá»n.');
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
            throw new BackupRuntimeException('ÄÆ°á»ng dáº«n tá»‡p trong snapshot khÃ´ng há»£p lá»‡.');
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
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y tá»‡p sau khi giáº£i snapshot.');
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
            return "T\u{1EF1} \u{0111}\u{1ED9}ng: l\u{1ECB}ch c\u{1ED1} \u{0111}\u{1ECB}nh theo c\u{1EA5}u h\u{00EC}nh h\u{1EC7} th\u{1ED1}ng.";
        }

        $dayPart = count($labels) === 1
            ? $labels[0]
            : (implode(', ', array_slice($labels, 0, -1)) . " v\u{00E0} " . $labels[count($labels) - 1]);

        return $timePart !== null
            ? "T\u{1EF1} \u{0111}\u{1ED9}ng: {$dayPart} l\u{00FA}c {$timePart}"
            : "T\u{1EF1} \u{0111}\u{1ED9}ng: {$dayPart}";
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
            0 => "Ch\u{1EE7} nh\u{1EAD}t",
            1 => "Th\u{1EE9} 2",
            2 => "Th\u{1EE9} 3",
            3 => "Th\u{1EE9} 4",
            4 => "Th\u{1EE9} 5",
            5 => "Th\u{1EE9} 6",
            6 => "Th\u{1EE9} 7",
        ];

        return $map[$weekday] ?? "Th\u{1EE9} 2";
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
        $rcloneConfig = $this->resolveRuntimeRcloneConfigInfo();
        $serviceAccountFile = $this->resolveRuntimeRcloneServiceAccountInfo();
        $driveImpersonate = trim((string) config('backup.restic.rclone_drive_impersonate', ''));

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
            'rclone_config' => $rcloneConfig,
            'rclone_service_account_file' => $serviceAccountFile,
            'rclone_drive_impersonate' => $driveImpersonate !== '' ? $driveImpersonate : null,
            'rclone_remote' => $this->inspectConfiguredRcloneRemote(
                $repository,
                (string) ($rcloneConfig['resolved'] ?? ''),
                (string) ($serviceAccountFile['resolved'] ?? '')
            ),
            'database' => $this->inspectBackupDatabaseConfig(),
            'process_env' => [
                'RCLONE_CONFIG' => $this->inspectOptionalPath((string) ($resticEnv['RCLONE_CONFIG'] ?? '')),
                'RCLONE_DRIVE_SERVICE_ACCOUNT_FILE' => $this->inspectOptionalPath(
                    (string) ($resticEnv['RCLONE_DRIVE_SERVICE_ACCOUNT_FILE'] ?? '')
                ),
                'RCLONE_DRIVE_IMPERSONATE' => trim((string) ($resticEnv['RCLONE_DRIVE_IMPERSONATE'] ?? '')) !== ''
                    ? (string) $resticEnv['RCLONE_DRIVE_IMPERSONATE']
                    : null,
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
        $rcloneProbeTarget = $this->buildRcloneRepositoryProbeTarget($repository);
        $rcloneProgram = $this->resolveRcloneProgram();

        $tests = [
            'snapshot_limit_requested' => $snapshotLimit,
            'rclone_version' => $this->doctorRunCommand([$rcloneBinary, 'version'], [], 20),
            'restic_repository_config' => $this->doctorRunCommand(
                array_merge(
                    [(string) config('backup.restic.binary', 'restic')],
                    $rcloneProgram !== null
                        ? ['-o', 'rclone.program=' . $rcloneProgram]
                        : [],
                    ['cat', 'config']
                ),
                $this->resticEnv(),
                $this->repositoryOpenTimeoutSeconds()
            ),
            'restic_snapshots' => $this->doctorRunCommand(
                array_merge(
                    [(string) config('backup.restic.binary', 'restic')],
                    $rcloneProgram !== null
                        ? ['-o', 'rclone.program=' . $rcloneProgram]
                        : [],
                    ['snapshots', '--json', '--tag', 'spnc_backup']
                ),
                $this->resticEnv(),
                $this->snapshotListingTimeoutSeconds()
            ),
        ];

        if ($rcloneProbeTarget !== null) {
            $tests['rclone_lsd'] = $this->doctorRunCommand(
                [$rcloneBinary, 'lsd', $rcloneProbeTarget],
                $this->resticEnv(),
                $this->driveProbeTimeoutSeconds()
            );
        } else {
            $tests['rclone_lsd'] = [
                'ok' => false,
                'skipped' => true,
                'reason' => 'Repository khÃ´ng dÃ¹ng backend rclone hoáº·c khÃ´ng Ä‘á»c Ä‘Æ°á»£c remote.',
            ];
        }

        return $tests;
    }

    private function buildRepositoryAccessFailureMessage(string $repository, array $resticProbe): string
    {
        $resticMessage = trim((string) ($resticProbe['stderr'] ?? ''));
        if ($resticMessage === '') {
            $resticMessage = trim((string) ($resticProbe['stdout'] ?? ''));
        }

        if (
            ! str_starts_with(Str::lower($repository), 'rclone:')
            || ! Str::contains(Str::lower($resticMessage), [
                'error talking http to rclone',
                'unable to open repository at rclone:',
            ])
        ) {
            return $resticMessage !== '' ? $resticMessage : 'KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c chi tiáº¿t lá»—i repository.';
        }

        $probeTarget = $this->buildRcloneRepositoryProbeTarget($repository);
        if ($probeTarget === null) {
            return $resticMessage !== '' ? $resticMessage : 'KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c remote rclone.';
        }

        $diagnostics = $this->probeRepositoryVisibility($repository);
        Log::warning('backup.repository_visibility_probe', $diagnostics);

        if ((bool) ($diagnostics['repository_contents_probe']['successful'] ?? false)) {
            return $resticMessage !== '' ? $resticMessage : 'Repository backup da visible qua rclone, nhung restic van mo rat cham hoac khong doc duoc config.';
        }

        if ((bool) ($diagnostics['repository_folder_probe']['successful'] ?? false)) {
            return 'Repository folder da visible tren Google Drive, nhung khong doc duoc noi dung repository can thiet (config/data/index/keys/locks/snapshots).';
        }

        if ((bool) ($diagnostics['repository_parent_probe']['successful'] ?? false)) {
            return 'Da truy cap duoc thu muc cha cua repository tren Google Drive, nhung chua thay duoc thu muc repository mong doi. Hay kiem tra lai path repository.';
        }

        if ((bool) ($diagnostics['remote_root_probe']['successful'] ?? false)) {
            return 'Da truy cap duoc Google Drive root da cau hinh, nhung khong doc duoc thu muc cha cua repository backup.';
        }

        $rcloneProbe = $this->runRclone(['lsd', $probeTarget], true, 30);
        if ((bool) ($rcloneProbe['successful'] ?? false)) {
            return $resticMessage !== '' ? $resticMessage : 'Restic khÃ´ng Ä‘á»c Ä‘Æ°á»£c repository qua rclone.';
        }

        $rcloneMessage = trim((string) ($rcloneProbe['stderr'] ?? ''));
        if ($rcloneMessage === '') {
            $rcloneMessage = trim((string) ($rcloneProbe['stdout'] ?? ''));
        }

        return $rcloneMessage !== '' ? $rcloneMessage : ($resticMessage !== '' ? $resticMessage : 'KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c lá»—i rclone.');
    }

    private function doctorRunCommand(array $command, array $env = [], int $timeout = 60): array
    {
        $startedAt = microtime(true);
        try {
            $result = $this->runProcess($command, $env, true, $timeout);
            return $this->formatDoctorProcessResult($result, $startedAt);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'exit_code' => null,
                'command' => implode(' ', array_map(static fn ($part): string => (string) $part, $command)),
                'stdout_preview' => null,
                'stderr_preview' => null,
                'duration_seconds' => round(max(0, microtime(true) - $startedAt), 2),
                'exception' => $exception->getMessage(),
            ];
        }
    }

    private function formatDoctorProcessResult(array $result, float $startedAt): array
    {
        return [
            'ok' => (bool) ($result['successful'] ?? false),
            'exit_code' => $result['exit_code'] ?? null,
            'command' => (string) ($result['command'] ?? ''),
            'stdout_preview' => $this->trimDoctorOutput((string) ($result['stdout'] ?? ''), 12, 3000),
            'stderr_preview' => $this->trimDoctorOutput((string) ($result['stderr'] ?? ''), 12, 3000),
            'duration_seconds' => round(max(0, microtime(true) - $startedAt), 2),
        ];
    }

    private function buildRcloneRepositoryTarget(string $repository): ?string
    {
        $repository = trim($repository);
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            return null;
        }

        $target = trim((string) Str::after($repository, 'rclone:'));

        return $target !== '' ? $target : null;
    }

    private function repositoryOpenStateCacheKey(string $repository): string
    {
        return 'spnc:backup:repository-open:' . sha1(Str::lower(trim($repository)));
    }

    private function probeRepositoryVisibility(string $repository): array
    {
        $rootTarget = $this->parseRcloneRemoteRoot($repository);
        $parentTarget = $this->buildRcloneRepositoryProbeTarget($repository);
        $repositoryTarget = $this->buildRcloneRepositoryTarget($repository);

        $remoteRootProbe = $rootTarget !== null
            ? $this->runRclone(['lsd', $rootTarget], true, $this->driveProbeTimeoutSeconds())
            : ['successful' => false, 'stdout' => '', 'stderr' => 'Unable to resolve remote root target.'];

        $repositoryParentProbe = $parentTarget !== null
            ? $this->runRclone(['lsd', $parentTarget], true, min(60, $this->repositoryOpenTimeoutSeconds()))
            : ['successful' => false, 'stdout' => '', 'stderr' => 'Unable to resolve repository parent target.'];

        $repositoryFolderProbe = $repositoryTarget !== null
            ? $this->runRclone(['lsd', $repositoryTarget], true, min(60, $this->repositoryOpenTimeoutSeconds()))
            : ['successful' => false, 'stdout' => '', 'stderr' => 'Unable to resolve repository target.'];

        $repositoryContentsProbe = $repositoryTarget !== null
            ? $this->runRclone(['lsf', $repositoryTarget, '--max-depth', '1'], true, min(60, $this->repositoryOpenTimeoutSeconds()))
            : ['successful' => false, 'stdout' => '', 'stderr' => 'Unable to resolve repository target.'];

        return [
            'repository' => $repository,
            'repository_root_target' => $rootTarget,
            'repository_parent_target' => $parentTarget,
            'repository_target' => $repositoryTarget,
            'remote_root_probe' => $this->summarizeRepositoryProbe($remoteRootProbe),
            'repository_parent_probe' => $this->summarizeRepositoryProbe($repositoryParentProbe),
            'repository_folder_probe' => $this->summarizeRepositoryProbe($repositoryFolderProbe),
            'repository_contents_probe' => $this->summarizeRepositoryProbe($repositoryContentsProbe),
        ];
    }

    private function summarizeRepositoryProbe(array $probe): array
    {
        $stdout = trim((string) ($probe['stdout'] ?? ''));
        $stderr = trim((string) ($probe['stderr'] ?? ''));
        $entries = preg_split('/\r\n|\r|\n/', $stdout) ?: [];
        $entries = array_values(array_filter(array_map(
            static fn ($entry): string => trim((string) $entry),
            $entries
        )));

        return [
            'successful' => (bool) ($probe['successful'] ?? false),
            'exit_code' => $probe['exit_code'] ?? null,
            'command' => $probe['command'] ?? null,
            'stdout_preview' => $stdout !== '' ? implode(PHP_EOL, array_slice($entries, 0, 12)) : null,
            'stderr' => $stderr !== '' ? $stderr : null,
            'entries_preview' => array_slice($entries, 0, 12),
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
                'Lá»‡nh backup tháº¥t báº¡i: ' . trim((string) $result['stderr'])
            );
        }

        return $result;
    }

    private function resticEnv(): array
    {
        $runtimeRcloneConfig = $this->resolveRuntimeRcloneConfigInfo();
        $runtimeServiceAccountFile = $this->resolveRuntimeRcloneServiceAccountInfo();
        $env = [
            'RESTIC_REPOSITORY' => (string) config('backup.restic.repository', ''),
            'RESTIC_PASSWORD' => (string) config('backup.restic.password', ''),
            'HTTP_PROXY' => '',
            'http_proxy' => '',
            'HTTPS_PROXY' => '',
            'https_proxy' => '',
            'ALL_PROXY' => '',
            'all_proxy' => '',
            'NO_PROXY' => '',
            'no_proxy' => '',
        ];

        $rcloneConfig = trim((string) ($runtimeRcloneConfig['resolved'] ?? ''));
        if ($rcloneConfig !== '') {
            $env['RCLONE_CONFIG'] = $rcloneConfig;
        }

        $serviceAccountFile = trim((string) ($runtimeServiceAccountFile['resolved'] ?? ''));
        if ($serviceAccountFile !== '') {
            $env['RCLONE_DRIVE_SERVICE_ACCOUNT_FILE'] = $serviceAccountFile;
        }

        $driveImpersonate = trim((string) config('backup.restic.rclone_drive_impersonate', ''));
        if ($driveImpersonate !== '') {
            $env['RCLONE_DRIVE_IMPERSONATE'] = $driveImpersonate;
        }

        $httpProxy = trim((string) config('backup.network.http_proxy', ''));
        $httpsProxy = trim((string) config('backup.network.https_proxy', ''));
        $noProxy = trim((string) config('backup.network.no_proxy', ''));
        if (($httpProxy !== '' || $httpsProxy !== '') && $noProxy === '') {
            $noProxy = 'localhost,127.0.0.1,::1';
        }

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

    private function inspectBackupDatabaseConfig(): array
    {
        $configuredConnection = trim((string) config('backup.database.connection', config('database.default', 'mysql')));
        $selected = $configuredConnection !== '' ? $configuredConnection : (string) config('database.default', 'mysql');
        $connection = config("database.connections.{$selected}");
        $driver = is_array($connection) ? Str::lower((string) ($connection['driver'] ?? $selected)) : null;

        return [
            'connection' => $selected !== '' ? $selected : null,
            'driver' => $driver,
            'mysql_dump_binary' => $this->inspectBinaryConfig((string) config('backup.mysql.mysqldump_binary', 'mysqldump')),
            'mysql_restore_binary' => $this->inspectBinaryConfig((string) config('backup.mysql.mysql_binary', 'mysql')),
            'pgsql_dump_binary' => $this->inspectBinaryConfig((string) config('backup.pgsql.pg_dump_binary', 'pg_dump')),
            'pgsql_restore_binary' => $this->inspectBinaryConfig((string) config('backup.pgsql.psql_binary', 'psql')),
        ];
    }

    private function resolveBackupDatabaseConnection(): array
    {
        $configuredConnection = trim((string) config('backup.database.connection', config('database.default', 'mysql')));
        $connectionName = $configuredConnection !== '' ? $configuredConnection : (string) config('database.default', 'mysql');
        $connection = config("database.connections.{$connectionName}");
        if (! is_array($connection)) {
            throw new BackupRuntimeException('KhÃ´ng tÃ¬m tháº¥y cáº¥u hÃ¬nh database cho backup: ' . $connectionName);
        }

        return [
            'name' => $connectionName,
            'driver' => Str::lower((string) ($connection['driver'] ?? $connectionName)),
            'config' => $connection,
        ];
    }

    private function dumpMySqlDatabase(array $mysql, string $outputAbsolutePath): void
    {
        $database = (string) ($mysql['database'] ?? '');
        $username = (string) ($mysql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiáº¿u cáº¥u hÃ¬nh database Ä‘á»ƒ táº¡o dump backup.');
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
            throw new BackupRuntimeException('Táº¡o DB dump tháº¥t báº¡i: ' . trim((string) $result['stderr']));
        }
    }

    private function dumpPgSqlDatabase(array $pgsql, string $outputAbsolutePath): void
    {
        $database = (string) ($pgsql['database'] ?? '');
        $username = (string) ($pgsql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiáº¿u cáº¥u hÃ¬nh PostgreSQL Ä‘á»ƒ táº¡o dump backup.');
        }

        $command = [
            (string) config('backup.pgsql.pg_dump_binary', 'pg_dump'),
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--encoding=UTF8',
            '--file=' . $outputAbsolutePath,
            '--host=' . (string) ($pgsql['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($pgsql['port'] ?? 5432),
            '--username=' . $username,
            $database,
        ];

        $env = [];
        $password = (string) ($pgsql['password'] ?? '');
        if ($password !== '') {
            $env['PGPASSWORD'] = $password;
        }

        $sslMode = trim((string) ($pgsql['sslmode'] ?? ''));
        if ($sslMode !== '') {
            $env['PGSSLMODE'] = $sslMode;
        }

        $result = $this->runProcess($command, $env, false, 1800);
        if (! $result['successful']) {
            throw new BackupRuntimeException('Táº¡o PostgreSQL dump tháº¥t báº¡i: ' . trim((string) $result['stderr']));
        }
    }

    private function importMySqlDatabaseDump(array $mysql, string $dumpAbsolutePath): void
    {
        $database = (string) ($mysql['database'] ?? '');
        $username = (string) ($mysql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiáº¿u cáº¥u hÃ¬nh database Ä‘á»ƒ import báº£n sao lÆ°u.');
        }

        $blockedDatabases = ['mysql', 'information_schema', 'performance_schema', 'sys'];
        if (in_array(Str::lower($database), $blockedDatabases, true)) {
            throw new BackupRuntimeException('Tá»« chá»‘i import vÃ o database há»‡ thá»‘ng khÃ´ng an toÃ n.');
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
            throw new BackupRuntimeException('KhÃ´ng thá»ƒ Ä‘á»c tá»‡p DB dump Ä‘á»ƒ import.');
        }
        $process->setInput($stream);
        $process->run();
        fclose($stream);

        if (! $process->isSuccessful()) {
            throw new BackupRuntimeException('KhÃ´i phá»¥c database tháº¥t báº¡i: ' . trim((string) $process->getErrorOutput()));
        }
    }

    private function importPgSqlDatabaseDump(array $pgsql, string $dumpAbsolutePath): void
    {
        $database = (string) ($pgsql['database'] ?? '');
        $username = (string) ($pgsql['username'] ?? '');
        if ($database === '' || $username === '') {
            throw new BackupRuntimeException('Thiáº¿u cáº¥u hÃ¬nh PostgreSQL Ä‘á»ƒ import báº£n sao lÆ°u.');
        }

        $blockedDatabases = ['postgres', 'template0', 'template1'];
        if (in_array(Str::lower($database), $blockedDatabases, true)) {
            throw new BackupRuntimeException('Tá»« chá»‘i import vÃ o PostgreSQL system database khÃ´ng an toÃ n.');
        }

        $command = [
            (string) config('backup.pgsql.psql_binary', 'psql'),
            '--host=' . (string) ($pgsql['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($pgsql['port'] ?? 5432),
            '--username=' . $username,
            '--dbname=' . $database,
            '--set',
            'ON_ERROR_STOP=1',
            '--file=' . $dumpAbsolutePath,
        ];

        $env = [];
        $password = (string) ($pgsql['password'] ?? '');
        if ($password !== '') {
            $env['PGPASSWORD'] = $password;
        }

        $sslMode = trim((string) ($pgsql['sslmode'] ?? ''));
        if ($sslMode !== '') {
            $env['PGSSLMODE'] = $sslMode;
        }

        $result = $this->runProcess($command, $env, false, 3600);
        if (! $result['successful']) {
            throw new BackupRuntimeException('KhÃ´i phá»¥c PostgreSQL tháº¥t báº¡i: ' . trim((string) $result['stderr']));
        }
    }

    private function inspectConfiguredRcloneRemote(string $repository, string $rcloneConfigPath, string $serviceAccountFile): array
    {
        $remoteName = $this->extractRcloneRemoteName($repository);
        $inspection = [
            'name' => $remoteName,
            'defined' => false,
            'type' => null,
            'root_folder_id' => null,
            'auth_mode' => null,
            'refresh_token_present' => null,
            'has_token' => false,
            'has_client_id' => false,
            'has_client_secret' => false,
            'has_team_drive' => false,
            'has_section_service_account_file' => false,
            'has_stale_auth_fields' => false,
            'uses_service_account_env' => trim($serviceAccountFile) !== '',
        ];

        if ($remoteName === null) {
            return $inspection;
        }

        $resolvedConfigPath = trim($rcloneConfigPath) !== '' ? $this->resolveAbsolutePath($rcloneConfigPath) : '';
        if ($resolvedConfigPath === '' || ! is_file($resolvedConfigPath) || ! is_readable($resolvedConfigPath)) {
            return $inspection;
        }

        try {
            $parsed = parse_ini_file($resolvedConfigPath, true, INI_SCANNER_RAW);
        } catch (\Throwable) {
            return $inspection;
        }

        if (! is_array($parsed) || ! isset($parsed[$remoteName]) || ! is_array($parsed[$remoteName])) {
            return $inspection;
        }

        $section = $parsed[$remoteName];
        $tokenRaw = trim((string) ($section['token'] ?? ''));
        $clientId = trim((string) ($section['client_id'] ?? ''));
        $clientSecret = trim((string) ($section['client_secret'] ?? ''));
        $teamDrive = trim((string) ($section['team_drive'] ?? ''));
        $sectionServiceAccount = trim((string) ($section['service_account_file'] ?? ''));
        $authMode = null;
        $refreshTokenPresent = null;
        $hasToken = $tokenRaw !== '';
        $hasClientId = $clientId !== '';
        $hasClientSecret = $clientSecret !== '';
        $hasTeamDrive = $teamDrive !== '';
        $hasSectionServiceAccountFile = $sectionServiceAccount !== '';

        $usesServiceAccountEnv = trim($serviceAccountFile) !== '';
        $usesServiceAccountSection = $sectionServiceAccount !== '';
        $usesOauthFields = $tokenRaw !== '';
        $hasConflictingAuthFields = ($usesServiceAccountEnv || $usesServiceAccountSection) && $usesOauthFields;

        if ($usesServiceAccountEnv || $usesServiceAccountSection) {
            $authMode = 'service_account';
        } elseif ($usesOauthFields) {
            $authMode = 'oauth_token';
            $decoded = json_decode($tokenRaw, true);
            if (is_array($decoded)) {
                $refreshTokenPresent = array_key_exists('refresh_token', $decoded)
                    && trim((string) ($decoded['refresh_token'] ?? '')) !== '';
            }
        }

        return [
            'name' => $remoteName,
            'defined' => true,
            'type' => trim((string) ($section['type'] ?? '')) !== '' ? trim((string) $section['type']) : null,
            'root_folder_id' => trim((string) ($section['root_folder_id'] ?? '')) !== ''
                ? trim((string) ($section['root_folder_id']))
                : null,
            'auth_mode' => $authMode,
            'refresh_token_present' => $refreshTokenPresent,
            'has_token' => $hasToken,
            'has_client_id' => $hasClientId,
            'has_client_secret' => $hasClientSecret,
            'has_team_drive' => $hasTeamDrive,
            'has_section_service_account_file' => $hasSectionServiceAccountFile,
            'has_stale_auth_fields' => $hasConflictingAuthFields,
            'has_conflicting_auth_fields' => $hasConflictingAuthFields,
            'uses_service_account_env' => $usesServiceAccountEnv,
        ];
    }
    private function extractRcloneRemoteName(string $repository): ?string
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

        return $remoteName !== '' ? $remoteName : null;
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
            $found = $finder->find($configured, null, $searchDirs ?: []);
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

    private function resolveRuntimeRcloneConfigInfo(): array
    {
        return $this->resolveRuntimeConfigAsset(
            (string) config('backup.restic.rclone_config_path', ''),
            (string) config('backup.restic.rclone_config_base64', ''),
            'storage/app/runtime-config/backup/rclone.conf',
            'rclone.conf'
        );
    }

    private function resolveRuntimeRcloneServiceAccountInfo(): array
    {
        return $this->resolveRuntimeConfigAsset(
            (string) config('backup.restic.rclone_service_account_file', ''),
            (string) config('backup.restic.rclone_service_account_json_base64', ''),
            'storage/app/runtime-config/backup/google-service-account.json',
            'Google service account JSON'
        );
    }

    private function resolveRuntimeConfigAsset(
        string $configuredPath,
        string $inlineBase64,
        string $materializedRelativePath,
        string $label
    ): array {
        $configured = trim($configuredPath);
        $inlineBase64 = trim($inlineBase64);
        $resolved = null;
        $source = null;
        $error = null;
        $preferBase64 = app()->environment('production') && $inlineBase64 !== '';

        if ($preferBase64) {
            $materialized = $this->materializeRuntimeConfigAsset($inlineBase64, $materializedRelativePath, $label);
            if ($materialized['resolved'] !== null) {
                return [
                    'configured' => $configured !== '' ? $configured : null,
                    'resolved' => $materialized['resolved'],
                    'exists' => true,
                    'readable' => true,
                    'source' => 'base64',
                    'materialized' => true,
                    'error' => null,
                ];
            }

            $error = $materialized['error'];
            $source = 'base64';
        }

        if ($configured !== '') {
            $resolved = $this->resolveAbsolutePath($configured);
            $source = 'path';
            if (is_file($resolved) && is_readable($resolved)) {
                return [
                    'configured' => $configured,
                    'resolved' => $resolved,
                    'exists' => true,
                    'readable' => true,
                    'source' => $source,
                    'materialized' => false,
                    'error' => null,
                ];
            }
        }

        if (! $preferBase64 && $inlineBase64 !== '') {
            $materialized = $this->materializeRuntimeConfigAsset($inlineBase64, $materializedRelativePath, $label);
            if ($materialized['resolved'] !== null) {
                return [
                    'configured' => $configured !== '' ? $configured : null,
                    'resolved' => $materialized['resolved'],
                    'exists' => true,
                    'readable' => true,
                    'source' => 'base64',
                    'materialized' => true,
                    'error' => null,
                ];
            }

            $error = $materialized['error'];
            $source = 'base64';
        } elseif ($configured !== '') {
            $error = "{$label} khÃ´ng tá»“n táº¡i hoáº·c khÃ´ng Ä‘á»c Ä‘Æ°á»£c táº¡i Ä‘Æ°á»ng dáº«n runtime hiá»‡n táº¡i.";
        }

        return [
            'configured' => $configured !== '' ? $configured : null,
            'resolved' => $resolved,
            'exists' => $resolved !== null ? is_file($resolved) : false,
            'readable' => $resolved !== null ? is_readable($resolved) : false,
            'source' => $source,
            'materialized' => false,
            'error' => $error,
        ];
    }

    private function materializeRuntimeConfigAsset(string $inlineBase64, string $relativePath, string $label): array
    {
        $decoded = base64_decode(trim($inlineBase64), true);
        if ($decoded === false || $decoded === '') {
            return [
                'resolved' => null,
                'error' => "{$label} base64 khÃ´ng há»£p lá»‡ hoáº·c rá»—ng.",
            ];
        }

        $absolute = $this->resolveAbsolutePath($relativePath);
        $directory = dirname($absolute);
        File::ensureDirectoryExists($directory);
        File::put($absolute, $decoded);

        return [
            'resolved' => $absolute,
            'error' => null,
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

    private function buildRcloneRepositoryProbeTarget(string $repository): ?string
    {
        $repository = trim($repository);
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            return null;
        }

        $raw = trim((string) Str::after($repository, 'rclone:'));
        if ($raw === '') {
            return null;
        }

        $parts = explode(':', $raw, 2);
        $remoteName = trim((string) ($parts[0] ?? ''));
        $path = trim((string) ($parts[1] ?? ''), "/\\");
        if ($remoteName === '') {
            return null;
        }

        if ($path === '') {
            return $remoteName . ':';
        }

        $segments = preg_split('/[\/\\\\]+/', $path) ?: [];
        $segments = array_values(array_filter(array_map(
            static fn ($segment): string => trim((string) $segment),
            $segments
        )));

        if (count($segments) <= 1) {
            return $remoteName . ':';
        }

        array_pop($segments);

        return $remoteName . ':' . implode('/', $segments);
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
