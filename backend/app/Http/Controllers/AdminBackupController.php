<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupRunLauncher;
use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupRuntimeException;
use App\Services\Backup\ResticBackupManager;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminBackupController extends Controller
{
    private ResticBackupManager $backupManager;
    private BackupRunStateStore $stateStore;
    private BackupRunLauncher $launcher;

    public function __construct(
        ResticBackupManager $backupManager,
        BackupRunStateStore $stateStore,
        BackupRunLauncher $launcher
    ) {
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
        $this->launcher = $launcher;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $limit = (int) ($validated['limit'] ?? 30);
            $runs = $this->stateStore->listRecent(50);
            $schedule = $this->backupManager->buildScheduleMeta();
            $retention = $this->backupManager->buildRetentionMeta();
            $snapshots = [];
            $repositoryError = null;

            try {
                $snapshots = $this->backupManager->listSnapshots($limit);
            } catch (\Throwable $exception) {
                $repositoryError = $exception->getMessage();
            }

            $lastSuccess = collect($runs)->first(function ($run) {
                return ($run['status'] ?? null) === 'success';
            });

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'engine' => 'restic',
                    'repository_configured' => $repositoryError === null,
                    'repository_error' => $repositoryError,
                    'snapshots' => $snapshots,
                    'runs' => $runs,
                    'schedule' => $schedule,
                    'retention' => $retention,
                    'last_successful_backup_at' => $lastSuccess['finished_at'] ?? null,
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.index_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể tải danh sách backup. Vui lòng kiểm tra cấu hình hệ thống.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function run(Request $request)
    {
        $runId = $this->stateStore->generateRunId();
        $user = $request->user();

        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'trigger' => 'manual',
            'requested_by_user_id' => $user ? (int) $user->id : null,
            'requested_at' => now()->toIso8601String(),
            'message' => 'Đã xếp lịch chạy backup.',
        ]);

        $launchMode = 'detached';
        try {
            $this->launcher->launch($runId, (int) ($user?->id ?? 0), 'manual');
        } catch (\Throwable $exception) {
            // Fallback: chạy sync nếu không thể detach ở môi trường hiện tại.
            Log::warning('backup.launch_detached_failed', [
                'run_id' => $runId,
                'message' => $exception->getMessage(),
            ]);
            $launchMode = 'sync';
            Artisan::call('spnc:backup:run', [
                '--run-id' => $runId,
                '--trigger' => 'manual',
                '--initiated-by' => (int) ($user?->id ?? 0),
                '--no-interaction' => true,
            ]);
        }

        $state = $this->stateStore->get($runId);

        AuditLogger::log($request, [
            'action_group' => 'security',
            'action_code' => 'BACKUP_RUN_TRIGGERED',
            'action_label' => 'Kích hoạt sao lưu hệ thống',
            'target_type' => 'backup_run',
            'target_id' => $runId,
            'target_display' => 'Backup run ' . $runId,
            'result_status' => 'success',
            'note' => 'launch_mode=' . $launchMode,
        ], $user);

        return response()->json([
            'success' => true,
            'message' => 'Đã kích hoạt sao lưu thủ công.',
            'data' => [
                'run_id' => $runId,
                'launch_mode' => $launchMode,
                'status' => $state['status'] ?? 'queued',
                'status_url' => '/api/admin/backups/runs/' . $runId,
            ],
        ], $launchMode === 'detached' ? Response::HTTP_ACCEPTED : Response::HTTP_OK);
    }

    public function runStatus(Request $request, string $runId)
    {
        try {
            $this->stateStore->assertValidRunId($runId);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Mã run không hợp lệ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $state = $this->stateStore->get($runId);
        if (! $state) {
            return response()->json([
                'message' => 'Không tìm thấy trạng thái backup run.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $state,
        ], Response::HTTP_OK);
    }

    public function prune(Request $request)
    {
        try {
            $result = $this->backupManager->pruneBackups();

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_PRUNE',
                'action_label' => 'Dọn snapshot backup cũ',
                'target_type' => 'backup_repository',
                'target_display' => 'Kho sao lưu SPNC',
                'result_status' => 'success',
            ], $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Đã dọn snapshot cũ theo chính sách lưu trữ.',
                'data' => $result,
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.prune_api_failed', [
                'message' => $exception->getMessage(),
            ]);
            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_PRUNE_FAILED',
                'action_label' => 'Dọn snapshot backup thất bại',
                'target_type' => 'backup_repository',
                'target_display' => 'Kho sao lưu SPNC',
                'result_status' => 'failure',
                'result_error_message' => $exception->getMessage(),
            ], $request->user());

            return response()->json([
                'message' => 'Không thể dọn snapshot backup. Vui lòng kiểm tra cấu hình hệ thống.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(Request $request, string $snapshotId)
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:db_only,files_only,full'],
            'target' => ['required', 'string', 'in:staging,current'],
            'confirm' => ['required', 'boolean'],
            'confirm_phrase' => ['required', 'string', 'max:100'],
        ]);

        if (! $validated['confirm']) {
            return response()->json([
                'message' => 'Bạn phải xác nhận thao tác khôi phục dữ liệu.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $expectedPhrase = (string) config('backup.restore.confirm_phrase', 'KHOI_PHUC_DU_LIEU');
        if (trim((string) $validated['confirm_phrase']) !== $expectedPhrase) {
            return response()->json([
                'message' => 'Cụm từ xác nhận khôi phục không chính xác.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $restoreRunId = $this->stateStore->generateRunId();
        try {
            $result = $this->backupManager->restoreSnapshot(
                $snapshotId,
                (string) $validated['scope'],
                (string) $validated['target'],
                $restoreRunId
            );

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_RESTORE',
                'action_label' => 'Khôi phục sao lưu hệ thống',
                'target_type' => 'backup_snapshot',
                'target_id' => $snapshotId,
                'target_display' => 'Snapshot ' . $snapshotId,
                'result_status' => 'success',
                'changes' => [
                    'scope' => $validated['scope'],
                    'target' => $validated['target'],
                ],
            ], $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Khôi phục dữ liệu hoàn tất.',
                'data' => $result,
            ], Response::HTTP_OK);
        } catch (BackupRuntimeException $exception) {
            Log::error('backup.restore_failed', [
                'snapshot_id' => $snapshotId,
                'scope' => $validated['scope'],
                'target' => $validated['target'],
                'message' => $exception->getMessage(),
            ]);

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_RESTORE_FAILED',
                'action_label' => 'Khôi phục sao lưu thất bại',
                'target_type' => 'backup_snapshot',
                'target_id' => $snapshotId,
                'target_display' => 'Snapshot ' . $snapshotId,
                'result_status' => 'failure',
                'result_error_message' => $exception->getMessage(),
                'changes' => [
                    'scope' => $validated['scope'],
                    'target' => $validated['target'],
                ],
            ], $request->user());

            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            Log::error('backup.restore_unexpected_failed', [
                'snapshot_id' => $snapshotId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Khôi phục dữ liệu thất bại. Vui lòng kiểm tra log hệ thống.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadManifest(Request $request, string $snapshotId)
    {
        try {
            $download = $this->backupManager->extractManifestFromSnapshot($snapshotId);
            $path = (string) ($download['absolute_path'] ?? '');
            if ($path === '') {
                throw new BackupRuntimeException('Không tìm thấy manifest trong snapshot.');
            }

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_DOWNLOAD_MANIFEST',
                'action_label' => 'Tải manifest backup',
                'target_type' => 'backup_snapshot',
                'target_id' => $snapshotId,
                'target_display' => 'Snapshot ' . $snapshotId,
                'result_status' => 'success',
            ], $request->user());

            return response()->download(
                $path,
                'backup_manifest_' . $snapshotId . '.json',
                ['Content-Type' => 'application/json; charset=UTF-8']
            )->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            Log::error('backup.download_manifest_failed', [
                'snapshot_id' => $snapshotId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể tải manifest backup.',
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function downloadDatabaseDump(Request $request, string $snapshotId)
    {
        try {
            $download = $this->backupManager->extractDatabaseDumpFromSnapshot($snapshotId);
            $path = (string) ($download['absolute_path'] ?? '');
            if ($path === '') {
                throw new BackupRuntimeException('Không tìm thấy DB dump trong snapshot.');
            }

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_DOWNLOAD_DB_DUMP',
                'action_label' => 'Tải DB dump backup',
                'target_type' => 'backup_snapshot',
                'target_id' => $snapshotId,
                'target_display' => 'Snapshot ' . $snapshotId,
                'result_status' => 'success',
            ], $request->user());

            return response()->download(
                $path,
                'backup_database_' . $snapshotId . '.sql',
                ['Content-Type' => 'application/sql; charset=UTF-8']
            )->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            Log::error('backup.download_db_dump_failed', [
                'snapshot_id' => $snapshotId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể tải DB dump backup.',
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
