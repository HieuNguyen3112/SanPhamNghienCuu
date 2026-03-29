<?php

namespace App\Services\Evidence;

use App\Jobs\SyncResearchEvidenceToColdStorageJob;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ResearchEvidenceStorageService
{
    public const RCLONE_DISK = 'rclone_drive';
    public const LINK_DISK = 'evidence_link';

    private const DEFAULT_STREAM_CHUNK_BYTES = 1024 * 64;

    public function storePdf(
        UploadedFile $file,
        string $activityFolderName,
        string $uploaderFolderName
    ): array {
        $driver = strtolower(trim((string) config('evidence.storage.driver', 'rclone')));
        if ($driver === 'local') {
            return $this->storeLocal($file, $activityFolderName, $uploaderFolderName);
        }

        return $this->storeViaHotStorage($file, $activityFolderName, $uploaderFolderName);
    }

    public function queueColdSync(
        int $evidenceId,
        ?string $hotRelativePath = null,
        ?string $coldPath = null
    ): void {
        $connection = trim((string) config('evidence.storage.cold_sync_connection', config('queue.default', 'sync')));
        $queue = trim((string) config('evidence.storage.cold_sync_queue', 'evidence-sync'));
        $afterResponse = (bool) config('evidence.storage.cold_sync_after_response', true);

        $dispatch = SyncResearchEvidenceToColdStorageJob::dispatch(
            $evidenceId,
            $hotRelativePath,
            $coldPath
        );

        if ($connection !== '') {
            $dispatch->onConnection($connection);
        }

        if ($queue !== '') {
            $dispatch->onQueue($queue);
        }

        if ($afterResponse) {
            $dispatch->afterResponse();
        }
    }

    public function syncColdStorage(
        int $evidenceId,
        ?string $hotRelativePath = null,
        ?string $coldPath = null
    ): void {
        $row = DB::table('evidence_files')
            ->where('id', $evidenceId)
            ->select(['id', 'disk', 'path'])
            ->first();

        if (! $row || ! $this->isRcloneDisk((string) $row->disk)) {
            return;
        }

        $resolvedColdPath = trim((string) ($coldPath ?: $row->path));
        if ($resolvedColdPath === '') {
            throw new RuntimeException('Thiáº¿u Ä‘Æ°á»ng dáº«n cold storage cho minh chá»©ng.');
        }

        $resolvedHotPath = trim((string) ($hotRelativePath ?: $this->buildHotRelativePathFromRclonePath($resolvedColdPath)));
        $hotAbsolutePath = $this->resolveHotAbsolutePath($resolvedHotPath);
        if (! is_file($hotAbsolutePath) || ! is_readable($hotAbsolutePath)) {
            throw new RuntimeException('KhÃ´ng tÃ¬m tháº¥y báº£n sao hot storage Ä‘á»ƒ Ä‘á»“ng bá»™ lÃªn Google Drive.');
        }

        $uploadTimeout = max(30, (int) config('evidence.storage.upload_timeout_seconds', 600));
        $this->runRclone(['copyto', $hotAbsolutePath, $resolvedColdPath], false, $uploadTimeout);
    }

    public function streamPreview(
        Request $request,
        string $disk,
        string $path,
        string $filename,
        ?string $mimeType = null
    ): Response {
        $contentType = $mimeType && trim($mimeType) !== '' ? $mimeType : 'application/pdf';
        $absolutePath = $this->resolvePreviewAbsolutePath($disk, $path);

        return $this->streamLocalFile(
            $request,
            $absolutePath,
            $filename,
            $contentType,
            'inline'
        );
    }

    public function streamDownload(
        Request $request,
        string $disk,
        string $path,
        string $filename,
        ?string $mimeType = null
    ): Response {
        $contentType = $mimeType && trim($mimeType) !== '' ? $mimeType : 'application/octet-stream';
        $absolutePath = $this->resolvePreviewAbsolutePath($disk, $path);

        return $this->streamLocalFile(
            $request,
            $absolutePath,
            $filename,
            $contentType,
            'attachment'
        );
    }

    public function cleanupHotCache(): array
    {
        $disk = $this->resolveHotDiskName();
        $adapter = Storage::disk($disk);
        $baseDir = $this->hotBaseDir();
        $ttlDays = max(1, (int) config('evidence.storage.hot_cache_ttl_days', 14));
        $maxBytes = max(50 * 1024 * 1024, (int) config('evidence.storage.hot_cache_max_bytes', 2 * 1024 * 1024 * 1024));
        $now = time();
        $ttlSeconds = $ttlDays * 86400;

        $files = collect($adapter->allFiles($baseDir))
            ->map(function (string $filePath) use ($adapter) {
                return [
                    'path' => $filePath,
                    'size' => (int) $adapter->size($filePath),
                    'last_modified' => (int) $adapter->lastModified($filePath),
                ];
            })
            ->sortBy('last_modified')
            ->values();

        $removedCount = 0;
        $removedBytes = 0;

        $activeFiles = [];
        foreach ($files as $file) {
            if (($now - $file['last_modified']) > $ttlSeconds) {
                $adapter->delete($file['path']);
                $removedCount++;
                $removedBytes += (int) $file['size'];
                continue;
            }

            $activeFiles[] = $file;
        }

        $totalBytes = array_sum(array_map(static fn (array $item): int => (int) $item['size'], $activeFiles));

        while ($totalBytes > $maxBytes && $activeFiles !== []) {
            $oldest = array_shift($activeFiles);
            if (! $oldest) {
                break;
            }
            $adapter->delete($oldest['path']);
            $removedCount++;
            $removedBytes += (int) $oldest['size'];
            $totalBytes -= (int) $oldest['size'];
        }

        return [
            'removed_count' => $removedCount,
            'removed_bytes' => $removedBytes,
            'remaining_count' => count($activeFiles),
            'remaining_bytes' => max(0, $totalBytes),
            'ttl_days' => $ttlDays,
            'max_bytes' => $maxBytes,
        ];
    }

    public function isRcloneDisk(string $disk): bool
    {
        return strtolower(trim($disk)) === self::RCLONE_DISK;
    }

    public function deleteFromRclone(string $path, bool $allowFailure = false): void
    {
        if (trim($path) === '') {
            return;
        }

        $timeout = max(10, (int) config('evidence.storage.delete_timeout_seconds', 180));
        $this->runRclone(['deletefile', $path], $allowFailure, $timeout);
    }

    public function deleteHotCacheByColdPath(string $coldPath): void
    {
        $normalizedPath = trim($coldPath);
        if ($normalizedPath === '') {
            return;
        }

        $hotDisk = $this->resolveHotDiskName();
        $hotPath = $this->buildHotRelativePathFromRclonePath($normalizedPath);
        if (Storage::disk($hotDisk)->exists($hotPath)) {
            Storage::disk($hotDisk)->delete($hotPath);
        }
    }

    public function deleteFromLocalDisk(string $disk, string $path): void
    {
        if (! config("filesystems.disks.{$disk}")) {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function storeViaHotStorage(
        UploadedFile $file,
        string $activityFolderName,
        string $uploaderFolderName
    ): array {
        $remoteRoot = $this->resolveRcloneTargetRoot();
        $activityFolder = $this->sanitizePathSegment($activityFolderName, 'Cong-trinh');
        $uploaderFolder = $this->sanitizePathSegment($uploaderFolderName, 'Giang-vien');
        $filename = $this->buildStoredFilename($file->getClientOriginalName());
        $remoteFilePath = $this->joinRclonePath(
            $this->joinRclonePath($remoteRoot, $activityFolder),
            $uploaderFolder . '/' . $filename
        );

        $hotRelativePath = $this->buildHotRelativePathFromRclonePath($remoteFilePath);
        $hotDisk = $this->resolveHotDiskName();
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('KhÃ´ng thá»ƒ Ä‘á»c tá»‡p minh chá»©ng tá»« yÃªu cáº§u upload.');
        }

        try {
            $stored = Storage::disk($hotDisk)->put($hotRelativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw new RuntimeException('KhÃ´ng thá»ƒ ghi tá»‡p minh chá»©ng vÃ o hot storage.');
        }

        return [
            'disk' => self::RCLONE_DISK,
            'path' => $remoteFilePath,
            'hot_disk' => $hotDisk,
            'hot_path' => $hotRelativePath,
            'sync_mode' => 'queued',
        ];
    }

    private function storeLocal(
        UploadedFile $file,
        string $activityFolderName,
        string $uploaderFolderName
    ): array {
        $disk = trim((string) config('evidence.storage.local_disk', 'local'));
        if (! config("filesystems.disks.{$disk}")) {
            $disk = 'local';
        }

        $baseDir = trim((string) config('evidence.storage.local_dir', 'evidence/declarations'), "/\\");
        if ($baseDir === '') {
            $baseDir = 'evidence/declarations';
        }

        $activityFolder = $this->sanitizePathSegment($activityFolderName, 'Cong-trinh');
        $uploaderFolder = $this->sanitizePathSegment($uploaderFolderName, 'Giang-vien');
        $directory = $baseDir . '/' . $activityFolder . '/' . $uploaderFolder;
        $filename = $this->buildStoredFilename($file->getClientOriginalName());
        $storedPath = $file->storeAs($directory, $filename, $disk);

        if (! $storedPath) {
            throw new RuntimeException('KhÃ´ng thá»ƒ lÆ°u tá»‡p minh chá»©ng vÃ o kho tá»‡p.');
        }

        return [
            'disk' => $disk,
            'path' => $storedPath,
            'sync_mode' => 'local',
        ];
    }

    private function resolvePreviewAbsolutePath(string $disk, string $path): string
    {
        $normalizedDisk = strtolower(trim($disk));
        $normalizedPath = trim($path);
        if ($normalizedPath === '') {
            throw new RuntimeException('ÄÆ°á»ng dáº«n tá»‡p minh chá»©ng khÃ´ng há»£p lá»‡.');
        }

        if ($normalizedDisk === self::RCLONE_DISK) {
            return $this->ensureHotFileFromColdPath($normalizedPath);
        }

        $absolutePath = $this->resolveLocalAbsolutePath($disk, $normalizedPath);
        if ($absolutePath === '' || ! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException('KhÃ´ng tÃ¬m tháº¥y tá»‡p minh chá»©ng Ä‘á»ƒ xem trÆ°á»›c.');
        }

        return $absolutePath;
    }

    private function ensureHotFileFromColdPath(string $coldPath): string
    {
        $hotRelativePath = $this->buildHotRelativePathFromRclonePath($coldPath);
        $hotAbsolutePath = $this->resolveHotAbsolutePath($hotRelativePath);
        if (is_file($hotAbsolutePath) && is_readable($hotAbsolutePath)) {
            $this->touchHotFile($hotAbsolutePath);
            return $hotAbsolutePath;
        }

        $parentDir = dirname($hotAbsolutePath);
        if (! is_dir($parentDir) && ! mkdir($parentDir, 0775, true) && ! is_dir($parentDir)) {
            throw new RuntimeException('KhÃ´ng thá»ƒ táº¡o thÆ° má»¥c hot storage cho minh chá»©ng.');
        }

        $downloadTimeout = max(30, (int) config('evidence.storage.download_timeout_seconds', 600));
        $this->runRclone(['copyto', $coldPath, $hotAbsolutePath], false, $downloadTimeout);

        if (! is_file($hotAbsolutePath) || ! is_readable($hotAbsolutePath)) {
            throw new RuntimeException('KhÃ´ng thá»ƒ náº¡p tá»‡p minh chá»©ng tá»« Google Drive.');
        }

        $this->touchHotFile($hotAbsolutePath);
        return $hotAbsolutePath;
    }

    private function streamLocalFile(
        Request $request,
        string $absolutePath,
        string $filename,
        string $mimeType,
        string $disposition
    ): Response {
        clearstatcache(true, $absolutePath);
        $fileSize = filesize($absolutePath);
        $lastModified = filemtime($absolutePath);
        if ($fileSize === false || $lastModified === false) {
            throw new RuntimeException('KhÃ´ng thá»ƒ Ä‘á»c thÃ´ng tin tá»‡p minh chá»©ng.');
        }

        $etag = '"' . sha1($absolutePath . '|' . $fileSize . '|' . $lastModified) . '"';
        $lastModifiedHttp = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
        $cacheSeconds = max(60, (int) config('evidence.storage.hot_cache_control_seconds', 3600));

        $baseHeaders = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'ETag' => $etag,
            'Last-Modified' => $lastModifiedHttp,
            'Cache-Control' => 'private, max-age=' . $cacheSeconds . ', must-revalidate',
            'Content-Disposition' => $this->buildContentDispositionHeader($filename, $disposition),
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($this->isNotModified($request, $etag, $lastModified)) {
            return response('', Response::HTTP_NOT_MODIFIED, $baseHeaders);
        }

        $range = $this->resolveRange($request->headers->get('Range'), $fileSize);
        if ($range['type'] === 'invalid' || $range['type'] === 'unsatisfiable') {
            return response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, [
                'Content-Range' => 'bytes */' . $fileSize,
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $start = 0;
        $end = $fileSize - 1;
        $status = Response::HTTP_OK;
        if ($range['type'] === 'partial') {
            $start = (int) $range['start'];
            $end = (int) $range['end'];
            $status = Response::HTTP_PARTIAL_CONTENT;
            $baseHeaders['Content-Range'] = 'bytes ' . $start . '-' . $end . '/' . $fileSize;
        }

        $contentLength = $end - $start + 1;
        $baseHeaders['Content-Length'] = (string) $contentLength;

        return response()->stream(function () use ($absolutePath, $start, $contentLength) {
            $stream = fopen($absolutePath, 'rb');
            if ($stream === false) {
                return;
            }

            try {
                if ($start > 0) {
                    fseek($stream, $start);
                }

                $remaining = $contentLength;
                while ($remaining > 0 && ! feof($stream)) {
                    $readSize = min(self::DEFAULT_STREAM_CHUNK_BYTES, $remaining);
                    $buffer = fread($stream, $readSize);
                    if ($buffer === false || $buffer === '') {
                        break;
                    }

                    echo $buffer;
                    $remaining -= strlen($buffer);
                }
            } finally {
                fclose($stream);
            }
        }, $status, $baseHeaders);
    }

    private function resolveRange(?string $header, int $fileSize): array
    {
        $raw = trim((string) $header);
        if ($raw === '') {
            return ['type' => 'full'];
        }

        if (! preg_match('/^bytes=(\d*)-(\d*)$/i', $raw, $matches)) {
            return ['type' => 'invalid'];
        }

        $startRaw = $matches[1] ?? '';
        $endRaw = $matches[2] ?? '';
        if ($startRaw === '' && $endRaw === '') {
            return ['type' => 'invalid'];
        }

        if ($startRaw === '') {
            $suffixLength = (int) $endRaw;
            if ($suffixLength <= 0) {
                return ['type' => 'invalid'];
            }

            $start = max(0, $fileSize - $suffixLength);
            return [
                'type' => 'partial',
                'start' => $start,
                'end' => $fileSize - 1,
            ];
        }

        $start = (int) $startRaw;
        $end = $endRaw !== '' ? (int) $endRaw : ($fileSize - 1);
        if ($start < 0 || $end < 0 || $start > $end) {
            return ['type' => 'invalid'];
        }

        if ($start >= $fileSize) {
            return ['type' => 'unsatisfiable'];
        }

        return [
            'type' => 'partial',
            'start' => $start,
            'end' => min($end, $fileSize - 1),
        ];
    }

    private function buildContentDispositionHeader(string $filename, string $disposition): string
    {
        $safeName = trim($filename) !== '' ? trim($filename) : 'evidence.pdf';
        $asciiName = Str::ascii($safeName);
        $asciiName = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $asciiName ?? '') ?? '';
        if ($asciiName === '') {
            $asciiName = 'evidence.pdf';
        }

        return sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $disposition,
            $asciiName,
            rawurlencode($safeName)
        );
    }

    private function isNotModified(Request $request, string $etag, int $lastModified): bool
    {
        $ifNoneMatch = trim((string) $request->headers->get('If-None-Match'));
        if ($ifNoneMatch !== '') {
            $tokens = array_map('trim', explode(',', $ifNoneMatch));
            if (in_array('*', $tokens, true) || in_array($etag, $tokens, true)) {
                return true;
            }
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($ifModifiedSince) {
            $timestamp = strtotime($ifModifiedSince);
            if ($timestamp !== false && $lastModified <= $timestamp) {
                return true;
            }
        }

        return false;
    }

    private function resolveRcloneTargetRoot(): string
    {
        $configured = trim((string) config('evidence.storage.rclone_target', ''));
        if ($configured !== '') {
            return $this->normalizeRcloneRoot($configured);
        }

        $allowBackupFallback = (bool) config('evidence.storage.allow_backup_repository_fallback', false);
        if (! $allowBackupFallback) {
            throw new RuntimeException('ChÆ°a cáº¥u hÃ¬nh SPNC_EVIDENCE_DRIVE_TARGET cho lÆ°u minh chá»©ng.');
        }

        $repository = trim((string) config('backup.restic.repository', ''));
        if (! str_starts_with(Str::lower($repository), 'rclone:')) {
            throw new RuntimeException('ChÆ°a cáº¥u hÃ¬nh Ä‘Ã­ch Google Drive cho minh chá»©ng.');
        }

        $withoutPrefix = trim((string) Str::after($repository, 'rclone:'));
        $parts = explode(':', $withoutPrefix, 2);
        $remoteName = trim((string) ($parts[0] ?? ''));
        $repoPath = trim((string) ($parts[1] ?? ''), '/');
        if ($remoteName === '') {
            throw new RuntimeException('KhÃ´ng Ä‘á»c Ä‘Æ°á»£c remote rclone tá»« cáº¥u hÃ¬nh backup.');
        }

        $parentPath = $repoPath;
        if ($parentPath !== '' && str_contains($parentPath, '/')) {
            $parentPath = (string) Str::beforeLast($parentPath, '/');
        } elseif ($parentPath !== '') {
            $parentPath = '';
        }

        $folderName = trim((string) config('evidence.storage.folder_name', 'declaration-evidence'), "/\\");
        if ($folderName === '') {
            $folderName = 'declaration-evidence';
        }

        $targetPath = trim(($parentPath !== '' ? $parentPath . '/' : '') . $folderName, '/');
        return $targetPath !== ''
            ? "{$remoteName}:{$targetPath}"
            : "{$remoteName}:";
    }

    private function normalizeRcloneRoot(string $value): string
    {
        $raw = trim($value);
        if (str_starts_with(Str::lower($raw), 'rclone:')) {
            $raw = trim((string) Str::after($raw, 'rclone:'));
        }
        if ($raw === '' || ! str_contains($raw, ':')) {
            throw new RuntimeException('Cáº¥u hÃ¬nh SPNC_EVIDENCE_DRIVE_TARGET khÃ´ng há»£p lá»‡.');
        }

        $parts = explode(':', $raw, 2);
        $remoteName = trim((string) ($parts[0] ?? ''));
        $remotePath = trim((string) ($parts[1] ?? ''), '/');
        if ($remoteName === '') {
            throw new RuntimeException('Cáº¥u hÃ¬nh SPNC_EVIDENCE_DRIVE_TARGET thiáº¿u remote.');
        }

        return $remotePath !== '' ? "{$remoteName}:{$remotePath}" : "{$remoteName}:";
    }

    private function joinRclonePath(string $root, string $segment): string
    {
        $segment = trim($segment, '/');
        if ($segment === '') {
            return $root;
        }

        $parts = explode(':', $root, 2);
        $remote = trim((string) ($parts[0] ?? ''));
        $base = trim((string) ($parts[1] ?? ''), '/');
        if ($remote === '') {
            throw new RuntimeException('ÄÆ°á»ng dáº«n rclone khÃ´ng há»£p lá»‡.');
        }

        $path = $base === '' ? $segment : ($base . '/' . $segment);
        return "{$remote}:{$path}";
    }

    private function sanitizePathSegment(string $value, string $fallback): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', ' ', $value) ?? '';
        $clean = preg_replace('/\s+/u', ' ', trim($clean)) ?? '';
        $clean = trim($clean, ". \t\n\r\0\x0B");
        if ($clean === '') {
            $clean = $fallback;
        }

        if (mb_strlen($clean) > 120) {
            $clean = mb_substr($clean, 0, 120);
            $clean = rtrim($clean, ". \t\n\r\0\x0B");
        }

        return $clean !== '' ? $clean : $fallback;
    }

    private function buildStoredFilename(?string $originalName): string
    {
        $name = $originalName && trim($originalName) !== '' ? trim($originalName) : 'minh-chung.pdf';
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = $this->sanitizePathSegment((string) $base, 'minh-chung');
        if (mb_strlen($base) > 80) {
            $base = mb_substr($base, 0, 80);
        }

        $prefix = now()->format('Ymd_His') . '_' . Str::lower(Str::random(6));
        return $prefix . '_' . $base . '.pdf';
    }

    private function buildHotRelativePathFromRclonePath(string $coldPath): string
    {
        $hash = sha1(trim($coldPath));
        $baseDir = $this->hotBaseDir();
        return $baseDir . '/' . substr($hash, 0, 2) . '/' . $hash . '.pdf';
    }

    private function resolveHotDiskName(): string
    {
        $disk = trim((string) config('evidence.storage.hot_disk', 'local'));
        if ($disk === '' || ! config("filesystems.disks.{$disk}")) {
            return 'local';
        }

        // Range stream uses random access (`fseek`) so hot storage must expose a local path.
        $adapter = Storage::disk($disk);
        if (! method_exists($adapter, 'path')) {
            return 'local';
        }

        return $disk;
    }

    private function hotBaseDir(): string
    {
        $dir = trim((string) config('evidence.storage.hot_dir', 'evidence/hot-cache'), "/\\");
        return $dir !== '' ? $dir : 'evidence/hot-cache';
    }

    private function resolveHotAbsolutePath(string $hotRelativePath): string
    {
        $disk = $this->resolveHotDiskName();
        $adapter = Storage::disk($disk);
        if (! method_exists($adapter, 'path')) {
            throw new RuntimeException('Hot storage hiá»‡n táº¡i khÃ´ng há»— trá»£ stream local cho preview.');
        }

        return $adapter->path($hotRelativePath);
    }

    private function resolveLocalAbsolutePath(string $disk, string $path): string
    {
        if (! config("filesystems.disks.{$disk}")) {
            return '';
        }

        $adapter = Storage::disk($disk);
        if (! method_exists($adapter, 'path')) {
            return '';
        }

        return $adapter->path($path);
    }

    private function touchHotFile(string $absolutePath): void
    {
        @touch($absolutePath);
    }

    private function runRclone(array $args, bool $allowFailure = false, int $timeout = 600): array
    {
        $binary = trim((string) config('evidence.storage.rclone_binary', 'rclone'));
        if ($binary === '') {
            throw new RuntimeException('Thiáº¿u cáº¥u hÃ¬nh rclone binary Ä‘á»ƒ xá»­ lÃ½ minh chá»©ng.');
        }

        $this->validateRcloneConfiguration();
        $globalArgs = $this->buildRcloneGlobalArgs();
        $command = array_merge([$binary], $globalArgs, $args);
        $process = new Process($command, base_path(), $this->rcloneEnv(), null, $timeout);
        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            if ($allowFailure) {
                $timedOutProcess = $exception->getProcess();
                return [
                    'successful' => false,
                    'exit_code' => null,
                    'stdout' => $timedOutProcess->getOutput(),
                    'stderr' => $exception->getMessage(),
                    'command' => $timedOutProcess->getCommandLine(),
                    'timed_out' => true,
                ];
            }

            throw new RuntimeException('Lá»‡nh rclone quÃ¡ thá»i gian chá» khi xá»­ lÃ½ minh chá»©ng.', 0, $exception);
        }

        $result = [
            'successful' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'command' => $process->getCommandLine(),
        ];

        if (! $allowFailure && ! $result['successful']) {
            $stderr = trim((string) $result['stderr']);
            throw new RuntimeException(
                'Lá»‡nh rclone tháº¥t báº¡i: ' . ($stderr !== '' ? $stderr : 'unknown error')
            );
        }

        return $result;
    }

    private function buildRcloneGlobalArgs(): array
    {
        $args = [];

        $serviceAccountFile = $this->resolveRuntimeServiceAccountFile();
        if ($serviceAccountFile !== '') {
            $args[] = '--drive-service-account-file';
            $args[] = $serviceAccountFile;
        }

        $driveImpersonate = trim((string) config('evidence.storage.rclone_drive_impersonate', ''));
        if ($driveImpersonate !== '') {
            $args[] = '--drive-impersonate';
            $args[] = $driveImpersonate;
        }

        $retries = max(1, (int) config('evidence.storage.rclone_retries', 3));
        $lowLevelRetries = max(1, (int) config('evidence.storage.rclone_low_level_retries', 5));
        $retriesSleep = max(1, (int) config('evidence.storage.rclone_retries_sleep_seconds', 2));
        $connectTimeout = max(3, (int) config('evidence.storage.rclone_connect_timeout_seconds', 15));
        $ioTimeout = max(10, (int) config('evidence.storage.rclone_io_timeout_seconds', 60));

        $args[] = '--retries';
        $args[] = (string) $retries;
        $args[] = '--low-level-retries';
        $args[] = (string) $lowLevelRetries;
        $args[] = '--retries-sleep';
        $args[] = $retriesSleep . 's';
        $args[] = '--contimeout';
        $args[] = $connectTimeout . 's';
        $args[] = '--timeout';
        $args[] = $ioTimeout . 's';

        return $args;
    }

    private function validateRcloneConfiguration(): void
    {
        $rcloneConfigPath = $this->resolveRuntimeRcloneConfigPath();
        if ($rcloneConfigPath !== '') {
            if (! is_file($rcloneConfigPath) || ! is_readable($rcloneConfigPath)) {
                throw new RuntimeException('Khong doc duoc tep cau hinh rclone cho minh chung.');
            }
        }

        $serviceAccountFile = $this->resolveRuntimeServiceAccountFile();
        if ($serviceAccountFile !== '') {
            if (! is_file($serviceAccountFile) || ! is_readable($serviceAccountFile)) {
                throw new RuntimeException('Khong doc duoc tep service account Google Drive cho minh chung.');
            }

            return;
        }

        $requireWritableConfig = (bool) config('evidence.storage.require_writable_rclone_config', true);
        if (! $requireWritableConfig || $rcloneConfigPath === '') {
            return;
        }

        $configDir = dirname($rcloneConfigPath);
        if (! is_dir($configDir) || ! is_writable($configDir)) {
            throw new RuntimeException(
                'Thu muc cau hinh rclone cho minh chung khong co quyen ghi. '
                . 'Hay cau hinh SPNC_RCLONE_CONFIG toi duong dan server co quyen ghi.'
            );
        }
    }

    private function rcloneEnv(): array
    {
        $env = [];
        foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            if (is_array($value) || is_object($value) || $value === null) {
                continue;
            }
            $env[$key] = (string) $value;
        }

        foreach (['PATH', 'Path', 'SystemRoot', 'SYSTEMROOT', 'WINDIR', 'windir', 'ComSpec', 'COMSPEC'] as $key) {
            $value = getenv($key);
            if ($value === false || trim((string) $value) === '') {
                continue;
            }
            $env[$key] = (string) $value;
        }

        $rcloneConfig = $this->resolveRuntimeRcloneConfigPath();
        if ($rcloneConfig !== '') {
            $env['RCLONE_CONFIG'] = $rcloneConfig;
        }

        $serviceAccountFile = $this->resolveRuntimeServiceAccountFile();
        if ($serviceAccountFile !== '') {
            $env['RCLONE_DRIVE_SERVICE_ACCOUNT_FILE'] = $serviceAccountFile;
        }

        $httpProxy = trim((string) config('evidence.storage.http_proxy', ''));
        $httpsProxy = trim((string) config('evidence.storage.https_proxy', ''));
        $noProxy = trim((string) config('evidence.storage.no_proxy', ''));

        $env['HTTP_PROXY'] = $httpProxy;
        $env['http_proxy'] = $httpProxy;
        $env['HTTPS_PROXY'] = $httpsProxy;
        $env['https_proxy'] = $httpsProxy;
        $env['NO_PROXY'] = $noProxy;
        $env['no_proxy'] = $noProxy;

        return $env;
    }

    private function resolveConfiguredPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        if ($this->isAbsolutePath($trimmed)) {
            return $trimmed;
        }

        return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed));
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[a-zA-Z]:[\/\\\\]/', $path) === 1
            || str_starts_with($path, '\\\\');
    }

    private function resolveRuntimeRcloneConfigPath(): string
    {
        return $this->resolveRuntimeAsset(
            (string) config('evidence.storage.rclone_config_path', ''),
            (string) config('evidence.storage.rclone_config_base64', ''),
            'storage/app/runtime-config/evidence/rclone.conf'
        );
    }

    private function resolveRuntimeServiceAccountFile(): string
    {
        return $this->resolveRuntimeAsset(
            (string) config('evidence.storage.rclone_service_account_file', ''),
            (string) config('evidence.storage.rclone_service_account_json_base64', ''),
            'storage/app/runtime-config/evidence/service-account.json'
        );
    }

    private function resolveRuntimeAsset(string $configuredPath, string $inlineBase64, string $materializedRelativePath): string
    {
        $inlineBase64 = trim($inlineBase64);
        $preferBase64 = app()->environment('production') && $inlineBase64 !== '';

        if ($preferBase64) {
            $materialized = $this->materializeRuntimeAsset($inlineBase64, $materializedRelativePath);
            if ($materialized !== '') {
                return $materialized;
            }
        }

        $resolvedConfigured = $this->resolveConfiguredPath($configuredPath);
        if ($resolvedConfigured !== '' && is_file($resolvedConfigured) && is_readable($resolvedConfigured)) {
            return $resolvedConfigured;
        }

        if (! $preferBase64 && $inlineBase64 !== '') {
            return $this->materializeRuntimeAsset($inlineBase64, $materializedRelativePath);
        }

        return '';
    }

    private function materializeRuntimeAsset(string $inlineBase64, string $materializedRelativePath): string
    {
        $decoded = base64_decode(trim($inlineBase64), true);
        if ($decoded === false || $decoded === '') {
            return '';
        }

        $absolutePath = base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $materializedRelativePath));
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $decoded);

        return is_file($absolutePath) && is_readable($absolutePath) ? $absolutePath : '';
    }
}

