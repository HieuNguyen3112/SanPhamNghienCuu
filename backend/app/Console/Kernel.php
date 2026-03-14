<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Dọn PAT hết hạn của Sanctum (giữ lại token đã hết hạn <= 48h để tránh xóa nhầm log)
        $schedule->command('sanctum:prune-expired --hours=48')->daily();

        $backupDays = $this->scheduleDays();
        $timezone = (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh');
        $backupTime = (string) config('backup.schedule.time', '02:00');
        $pruneTime = (string) config('backup.schedule.prune_time', '03:00');
        $verifyTime = (string) config('backup.verification.time', '04:00');
        $snapshotRefreshIntervalMinutes = min(59, max(5, (int) config('backup.snapshot_cache.refresh_interval_minutes', 10)));

        $schedule
            ->command('spnc:backup:run --trigger=schedule')
            ->days($backupDays)
            ->at($backupTime)
            ->timezone($timezone)
            ->withoutOverlapping(360)
            ->onOneServer();

        if ((bool) config('backup.snapshot_cache.auto_refresh', true)) {
            $schedule
                ->command('spnc:backup:snapshots:refresh --trigger=schedule')
                ->cron('*/' . $snapshotRefreshIntervalMinutes . ' * * * *')
                ->timezone($timezone)
                ->withoutOverlapping(30)
                ->onOneServer();
        }

        $evidenceCacheCleanupTime = (string) config('evidence.storage.hot_cache_cleanup_time', '04:30');
        $schedule
            ->command('spnc:evidence:cache:prune')
            ->dailyAt($evidenceCacheCleanupTime)
            ->timezone($timezone)
            ->withoutOverlapping(30)
            ->onOneServer();

        $schedule
            ->command('spnc:backup:prune --trigger=schedule')
            ->days($backupDays)
            ->at($pruneTime)
            ->timezone($timezone)
            ->withoutOverlapping(180)
            ->onOneServer();

        if ((bool) config('backup.verification.enabled', true)) {
            $schedule
                ->command('spnc:backup:check --trigger=schedule')
                ->dailyAt($verifyTime)
                ->timezone($timezone)
                ->withoutOverlapping(120)
                ->onOneServer();
        }
    }

    private function scheduleDays(): array
    {
        $days = array_values(array_filter(
            (array) config('backup.schedule.days', [1, 4]),
            static fn ($day): bool => is_numeric($day) && (int) $day >= 0 && (int) $day <= 6
        ));
        if ($days === []) {
            return [1, 4];
        }

        return array_values(array_unique(array_map(static fn ($day): int => (int) $day, $days)));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
