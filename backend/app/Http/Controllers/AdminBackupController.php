<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupRunLauncher;
use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupRuntimeException;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminBackupController extends Controller
{
    private ResticBackupManager $backupManager;
    private BackupRunStateStore $stateStore;
    private BackupRunLauncher $launcher;
    private BackupSnapshotStore $snapshotStore;

    public function __construct(
        ResticBackupManager $backupManager,
        BackupRunStateStore $stateStore,
        BackupRunLauncher $launcher,
        BackupSnapshotStore $snapshotStore
    ) {
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
        $this->launcher = $launcher;
        $this->snapshotStore = $snapshotStore;
    }

    public function index(Request $request)
    {
        $startedAt = microtime(true);
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'in:queued,running,success,failed,unknown'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            $filters = [
                'page' => (int) ($validated['page'] ?? 1),
                'per_page' => (int) ($validated['per_page'] ?? 20),
                'status' => $validated['status'] ?? null,
                'from' => ! empty($validated['from'])
                    ? Carbon::parse((string) $validated['from'])->startOfDay()->toIso8601String()
                    : null,
                'to' => ! empty($validated['to'])
                    ? Carbon::parse((string) $validated['to'])->endOfDay()->toIso8601String()
                    : null,
            ];

            $list = $this->snapshotStore->filterAndPaginate($filters);
            $cacheMeta = $this->snapshotStore->cacheMeta();
            $systemActiveRun = $this->stateStore->latestActive([
                'backup',
                'backup_postprocess',
                'prune',
                'restore',
                'snapshot_refresh',
                'forget',
            ]);
            $parentRunCache = [];
            $postProcessRunCache = [];
            $list['items'] = array_values(array_map(
                fn (array $item): array => $this->decorateSnapshotWithExportState($item, $parentRunCache, $postProcessRunCache),
                array_values((array) ($list['items'] ?? []))
            ));
            $activeRun = $this->toUiActiveRun($systemActiveRun);
            $schedule = $this->backupManager->buildScheduleMeta();
            $scheduleRuntime = $this->latestScheduleRunSummary();
            $retention = $this->backupManager->buildRetentionMeta();
            $readiness = $this->backupManager->buildReadinessReport();
            $exportOverview = $this->backupManager->buildExportOverview();
            $lastSuccess = $this->stateStore->latestSuccessfulBackup();

            $refreshRequested = (bool) ($validated['refresh'] ?? false);
            $refreshRun = null;

            if ($refreshRequested) {
                $refreshRun = $this->startSnapshotRefresh($request, 'manual_refresh');
                if ($refreshRun !== null) {
                    $cacheMeta = $this->snapshotStore->cacheMeta();
                }
            }

            $cacheRefreshState = null;
            $cacheRefreshRunId = trim((string) ($cacheMeta['refresh_run_id'] ?? ''));
            if ($cacheRefreshRunId !== '') {
                $cacheRefreshState = $this->stateStore->get($cacheRefreshRunId);
            }
            $cacheRefreshOperation = Str::lower(trim((string) ($cacheRefreshState['operation'] ?? '')));
            $cacheRefreshStatus = Str::lower(trim((string) ($cacheRefreshState['status'] ?? '')));
            $cacheRefreshTrigger = Str::lower(trim((string) ($cacheRefreshState['trigger'] ?? '')));

            if ((bool) ($cacheMeta['refreshing'] ?? false)) {
                $isActiveSnapshotRefresh = $cacheRefreshOperation === 'snapshot_refresh'
                    && in_array($cacheRefreshStatus, ['queued', 'running'], true);

                if (! $isActiveSnapshotRefresh) {
                    if ($cacheRefreshOperation === 'snapshot_refresh' && $cacheRefreshStatus === 'failed') {
                        $refreshErrorMessage = trim((string) (
                            $cacheRefreshState['message']
                            ?? $cacheRefreshState['error_message']
                            ?? ''
                        ));
                        $refreshError = $this->toPublicErrorPayload($refreshErrorMessage, 'snapshot_refresh', 'failed');
                        $cacheMeta = $this->snapshotStore->markRefreshFailed(
                            $refreshError['user_message'] ?? 'Đồng bộ danh sách snapshot thất bại. Vui lòng thử lại.',
                            $cacheRefreshRunId !== '' ? $cacheRefreshRunId : null
                        );
                    } else {
                        $cacheMeta = $this->snapshotStore->markIdle();
                    }

                    $cacheRefreshState = null;
                    $cacheRefreshRunId = trim((string) ($cacheMeta['refresh_run_id'] ?? ''));
                    $cacheRefreshOperation = '';
                    $cacheRefreshStatus = '';
                    $cacheRefreshTrigger = '';
                    if ($cacheRefreshRunId !== '') {
                        $cacheRefreshState = $this->stateStore->get($cacheRefreshRunId);
                        $cacheRefreshOperation = Str::lower(trim((string) ($cacheRefreshState['operation'] ?? '')));
                        $cacheRefreshStatus = Str::lower(trim((string) ($cacheRefreshState['status'] ?? '')));
                        $cacheRefreshTrigger = Str::lower(trim((string) ($cacheRefreshState['trigger'] ?? '')));
                    }
                }
            }

            if (! $activeRun && ! empty($refreshRun['run_id'])) {
                $activeRun = [
                    'run_id' => $refreshRun['run_id'],
                    'operation' => 'snapshot_refresh',
                    'status' => 'queued',
                    'message' => 'Đã xếp lịch làm mới danh sách snapshot.',
                ];
            }

            if (! $activeRun && ! empty($cacheMeta['refresh_run_id'])) {
                $refreshState = $cacheRefreshState ?: $this->stateStore->get((string) $cacheMeta['refresh_run_id']);
                if (is_array($refreshState)) {
                    $candidateRun = [
                        'run_id' => $refreshState['run_id'] ?? null,
                        'operation' => $refreshState['operation'] ?? 'snapshot_refresh',
                        'status' => $refreshState['status'] ?? null,
                        'trigger' => $refreshState['trigger'] ?? null,
                        'step' => $refreshState['step'] ?? null,
                        'message' => $refreshState['message'] ?? null,
                        'requested_by_user_id' => $refreshState['requested_by_user_id'] ?? null,
                        'requested_at' => $refreshState['requested_at'] ?? null,
                        'started_at' => $refreshState['started_at'] ?? null,
                        'finished_at' => $refreshState['finished_at'] ?? null,
                        'error_message' => $refreshState['error_message'] ?? null,
                        'snapshot_id' => $refreshState['snapshot_id'] ?? null,
                    ];
                    $activeRun = $this->toUiActiveRun($candidateRun);
                }
            }

            $syncStatusSource = $systemActiveRun ?? $activeRun;
            $activeOperation = Str::lower(trim((string) ($syncStatusSource['operation'] ?? '')));
            $activeStatus = Str::lower(trim((string) ($syncStatusSource['status'] ?? '')));
            $isSyncing = (
                    (bool) ($cacheMeta['refreshing'] ?? false)
                    && $cacheRefreshOperation === 'snapshot_refresh'
                    && in_array($cacheRefreshStatus, ['queued', 'running'], true)
                )
                || $refreshRun !== null
                || (
                    $activeOperation === 'snapshot_refresh'
                    && in_array($activeStatus, ['queued', 'running'], true)
                );

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $activeRun = $this->toPublicRunState($activeRun);
            $systemActiveRun = $this->toPublicRunState($systemActiveRun);
            $cacheLastError = $this->toPublicErrorPayload(
                (string) ($cacheMeta['last_error'] ?? ''),
                'snapshot_refresh',
                'failed'
            );
            $readinessIssue = $this->primaryReadinessIssue($readiness);
            $repositoryError = $cacheLastError['user_message']
                ?? ($readinessIssue['message'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'engine' => 'restic',
                    'items' => $list['items'],
                    'snapshots' => $list['items'], // compatibility
                    'pagination' => $list['pagination'],
                    'is_syncing' => $isSyncing,
                    'last_sync_at' => $cacheMeta['refreshed_at'] ?? null,
                    'active_run' => $activeRun,
                    'system_active_run' => $systemActiveRun,
                    'runs' => $activeRun ? [$activeRun] : [],
                    'cache' => array_merge($cacheMeta, [
                        'last_error' => $cacheLastError['user_message'],
                        'last_error_code' => $cacheLastError['error_code'],
                        'refresh_queued' => $refreshRun !== null,
                        'refresh_run_id' => $refreshRun['run_id'] ?? ($cacheMeta['refresh_run_id'] ?? null),
                        'refresh_operation' => $cacheRefreshOperation !== '' ? $cacheRefreshOperation : null,
                        'refresh_status' => $cacheRefreshStatus !== '' ? $cacheRefreshStatus : null,
                        'refresh_trigger' => $cacheRefreshTrigger !== '' ? $cacheRefreshTrigger : null,
                    ]),
                    'schedule' => $schedule,
                    'schedule_runtime' => $scheduleRuntime,
                    'retention' => $retention,
                    'export_overview' => $exportOverview,
                    'friendly_messages' => [
                        'safe' => 'Hệ thống đã sao lưu an toàn.',
                        'drive' => 'Bạn có thể mở thư mục Backup trên Google Drive để xem bản sao lưu dễ đọc.',
                        'restore' => 'Khi cần khôi phục, vui lòng dùng chức năng Khôi phục trong hệ thống.',
                    ],
                    'last_successful_backup_at' => $lastSuccess['finished_at'] ?? null,
                    'repository_configured' => (bool) ($readiness['ready_for_operations'] ?? false),
                    'repository_env_configured' => (bool) ($readiness['repository_env_configured'] ?? false),
                    'runtime_ready' => (bool) ($readiness['runtime_ready'] ?? false),
                    'repository_error' => $repositoryError,
                    'repository_error_code' => $cacheLastError['error_code']
                        ?? ($readinessIssue['code'] ?? null),
                    'runtime_readiness' => $readiness,
                    'performance' => [
                        'served_in_ms' => $durationMs,
                    ],
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.index_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể tải danh sách backup. Vui lòng thử lại sau.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function exportsInfo(Request $request)
    {
        try {
            $readiness = $this->backupManager->buildReadinessReport();
            $overview = $this->backupManager->buildExportOverview();
            $openUrl = trim((string) config('backup.exports.open_url', ''));
            $readinessIssue = $this->primaryReadinessIssue($readiness);

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => [
                    'exports_root_path' => $overview['export_root'] ?? null,
                    'exports_drive_path' => $overview['export_root'] ?? null,
                    'exports_folder_name' => $overview['export_folder_name'] ?? 'exports',
                    'repository_type' => $overview['repository_type'] ?? null,
                    'available' => (bool) ($overview['available'] ?? false),
                    'error_code' => $overview['error_code'] ?? ($readinessIssue['code'] ?? null),
                    'error_message' => $overview['error_message'] ?? ($readinessIssue['message'] ?? null),
                    'repository_configured' => (bool) ($readiness['ready_for_operations'] ?? false),
                    'repository_env_configured' => (bool) ($readiness['repository_env_configured'] ?? false),
                    'runtime_ready' => (bool) ($readiness['runtime_ready'] ?? false),
                    'runtime_readiness' => $readiness,
                    'open_url' => $openUrl !== '' ? $openUrl : null,
                    'note' => $overview['note'] ?? null,
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.exports_info_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể tải thông tin thư mục exports backup.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function run(Request $request)
    {
        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'prune', 'forget', 'restore']);
        if ($conflict !== null) {
            return $conflict;
        }

        $runId = $this->stateStore->generateRunId();
        $user = $request->user();

        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'backup',
            'trigger' => 'manual',
            'requested_by_user_id' => $user ? (int) $user->id : null,
            'requested_at' => now()->toIso8601String(),
            'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            'message' => 'Đã xếp lịch chạy backup.',
        ]);

        try {
            $this->launcher->launchBackup($runId, (int) ($user?->id ?? 0), 'manual');
        } catch (\Throwable $exception) {
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup',
                'step' => 'failed',
                'message' => 'Không thể khởi chạy tiến trình backup nền.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Không thể khởi chạy tác vụ nền: ' . $exception->getMessage(), 'error');

            Log::error('backup.launch_failed', [
                'run_id' => $runId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể khởi chạy backup nền. Vui lòng kiểm tra cấu hình máy chủ.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        AuditLogger::log($request, [
            'action_group' => 'security',
            'action_code' => 'BACKUP_RUN_TRIGGERED',
            'action_label' => 'Kích hoạt sao lưu hệ thống',
            'target_type' => 'backup_run',
            'target_id' => $runId,
            'target_display' => 'Backup run ' . $runId,
            'result_status' => 'success',
        ], $user);

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận yêu cầu sao lưu.',
            'data' => [
                'run_id' => $runId,
                'operation' => 'backup',
                'status' => 'queued',
                'user_message' => 'Đã tiếp nhận yêu cầu sao lưu.',
                'error_code' => null,
                'status_url' => '/api/admin/backups/runs/' . $runId,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    public function refresh(Request $request)
    {
        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'prune', 'forget', 'restore']);
        if ($conflict !== null) {
            return $conflict;
        }

        $run = $this->startSnapshotRefresh($request, 'manual_refresh');
        if ($run === null) {
            return response()->json([
                'success' => true,
                'message' => 'Danh sách bản sao lưu đang được làm mới.',
                'data' => [
                    'run_id' => $this->snapshotStore->cacheMeta()['refresh_run_id'] ?? null,
                    'status' => 'queued',
                    'user_message' => 'Danh sách bản sao lưu đang được làm mới.',
                    'error_code' => null,
                    'status_url' => null,
                ],
            ], Response::HTTP_ACCEPTED);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận yêu cầu làm mới danh sách.',
            'data' => [
                'run_id' => $run['run_id'],
                'operation' => 'snapshot_refresh',
                'status' => 'queued',
                'user_message' => 'Đã tiếp nhận yêu cầu làm mới danh sách.',
                'error_code' => null,
                'status_url' => '/api/admin/backups/runs/' . $run['run_id'],
            ],
        ], Response::HTTP_ACCEPTED);
    }

    public function doctor(Request $request)
    {
        $validated = $request->validate([
            'snapshot_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $snapshotLimit = (int) ($validated['snapshot_limit'] ?? 10);
            $report = $this->backupManager->buildDoctorReport($snapshotLimit);

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.doctor_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể thu thập chẩn đoán backup. Vui lòng kiểm tra log hệ thống.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function runStatus(Request $request, string $runId)
    {
        $validated = $request->validate([
            'include_technical' => ['nullable', 'boolean'],
        ]);
        $includeTechnical = (bool) ($validated['include_technical'] ?? false);

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
            'data' => $this->toPublicRunState($state, $includeTechnical),
        ], Response::HTTP_OK);
    }

    public function show(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
            $snapshotHint = $this->snapshotStore->findBySnapshotId($snapshotId);
            $detail = $this->backupManager->getSnapshotDetails($snapshotId, $snapshotHint);
            $detail = $this->decorateBackupDetailWithExportState($detail);

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $detail,
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            $normalized = Str::lower((string) $exception->getMessage());
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            if (
                $exception instanceof BackupRuntimeException
                && (
                    str_contains($normalized, 'không tìm thấy snapshot')
                    || str_contains($normalized, 'khong tim thay snapshot')
                )
            ) {
                $status = Response::HTTP_NOT_FOUND;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Không thể tải chi tiết bản sao lưu.',
            ], $status);
        }
    }
    public function prune(Request $request)
    {
        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'prune', 'forget', 'restore']);
        if ($conflict !== null) {
            return $conflict;
        }

        $runId = $this->stateStore->generateRunId();
        $user = $request->user();

        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'prune',
            'trigger' => 'manual',
            'requested_by_user_id' => $user ? (int) $user->id : null,
            'requested_at' => now()->toIso8601String(),
            'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            'message' => 'Đã xếp lịch dọn snapshot cũ.',
        ]);

        try {
            $this->launcher->launchPrune($runId, (int) ($user?->id ?? 0), 'manual');
        } catch (\Throwable $exception) {
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'prune',
                'step' => 'failed',
                'message' => 'Không thể khởi chạy tiến trình prune nền.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Không thể khởi chạy tác vụ nền: ' . $exception->getMessage(), 'error');

            Log::error('backup.prune_api_launch_failed', [
                'run_id' => $runId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể khởi chạy dọn snapshot nền. Vui lòng thử lại sau.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        AuditLogger::log($request, [
            'action_group' => 'security',
            'action_code' => 'BACKUP_PRUNE_TRIGGERED',
            'action_label' => 'Kích hoạt dọn snapshot backup',
            'target_type' => 'backup_run',
            'target_id' => $runId,
            'target_display' => 'Prune run ' . $runId,
            'result_status' => 'success',
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Đã kích hoạt dọn snapshot cũ.',
            'data' => [
                'run_id' => $runId,
                'operation' => 'prune',
                'status' => 'queued',
                'user_message' => 'Đã tiếp nhận yêu cầu dọn bản sao lưu cũ.',
                'error_code' => null,
                'status_url' => '/api/admin/backups/runs/' . $runId,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    public function restore(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Mã snapshot không hợp lệ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'prune', 'forget', 'restore']);
        if ($conflict !== null) {
            return $conflict;
        }

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

        $expectedPhrase = (string) config('backup.restore.confirm_phrase', 'RESTORE');
        $legacyPhrase = (string) config('backup.restore.legacy_confirm_phrase', 'KHOI_PHUC_DU_LIEU');
        $providedPhrase = trim((string) $validated['confirm_phrase']);
        if ($providedPhrase !== $expectedPhrase && $providedPhrase !== $legacyPhrase) {
            return response()->json([
                'message' => 'Cụm từ xác nhận khôi phục không chính xác.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $runId = $this->stateStore->generateRunId();
        $user = $request->user();
        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'restore',
            'trigger' => 'manual',
            'requested_by_user_id' => $user ? (int) $user->id : null,
            'requested_at' => now()->toIso8601String(),
            'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            'snapshot_id' => $snapshotId,
            'scope' => (string) $validated['scope'],
            'target' => (string) $validated['target'],
            'message' => 'Đã xếp lịch khôi phục dữ liệu.',
        ]);

        try {
            $this->launcher->launchRestore(
                $runId,
                (int) ($user?->id ?? 0),
                $snapshotId,
                (string) $validated['scope'],
                (string) $validated['target'],
                'manual'
            );
        } catch (\Throwable $exception) {
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'restore',
                'step' => 'failed',
                'message' => 'Không thể khởi chạy tiến trình restore nền.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Không thể khởi chạy tác vụ nền: ' . $exception->getMessage(), 'error');

            Log::error('backup.restore_launch_failed', [
                'run_id' => $runId,
                'snapshot_id' => $snapshotId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể khởi chạy khôi phục nền. Vui lòng thử lại sau.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        AuditLogger::log($request, [
            'action_group' => 'security',
            'action_code' => 'BACKUP_RESTORE_TRIGGERED',
            'action_label' => 'Kích hoạt khôi phục sao lưu hệ thống',
            'target_type' => 'backup_snapshot',
            'target_id' => $snapshotId,
            'target_display' => 'Snapshot ' . $snapshotId,
            'result_status' => 'success',
            'changes' => [
                'scope' => $validated['scope'],
                'target' => $validated['target'],
                'run_id' => $runId,
            ],
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận yêu cầu khôi phục dữ liệu.',
            'data' => [
                'run_id' => $runId,
                'operation' => 'restore',
                'status' => 'queued',
                'user_message' => 'Đã tiếp nhận yêu cầu khôi phục dữ liệu.',
                'error_code' => null,
                'status_url' => '/api/admin/backups/runs/' . $runId,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    public function downloadManifest(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
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
            return $this->toDownloadErrorResponse(
                'backup.download_manifest_failed',
                $snapshotId,
                $exception,
                'Không thể tải manifest backup.'
            );
        }
    }

    public function downloadDatabaseDump(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
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
            return $this->toDownloadErrorResponse(
                'backup.download_db_dump_failed',
                $snapshotId,
                $exception,
                'Không thể tải DB dump backup.'
            );
        }
    }

    public function exportMetadata(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
            $data = $this->backupManager->getSnapshotExportMetadata($snapshotId);

            return response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            $normalized = Str::lower((string) $exception->getMessage());
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            if (
                $exception instanceof BackupRuntimeException
                && (
                    str_contains($normalized, 'không tìm thấy snapshot')
                    || str_contains($normalized, 'khong tim thay snapshot')
                    || str_contains($normalized, 'chưa có export')
                    || str_contains($normalized, 'chua co export')
                )
            ) {
                $status = Response::HTTP_NOT_FOUND;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Không thể lấy thông tin export backup.',
            ], $status);
        }
    }

    public function downloadExport(Request $request, string $snapshotId)
    {
        try {
            $this->backupManager->assertValidSnapshotId($snapshotId);
            $download = $this->backupManager->prepareExportBundleDownload($snapshotId);
            $path = (string) ($download['absolute_path'] ?? '');
            if ($path === '') {
                throw new BackupRuntimeException('Không tìm thấy tệp export.');
            }

            $filename = (string) ($download['filename'] ?? ('backup_export_' . $snapshotId . '.zip'));
            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_DOWNLOAD_EXPORT',
                'action_label' => 'Tải gói export backup',
                'target_type' => 'backup_snapshot',
                'target_id' => $snapshotId,
                'target_display' => 'Snapshot ' . $snapshotId,
                'result_status' => 'success',
            ], $request->user());

            return response()->download(
                $path,
                $filename,
                ['Content-Type' => 'application/zip']
            );
        } catch (\Throwable $exception) {
            return $this->toDownloadErrorResponse(
                'backup.download_export_failed',
                $snapshotId,
                $exception,
                'Không thể tải gói export backup.'
            );
        }
    }

    public function forget(Request $request)
    {
        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'backup_postprocess', 'prune', 'forget', 'restore', 'snapshot_refresh']);
        if ($conflict !== null) {
            return $conflict;
        }

        $validated = $request->validate([
            'snapshot_ids' => ['required', 'array', 'min:1', 'max:100'],
            'snapshot_ids.*' => ['required', 'string', 'regex:/^[A-Fa-f0-9]{6,64}$/'],
            'prune_after' => ['nullable', 'boolean'],
        ]);

        $snapshotIds = array_values(array_unique(array_map(
            static fn ($id): string => Str::lower(trim((string) $id)),
            (array) ($validated['snapshot_ids'] ?? [])
        )));
        $pruneAfter = (bool) ($validated['prune_after'] ?? false);

        if ($pruneAfter) {
            return response()->json([
                'message' => 'Xóa snapshot chỉ hỗ trợ forget. Việc dọn dung lượng được hệ thống xử lý riêng theo bảo trì.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($snapshotIds === []) {
            return response()->json([
                'message' => 'Danh sách snapshot cần xóa không hợp lệ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $runId = $this->stateStore->generateRunId();
        $user = $request->user();
        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'forget',
            'trigger' => 'manual',
            'requested_by_user_id' => $user ? (int) $user->id : null,
            'requested_at' => now()->toIso8601String(),
            'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            'snapshot_ids' => $snapshotIds,
            'message' => 'Đã xếp lịch xóa snapshot đã chọn.',
        ]);

        try {
            $this->launcher->launchForget($runId, (int) ($user?->id ?? 0), $snapshotIds, 'manual');

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_SNAPSHOT_FORGET',
                'action_label' => 'Xóa snapshot backup theo lựa chọn',
                'target_type' => 'backup_snapshot',
                'target_id' => implode(',', $snapshotIds),
                'target_display' => 'Snapshots ' . implode(', ', $snapshotIds),
                'result_status' => 'success',
                'changes' => [
                    'queued' => true,
                    'snapshot_count' => count($snapshotIds),
                ],
            ], $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Đã tiếp nhận yêu cầu xóa bản sao lưu.',
                'data' => [
                    'accepted' => true,
                    'run_id' => $runId,
                    'operation' => 'forget',
                    'status' => 'queued',
                    'user_message' => 'Đã tiếp nhận yêu cầu xóa bản sao lưu.',
                    'error_code' => null,
                    'status_url' => '/api/admin/backups/runs/' . $runId,
                    'snapshot_ids' => $snapshotIds,
                ],
            ], Response::HTTP_ACCEPTED);
        } catch (\Throwable $exception) {
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'forget',
                'step' => 'failed',
                'message' => 'Không thể khởi chạy tiến trình xóa snapshot nền.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Không thể khởi chạy tác vụ nền: ' . $exception->getMessage(), 'error');

            Log::error('backup.forget_launch_failed', [
                'run_id' => $runId,
                'snapshot_ids' => $snapshotIds,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể khởi chạy xóa snapshot nền. Vui lòng thử lại sau.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function unlockStaleLock(Request $request)
    {
        $conflict = $this->rejectWhenConflictingRunActive(['backup', 'prune', 'forget', 'restore']);
        if ($conflict !== null) {
            return $conflict;
        }

        try {
            $result = $this->backupManager->unlockStaleRepositoryLocks();
            $this->snapshotStore->markIdle();

            AuditLogger::log($request, [
                'action_group' => 'security',
                'action_code' => 'BACKUP_UNLOCK_STALE_LOCK',
                'action_label' => 'Gỡ khóa stale repository backup',
                'target_type' => 'backup_repository',
                'target_id' => 'restic',
                'target_display' => 'Restic repository',
                'result_status' => 'success',
            ], $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu gỡ khóa sao lưu.',
                'data' => [
                    'unlocked' => true,
                    'user_message' => 'Đã gửi yêu cầu gỡ khóa sao lưu.',
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            Log::error('backup.unlock_stale_lock_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Không thể gỡ khóa sao lưu lúc này. Vui lòng thử lại.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private function rejectWhenConflictingRunActive(array $operations): ?\Illuminate\Http\JsonResponse
    {
        $activeRun = $this->stateStore->latestActive($operations);
        if (! is_array($activeRun)) {
            return null;
        }

        $operation = Str::lower(trim((string) ($activeRun['operation'] ?? '')));
        $message = $this->conflictMessageForOperation($operation);

        return response()->json([
            'message' => $message,
            'data' => [
                'conflict_run' => $this->summarizeRunForConflict($activeRun),
            ],
        ], Response::HTTP_CONFLICT);
    }

    private function conflictMessageForOperation(string $operation): string
    {
        return match ($operation) {
            'backup' => 'Đang có một bản sao lưu đang chạy. Vui lòng đợi hoàn tất.',
            'backup_postprocess' => 'Snapshot đã sao lưu an toàn, nhưng readable export vẫn đang xử lý ở nền. Vui lòng đợi hoàn tất rồi mới xóa snapshot.',
            'prune' => 'Đang có tiến trình dọn bản sao lưu cũ. Vui lòng đợi hoàn tất.',
            'forget' => 'Đang có tiến trình xóa snapshot đang chạy. Vui lòng đợi hoàn tất.',
            'restore' => 'Đang có tiến trình khôi phục dữ liệu đang chạy. Vui lòng đợi hoàn tất.',
            'snapshot_refresh' => 'Đang có tiến trình làm mới danh sách snapshot. Vui lòng đợi hoàn tất.',
            default => 'Đang có tiến trình nền đang chạy. Vui lòng đợi hoàn tất.',
        };
    }

    private function summarizeRunForConflict(array $run): array
    {
        $safeRun = $this->toPublicRunState($run);
        return [
            'run_id' => $safeRun['run_id'] ?? null,
            'operation' => $safeRun['operation'] ?? null,
            'status' => $safeRun['status'] ?? null,
            'message' => $safeRun['message'] ?? null,
            'user_message' => $safeRun['user_message'] ?? null,
            'error_code' => $safeRun['error_code'] ?? null,
            'trigger' => $safeRun['trigger'] ?? null,
            'requested_at' => $safeRun['requested_at'] ?? null,
            'started_at' => $safeRun['started_at'] ?? null,
        ];
    }

    private function latestScheduleRunSummary(): array
    {
        foreach ($this->stateStore->listRecent(240) as $run) {
            if (! is_array($run)) {
                continue;
            }

            $operation = Str::lower(trim((string) ($run['operation'] ?? '')));
            $trigger = Str::lower(trim((string) ($run['trigger'] ?? '')));
            if ($operation !== 'backup' || $trigger !== 'schedule') {
                continue;
            }

            return [
                'last_run_id' => $run['run_id'] ?? null,
                'last_run_status' => $run['status'] ?? null,
                'last_run_at' => $run['started_at'] ?? $run['requested_at'] ?? $run['updated_at'] ?? null,
            ];
        }

        return [
            'last_run_id' => null,
            'last_run_status' => null,
            'last_run_at' => null,
        ];
    }

    private function decorateBackupDetailWithExportState(array $detail): array
    {
        $snapshot = is_array($detail['snapshot'] ?? null) ? $detail['snapshot'] : [];
        $parentRunCache = [];
        $postProcessRunCache = [];
        $snapshot = $this->decorateSnapshotWithExportState($snapshot, $parentRunCache, $postProcessRunCache);
        $detail['snapshot'] = $snapshot;

        $detail['export'] = array_merge(
            is_array($detail['export'] ?? null) ? $detail['export'] : [],
            [
                'state' => $snapshot['export_state'] ?? null,
                'message' => $snapshot['export_message'] ?? null,
                'run' => $snapshot['export_run'] ?? null,
            ]
        );

        return $detail;
    }

    private function decorateSnapshotWithExportState(
        array $snapshot,
        array &$parentRunCache = [],
        array &$postProcessRunCache = []
    ): array {
        $postProcessRun = $this->resolvePostProcessRunForSnapshot($snapshot, $parentRunCache, $postProcessRunCache);
        $exportState = $this->resolveExportStatePayload($snapshot, $postProcessRun);

        return array_merge($snapshot, [
            'export_state' => $exportState['state'],
            'export_message' => $exportState['message'],
            'export_run' => $exportState['run'],
        ]);
    }

    private function resolvePostProcessRunForSnapshot(
        array $snapshot,
        array &$parentRunCache = [],
        array &$postProcessRunCache = []
    ): ?array {
        $parentRunId = trim((string) ($snapshot['run_id'] ?? ''));
        if ($parentRunId === '') {
            return null;
        }

        if (! array_key_exists($parentRunId, $parentRunCache)) {
            $parentRunCache[$parentRunId] = $this->stateStore->get($parentRunId);
        }

        $parentRun = $parentRunCache[$parentRunId] ?? null;
        if (! is_array($parentRun)) {
            return null;
        }

        $postProcessRunId = trim((string) (($parentRun['result']['export']['run_id'] ?? null) ?: ''));
        if ($postProcessRunId === '') {
            return null;
        }

        if (! array_key_exists($postProcessRunId, $postProcessRunCache)) {
            $postProcessRunCache[$postProcessRunId] = $this->stateStore->get($postProcessRunId);
        }

        $postProcessRun = $postProcessRunCache[$postProcessRunId] ?? null;
        if (! is_array($postProcessRun)) {
            return null;
        }

        return Str::lower(trim((string) ($postProcessRun['operation'] ?? ''))) === 'backup_postprocess'
            ? $postProcessRun
            : null;
    }

    private function resolveExportStatePayload(array $snapshot, ?array $postProcessRun): array
    {
        $publicPostProcessRun = $this->toPublicRunState($postProcessRun);
        $safeStatus = Str::lower(trim((string) ($snapshot['status'] ?? $snapshot['run_state'] ?? '')));
        $exportAvailable = (bool) ($snapshot['export_available'] ?? false);
        $exportsEnabled = (bool) config('backup.exports.enabled', true);

        if ($exportAvailable) {
            return [
                'state' => 'ready',
                'message' => 'Readable export đã sẵn sàng để tải và mở trên Drive.',
                'run' => $publicPostProcessRun,
            ];
        }

        $postProcessStatus = Str::lower(trim((string) ($postProcessRun['status'] ?? '')));
        if ($postProcessStatus === 'queued') {
            return [
                'state' => 'queued',
                'message' => $publicPostProcessRun['user_message']
                    ?? 'Snapshot an toàn đã xong. Readable export đang chờ được xử lý.',
                'run' => $publicPostProcessRun,
            ];
        }

        if ($postProcessStatus === 'running') {
            return [
                'state' => 'running',
                'message' => $publicPostProcessRun['user_message']
                    ?? 'Snapshot an toàn đã xong. Readable export đang được tạo ở nền.',
                'run' => $publicPostProcessRun,
            ];
        }

        if ($postProcessStatus === 'failed') {
            return [
                'state' => 'failed',
                'message' => $publicPostProcessRun['user_message']
                    ?? 'Snapshot an toàn đã hoàn tất nhưng readable export chưa thể hoàn thiện.',
                'run' => $publicPostProcessRun,
            ];
        }

        if ($postProcessStatus === 'success') {
            return [
                'state' => 'finalizing',
                'message' => 'Readable export đã xử lý xong, đang chờ công bố đầy đủ trên giao diện.',
                'run' => $publicPostProcessRun,
            ];
        }

        if ($safeStatus === 'success' && $exportsEnabled) {
            return [
                'state' => 'pending',
                'message' => 'Snapshot an toàn đã hoàn tất. Readable export sẽ tiếp tục được xử lý ở nền.',
                'run' => $publicPostProcessRun,
            ];
        }

        if (! $exportsEnabled) {
            return [
                'state' => 'disabled',
                'message' => 'Readable export đang tắt theo cấu hình hệ thống.',
                'run' => $publicPostProcessRun,
            ];
        }

        return [
            'state' => 'unavailable',
            'message' => 'Readable export chưa sẵn sàng.',
            'run' => $publicPostProcessRun,
        ];
    }

    private function toUiActiveRun(?array $run): ?array
    {
        if (! is_array($run)) {
            return null;
        }

        $status = Str::lower(trim((string) ($run['status'] ?? '')));
        if (! in_array($status, ['queued', 'running'], true)) {
            return null;
        }

        $trigger = Str::lower(trim((string) ($run['trigger'] ?? '')));
        if ($trigger === 'schedule') {
            return null;
        }

        return $this->toPublicRunState($run);
    }

    private function toPublicRunState(?array $run, bool $includeTechnical = false): ?array
    {
        if (! is_array($run)) {
            return null;
        }

        $operation = Str::lower(trim((string) ($run['operation'] ?? '')));
        $status = Str::lower(trim((string) ($run['status'] ?? '')));
        $rawMessage = trim((string) ($run['message'] ?? ''));
        $rawError = trim((string) ($run['error_message'] ?? ''));
        $errorCode = $this->detectErrorCode($rawMessage, $rawError);
        $userMessage = $this->buildUserMessage($operation, $status, $errorCode, $rawMessage);

        $payload = [
            'run_id' => $run['run_id'] ?? null,
            'operation' => $run['operation'] ?? null,
            'status' => $run['status'] ?? null,
            'trigger' => $run['trigger'] ?? null,
            'step' => $run['step'] ?? null,
            'message' => $userMessage,
            'user_message' => $userMessage,
            'error_code' => $errorCode,
            'requested_by_user_id' => $run['requested_by_user_id'] ?? null,
            'requested_at' => $run['requested_at'] ?? null,
            'started_at' => $run['started_at'] ?? null,
            'finished_at' => $run['finished_at'] ?? null,
            'snapshot_id' => $run['snapshot_id'] ?? null,
            'snapshot_ids' => is_array($run['snapshot_ids'] ?? null)
                ? array_values(array_map(
                    static fn ($snapshotId): string => (string) $snapshotId,
                    array_values((array) $run['snapshot_ids'])
                ))
                : null,
            'scope' => $run['scope'] ?? null,
            'target' => $run['target'] ?? null,
        ];

        if ($includeTechnical) {
            $payload['error_message'] = $rawError !== '' ? $rawError : null;
            $payload['technical_message'] = $rawError !== '' ? $rawError : null;
            $payload['launcher_log_relative_path'] = $run['launcher_log_relative_path'] ?? null;
            if (is_array($run['logs'] ?? null)) {
                $payload['logs'] = $run['logs'];
            }
            if (array_key_exists('result', $run)) {
                $payload['result'] = $run['result'];
            }
        }

        return $payload;
    }

    private function toPublicErrorPayload(
        string $rawMessage,
        string $operation = 'snapshot_refresh',
        string $status = 'failed'
    ): array {
        $raw = trim($rawMessage);
        if ($raw === '') {
            return [
                'user_message' => null,
                'error_code' => null,
            ];
        }

        $errorCode = $this->detectErrorCode($raw, $raw);
        return [
            'user_message' => $this->buildUserMessage($operation, $status, $errorCode, $raw),
            'error_code' => $errorCode,
        ];
    }

    private function primaryReadinessIssue(array $readiness): ?array
    {
        $issues = is_array($readiness['blocking_issues'] ?? null)
            ? array_values(array_filter($readiness['blocking_issues'], static fn ($item): bool => is_array($item)))
            : [];

        if ($issues === []) {
            return null;
        }

        return [
            'code' => $issues[0]['code'] ?? null,
            'message' => $issues[0]['message'] ?? null,
        ];
    }

    private function buildUserMessage(
        string $operation,
        string $status,
        ?string $errorCode,
        string $fallbackMessage
    ): string {
        if ($status === 'queued') {
            return match ($operation) {
                'backup' => '�� ti?p nh?n y�u c?u sao luu.',
                'backup_postprocess' => 'Snapshot d� sao luu an to�n, nhung readable export v?n dang x? l� ? n?n. Vui l�ng d?i ho�n t?t r?i m?i x�a snapshot.',
                'backup_check' => '�� ti?p nh?n y�u c?u ki?m tra repository backup.',
                'prune' => '�� ti?p nh?n y�u c?u d?n b?n sao luu cu.',
                'forget' => '�� ti?p nh?n y�u c?u x�a b?n sao luu.',
                'restore' => '�� ti?p nh?n y�u c?u kh�i ph?c d? li?u.',
                'snapshot_refresh' => '�� ti?p nh?n y�u c?u l�m m?i danh s�ch.',
                default => '�� ti?p nh?n y�u c?u x? l�.',
            };
        }

        if ($status === 'running') {
            return match ($operation) {
                'backup' => '�ang sao luu d? li?u...',
                'backup_postprocess' => 'Snapshot d� sao luu an to�n, nhung readable export v?n dang x? l� ? n?n. Vui l�ng d?i ho�n t?t r?i m?i x�a snapshot.',
                'backup_check' => '�ang ki?m tra t�nh to�n v?n repository backup...',
                'prune' => '�ang d?n b?n sao luu cu...',
                'forget' => '�ang x�a b?n sao luu...',
                'restore' => '�ang kh�i ph?c d? li?u...',
                'snapshot_refresh' => '�ang d?ng b? danh s�ch b?n sao luu...',
                default => '�ang x? l� d? li?u...',
            };
        }

        if ($status === 'success') {
            if ($fallbackMessage !== '' && ! $this->isTechnicalMessage($fallbackMessage)) {
                return $fallbackMessage;
            }

            return match ($operation) {
                'backup' => 'Sao luu d? li?u ho�n t?t.',
                'backup_postprocess' => 'Snapshot d� sao luu an to�n, nhung readable export v?n dang x? l� ? n?n. Vui l�ng d?i ho�n t?t r?i m?i x�a snapshot.',
                'backup_check' => 'Ki?m tra repository backup ho�n t?t.',
                'prune' => 'D?n b?n sao luu cu ho�n t?t.',
                'forget' => '�� x�a b?n sao luu th�nh c�ng.',
                'restore' => 'Kh�i ph?c d? li?u ho�n t?t.',
                'snapshot_refresh' => '�?ng b? danh s�ch b?n sao luu ho�n t?t.',
                default => 'X? l� ho�n t?t.',
            };
        }

        if ($status === 'failed') {
            if ($errorCode === 'BACKUP_LOCKED') {
                return match ($operation) {
                    'forget' => 'Kh�ng th? x�a snapshot l�c n�y v� h? th?ng sao luu dang b?n. N?u snapshot v?a sao luu xong, h�y d?i readable export ho�n t?t r?i th? l?i.',
                    default => 'H? th?ng dang c� ti?n tr�nh kh�c gi? kh�a sao luu. Vui l�ng d?i r?i th? l?i.',
                };
            }

            if ($errorCode === 'DRIVE_AUTH_INVALID') {
                return 'Kh�ng th? x�c th?c Google Drive cho backup. H�y c?p nh?t remote spnc_gdrive trong rclone.conf runtime ho?c secret env tuong ?ng r?i th? l?i.';
            }

            if ($errorCode === 'RCLONE_CONFIG_INVALID') {
                return 'Kh�ng d?c du?c rclone.conf runtime cho backup. H�y ki?m tra SPNC_RCLONE_CONFIG ho?c SPNC_RCLONE_CONFIG_BASE64 tr�n m�i tru?ng tri?n khai.';
            }

            if ($errorCode === 'RCLONE_BINARY_INVALID') {
                return 'Kh�ng th? ch?y rclone d? truy c?p Google Drive. H�y ki?m tra SPNC_RCLONE_BINARY.';
            }

            if ($errorCode === 'RCLONE_REMOTE_INVALID') {
                return 'Remote Google Drive spnc_gdrive kh�ng h?p l? ho?c kh�ng t?n t?i trong t?p rclone.conf d�ng chung.';
            }

            if ($errorCode === 'BACKUP_CONFIG_INVALID') {
                return 'C?u h�nh backup chua d?y d?. H�y ki?m tra repository v� m?t kh?u backup tru?c khi thao t�c.';
            }

            if ($errorCode === 'RESTIC_BINARY_INVALID') {
                return 'Kh�ng t�m th?y restic trong runtime hi?n t?i. H�y ki?m tra SPNC_BACKUP_RESTIC_BINARY ho?c image deploy.';
            }

            if ($errorCode === 'PG_DUMP_BINARY_INVALID') {
                return 'Kh�ng t�m th?y pg_dump trong runtime hi?n t?i. Image production c?n c� PostgreSQL client d? t?o database dump.';
            }

            if ($errorCode === 'PSQL_BINARY_INVALID') {
                return 'Kh�ng t�m th?y psql trong runtime hi?n t?i. Image production c?n c� PostgreSQL client d? kh�i ph?c d? li?u t? backup.';
            }

            if ($errorCode === 'POSTGRES_DUMP_FAILED') {
                return 'T?o PostgreSQL dump th?t b?i. H�y ki?m tra pg_dump, k?t n?i PostgreSQL v� quy?n truy c?p database backup.';
            }

            if ($errorCode === 'EXPORT_TARGET_INVALID') {
                return 'Kh�ng suy ra du?c d�ch exports t? c?u h�nh repository hi?n t?i. H�y ki?m tra SPNC_BACKUP_REPOSITORY v� SPNC_BACKUP_EXPORT_TARGET.';
            }

            if ($errorCode === 'RUN_TIMEOUT') {
                if ($fallbackMessage !== '' && ! $this->isTechnicalMessage($fallbackMessage)) {
                    return $fallbackMessage;
                }
                return match ($operation) {
                    'snapshot_refresh' => '�?ng b? danh s�ch b? qu� th?i gian. Vui l�ng th? l?i.',
                    'forget' => 'Ti?n tr�nh x�a b?n sao luu b? qu� th?i gian. Vui l�ng th? l?i.',
                    'prune' => 'Ti?n tr�nh d?n b?n sao luu cu b? qu� th?i gian. Vui l�ng th? l?i.',
                    'backup' => 'Ti?n tr�nh sao luu b? qu� th?i gian. Vui l�ng th? l?i.',
                    'backup_postprocess' => 'Snapshot d� sao luu an to�n, nhung readable export v?n dang x? l� ? n?n. Vui l�ng d?i ho�n t?t r?i m?i x�a snapshot.',
                    'restore' => 'Ti?n tr�nh kh�i ph?c b? qu� th?i gian. Vui l�ng th? l?i.',
                    default => 'Ti?n tr�nh x? l� b? qu� th?i gian. Vui l�ng th? l?i.',
                };
            }

            if ($errorCode === 'SNAPSHOT_NOT_FOUND') {
                return 'Kh�ng t�m th?y b?n sao luu. Vui l�ng t?i l?i danh s�ch.';
            }

            if ($fallbackMessage !== '' && ! $this->isTechnicalMessage($fallbackMessage)) {
                return $fallbackMessage;
            }

            return match ($operation) {
                'backup' => 'Kh�ng th? sao luu d? li?u. Vui l�ng th? l?i.',
                'backup_postprocess' => 'Snapshot d� sao luu an to�n, nhung readable export v?n dang x? l� ? n?n. Vui l�ng d?i ho�n t?t r?i m?i x�a snapshot.',
                'backup_check' => 'Kh�ng th? ki?m tra repository backup. Vui l�ng th? l?i.',
                'prune' => 'Kh�ng th? d?n b?n sao luu cu. Vui l�ng th? l?i.',
                'forget' => 'Kh�ng th? x�a b?n sao luu. Vui l�ng th? l?i.',
                'restore' => 'Kh�ng th? kh�i ph?c d? li?u. Vui l�ng th? l?i.',
                'snapshot_refresh' => 'Kh�ng th? d?ng b? danh s�ch b?n sao luu. Vui l�ng th? l?i.',
                default => 'Kh�ng th? x? l� y�u c?u. Vui l�ng th? l?i.',
            };
        }

        if ($fallbackMessage !== '' && ! $this->isTechnicalMessage($fallbackMessage)) {
            return $fallbackMessage;
        }

        return 'H? th?ng dang x? l�...';
    }

    private function detectErrorCode(string $rawMessage, string $rawError): ?string
    {
        $combined = Str::lower(trim($rawMessage . ' ' . $rawError));
        if ($combined === '') {
            return null;
        }

        if ($this->containsLockHint($combined)) {
            return 'BACKUP_LOCKED';
        }

        if ($this->containsTimeoutHint($combined)) {
            return 'RUN_TIMEOUT';
        }

        if ($this->containsBackupConfigHint($combined)) {
            return 'BACKUP_CONFIG_INVALID';
        }

        if ($this->containsResticBinaryHint($combined)) {
            return 'RESTIC_BINARY_INVALID';
        }

        if (Str::contains($combined, ['pg_dump', 'postgresql-client', 'postgres client']) && Str::contains($combined, [
            'command not found',
            'not recognized as an internal or external command',
            'failed to start process',
            'no such file or directory',
            'executable not found',
            'kh�ng t�m th?y',
            'khong tim thay',
        ])) {
            return 'PG_DUMP_BINARY_INVALID';
        }

        if (Str::contains($combined, ['psql']) && Str::contains($combined, [
            'command not found',
            'not recognized as an internal or external command',
            'failed to start process',
            'no such file or directory',
            'executable not found',
            'kh�ng t�m th?y',
            'khong tim thay',
        ])) {
            return 'PSQL_BINARY_INVALID';
        }

        if (Str::contains($combined, ['tao postgresql dump that bai', 'pg_dump:'])) {
            return 'POSTGRES_DUMP_FAILED';
        }

        if ($this->containsExportTargetHint($combined)) {
            return 'EXPORT_TARGET_INVALID';
        }

        if (
            Str::contains($combined, [
                'kh�ng t�m th?y snapshot',
                'khong tim thay snapshot',
                'snapshot not found',
            ])
        ) {
            return 'SNAPSHOT_NOT_FOUND';
        }

        if ($this->containsDriveAuthHint($combined)) {
            return 'DRIVE_AUTH_INVALID';
        }

        if ($this->containsRcloneConfigHint($combined)) {
            return 'RCLONE_CONFIG_INVALID';
        }

        if ($this->containsRcloneBinaryHint($combined)) {
            return 'RCLONE_BINARY_INVALID';
        }

        if ($this->containsRcloneRemoteHint($combined)) {
            return 'RCLONE_REMOTE_INVALID';
        }

        return null;
    }
private function containsLockHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'unable to create lock',
            'repository is already locked',
            'already locked by pid',
            'the `unlock` command',
            'đang bị khóa',
            'dang bi khoa',
            'stale lock',
        ]);
    }

    private function containsTimeoutHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'timed out',
            'timeout',
            'exceeded the timeout',
            'quá thời gian',
            'qua thoi gian',
        ]);
    }

    private function containsDriveAuthHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'invalid_grant',
            'couldn\'t fetch token',
            'could not fetch token',
            'token expired',
            'refresh token',
            'reconnect spnc_gdrive',
        ]);
    }

    private function containsRcloneConfigHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'rclone config file',
            'rclone.conf',
            'config file not found',
            'failed to load config file',
            'không đọc được tệp cấu hình rclone',
            'khong doc duoc tep cau hinh rclone',
        ]);
    }

    private function containsRcloneBinaryHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'failed to start process',
            'the system cannot find the file specified',
            'not recognized as an internal or external command',
            'executable not found',
            'không thể chạy rclone',
            'khong the chay rclone',
        ]);
    }

    private function containsRcloneRemoteHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'didn\'t find section in config file',
            'config section',
            'failed to create file system for',
            'couldn\'t find root directory id',
            'could not find root directory id',
            'unknown backend',
        ]);
    }

    private function containsBackupConfigHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'missing spnc_backup_repository',
            'missing spnc_backup_password',
            'backup repository is not configured',
            'backup password is not configured',
            'thieu spnc_backup_repository',
            'thieu spnc_backup_password',
        ]);
    }

    private function containsResticBinaryHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'khong tim thay restic binary',
            'không tìm thấy restic binary',
            'restic binary',
            'spnc_backup_restic_binary',
        ]);
    }

    private function containsExportTargetHint(string $normalized): bool
    {
        return Str::contains($normalized, [
            'khong suy ra duoc dich export',
            'không suy ra được đích export',
            'spnc_backup_export_target',
            'spnc_backup_repository dang o dang rclone nhung khong chua remote hop le',
            'remote/path',
        ]);
    }

    private function isTechnicalMessage(string $value): bool
    {
        $normalized = Str::lower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return Str::contains($normalized, [
            'restic',
            'repository',
            'rclone',
            'pid',
            'stdout',
            'stderr',
            'lock id',
            'unable to create lock',
            'run timed out after',
            'exit status',
        ]);
    }

    private function toDownloadErrorResponse(
        string $logKey,
        string $snapshotId,
        \Throwable $exception,
        string $fallbackMessage
    ) {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = $fallbackMessage;
        $exceptionMessage = trim((string) $exception->getMessage());
        $normalizedMessage = Str::lower($exceptionMessage);

        if ($exception instanceof BackupRuntimeException) {
            if (
                str_contains($normalizedMessage, 'mã snapshot không hợp lệ')
                || str_contains($normalizedMessage, 'ma snapshot khong hop le')
            ) {
                $status = Response::HTTP_UNPROCESSABLE_ENTITY;
                $message = 'Mã snapshot không hợp lệ.';
            } elseif (
                str_contains($normalizedMessage, 'không tìm thấy snapshot')
                || str_contains($normalizedMessage, 'khong tim thay snapshot')
                || str_contains($normalizedMessage, 'không tìm thấy manifest')
                || str_contains($normalizedMessage, 'khong tim thay manifest')
                || str_contains($normalizedMessage, 'không tìm thấy db dump')
                || str_contains($normalizedMessage, 'khong tim thay db dump')
                || str_contains($normalizedMessage, 'snapshot chưa có export')
                || str_contains($normalizedMessage, 'snapshot chua co export')
                || str_contains($normalizedMessage, 'không tìm thấy tệp export')
                || str_contains($normalizedMessage, 'khong tim thay tep export')
                || str_contains($normalizedMessage, 'không tìm thấy tệp')
                || str_contains($normalizedMessage, 'khong tim thay tep')
            ) {
                $status = Response::HTTP_NOT_FOUND;
            }
        }

        Log::error($logKey, [
            'snapshot_id' => $snapshotId,
            'status' => $status,
            'message' => $exceptionMessage,
            'exception_class' => get_class($exception),
        ]);

        return response()->json([
            'message' => $message,
        ], $status);
    }

    private function startSnapshotRefresh(Request $request, string $trigger): ?array
    {
        $cacheMeta = $this->snapshotStore->cacheMeta();
        $isRefreshing = (bool) ($cacheMeta['refreshing'] ?? false);
        $refreshTimedOut = $isRefreshing && $this->snapshotStore->shouldStartRefresh();
        if ($isRefreshing && ! $refreshTimedOut) {
            return null;
        }

        if ($refreshTimedOut) {
            $staleRunId = trim((string) ($cacheMeta['refresh_run_id'] ?? ''));
            $message = 'Đồng bộ snapshot trước đó quá thời gian chờ và đã được đặt lại.';
            $this->snapshotStore->markRefreshFailed($message, $staleRunId !== '' ? $staleRunId : null);

            if ($staleRunId !== '') {
                $staleState = $this->stateStore->get($staleRunId);
                if (
                    is_array($staleState)
                    && in_array(Str::lower(trim((string) ($staleState['status'] ?? ''))), ['queued', 'running'], true)
                ) {
                    $this->stateStore->update($staleRunId, [
                        'status' => 'failed',
                        'operation' => 'snapshot_refresh',
                        'step' => 'timeout',
                        'message' => $message,
                        'finished_at' => now()->toIso8601String(),
                        'error_message' => $message,
                    ]);
                }
            }

            Log::warning('backup.snapshot_refresh_stale_reset', [
                'stale_run_id' => $staleRunId !== '' ? $staleRunId : null,
                'trigger' => $trigger,
            ]);
        }

        if (! $this->snapshotStore->shouldStartRefresh() && $trigger !== 'manual_refresh') {
            return null;
        }

        $runId = $this->stateStore->generateRunId();
        $userId = (int) ($request->user()?->id ?? 0);
        $this->snapshotStore->markRefreshing($runId);
        $this->stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'snapshot_refresh',
            'trigger' => $trigger,
            'requested_by_user_id' => $userId > 0 ? $userId : null,
            'requested_at' => now()->toIso8601String(),
            'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            'message' => 'Đã xếp lịch làm mới danh sách snapshot.',
        ]);

        try {
            $this->launcher->launchSnapshotRefresh($runId, $userId, $trigger);
        } catch (\Throwable $exception) {
            $errorCode = $this->detectErrorCode($exception->getMessage(), $exception->getMessage());
            $userMessage = $this->buildUserMessage('snapshot_refresh', 'failed', $errorCode, '');
            $this->snapshotStore->markRefreshFailed($userMessage, $runId);
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'snapshot_refresh',
                'step' => 'failed',
                'message' => $userMessage,
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
                'error_code' => $errorCode,
            ]);
            $this->stateStore->appendLog($runId, 'Không thể khởi chạy refresh snapshot: ' . $exception->getMessage(), 'error');

            Log::warning('backup.snapshot_refresh_launch_failed', [
                'run_id' => $runId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return ['run_id' => $runId];
    }
}

