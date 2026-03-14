<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Backup\BackupRunLauncher;
use App\Services\Backup\BackupRunStateStore;
use App\Services\Backup\ReadableExportPdfRenderer;
use App\Services\Backup\BackupSnapshotStore;
use App\Services\Backup\ResticBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBackupApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('backup.paths.state_dir', 'testing/backup-runs');
        config()->set('backup.paths.snapshot_cache_file', 'testing/backup-snapshots/cache.json');
        config()->set('backup.paths.workspace_dir', 'testing/backup-workspace');
        config()->set('backup.paths.restore_dir', 'testing/backup-restores');
        config()->set('backup.paths.download_dir', 'testing/backup-downloads');
        config()->set('backup.exports.local_dir', 'testing/backup-exports/items');
        config()->set('backup.exports.index_file', 'testing/backup-exports/index.json');
        File::deleteDirectory(storage_path('app/testing'));

        Role::findOrCreate('SCIENCE_OFFICE', 'web');
        Role::findOrCreate('GV', 'web');
    }

    public function test_backup_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/backups')
            ->assertStatus(401);
    }

    public function test_backup_list_forbidden_for_non_science_office(): void
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/backups')
            ->assertStatus(403);
    }

    public function test_science_office_can_read_cached_backup_list(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        $snapshotStore->replace([
            [
                'snapshot_id' => 'a1b2c3d4e5f6',
                'short_id' => 'a1b2c3d4',
                'backup_name' => 'Backup 01/03/2026 22:00:00',
                'created_at' => '2026-03-01T22:00:00+07:00',
                'run_id' => 'run-20260301',
                'run_state' => 'success',
                'status' => 'success',
                'trigger' => 'manual',
                'size_bytes' => 1024,
                'backup_type' => 'full',
                'contains_db_dump' => true,
                'contains_files' => true,
                'hostname' => 'server-01',
                'paths' => ['storage/app/backup-workspace/run-20260301'],
                'tags' => ['spnc_backup', 'run_id:run-20260301'],
            ],
        ], 'run-20260301');

        $this->getJson('/api/admin/backups?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.snapshot_id', 'a1b2c3d4e5f6')
            ->assertJsonPath('data.items.0.backup_type', 'full')
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonStructure([
                'data' => [
                    'is_syncing',
                    'last_sync_at',
                    'schedule' => ['description_vi', 'interval_weeks', 'weekday'],
                ],
            ]);
    }

    public function test_index_exposes_running_export_state_for_successful_snapshot(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);

        $snapshotStore->replace([
            [
                'snapshot_id' => 'snap-running-export',
                'short_id' => 'snaprun1',
                'backup_name' => 'Backup 14/03/2026 13:53:09',
                'created_at' => '2026-03-14T13:53:09+07:00',
                'run_id' => 'parent-export-running',
                'run_state' => 'success',
                'status' => 'success',
                'trigger' => 'manual',
                'size_bytes' => 1153433,
                'backup_type' => 'full',
                'contains_db_dump' => true,
                'contains_files' => true,
                'export_available' => false,
                'export_path' => null,
                'export_drive_path' => null,
                'export_generated_at' => null,
                'export_bundle_filename' => null,
                'export_artifacts' => [],
            ],
        ], 'parent-export-running');

        $stateStore->initialize('parent-export-running', [
            'status' => 'success',
            'operation' => 'backup',
            'trigger' => 'manual',
            'requested_at' => now()->subMinutes(2)->toIso8601String(),
            'finished_at' => now()->subMinute()->toIso8601String(),
            'result' => [
                'export' => [
                    'run_id' => 'postproc-running-1',
                ],
            ],
        ]);
        $stateStore->initialize('postproc-running-1', [
            'status' => 'running',
            'operation' => 'backup_postprocess',
            'trigger' => 'manual',
            'snapshot_id' => 'snap-running-export',
            'requested_at' => now()->subMinute()->toIso8601String(),
            'started_at' => now()->subSeconds(45)->toIso8601String(),
            'message' => '�ang t?o readable export v� d?ng b? l�n Drive...',
        ]);

        $this->getJson('/api/admin/backups')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.snapshot_id', 'snap-running-export')
            ->assertJsonPath('data.items.0.status', 'success')
            ->assertJsonPath('data.items.0.export_available', false)
            ->assertJsonPath('data.items.0.export_state', 'running')
            ->assertJsonPath('data.items.0.export_run.operation', 'backup_postprocess')
            ->assertJsonPath('data.items.0.export_run.status', 'running');
    }

    public function test_index_exposes_failed_export_state_without_marking_snapshot_failed(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);

        $snapshotStore->replace([
            [
                'snapshot_id' => 'snap-failed-export',
                'short_id' => 'snapfail',
                'backup_name' => 'Backup 14/03/2026 14:10:00',
                'created_at' => '2026-03-14T14:10:00+07:00',
                'run_id' => 'parent-export-failed',
                'run_state' => 'success',
                'status' => 'success',
                'trigger' => 'manual',
                'size_bytes' => 2048,
                'backup_type' => 'full',
                'contains_db_dump' => true,
                'contains_files' => true,
                'export_available' => false,
                'export_path' => null,
                'export_drive_path' => null,
                'export_generated_at' => null,
                'export_bundle_filename' => null,
                'export_artifacts' => [],
            ],
        ], 'parent-export-failed');

        $stateStore->initialize('parent-export-failed', [
            'status' => 'success',
            'operation' => 'backup',
            'trigger' => 'manual',
            'requested_at' => now()->subMinutes(5)->toIso8601String(),
            'finished_at' => now()->subMinutes(4)->toIso8601String(),
            'result' => [
                'export' => [
                    'run_id' => 'postproc-failed-1',
                ],
            ],
        ]);
        $stateStore->initialize('postproc-failed-1', [
            'status' => 'failed',
            'operation' => 'backup_postprocess',
            'trigger' => 'manual',
            'snapshot_id' => 'snap-failed-export',
            'requested_at' => now()->subMinutes(4)->toIso8601String(),
            'finished_at' => now()->subMinutes(3)->toIso8601String(),
            'message' => 'Kh�ng th? ho�n thi?n readable export. Vui l�ng th? l?i.',
        ]);

        $this->getJson('/api/admin/backups')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.snapshot_id', 'snap-failed-export')
            ->assertJsonPath('data.items.0.status', 'success')
            ->assertJsonPath('data.items.0.export_available', false)
            ->assertJsonPath('data.items.0.export_state', 'failed')
            ->assertJsonPath('data.items.0.export_run.operation', 'backup_postprocess')
            ->assertJsonPath('data.items.0.export_run.status', 'failed');
    }

    public function test_index_does_not_auto_trigger_snapshot_refresh_on_page_load(): void
    {
        $this->actingAsScienceOffice();
        config()->set('backup.snapshot_cache.auto_refresh', true);

        $launcher = \Mockery::mock(BackupRunLauncher::class);
        $launcher->shouldReceive('launchSnapshotRefresh')->never();
        $this->app->instance(BackupRunLauncher::class, $launcher);

        $this->getJson('/api/admin/backups')
            ->assertStatus(200);
    }
    public function test_index_returns_fixed_schedule_meta(): void
    {
        $this->actingAsScienceOffice();
        config()->set('backup.schedule.days', [1, 4]);
        config()->set('backup.schedule.time', '02:00');

        $response = $this->getJson('/api/admin/backups')
            ->assertStatus(200);

        $this->assertSame('fixed', $response->json('data.schedule.mode'));
        $this->assertSame([1, 4], $response->json('data.schedule.days'));
        $this->assertStringContainsString('Thứ 2', (string) $response->json('data.schedule.description_vi'));
        $this->assertStringContainsString('Thứ 5', (string) $response->json('data.schedule.description_vi'));
    }

    public function test_index_hides_schedule_triggered_run_from_ui_active_run(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'schedulebk1';
        $stateStore->initialize($runId, [
            'status' => 'running',
            'operation' => 'backup',
            'trigger' => 'schedule',
            'requested_at' => now()->subMinute()->toIso8601String(),
            'started_at' => now()->subSeconds(45)->toIso8601String(),
            'message' => '�ang t?o snapshot backup...',
        ]);

        $response = $this->getJson('/api/admin/backups')
            ->assertStatus(200);

        $this->assertNull($response->json('data.active_run'));
        $this->assertSame($runId, $response->json('data.system_active_run.run_id'));
    }

    public function test_index_ignores_non_snapshot_refresh_cache_flag_for_sync_banner(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        $runId = 'schedulebk2';
        $stateStore->initialize($runId, [
            'status' => 'running',
            'operation' => 'backup',
            'trigger' => 'schedule',
            'requested_at' => now()->subMinute()->toIso8601String(),
            'started_at' => now()->subSeconds(30)->toIso8601String(),
            'message' => '�ang t?o snapshot backup...',
        ]);
        $snapshotStore->markRefreshing($runId);

        $response = $this->getJson('/api/admin/backups')
            ->assertStatus(200);

        $this->assertFalse((bool) $response->json('data.is_syncing'));
        $this->assertFalse((bool) $response->json('data.cache.refreshing'));
        $this->assertNull($response->json('data.active_run'));
    }

    public function test_run_is_rejected_when_another_backup_run_is_active(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $conflictRunId = 'runningbk1';
        $stateStore->initialize($conflictRunId, [
            'status' => 'running',
            'operation' => 'backup',
            'trigger' => 'schedule',
            'requested_at' => now()->subMinute()->toIso8601String(),
            'started_at' => now()->subSeconds(30)->toIso8601String(),
            'message' => '�ang t?o snapshot backup...',
        ]);

        $launcher = \Mockery::mock(BackupRunLauncher::class);
        $launcher->shouldReceive('launchBackup')->never();
        $this->app->instance(BackupRunLauncher::class, $launcher);

        $this->postJson('/api/admin/backups/run')
            ->assertStatus(409)
            ->assertJsonPath('data.conflict_run.run_id', $conflictRunId);
    }

    public function test_forget_is_rejected_while_backup_postprocess_is_active(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $conflictRunId = 'postproc01';
        $stateStore->initialize($conflictRunId, [
            'status' => 'running',
            'operation' => 'backup_postprocess',
            'trigger' => 'manual',
            'snapshot_id' => 'abcdef123456',
            'requested_at' => now()->subMinute()->toIso8601String(),
            'started_at' => now()->subSeconds(20)->toIso8601String(),
            'message' => 'Dang tao readable export va dong bo len Drive...',
        ]);

        $launcher = \Mockery::mock(BackupRunLauncher::class);
        $launcher->shouldReceive('launchForget')->never();
        $this->app->instance(BackupRunLauncher::class, $launcher);

        $this->postJson('/api/admin/backups/forget', [
            'snapshot_ids' => ['abcdef123456'],
        ])
            ->assertStatus(409)
            ->assertJsonPath('data.conflict_run.run_id', $conflictRunId)
            ->assertJsonPath('data.conflict_run.operation', 'backup_postprocess')
            ->assertJsonPath(
                'message',
                'Snapshot đã sao lưu an toàn, nhưng readable export vẫn đang xử lý ở nền. Vui lòng đợi hoàn tất rồi mới xóa snapshot.'
            );
    }

    public function test_science_office_can_unlock_stale_repository_lock(): void
    {
        $this->actingAsScienceOffice();

        $mock = \Mockery::mock(ResticBackupManager::class);
        $mock->shouldReceive('unlockStaleRepositoryLocks')
            ->once()
            ->andReturn([
                'successful' => true,
                'stdout' => 'removed stale locks',
                'stderr' => '',
            ]);
        $this->app->instance(ResticBackupManager::class, $mock);

        $this->postJson('/api/admin/backups/unlock-stale')
            ->assertStatus(200)
            ->assertJsonPath('data.unlocked', true);
    }

    public function test_restore_rejects_invalid_snapshot_id(): void
    {
        $this->actingAsScienceOffice();

        $this->postJson('/api/admin/backups/invalid_snapshot_id/restore', [
            'scope' => 'full',
            'target' => 'staging',
            'confirm' => true,
            'confirm_phrase' => 'RESTORE',
        ])->assertStatus(422);
    }

    public function test_science_office_can_download_manifest(): void
    {
        $this->actingAsScienceOffice();
        $filePath = $this->createTempDownloadFile('manifest.json', '{"ok":true}');

        $mock = \Mockery::mock(ResticBackupManager::class);
        $mock->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('a1b2c3d4e5f6');
        $mock->shouldReceive('extractManifestFromSnapshot')
            ->once()
            ->with('a1b2c3d4e5f6')
            ->andReturn(['absolute_path' => $filePath]);
        $this->app->instance(ResticBackupManager::class, $mock);

        $this->get('/api/admin/backups/a1b2c3d4e5f6/manifest')
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/json; charset=UTF-8');
    }

    public function test_science_office_can_download_database_dump(): void
    {
        $this->actingAsScienceOffice();
        $filePath = $this->createTempDownloadFile('database.sql', "SELECT 1;\n");

        $mock = \Mockery::mock(ResticBackupManager::class);
        $mock->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('a1b2c3d4e5f6');
        $mock->shouldReceive('extractDatabaseDumpFromSnapshot')
            ->once()
            ->with('a1b2c3d4e5f6')
            ->andReturn(['absolute_path' => $filePath]);
        $this->app->instance(ResticBackupManager::class, $mock);

        $this->get('/api/admin/backups/a1b2c3d4e5f6/database-dump')
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/sql; charset=UTF-8');
    }

    public function test_science_office_can_get_export_metadata(): void
    {
        $this->actingAsScienceOffice();

        $mock = \Mockery::mock(ResticBackupManager::class);
        $mock->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('a1b2c3d4e5f6');
        $mock->shouldReceive('getSnapshotExportMetadata')
            ->once()
            ->with('a1b2c3d4e5f6')
            ->andReturn([
                'snapshot_id' => 'a1b2c3d4e5f6',
                'available' => true,
                'export_path' => 'rclone:drive:spnc-backups/exports/a1b2c3d4e5f6',
            ]);
        $this->app->instance(ResticBackupManager::class, $mock);

        $this->getJson('/api/admin/backups/a1b2c3d4e5f6/export/metadata')
            ->assertStatus(200)
            ->assertJsonPath('data.snapshot_id', 'a1b2c3d4e5f6')
            ->assertJsonPath('data.available', true);
    }

    public function test_science_office_can_download_export_bundle(): void
    {
        $this->actingAsScienceOffice();
        $filePath = $this->createTempDownloadFile('backup_export_a1b2.zip', 'zip-content');

        $mock = \Mockery::mock(ResticBackupManager::class);
        $mock->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('a1b2c3d4e5f6');
        $mock->shouldReceive('prepareExportBundleDownload')
            ->once()
            ->with('a1b2c3d4e5f6')
            ->andReturn([
                'absolute_path' => $filePath,
                'filename' => 'backup_export_a1b2.zip',
            ]);
        $this->app->instance(ResticBackupManager::class, $mock);

        $this->get('/api/admin/backups/a1b2c3d4e5f6/export/download')
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_science_office_can_forget_selected_snapshots(): void
    {
        $this->actingAsScienceOffice();

        $mock = \Mockery::mock(BackupRunLauncher::class);
        $mock->shouldReceive('launchForget')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('int'),
                ['a1b2c3d4e5f6'],
                'manual'
            );
        $this->app->instance(BackupRunLauncher::class, $mock);

        $this->postJson('/api/admin/backups/forget', [
            'snapshot_ids' => ['a1b2c3d4e5f6'],
        ])->assertStatus(202)
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.operation', 'forget')
            ->assertJsonPath('data.snapshot_ids.0', 'a1b2c3d4e5f6')
            ->assertJsonMissingPath('data.prune_after');
    }

    public function test_index_marks_stale_snapshot_refresh_run_as_failed_and_non_blocking(): void
    {
        $this->actingAsScienceOffice();
        config()->set('backup.snapshot_cache.auto_refresh', false);
        config()->set('backup.runs.stale_grace_seconds', 0);
        config()->set('backup.runs.queue_stale_after_seconds', 60);
        config()->set('backup.runs.queue_stale_after_by_operation', [
            'snapshot_refresh' => 60,
        ]);

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'runstale01';
        $stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'snapshot_refresh',
            'requested_at' => now()->subMinutes(10)->toIso8601String(),
            'updated_at' => now()->subMinutes(10)->toIso8601String(),
            'message' => '�ang ch? d?ng b? snapshot.',
        ]);

        $response = $this->getJson('/api/admin/backups')
            ->assertStatus(200);

        $activeRun = $response->json('data.active_run');
        if (is_array($activeRun)) {
            $this->assertNotSame($runId, $activeRun['run_id'] ?? null);
        }

        $state = $stateStore->get($runId);
        $this->assertIsArray($state);
        $this->assertSame('failed', $state['status'] ?? null);
        $this->assertSame('timeout', $state['step'] ?? null);
    }

    public function test_run_status_marks_stale_forget_run_as_failed_with_friendly_message(): void
    {
        $this->actingAsScienceOffice();
        config()->set('backup.runs.stale_grace_seconds', 0);
        config()->set('backup.runs.queue_stale_after_by_operation', [
            'forget' => 30,
        ]);

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'forgetstale1';
        $stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'forget',
            'requested_at' => now()->subMinutes(5)->toIso8601String(),
            'updated_at' => now()->subMinutes(5)->toIso8601String(),
            'snapshot_ids' => ['a1b2c3d4e5f6'],
            'message' => '�� x?p l?ch x�a snapshot d� ch?n.',
        ]);

        $this->getJson('/api/admin/backups/runs/' . $runId)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.step', 'timeout')
            ->assertJsonPath('data.snapshot_ids.0', 'a1b2c3d4e5f6')
            ->assertJsonPath('data.message', 'Tiến trình xóa snapshot bị quá thời gian chờ và đã được đánh dấu thất bại. Bạn có thể thử lại.');
    }

    public function test_run_status_hides_technical_error_by_default(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'forgetlock1';
        $stateStore->initialize($runId, [
            'status' => 'failed',
            'operation' => 'forget',
            'message' => '�ang x�a snapshot d� ch?n kh?i repository restic...',
            'error_message' => 'unable to create lock in backend: repository is already locked by PID 1234',
            'finished_at' => now()->toIso8601String(),
        ]);

        $response = $this->getJson('/api/admin/backups/runs/' . $runId)
            ->assertStatus(200)
            ->assertJsonPath('data.error_code', 'BACKUP_LOCKED')
            ->assertJsonMissingPath('data.error_message');

        $this->assertStringNotContainsStringIgnoringCase('repository', (string) $response->json('data.user_message'));
    }

    public function test_run_status_can_return_technical_error_when_requested(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'forgetlock2';
        $stateStore->initialize($runId, [
            'status' => 'failed',
            'operation' => 'forget',
            'message' => 'X�a snapshot th?t b?i.',
            'error_message' => 'unable to create lock in backend: repository is already locked by PID 4321',
            'finished_at' => now()->toIso8601String(),
        ]);

        $this->getJson('/api/admin/backups/runs/' . $runId . '?include_technical=1')
            ->assertStatus(200)
            ->assertJsonPath('data.error_code', 'BACKUP_LOCKED')
            ->assertJsonPath('data.error_message', 'unable to create lock in backend: repository is already locked by PID 4321');
    }

    public function test_index_sanitizes_cache_last_error_message_for_ui(): void
    {
        $this->actingAsScienceOffice();

        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        $snapshotStore->markRefreshFailed('unable to create lock in backend: repository is already locked by PID 1234');

        $response = $this->getJson('/api/admin/backups')
            ->assertStatus(200)
            ->assertJsonPath('data.cache.last_error_code', 'BACKUP_LOCKED');

        $this->assertStringNotContainsStringIgnoringCase('repository', (string) $response->json('data.cache.last_error'));
    }

    public function test_forget_rejects_prune_in_same_request(): void
    {
        $this->actingAsScienceOffice();

        $this->postJson('/api/admin/backups/forget', [
            'snapshot_ids' => ['a1b2c3d4e5f6'],
            'prune_after' => true,
        ])->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Xóa snapshot chỉ hỗ trợ forget. Việc dọn dung lượng được hệ thống xử lý riêng theo bảo trì.'
            );
    }

    public function test_forget_command_can_fallback_snapshot_ids_from_existing_run_state(): void
    {
        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $runId = 'forgetrun01';
        $stateStore->initialize($runId, [
            'status' => 'queued',
            'operation' => 'forget',
            'trigger' => 'manual',
            'requested_at' => now()->toIso8601String(),
            'snapshot_ids' => ['a1b2c3d4e5f6'],
            'message' => '�� x?p l?ch x�a snapshot d� ch?n.',
        ]);

        $manager = \Mockery::mock(ResticBackupManager::class);
        $manager->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('a1b2c3d4e5f6');
        $manager->shouldReceive('forgetSnapshots')
            ->once()
            ->with(['a1b2c3d4e5f6'])
            ->andReturn([
                'snapshot_ids' => ['a1b2c3d4e5f6'],
                'deleted_count' => 1,
                'stdout' => '',
                'stderr' => '',
            ]);
        $this->app->instance(ResticBackupManager::class, $manager);

        $this->artisan('spnc:backup:forget', [
            '--run-id' => $runId,
            '--trigger' => 'manual',
        ])->assertExitCode(0);

        $state = $stateStore->get($runId);
        $this->assertIsArray($state);
        $this->assertSame('success', $state['status'] ?? null);
        $this->assertSame('forget', $state['operation'] ?? null);
    }

    public function test_schedule_backup_run_is_skipped_when_recent_schedule_run_exists(): void
    {
        config()->set('backup.schedule.days', [1, 4]);

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $stateStore->initialize('schedprev1', [
            'status' => 'success',
            'operation' => 'backup',
            'trigger' => 'schedule',
            'requested_at' => now()->subHour()->toIso8601String(),
            'started_at' => now()->subHour()->toIso8601String(),
            'finished_at' => now()->subMinutes(50)->toIso8601String(),
            'message' => 'Backup ho�n t?t.',
        ]);

        $manager = \Mockery::mock(ResticBackupManager::class);
        $manager->shouldReceive('runBackup')->never();
        $manager->shouldReceive('pruneBackups')->never();
        $manager->shouldReceive('listSnapshots')->never();
        $this->app->instance(ResticBackupManager::class, $manager);

        $this->artisan('spnc:backup:run', [
            '--run-id' => 'schedskip1',
            '--trigger' => 'schedule',
        ])->assertExitCode(0);

        $state = $stateStore->get('schedskip1');
        $this->assertIsArray($state);
        $this->assertSame('success', $state['status'] ?? null);
        $this->assertSame('skipped_schedule_window', $state['step'] ?? null);
    }

    public function test_completed_backup_reconciles_new_snapshot_cache_row_immediately(): void
    {
        config()->set('backup.exports.enabled', false);

        $manager = \Mockery::mock(ResticBackupManager::class);
        $manager->shouldReceive('runBackup')
            ->once()
            ->with('runbkcache1', 'manual', null)
            ->andReturn([
                'snapshot' => [
                    'snapshot_id' => 'snapnew1234567890',
                    'snapshot_id_full' => 'snapnew1234567890',
                    'short_id' => 'snapnew1',
                    'backup_name' => 'Backup 14/03/2026 10:30:00',
                    'created_at' => '2026-03-14T10:30:00+07:00',
                    'hostname' => 'server-01',
                    'paths' => ['storage/app/backup-workspace/runbkcache1'],
                    'tags' => ['spnc_backup', 'run_id:runbkcache1', 'trigger:manual'],
                    'run_id' => 'runbkcache1',
                    'trigger' => 'manual',
                    'workspace_path' => 'testing/backup-workspace/runbkcache1',
                    'contains_db_dump' => true,
                    'contains_files' => true,
                    'backup_type' => 'full',
                    'export_available' => false,
                    'export_path' => null,
                    'export_drive_path' => null,
                    'export_generated_at' => null,
                    'export_bundle_filename' => null,
                    'export_artifacts' => [],
                ],
                'summary' => [
                    'data_added' => 2048,
                ],
                'verification' => [
                    'contains_db_dump' => true,
                    'contains_files' => true,
                ],
                'check' => [
                    'scheduled' => true,
                    'deferred' => true,
                ],
                'manifest_relative_path' => 'testing/backup-workspace/runbkcache1/manifest.json',
                'db_dump_relative_path' => 'testing/backup-workspace/runbkcache1/db/mysql.sql',
                'workspace_relative_path' => 'testing/backup-workspace/runbkcache1',
            ]);
        $manager->shouldReceive('buildScheduleMeta')
            ->andReturn([
                'mode' => 'fixed',
                'description_vi' => 'Lich co dinh',
                'interval_weeks' => 1,
                'weekday' => 1,
                'days' => [1, 4],
                'time' => '02:00',
            ]);
        $manager->shouldReceive('buildRetentionMeta')
            ->andReturn([
                'keep_last' => 12,
                'keep_weekly' => 8,
                'keep_monthly' => 6,
            ]);
        $manager->shouldReceive('buildExportOverview')
            ->andReturn([
                'enabled' => false,
                'sync_to_drive' => false,
                'repository_type' => 'rclone',
                'export_root' => 'spnc-backups/exports',
                'export_folder_name' => 'exports',
                'note' => 'test',
            ]);
        $this->app->instance(ResticBackupManager::class, $manager);

        $this->artisan('spnc:backup:run', [
            '--run-id' => 'runbkcache1',
            '--trigger' => 'manual',
        ])->assertExitCode(0);

        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        $snapshot = $snapshotStore->findBySnapshotId('snapnew1234567890');
        $this->assertIsArray($snapshot);
        $this->assertSame('success', $snapshot['status'] ?? null);
        $this->assertSame('success', $snapshot['run_state'] ?? null);
        $this->assertSame(2048, $snapshot['size_bytes'] ?? null);
        $this->assertSame('full', $snapshot['backup_type'] ?? null);

        $this->actingAsScienceOffice();
        $this->getJson('/api/admin/backups')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.snapshot_id', 'snapnew1234567890')
            ->assertJsonPath('data.items.0.status', 'success')
            ->assertJsonPath('data.items.0.run_state', 'success')
            ->assertJsonPath('data.items.0.size_bytes', 2048)
            ->assertJsonPath('data.items.0.backup_type', 'full');
    }

    public function test_work_summary_pdf_renderer_accepts_structured_file_type_metadata(): void
    {
        /** @var ReadableExportPdfRenderer $renderer */
        $renderer = app(ReadableExportPdfRenderer::class);

        $pdf = $renderer->renderWorkSummary([
            'snapshot' => [
                'generated_at' => '2026-03-14T10:30:00+07:00',
            ],
            'activity' => [
                'activity_code' => 'RA-001',
                'title' => 'Bao cao tong hop',
                'kind' => ['name' => 'Hoi thao'],
                'type' => ['name' => 'Bao cao'],
                'status' => ['name' => 'Da duyet'],
                'academic_year' => ['code' => '2025-2026'],
                'total_hours_calc' => 8,
            ],
            'owner' => [
                'full_name' => 'Nguyen Van A',
                'code' => 'GV-001',
                'faculty' => ['name' => 'Khoa CNTT'],
                'department' => ['name' => 'Bo mon KTPM'],
            ],
            'participants' => [],
            'evidence_files' => [
                [
                    'stored_filename' => 'minh-chung-001.pdf',
                    'file_type' => [
                        'id' => 1,
                        'name' => 'PDF minh chung',
                    ],
                    'uploaded_by' => [
                        'full_name' => 'Nguyen Van A',
                        'code' => 'GV-001',
                    ],
                    'uploaded_at' => '2026-03-14T10:35:00+07:00',
                    'size_bytes' => 4096,
                    'export_status' => 'copied',
                ],
            ],
            'stats' => [
                'participant_count' => 0,
                'evidence_file_count' => 1,
                'copied_evidence_count' => 1,
                'metadata_only_evidence_count' => 0,
            ],
        ]);

        $this->assertIsString($pdf);
        $this->assertNotSame('', $pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_backup_postprocess_finalizes_export_metadata_after_success(): void
    {
        /** @var BackupSnapshotStore $snapshotStore */
        $snapshotStore = app(BackupSnapshotStore::class);
        $snapshotStore->replace([
            [
                'snapshot_id' => 'snapexport123456',
                'snapshot_id_full' => 'snapexport123456',
                'short_id' => 'snapexpo',
                'backup_name' => 'Backup 14/03/2026 11:00:00',
                'created_at' => '2026-03-14T11:00:00+07:00',
                'run_id' => 'parentrun1',
                'run_state' => 'success',
                'status' => 'success',
                'trigger' => 'manual',
                'size_bytes' => 1024,
                'backup_type' => 'full',
                'contains_db_dump' => true,
                'contains_files' => true,
                'export_available' => false,
                'export_path' => null,
                'export_drive_path' => null,
                'export_generated_at' => null,
                'export_bundle_filename' => null,
                'export_artifacts' => [],
            ],
        ], 'parentrun1');

        $manager = \Mockery::mock(ResticBackupManager::class);
        $manager->shouldReceive('assertValidSnapshotId')
            ->once()
            ->with('snapexport123456');
        $manager->shouldReceive('generateReadableExportForSnapshot')
            ->once()
            ->with('snapexport123456', 'postproc1', 'manual', null)
            ->andReturn([
                'available' => true,
                'snapshot_id' => 'snapexport123456',
                'folder_name' => '14-03-2026_11-00-00_Sao-luu-snapexpo',
                'export_path' => 'spnc_gdrive:spnc-backups/exports/14-03-2026_11-00-00_Sao-luu-snapexpo',
                'drive_path' => 'rclone:spnc_gdrive:spnc-backups/exports/14-03-2026_11-00-00_Sao-luu-snapexpo',
                'bundle_filename' => 'goi-sao-luu.zip',
                'generated_at' => '2026-03-14T11:05:00+07:00',
                'artifacts' => [
                    'README.txt',
                    'tong-quan.json',
                    'database/du-lieu.sql.gz',
                    'cong-trinh/',
                    'giang-vien/',
                ],
            ]);
        $this->app->instance(ResticBackupManager::class, $manager);

        $this->artisan('spnc:backup:post-process', [
            '--run-id' => 'postproc1',
            '--snapshot-id' => 'snapexport123456',
            '--trigger' => 'manual',
        ])->assertExitCode(0);

        /** @var BackupRunStateStore $stateStore */
        $stateStore = app(BackupRunStateStore::class);
        $run = $stateStore->get('postproc1');
        $this->assertIsArray($run);
        $this->assertSame('success', $run['status'] ?? null);
        $this->assertSame('backup_postprocess', $run['operation'] ?? null);
        $this->assertSame('snapexport123456', $run['snapshot_id'] ?? null);

        $snapshot = $snapshotStore->findBySnapshotId('snapexport123456');
        $this->assertIsArray($snapshot);
        $this->assertTrue((bool) ($snapshot['export_available'] ?? false));
        $this->assertSame(
            'spnc_gdrive:spnc-backups/exports/14-03-2026_11-00-00_Sao-luu-snapexpo',
            $snapshot['export_path'] ?? null
        );
        $this->assertSame(
            'rclone:spnc_gdrive:spnc-backups/exports/14-03-2026_11-00-00_Sao-luu-snapexpo',
            $snapshot['export_drive_path'] ?? null
        );
        $this->assertSame('2026-03-14T11:05:00+07:00', $snapshot['export_generated_at'] ?? null);
        $this->assertSame('goi-sao-luu.zip', $snapshot['export_bundle_filename'] ?? null);
        $this->assertSame([
            'README.txt',
            'tong-quan.json',
            'database/du-lieu.sql.gz',
            'cong-trinh/',
            'giang-vien/',
        ], $snapshot['export_artifacts'] ?? null);
    }

    private function actingAsScienceOffice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');
        Sanctum::actingAs($user);
    }

    private function createTempDownloadFile(string $fileName, string $content): string
    {
        $dir = storage_path('framework/testing/download-fixtures');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($path, $content);

        return $path;
    }
}

