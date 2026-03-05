<?php

namespace App\Console\Commands;

use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SpncBackupDoctor extends Command
{
    protected $signature = 'spnc:backup:doctor
        {--snapshot-limit=10 : Gioi han thong tin snapshot khi chay smoke test}';

    protected $description = 'Thu thap chan doan runtime backup de doi chieu giua CLI va UI.';

    private ResticBackupManager $backupManager;

    public function __construct(ResticBackupManager $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle(): int
    {
        $snapshotLimit = max(1, min(50, (int) $this->option('snapshot-limit')));

        try {
            $report = $this->backupManager->buildDoctorReport($snapshotLimit);
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                $this->error('Khong the ma hoa ket qua doctor.');
                return self::FAILURE;
            }

            $this->line($encoded);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.doctor_command_failed', [
                'message' => $exception->getMessage(),
            ]);
            $this->error('Backup doctor that bai: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }
}
