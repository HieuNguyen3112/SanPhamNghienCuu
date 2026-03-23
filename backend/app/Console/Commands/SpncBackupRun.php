<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRunLauncher;
use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpncBackupRun extends Command
{
    protected $signature = 'spnc:backup:run
        {--run-id= : MÃ£ run Ä‘á»ƒ theo dÃµi tráº¡ng thÃ¡i}
        {--trigger=manual : Nguá»“n kÃ­ch hoáº¡t backup (manual/schedule)}
        {--initiated-by= : user_id kÃ­ch hoáº¡t}';

    protected $description = 'Cháº¡y backup SPNC (DB + tá»‡p nháº¡y cáº£m) báº±ng restic.';

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
        parent::__construct();
        $this->backupManager = $backupManager;
        $this->stateStore = $stateStore;
        $this->launcher = $launcher;
        $this->snapshotStore = $snapshotStore;
    }

    public function handle(): int
    {
        $runId = trim((string) ($this->option('run-id') ?: $this->stateStore->generateRunId()));
        $trigger = trim((string) ($this->option('trigger') ?: 'manual'));
        if ($trigger === '') {
            $trigger = 'manual';
        }
        $initiatedBy = $this->option('initiated-by') !== null
            ? (int) $this->option('initiated-by')
            : null;
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
                'operation' => 'backup',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy,
                'requested_at' => now()->toIso8601String(),
                'launcher_log_relative_path' => $this->launcher->logRelativePath($runId),
            ]);
        } else {
            $this->stateStore->update($runId, [
                'operation' => 'backup',
                'trigger' => $trigger,
                'requested_by_user_id' => $initiatedBy ?? ($existing['requested_by_user_id'] ?? null),
            ]);
        }
        $this->stateStore->appendLog($runId, 'Báº¯t Ä‘áº§u tiáº¿n trÃ¬nh backup. trigger=' . $trigger);

        $lock = Cache::lock('spnc:backup:run', 21600);
        if (! $lock->get()) {
            $message = 'Äang cÃ³ má»™t báº£n sao lÆ°u Ä‘ang cháº¡y. Vui lÃ²ng Ä‘á»£i hoÃ n táº¥t.';
            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup',
                'finished_at' => now()->toIso8601String(),
                'message' => $message,
                'error_message' => $message,
            ]);
            $this->stateStore->appendLog($runId, $message, 'warning');
            $this->error($message);
            return self::FAILURE;
        }

        try {
            if (strtolower($trigger) === 'schedule' && $this->shouldSkipScheduleRun($runId)) {
                $message = 'Bá» qua backup lá»‹ch vÃ¬ láº§n cháº¡y gáº§n nháº¥t chÆ°a Ä‘áº¡t khoáº£ng cÃ¡ch theo cáº¥u hÃ¬nh.';
                $this->stateStore->update($runId, [
                    'status' => 'success',
                    'operation' => 'backup',
                    'step' => 'skipped_schedule_window',
                    'message' => $message,
                    'finished_at' => now()->toIso8601String(),
                    'result' => [
                        'skipped' => true,
                        'reason' => 'schedule_window_guard',
                    ],
                ]);
                $this->stateStore->appendLog($runId, $message, 'info');
                Log::warning('backup.schedule_run_skipped_frequency_guard', [
                    'run_id' => $runId,
                    'trigger' => $trigger,
                ]);
                $this->info($message . ' run_id=' . $runId);
                return self::SUCCESS;
            }

            $this->stateStore->update($runId, [
                'status' => 'running',
                'operation' => 'backup',
                'started_at' => now()->toIso8601String(),
                'step' => 'running_backup',
                'message' => 'Äang táº¡o snapshot backup...',
            ]);
            $this->stateStore->appendLog($runId, 'Äang cháº¡y restic backup...');

            $result = $this->backupManager->runBackup($runId, $trigger, $initiatedBy);
            $this->stateStore->appendLog($runId, 'Táº¡o snapshot hoÃ n táº¥t.');
            $summary = $result['summary'] ?? null;
            if (is_array($summary)) {
                $this->stateStore->appendLog(
                    $runId,
                    'TÃ³m táº¯t backup: ' . json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
            $snapshot = is_array($result['snapshot'] ?? null) ? $result['snapshot'] : null;
            $snapshotId = trim((string) ($snapshot['snapshot_id'] ?? $snapshot['snapshot_id_full'] ?? ''));
            if ($snapshot) {
                $runningState = $this->stateStore->get($runId);
                $this->snapshotStore->upsert(
                    array_merge($snapshot, $this->buildSnapshotCachePatch($result, $runningState)),
                    $runId
                );
                $this->stateStore->appendLog($runId, 'ÄÃ£ ghi tháº³ng snapshot má»›i vÃ o cache cá»¥c bá»™, khÃ´ng cáº§n quÃ©t láº¡i toÃ n bá»™ repository.');
            }

            $postProcess = [
                'scheduled' => false,
                'status' => 'disabled',
                'run_id' => null,
                'inline' => false,
            ];
            if ((bool) config('backup.exports.enabled', true) && $snapshotId !== '') {
                $postProcessRunId = $this->stateStore->generateRunId();
                $this->stateStore->initialize($postProcessRunId, [
                    'status' => 'queued',
                    'operation' => 'backup_postprocess',
                    'trigger' => $trigger,
                    'requested_by_user_id' => $initiatedBy,
                    'requested_at' => now()->toIso8601String(),
                    'launcher_log_relative_path' => $this->launcher->logRelativePath($postProcessRunId),
                    'parent_run_id' => $runId,
                    'snapshot_id' => $snapshotId,
                    'message' => 'ÄÃ£ xáº¿p lá»‹ch hoÃ n thiá»‡n export sao lÆ°u.',
                ]);

                if ((bool) config('backup.exports.inline_postprocess', true)) {
                    $this->stateStore->appendLog(
                        $runId,
                        'Dang chay inline readable export va dong bo Drive trong cung tien trinh backup.'
                    );

                    $exitCode = $this->call('spnc:backup:post-process', [
                        '--run-id' => $postProcessRunId,
                        '--snapshot-id' => $snapshotId,
                        '--trigger' => $trigger,
                        '--initiated-by' => $initiatedBy,
                    ]);

                    $postProcessState = $this->stateStore->get($postProcessRunId);
                    $postProcessStatus = strtolower(trim((string) ($postProcessState['status'] ?? '')));
                    $postProcess = [
                        'scheduled' => true,
                        'status' => $postProcessStatus !== '' ? $postProcessStatus : ($exitCode === self::SUCCESS ? 'success' : 'failed'),
                        'run_id' => $postProcessRunId,
                        'operation' => 'backup_postprocess',
                        'status_url' => '/api/admin/backups/runs/' . $postProcessRunId,
                        'inline' => true,
                    ];

                    if ($exitCode === self::SUCCESS) {
                        $this->stateStore->appendLog(
                            $runId,
                            'Da hoan tat inline readable export trong child run ' . $postProcessRunId . '.'
                        );
                    } else {
                        $postProcess['error_message'] = trim((string) ($postProcessState['error_message'] ?? ''));
                        $this->stateStore->appendLog(
                            $runId,
                            'Inline hau xu ly export khong thanh cong cho child run ' . $postProcessRunId . '.',
                            'warning'
                        );
                    }
                } else {
                    try {
                        $this->launcher->launchBackupPostProcess(
                            $postProcessRunId,
                            (int) ($initiatedBy ?? 0),
                            $snapshotId,
                            $trigger
                        );
                        $postProcess = [
                            'scheduled' => true,
                            'status' => 'queued',
                            'run_id' => $postProcessRunId,
                            'operation' => 'backup_postprocess',
                            'status_url' => '/api/admin/backups/runs/' . $postProcessRunId,
                            'inline' => false,
                        ];
                        $this->stateStore->appendLog(
                            $runId,
                            'ÄÃ£ chuyá»ƒn bÆ°á»›c export readable vÃ  Ä‘á»“ng bá»™ Drive sang child run ' . $postProcessRunId . '.'
                        );
                    } catch (\Throwable $exception) {
                        $this->stateStore->update($postProcessRunId, [
                            'status' => 'failed',
                            'operation' => 'backup_postprocess',
                            'step' => 'failed',
                            'finished_at' => now()->toIso8601String(),
                            'message' => 'KhÃ´ng thá»ƒ khá»Ÿi cháº¡y hoÃ n thiá»‡n export sao lÆ°u.',
                            'error_message' => $exception->getMessage(),
                        ]);
                        $this->stateStore->appendLog(
                            $runId,
                            'KhÃ´ng thá»ƒ khá»Ÿi cháº¡y háº­u xá»­ lÃ½ export: ' . $exception->getMessage(),
                            'warning'
                        );
                        Log::warning('backup.postprocess_launch_failed', [
                            'run_id' => $runId,
                            'postprocess_run_id' => $postProcessRunId,
                            'snapshot_id' => $snapshotId,
                            'message' => $exception->getMessage(),
                        ]);
                        $postProcess = [
                            'scheduled' => false,
                            'status' => 'failed_to_launch',
                            'run_id' => $postProcessRunId,
                            'operation' => 'backup_postprocess',
                            'error_message' => $exception->getMessage(),
                            'inline' => false,
                        ];
                    }
                }
            }

            $payload = [
                'status' => 'success',
                'operation' => 'backup',
                'step' => 'completed',
                'snapshot_id' => $snapshotId !== '' ? $snapshotId : null,
                'message' => ($postProcess['status'] ?? null) === 'success'
                    ? 'Backup va readable export da hoan tat.'
                    : ($postProcess['scheduled']
                        ? 'Backup an toÃ n Ä‘Ã£ hoÃ n táº¥t. Export readable Ä‘ang tiáº¿p tá»¥c á»Ÿ ná»n.'
                        : 'Backup hoÃ n táº¥t.'),
                'finished_at' => now()->toIso8601String(),
                'result' => [
                    'snapshot' => $result['snapshot'] ?? null,
                    'summary' => $result['summary'] ?? null,
                    'manifest_relative_path' => $result['manifest_relative_path'] ?? null,
                    'db_dump_relative_path' => $result['db_dump_relative_path'] ?? null,
                    'workspace_relative_path' => $result['workspace_relative_path'] ?? null,
                    'verification' => $result['verification'] ?? null,
                    'check' => $result['check'] ?? null,
                    'export' => $postProcess,
                ],
            ];
            $finalState = $this->stateStore->update($runId, $payload);
            if ($snapshotId !== '') {
                $this->snapshotStore->mergeBySnapshotId(
                    $snapshotId,
                    $this->buildSnapshotCachePatch($result, $finalState),
                    $runId
                );
                $this->stateStore->appendLog(
                    $runId,
                    'Da dong bo snapshot cache voi trang thai hoan tat, kich thuoc va metadata xac minh cuoi cung.'
                );
            }

            $this->info('Backup hoÃ n táº¥t. run_id=' . $runId);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.run_failed', [
                'run_id' => $runId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);

            $this->stateStore->update($runId, [
                'status' => 'failed',
                'operation' => 'backup',
                'step' => 'failed',
                'message' => 'Backup tháº¥t báº¡i.',
                'finished_at' => now()->toIso8601String(),
                'error_message' => $exception->getMessage(),
            ]);
            $this->stateStore->appendLog($runId, 'Backup tháº¥t báº¡i: ' . $exception->getMessage(), 'error');

            $this->error('Backup tháº¥t báº¡i: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }

    private function shouldSkipScheduleRun(string $runId): bool
    {
        $minGapSeconds = $this->scheduleMinGapSeconds();
        $threshold = now()->subSeconds($minGapSeconds);

        foreach ($this->stateStore->listRecent(240) as $state) {
            if (! is_array($state)) {
                continue;
            }

            $candidateRunId = trim((string) ($state['run_id'] ?? ''));
            if ($candidateRunId === '' || $candidateRunId === $runId) {
                continue;
            }

            $operation = strtolower(trim((string) ($state['operation'] ?? '')));
            if ($operation !== 'backup') {
                continue;
            }

            $trigger = strtolower(trim((string) ($state['trigger'] ?? '')));
            if ($trigger !== 'schedule') {
                continue;
            }

            $status = strtolower(trim((string) ($state['status'] ?? '')));
            if (! in_array($status, ['queued', 'running', 'success', 'failed'], true)) {
                continue;
            }

            $referenceAt = $this->resolveRecentScheduleReferenceAt($state);
            if (! $referenceAt) {
                continue;
            }

            return $referenceAt->greaterThan($threshold);
        }

        return false;
    }

    private function scheduleMinGapSeconds(): int
    {
        $days = array_values(array_unique(array_filter(array_map(
            static fn ($day): int => (int) $day,
            (array) config('backup.schedule.days', [1, 4])
        ), static fn (int $day): bool => $day >= 0 && $day <= 6)));
        if ($days === []) {
            $days = [1, 4];
        }

        sort($days);
        $count = count($days);
        $minDayGap = 7;
        for ($i = 0; $i < $count; $i++) {
            $current = $days[$i];
            $next = $days[($i + 1) % $count];
            $gap = $next - $current;
            if ($gap <= 0) {
                $gap += 7;
            }
            $minDayGap = min($minDayGap, $gap);
        }

        return max(3600, ($minDayGap * 86400) - 600);
    }

    private function resolveRecentScheduleReferenceAt(array $state): ?Carbon
    {
        foreach (['started_at', 'requested_at', 'finished_at', 'updated_at'] as $field) {
            $value = trim((string) ($state[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function buildSnapshotCachePatch(array $result, ?array $state): array
    {
        $patch = [];

        $status = strtolower(trim((string) ($state['status'] ?? '')));
        if ($status !== '') {
            $patch['run_state'] = $status;
            $patch['status'] = $status;
        }

        $trigger = trim((string) ($state['trigger'] ?? ''));
        if ($trigger !== '') {
            $patch['trigger'] = $trigger;
        }

        $sizeBytes = $this->resolveSnapshotSizeBytes(
            is_array($result['summary'] ?? null)
                ? $result['summary']
                : (is_array($state['result']['summary'] ?? null) ? $state['result']['summary'] : null)
        );
        if ($sizeBytes !== null) {
            $patch['size_bytes'] = $sizeBytes;
        }

        $verification = is_array($result['verification'] ?? null)
            ? $result['verification']
            : (is_array($state['result']['verification'] ?? null) ? $state['result']['verification'] : null);
        if (is_array($verification)) {
            if (array_key_exists('contains_db_dump', $verification)) {
                $patch['contains_db_dump'] = (bool) $verification['contains_db_dump'];
            }
            if (array_key_exists('contains_files', $verification)) {
                $patch['contains_files'] = (bool) $verification['contains_files'];
            }
            if (array_key_exists('contains_db_dump', $patch) || array_key_exists('contains_files', $patch)) {
                $patch['backup_type'] = $this->resolveBackupType(
                    (bool) ($patch['contains_db_dump'] ?? false),
                    (bool) ($patch['contains_files'] ?? false)
                );
            }
        }

        return $patch;
    }

    private function resolveSnapshotSizeBytes(?array $summary): ?int
    {
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
}
