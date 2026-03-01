<?php

namespace App\Console\Commands;

use App\Services\Backup\ResticBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SpncBackupPrune extends Command
{
    protected $signature = 'spnc:backup:prune {--trigger=manual : Nguồn kích hoạt prune}';

    protected $description = 'Dọn snapshot backup cũ theo retention policy.';

    private ResticBackupManager $backupManager;

    public function __construct(ResticBackupManager $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle(): int
    {
        $trigger = trim((string) $this->option('trigger'));

        try {
            $result = $this->backupManager->pruneBackups();
            $this->info('Prune hoàn tất.');
            if (! empty($result['stdout'])) {
                $this->line((string) $result['stdout']);
            }
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('backup.prune_failed', [
                'trigger' => $trigger !== '' ? $trigger : null,
                'message' => $exception->getMessage(),
            ]);
            $this->error('Prune thất bại: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }
}
