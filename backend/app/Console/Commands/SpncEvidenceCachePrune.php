<?php

namespace App\Console\Commands;

use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SpncEvidenceCachePrune extends Command
{
    protected $signature = 'spnc:evidence:cache:prune';
    protected $description = 'Dọn cache hot storage của minh chứng theo TTL + LRU';

    public function handle(ResearchEvidenceStorageService $storageService): int
    {
        $summary = $storageService->cleanupHotCache();

        Log::info('research_activity.evidence_hot_cache_pruned', $summary);

        $this->info(sprintf(
            'Đã dọn cache minh chứng: xóa %d file (%d bytes), còn %d file (%d bytes).',
            (int) ($summary['removed_count'] ?? 0),
            (int) ($summary['removed_bytes'] ?? 0),
            (int) ($summary['remaining_count'] ?? 0),
            (int) ($summary['remaining_bytes'] ?? 0)
        ));

        return self::SUCCESS;
    }
}
