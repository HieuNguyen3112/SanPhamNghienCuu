<?php

namespace App\Jobs;

use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncResearchEvidenceToColdStorageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(
        public int $evidenceId,
        public ?string $hotRelativePath = null,
        public ?string $coldPath = null
    ) {
    }

    public function handle(ResearchEvidenceStorageService $storageService): void
    {
        try {
            $storageService->syncColdStorage(
                $this->evidenceId,
                $this->hotRelativePath,
                $this->coldPath
            );
        } catch (Throwable $exception) {
            Log::error('research_activity.evidence_cold_sync_failed', [
                'evidence_id' => $this->evidenceId,
                'hot_path' => $this->hotRelativePath,
                'cold_path' => $this->coldPath,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
