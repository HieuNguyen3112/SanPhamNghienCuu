<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Backup\BackupRunLauncher;
use App\Services\Backup\BackupRunStateStore;
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
            'message' => 'Đang tạo snapshot backup...',
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
            'message' => 'Đang tạo snapshot backup...',
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
            'message' => 'Đang tạo snapshot backup...',
        ]);

        $launcher = \Mockery::mock(BackupRunLauncher::class);
        $launcher->shouldReceive('launchBackup')->never();
        $this->app->instance(BackupRunLauncher::class, $launcher);

        $this->postJson('/api/admin/backups/run')
            ->assertStatus(409)
            ->assertJsonPath('data.conflict_run.run_id', $conflictRunId);
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
            'prune_after' => false,
        ])->assertStatus(202)
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.operation', 'forget')
            ->assertJsonPath('data.snapshot_ids.0', 'a1b2c3d4e5f6');
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
            'message' => 'Đang chờ đồng bộ snapshot.',
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
            'message' => 'Đã xếp lịch xóa snapshot đã chọn.',
        ]);

        $this->getJson('/api/admin/backups/runs/' . $runId)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.step', 'timeout')
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
            'message' => 'Đang xóa snapshot đã chọn khỏi repository restic...',
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
            'message' => 'Xóa snapshot thất bại.',
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
        ])->assertStatus(422);
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
            'message' => 'Đã xếp lịch xóa snapshot đã chọn.',
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
            'message' => 'Backup hoàn tất.',
        ]);

        $manager = \Mockery::mock(ResticBackupManager::class);
        $manager->shouldReceive('runBackup')->never();
        $manager->shouldReceive('pruneBackups')->never();
        $manager->shouldReceive('listSnapshots')->never();
        $this->app->instance(ResticBackupManager::class, $manager);

        $this->artisan('spnc:backup:run', [
            '--run-id' => 'schedskip1',
            '--trigger' => 'schedule',
            '--skip-prune' => true,
        ])->assertExitCode(0);

        $state = $stateStore->get('schedskip1');
        $this->assertIsArray($state);
        $this->assertSame('success', $state['status'] ?? null);
        $this->assertSame('skipped_schedule_window', $state['step'] ?? null);
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

