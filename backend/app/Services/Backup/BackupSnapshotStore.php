<?php

namespace App\Services\Backup;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BackupSnapshotStore
{
    public function read(): array
    {
        $path = $this->path();
        if (! File::exists($path)) {
            return $this->defaultPayload();
        }

        $decoded = json_decode((string) File::get($path), true);
        if (! is_array($decoded)) {
            return $this->defaultPayload();
        }

        $payload = array_merge($this->defaultPayload(), $decoded);
        $payload['context'] = is_array($payload['context'] ?? null)
            ? $payload['context']
            : [];
        $payload['items'] = $this->normalizeItems((array) ($payload['items'] ?? []));

        if ($this->hasContextMismatch($payload)) {
            return $this->defaultPayload();
        }

        return $payload;
    }

    public function replace(array $items, ?string $runId = null): array
    {
        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $snapshotId = trim((string) ($item['snapshot_id'] ?? ''));
            if ($snapshotId === '') {
                continue;
            }

            $item['snapshot_id_full'] = trim((string) ($item['snapshot_id_full'] ?? $snapshotId));
            $item['short_id'] = trim((string) ($item['short_id'] ?? '')) !== ''
                ? (string) $item['short_id']
                : substr($snapshotId, 0, 8);
            $normalized[] = $item;
        }

        usort($normalized, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $payload = $this->read();
        $payload['items'] = array_values($normalized);
        $payload['refreshing'] = false;
        $payload['refresh_started_at'] = null;
        $payload['refresh_run_id'] = null;
        $payload['last_error'] = null;
        $payload['last_error_code'] = null;
        $payload['last_error_step'] = null;
        $payload['last_error_technical_message'] = null;
        $payload['last_error_operation'] = null;
        $payload['last_refresh_run_id'] = $runId;
        $payload['refreshed_at'] = now()->toIso8601String();
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function upsert(array $item, ?string $runId = null): array
    {
        $snapshotId = trim((string) ($item['snapshot_id'] ?? ''));
        if ($snapshotId === '') {
            return $this->read();
        }

        $payload = $this->read();
        $items = array_values((array) ($payload['items'] ?? []));
        $normalizedItem = $this->normalizeItems([$item]);
        if ($normalizedItem === []) {
            return $payload;
        }

        $normalizedSnapshotId = Str::lower($snapshotId);
        $items = array_values(array_filter($items, static function (array $existing) use ($normalizedSnapshotId): bool {
            $candidate = Str::lower(trim((string) ($existing['snapshot_id'] ?? '')));
            $candidateFull = Str::lower(trim((string) ($existing['snapshot_id_full'] ?? $candidate)));

            return $candidate !== $normalizedSnapshotId && $candidateFull !== $normalizedSnapshotId;
        }));
        $items[] = $normalizedItem[0];

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $maxItems = max(10, (int) config('backup.snapshot_cache.max_items', 200));
        $payload['items'] = array_slice(array_values($items), 0, $maxItems);
        $payload['refreshing'] = false;
        $payload['refresh_started_at'] = null;
        $payload['refresh_run_id'] = null;
        $payload['last_error'] = null;
        $payload['last_error_code'] = null;
        $payload['last_error_step'] = null;
        $payload['last_error_technical_message'] = null;
        $payload['last_error_operation'] = null;
        $payload['last_refresh_run_id'] = $runId;
        $payload['refreshed_at'] = now()->toIso8601String();
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function mergeBySnapshotId(string $snapshotId, array $patch, ?string $runId = null): array
    {
        $snapshotId = trim($snapshotId);
        if ($snapshotId === '') {
            return $this->read();
        }

        $payload = $this->read();
        $items = array_values((array) ($payload['items'] ?? []));
        $updated = false;

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $candidate = trim((string) ($item['snapshot_id'] ?? ''));
            $candidateFull = trim((string) ($item['snapshot_id_full'] ?? $candidate));
            if (
                $candidate !== $snapshotId
                && $candidateFull !== $snapshotId
                && ! str_starts_with($candidate, $snapshotId)
                && ! str_starts_with($candidateFull, $snapshotId)
            ) {
                continue;
            }

            $items[$index] = array_merge($item, $patch, [
                'snapshot_id' => $item['snapshot_id'] ?? $snapshotId,
                'snapshot_id_full' => $item['snapshot_id_full'] ?? $item['snapshot_id'] ?? $snapshotId,
            ]);
            $updated = true;
            break;
        }

        if (! $updated) {
            return $payload;
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $payload['items'] = array_values($items);
        $payload['last_refresh_run_id'] = $runId ?? ($payload['last_refresh_run_id'] ?? null);
        $payload['updated_at'] = now()->toIso8601String();
        $payload['refreshed_at'] = $payload['refreshed_at'] ?? now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function markRefreshing(string $runId): array
    {
        $payload = $this->read();
        $payload['refreshing'] = true;
        $payload['refresh_started_at'] = now()->toIso8601String();
        $payload['refresh_run_id'] = $runId;
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function markRefreshFailed(string|array $error, ?string $runId = null): array
    {
        $errorPayload = is_array($error) ? $error : ['user_message' => $error];
        $userMessage = trim((string) ($errorPayload['user_message'] ?? $errorPayload['message'] ?? ''));
        $technicalMessage = trim((string) ($errorPayload['technical_message'] ?? $errorPayload['error_message'] ?? ''));
        $errorCode = trim((string) ($errorPayload['error_code'] ?? ''));
        $step = trim((string) ($errorPayload['step'] ?? ''));
        $operation = trim((string) ($errorPayload['operation'] ?? ''));

        $payload = $this->read();
        $payload['refreshing'] = false;
        $payload['refresh_started_at'] = null;
        $payload['refresh_run_id'] = null;
        $payload['last_error'] = $userMessage !== '' ? $userMessage : null;
        $payload['last_error_code'] = $errorCode !== '' ? $errorCode : null;
        $payload['last_error_step'] = $step !== '' ? $step : null;
        $payload['last_error_technical_message'] = $technicalMessage !== '' ? $technicalMessage : null;
        $payload['last_error_operation'] = $operation !== '' ? $operation : 'snapshot_refresh';
        $payload['last_refresh_run_id'] = $runId;
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function markIdle(): array
    {
        $payload = $this->read();
        $payload['refreshing'] = false;
        $payload['refresh_started_at'] = null;
        $payload['refresh_run_id'] = null;
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function findBySnapshotId(string $snapshotId): ?array
    {
        $snapshotId = trim($snapshotId);
        if ($snapshotId === '') {
            return null;
        }

        $payload = $this->read();
        foreach ((array) ($payload['items'] ?? []) as $item) {
            $id = (string) ($item['snapshot_id'] ?? '');
            if ($id === '') {
                continue;
            }

            if ($id === $snapshotId || str_starts_with($id, $snapshotId)) {
                return $item;
            }
        }

        return null;
    }

    public function removeBySnapshotIds(array $snapshotIds): array
    {
        $targets = array_values(array_filter(array_map(
            static fn ($id): string => Str::lower(trim((string) $id)),
            $snapshotIds
        )));

        if ($targets === []) {
            return $this->read();
        }

        $payload = $this->read();
        $items = array_values((array) ($payload['items'] ?? []));
        if ($items === []) {
            return $payload;
        }

        $payload['items'] = array_values(array_filter($items, static function (array $item) use ($targets): bool {
            $snapshotId = Str::lower(trim((string) ($item['snapshot_id'] ?? '')));
            $fullId = Str::lower(trim((string) ($item['snapshot_id_full'] ?? $snapshotId)));

            foreach ($targets as $target) {
                if ($target === '') {
                    continue;
                }

                if (
                    $snapshotId === $target
                    || $fullId === $target
                    || ($snapshotId !== '' && str_starts_with($snapshotId, $target))
                    || ($fullId !== '' && str_starts_with($fullId, $target))
                ) {
                    return false;
                }
            }

            return true;
        }));
        $payload['updated_at'] = now()->toIso8601String();
        $payload['context'] = $this->contextPayload();

        $this->write($payload);

        return $payload;
    }

    public function isStale(?int $ttlSeconds = null): bool
    {
        $payload = $this->read();
        $refreshedAt = (string) ($payload['refreshed_at'] ?? '');
        if ($refreshedAt === '') {
            return true;
        }

        try {
            $refreshed = Carbon::parse($refreshedAt);
        } catch (\Throwable) {
            return true;
        }

        $ttl = $this->effectiveStaleAfterSeconds($ttlSeconds);

        return $refreshed->lt(now()->subSeconds($ttl));
    }

    public function shouldStartRefresh(?int $staleAfterSeconds = null): bool
    {
        $payload = $this->read();
        if (! $this->isStale($staleAfterSeconds) && ! empty($payload['items'])) {
            return false;
        }

        if (! (bool) ($payload['refreshing'] ?? false)) {
            return true;
        }

        $startedAt = (string) ($payload['refresh_started_at'] ?? '');
        if ($startedAt === '') {
            return true;
        }

        try {
            $started = Carbon::parse($startedAt);
        } catch (\Throwable) {
            return true;
        }

        $timeout = max(60, (int) config('backup.snapshot_cache.refresh_timeout_seconds', 900));
        return $started->lt(now()->subSeconds($timeout));
    }

    public function cacheMeta(): array
    {
        $payload = $this->read();
        return [
            'refreshed_at' => $payload['refreshed_at'] ?? null,
            'refreshing' => (bool) ($payload['refreshing'] ?? false),
            'refresh_run_id' => $payload['refresh_run_id'] ?? null,
            'last_refresh_run_id' => $payload['last_refresh_run_id'] ?? null,
            'last_error' => $payload['last_error'] ?? null,
            'last_error_code' => $payload['last_error_code'] ?? null,
            'last_error_step' => $payload['last_error_step'] ?? null,
            'last_error_technical_message' => $payload['last_error_technical_message'] ?? null,
            'last_error_operation' => $payload['last_error_operation'] ?? null,
            'stale' => $this->isStale(),
            'stale_after_seconds' => $this->effectiveStaleAfterSeconds(),
        ];
    }

    public function filterAndPaginate(array $filters): array
    {
        $payload = $this->read();
        $items = array_values((array) ($payload['items'] ?? []));

        $status = Str::lower(trim((string) ($filters['status'] ?? '')));
        if ($status !== '') {
            $items = array_values(array_filter($items, static function (array $item) use ($status): bool {
                return Str::lower((string) ($item['run_state'] ?? $item['status'] ?? '')) === $status;
            }));
        }

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '') {
            $items = array_values(array_filter($items, static function (array $item) use ($from): bool {
                $createdAt = trim((string) ($item['created_at'] ?? ''));
                if ($createdAt === '') {
                    return false;
                }

                try {
                    return Carbon::parse($createdAt)->greaterThanOrEqualTo(Carbon::parse($from));
                } catch (\Throwable) {
                    return false;
                }
            }));
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '') {
            $items = array_values(array_filter($items, static function (array $item) use ($to): bool {
                $createdAt = trim((string) ($item['created_at'] ?? ''));
                if ($createdAt === '') {
                    return false;
                }

                try {
                    return Carbon::parse($createdAt)->lessThanOrEqualTo(Carbon::parse($to));
                } catch (\Throwable) {
                    return false;
                }
            }));
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($items, $offset, $perPage);

        return [
            'items' => array_values($paged),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    private function path(): string
    {
        $configured = trim((string) config('backup.paths.snapshot_cache_file', 'backup-snapshots/cache.json'), "/\\");
        return storage_path('app' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured));
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->path());
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    private function write(array $payload): void
    {
        $this->ensureDirectory();
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new BackupRuntimeException('Không thể ghi metadata snapshot backup.');
        }

        File::put($this->path(), $encoded . PHP_EOL, true);
    }

    private function defaultPayload(): array
    {
        return [
            'items' => [],
            'refreshed_at' => null,
            'refreshing' => false,
            'refresh_started_at' => null,
            'refresh_run_id' => null,
            'last_refresh_run_id' => null,
            'last_error' => null,
            'last_error_code' => null,
            'last_error_step' => null,
            'last_error_technical_message' => null,
            'last_error_operation' => null,
            'updated_at' => null,
            'context' => $this->contextPayload(),
        ];
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $snapshotId = trim((string) ($item['snapshot_id'] ?? ''));
            if ($snapshotId === '') {
                continue;
            }

            $item['snapshot_id_full'] = trim((string) ($item['snapshot_id_full'] ?? $snapshotId));
            $item['short_id'] = trim((string) ($item['short_id'] ?? '')) !== ''
                ? (string) $item['short_id']
                : substr($snapshotId, 0, 8);
            $normalized[] = $item;
        }

        return array_values($normalized);
    }

    private function contextPayload(): array
    {
        return [
            'app_env' => app()->environment(),
            'repository' => trim((string) config('backup.restic.repository', '')),
        ];
    }

    private function hasContextMismatch(array $payload): bool
    {
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $cachedEnv = trim((string) ($context['app_env'] ?? ''));
        $cachedRepository = trim((string) ($context['repository'] ?? ''));
        $currentEnv = app()->environment();
        $currentRepository = trim((string) config('backup.restic.repository', ''));

        if ($cachedEnv === '') {
            return count((array) ($payload['items'] ?? [])) > 0 || ! empty($payload['refreshed_at']);
        }

        if ($cachedEnv !== $currentEnv) {
            return true;
        }

        if ($cachedRepository === '' && $currentRepository === '') {
            return false;
        }

        if ($cachedRepository === '' || $currentRepository === '') {
            return count((array) ($payload['items'] ?? [])) > 0 || ! empty($payload['refreshed_at']);
        }

        return $cachedRepository !== $currentRepository;
    }

    private function effectiveStaleAfterSeconds(?int $ttlSeconds = null): int
    {
        $configured = $ttlSeconds ?? (int) config('backup.snapshot_cache.stale_after_seconds', 900);
        $configured = max(30, $configured);
        $refreshIntervalMinutes = min(59, max(5, (int) config('backup.snapshot_cache.refresh_interval_minutes', 10)));
        $refreshAwareFloor = ($refreshIntervalMinutes * 60) + 60;

        return max($configured, $refreshAwareFloor);
    }
}
