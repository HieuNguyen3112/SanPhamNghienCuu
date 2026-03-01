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

        $backupDays = array_values(array_filter(
            (array) config('backup.schedule.days', [1, 4]),
            static fn ($day): bool => is_numeric($day) && (int) $day >= 0 && (int) $day <= 6
        ));
        if ($backupDays === []) {
            $backupDays = [1, 4];
        }

        $timezone = (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh');
        $backupTime = (string) config('backup.schedule.time', '02:00');
        $pruneTime = (string) config('backup.schedule.prune_time', '03:00');

        $schedule
            ->command('spnc:backup:run --trigger=schedule')
            ->days($backupDays)
            ->at($backupTime)
            ->timezone($timezone)
            ->withoutOverlapping(360)
            ->onOneServer();

        $schedule
            ->command('spnc:backup:prune --trigger=schedule')
            ->days($backupDays)
            ->at($pruneTime)
            ->timezone($timezone)
            ->withoutOverlapping(180)
            ->onOneServer();
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
