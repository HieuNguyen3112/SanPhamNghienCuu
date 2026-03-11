<?php

namespace App\Support;

use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class StorageDownload
{
    public static function stream(
        string $disk,
        string $path,
        string $filename,
        array $headers = []
    ): Response {
        $normalizedDisk = strtolower(trim($disk));

        if ($normalizedDisk === ResearchEvidenceStorageService::LINK_DISK) {
            $targetUrl = trim($path);
            $scheme = strtolower((string) parse_url($targetUrl, PHP_URL_SCHEME));
            $isValidUrl = $targetUrl !== ''
                && filter_var($targetUrl, FILTER_VALIDATE_URL) !== false
                && in_array($scheme, ['http', 'https'], true);

            if (! $isValidUrl) {
                return response()->json([
                    'message' => 'Liên kết minh chứng không hợp lệ.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->away($targetUrl);
        }

        if ($normalizedDisk === ResearchEvidenceStorageService::RCLONE_DISK) {
            try {
                /** @var ResearchEvidenceStorageService $service */
                $service = app(ResearchEvidenceStorageService::class);
                $mimeType = isset($headers['Content-Type']) ? (string) $headers['Content-Type'] : null;
                return $service->streamDownload(
                    request(),
                    $disk,
                    $path,
                    $filename,
                    $mimeType
                );
            } catch (RuntimeException $exception) {
                Log::error('evidence.download_rclone_failed', [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json(['message' => 'Không thể tải tệp minh chứng.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if (! config("filesystems.disks.{$disk}")) {
            return response()->json(['message' => 'Kho lưu trữ chưa được cấu hình.'], Response::HTTP_NOT_FOUND);
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);

        if (! $adapter->exists($path)) {
            return response()->json(['message' => 'Không tìm thấy tệp minh chứng.'], Response::HTTP_NOT_FOUND);
        }

        $stream = $adapter->readStream($path);
        if ($stream === false) {
            return response()->json(['message' => 'Không tìm thấy tệp minh chứng.'], Response::HTTP_NOT_FOUND);
        }

        $finalHeaders = array_merge([
            'Content-Type' => 'application/octet-stream',
        ], $headers);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, $finalHeaders);
    }
}
