<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpncBackupSnapshotRefresh extends Command
{
    protected $signature = 'spnc:backup:snapshots:refresh
        {--run-id= : Ma run theo doi refresh}
        {--trigger=manual : Nguon kich hoat}
        {--initiated-by= : user_id kich hoat}';

    protected $description = 'Lam moi cache danh sach snapshot backup tu repository restic.';

    private ResticBackupManager $backupManager;
    private BackupRunStateStore $stateStore;
    private BackupSnapshotStore $snapshotStore;

    public function __construct(
        ResticBackupManager $backupManager,
        BackupRunStateStore $stateStore,
        BackupSnapshotStore $snapshotStore
    ) {
        parent::__construct();
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
        $this->snapshotStore = $snapshotStore;
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;
        $refreshStartedAt = microtime(true);
        $driveProbeDurationSeconds = null;
        $repositoryOpenDurationSeconds = null;
        $snapshotListingDurationSeconds = null;

        try {
            $this->stateStore->assertValidRunId($runId);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $existing = $this->stateStore->get($runId);
        if (! $existing) {
            $this->stateStore->initialize($runId, [
                'status' => 'queued',
                'operation' => 'snapshot_refresh',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'message' => 'Da xep lich lam moi danh sach snapshot.',
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'snapshot_refresh',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
            ]);
        }
        $this->snapshotStore->markRefreshing($runId);

        $lock = Cache::lock('spnc:backup:snapshots:refresh', 1800);
        if (! $lock->get()) {
            $message = 'Dang co mot tien trinh lam moi snapshot khac chay.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'snapshot_refresh',
                'step' => 'failed',
                'message' => $message,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $message,
            ]);
            $this->snapshotStore->markRefreshFailed($message, $runId);
            $this->error($message);
            return self::FAILURE;
        }

        $currentStep = 'probing_drive_root';

        try {
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'probing_drive_root',
                'started_at' => now()->toIso8601String(),
                'message' => 'Dang kiem tra Google Drive backup...',
            ]);

            $this->backupManager->assertDriveReadiness('snapshot_refresh');
            $driveProbeDurationSeconds = round(microtime(true) - $refreshStartedAt, 3);
            $this->snapshotStore->updateHealth([
                'config_valid' => true,
                'drive_reachable' => true,
            ], [
                'last_drive_probe_at' => now()->toIso8601String(),
                'last_drive_probe_duration_seconds' => $driveProbeDurationSeconds,
            ]);

            $currentStep = 'opening_repository';
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'opening_repository',
                'message' => 'Dang mo repository backup...',
            ]);
            $repositoryProbeStartedAt = microtime(true);
            $this->backupManager->assertRepositoryReady('snapshot_refresh');
            $repositoryOpenDurationSeconds = round(microtime(true) - $repositoryProbeStartedAt, 3);
            $this->snapshotStore->updateHealth([
                'config_valid' => true,
                'drive_reachable' => true,
                'repository_openable' => true,
            ], [
                'last_repository_open_at' => now()->toIso8601String(),
                'last_repository_open_duration_seconds' => $repositoryOpenDurationSeconds,
            ]);
            $this->stateStore->appendLog(
                $runId,
                'Repository backup da mo xong sau ' . number_format((float) $repositoryOpenDurationSeconds, 2) . ' giay.',
                'info'
            );

            $limit = max(10, (int) config('backup.snapshot_cache.max_items', 200));
            $currentStep = 'listing_snapshots';
            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'snapshot_refresh',
                'step' => 'listing_snapshots',
                'message' => 'Dang dong bo danh sach ban sao luu...',
            ]);
            $snapshotListingStartedAt = microtime(true);
            $snapshots = $this->backupManager->listSnapshots($limit, true);
            $snapshotListingDurationSeconds = round(microtime(true) - $snapshotListingStartedAt, 3);
            $this->stateStore->appendLog(
                $runId,
                'Da doc ' . count($snapshots) . ' snapshot sau ' . number_format((float) $snapshotListingDurationSeconds, 2) . ' giay.',
                'info'
            );
            $cache = $this->snapshotStore->replace($snapshots, $runId);
            $refreshDurationSeconds = round(microtime(true) - $refreshStartedAt, 3);
            $this->snapshotStore->updateHealth([
                'config_valid' => true,
                'drive_reachable' => true,
                'repository_openable' => true,
                'snapshots_readable' => true,
                'snapshot_cache_fresh' => true,
                'last_successful_refresh_at' => $cache['refreshed_at'] ?? now()->toIso8601String(),
                'last_failure_at' => null,
                'last_failure_code' => null,
                'last_failure_step' => null,
            ], [
                'last_snapshot_refresh_at' => $cache['refreshed_at'] ?? now()->toIso8601String(),
                'last_snapshot_refresh_duration_seconds' => $refreshDurationSeconds,
                'last_snapshot_listing_at' => now()->toIso8601String(),
                'last_snapshot_listing_duration_seconds' => $snapshotListingDurationSeconds,
            ]);

            $this->stateStore->update($runId, [
                'status' => 'success',
                'operation' => 'snapshot_refresh',
                'step' => 'ready',
                'message' => 'Lam moi danh sach snapshot hoan tat.',
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'snapshot_count' => count($snapshots),
                    'refreshed_at' => $cache['refreshed_at'] ?? null,
                ],
            ]);

            $this->info('Snapshot cache refreshed. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $userMessage = $this->toUserFacingFailureMessage($exception);
            $errorCode = $this->detectErrorCode($exception);
            if ($errorCode === 'DRIVE_PROBE_TIMEOUT' && $currentStep === 'opening_repository') {
                $errorCode = 'REPOSITORY_OPEN_TIMEOUT';
            }
            if ($errorCode === 'DRIVE_PROBE_TIMEOUT' && $currentStep === 'listing_snapshots') {
                $errorCode = 'SNAPSHOT_LIST_TIMEOUT';
            }
            $technicalMessage = trim((string) $exception->getMessage());
            $userMessage = $this->refineUserFacingFailureMessage($userMessage, $errorCode, $currentStep);
            $this->snapshotStore->updateHealth(
                $this->buildFailureHealthState($currentStep, $errorCode),
                array_filter([
                    'last_drive_probe_at' => $driveProbeDurationSeconds !== null ? now()->toIso8601String() : null,
                    'last_drive_probe_duration_seconds' => $driveProbeDurationSeconds,
                    'last_repository_open_at' => $repositoryOpenDurationSeconds !== null ? now()->toIso8601String() : null,
                    'last_repository_open_duration_seconds' => $repositoryOpenDurationSeconds,
                    'last_snapshot_listing_at' => $snapshotListingDurationSeconds !== null ? now()->toIso8601String() : null,
                    'last_snapshot_listing_duration_seconds' => $snapshotListingDurationSeconds,
                ], static fn ($value) => $value !== null)
            );
            Log::error('backup.snapshot_refresh_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'message' => $technicalMessage,
                'error_code' => $errorCode,
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'snapshot_refresh',
                'step' => $currentStep,
                'message' => $userMessage,
                'user_message' => $userMessage,
                'error_code' => $errorCode,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $technicalMessage,
                'technical_message' => $technicalMessage,
            ]);
            $this->snapshotStore->markRefreshFailed([
                'operation' => 'snapshot_refresh',
                'step' => $currentStep,
                'user_message' => $userMessage,
                'error_code' => $errorCode,
                'technical_message' => $technicalMessage,
            ], $runId);

            $this->error('Snapshot refresh failed: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }

    private function detectErrorCode(\Throwable $exception): ?string
    {
        $message = Str::lower(trim((string) $exception->getMessage()));
        if ($message === '') {
            return null;
        }

        if (str_contains($message, 'service_account_invalid') || str_contains($message, 'service_account_required')) {
            return 'SERVICE_ACCOUNT_INVALID';
        }
        if (str_contains($message, 'rclone_service_account_required')) {
            return 'RCLONE_SERVICE_ACCOUNT_REQUIRED';
        }
        if (str_contains($message, 'rclone_remote_auth_conflict')) {
            return 'RCLONE_REMOTE_AUTH_CONFLICT';
        }
        if (str_contains($message, 'rclone_remote_auth_invalid')) {
            return 'RCLONE_REMOTE_AUTH_INVALID';
        }
        if (str_contains($message, 'rclone_remote_not_minimal')) {
            return 'RCLONE_REMOTE_NOT_MINIMAL';
        }
        if (str_contains($message, 'drive_remote_inaccessible')) {
            return 'DRIVE_REMOTE_INACCESSIBLE';
        }
        if (str_contains($message, 'snapshot_list_timeout')) {
            return 'SNAPSHOT_LIST_TIMEOUT';
        }
        if (str_contains($message, 'repository_open_timeout')) {
            return 'REPOSITORY_OPEN_TIMEOUT';
        }
        if (str_contains($message, 'repository_not_visible')) {
            return 'REPOSITORY_NOT_VISIBLE';
        }
        if (str_contains($message, 'repository_locked')) {
            return 'REPOSITORY_LOCKED';
        }
        if (str_contains($message, 'repository_access_failed')) {
            return 'REPOSITORY_ACCESS_FAILED';
        }
        if (str_contains($message, 'drive_probe_timeout') || str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'DRIVE_PROBE_TIMEOUT';
        }
        if (str_contains($message, 'invalid_grant') || str_contains($message, 'couldn\'t fetch token')) {
            return 'DRIVE_AUTH_INVALID';
        }

        return null;
    }

    private function toUserFacingFailureMessage(\Throwable $exception): string
    {
        $raw = Str::lower(trim((string) $exception->getMessage()));
        if (str_contains($raw, 'service_account_invalid') || str_contains($raw, 'service_account_required')) {
            return 'Google Drive backup chua san sang vi thong tin xac thuc runtime chua duoc nap dung. Vui long kiem tra cau hinh production.';
        }

        if (str_contains($raw, 'rclone_service_account_required') || str_contains($raw, 'drive_auth_invalid')) {
            return 'Khong the xac thuc Google Drive backup. Hay kiem tra lai remote spnc_gdrive va token OAuth trong rclone.conf.';
        }

        if (str_contains($raw, 'rclone_remote_auth_conflict')) {
            return 'Remote backup dang tron OAuth va service account. Vui long giu duy nhat mot auth mode.';
        }

        if (str_contains($raw, 'rclone_remote_auth_invalid')) {
            return 'Remote backup khong co auth hop le. Vui long kiem tra token OAuth trong rclone.conf hoac runtime config da materialize.';
        }

        if (str_contains($raw, 'drive_remote_inaccessible')) {
            return 'Khong the truy cap thu muc Google Drive backup. Vui long kiem tra root_folder_id, token OAuth va remote spnc_gdrive.';
        }

        if (str_contains($raw, 'repository_access_failed')) {
            return 'Google Drive da truy cap duoc nhung repository backup chua mo duoc. Vui long kiem tra restic repository.';
        }

        if (str_contains($raw, 'repository_not_visible')) {
            return 'Khong thay duoc config repository backup trong Google Drive. Vui long kiem tra path repository va quyen truy cap noi dung repository.';
        }

        if (str_contains($raw, 'repository_locked')) {
            return 'Repository backup dang bi khoa. Vui long kiem tra stale lock truoc khi refresh lai.';
        }

        if (str_contains($raw, 'snapshot_list_timeout')) {
            return 'Khong mo rong duoc danh sach snapshot trong repository backup trong thoi gian cho phep. Vui long thu lai.';
        }

        if (str_contains($raw, 'repository_open_timeout')) {
            return 'Repository backup phan hoi qua cham khi mo qua Google Drive. Vui long thu lai.';
        }

        if (
            str_contains($raw, 'timed out')
            || str_contains($raw, 'timeout')
            || str_contains($raw, 'exceeded the timeout')
            || str_contains($raw, 'qua thoi gian')
        ) {
            return 'Dong bo danh sach bi qua thoi gian. Vui long thu lai.';
        }

        return 'Dong bo danh sach that bai. Vui long thu lai.';
    }

    private function refineUserFacingFailureMessage(string $message, ?string $errorCode, string $step): string
    {
        $normalizedMessage = Str::lower(trim($message));
        $normalizedCode = Str::upper(trim((string) $errorCode));
        $normalizedStep = trim(Str::lower($step));
        $isGenericTimeout = $normalizedMessage === 'dong bo danh sach bi qua thoi gian. vui long thu lai.';
        $isGenericFailure = $normalizedMessage === 'dong bo danh sach that bai. vui long thu lai.';

        if (($normalizedCode === 'REPOSITORY_OPEN_TIMEOUT' || $normalizedStep === 'opening_repository') && ($isGenericTimeout || $isGenericFailure)) {
            return 'Da vao duoc Google Drive nhung repository backup phan hoi qua cham khi mo. Vui long kiem tra hieu nang restic-repo hoac tang timeout opening_repository.';
        }

        if (($normalizedCode === 'SNAPSHOT_LIST_TIMEOUT' || $normalizedStep === 'listing_snapshots') && ($isGenericTimeout || $isGenericFailure)) {
            return 'Repository backup da mo duoc nhung buoc doc danh sach snapshot qua cham. Vui long kiem tra so luong snapshot cu hoac hieu nang repository.';
        }

        if (($normalizedCode === 'DRIVE_PROBE_TIMEOUT' || $normalizedStep === 'probing_drive_root') && ($isGenericTimeout || $isGenericFailure)) {
            return 'Google Drive backup khong phan hoi kip trong buoc kiem tra ban dau. Vui long kiem tra remote spnc_gdrive va ket noi runtime.';
        }

        return $message;
    }

    private function buildFailureHealthState(string $step, ?string $errorCode): array
    {
        $normalizedStep = Str::lower(trim($step));
        $normalizedCode = Str::upper(trim((string) $errorCode));
        $configErrors = [
            'SERVICE_ACCOUNT_INVALID',
            'RCLONE_SERVICE_ACCOUNT_REQUIRED',
            'RCLONE_REMOTE_AUTH_INVALID',
            'RCLONE_REMOTE_AUTH_CONFLICT',
            'RCLONE_REMOTE_NOT_MINIMAL',
            'SERVICE_ACCOUNT_REQUIRED',
            'RCLONE_CONFIG_INVALID',
            'RCLONE_REMOTE_UNDEFINED',
        ];

        $health = [
            'config_valid' => ! in_array($normalizedCode, $configErrors, true),
            'last_failure_at' => now()->toIso8601String(),
            'last_failure_code' => $normalizedCode !== '' ? $normalizedCode : null,
            'last_failure_step' => $normalizedStep !== '' ? $normalizedStep : null,
        ];

        if ($normalizedStep === 'probing_drive_root') {
            $health['drive_reachable'] = false;
        } elseif ($normalizedStep === 'opening_repository') {
            $health['drive_reachable'] = true;
            $health['repository_openable'] = false;
        } elseif ($normalizedStep === 'listing_snapshots') {
            $health['drive_reachable'] = true;
            $health['repository_openable'] = true;
            $health['snapshots_readable'] = false;
        }

        $health['snapshot_cache_fresh'] = ! $this->snapshotStore->isStale();

        return $health;
    }
}
