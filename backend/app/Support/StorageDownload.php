<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageDownload
{
    public static function stream(
        string $disk,
        string $path,
        string $filename,
        array $headers = []
    ): Response {
        if (! config("filesystems.disks.{$disk}")) {
            return response()->json(['message' => 'storage disk not configured'], Response::HTTP_NOT_FOUND);
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);

        if (! $adapter->exists($path)) {
            return response()->json(['message' => 'file not found'], Response::HTTP_NOT_FOUND);
        }

        $stream = $adapter->readStream($path);
        if ($stream === false) {
            return response()->json(['message' => 'file not found'], Response::HTTP_NOT_FOUND);
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
