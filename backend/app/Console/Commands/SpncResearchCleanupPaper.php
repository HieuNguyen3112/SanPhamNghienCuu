<?php

namespace App\Console\Commands;

use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SpncResearchCleanupPaper extends Command
{
    private ResearchEvidenceStorageService $evidenceStorageService;

    protected $signature = 'spnc:research:cleanup-paper
        {--execute : Thuc thi xoa that}
        {--force-production : Cho phep chay xoa that tren production sau khi da co backup}
        {--chunk=50 : So cong trinh xu ly moi dot}
        {--limit=0 : Gioi han so cong trinh can quet}
        {--activity-id=* : Chi xu ly mot danh sach activity id cu the}';

    protected $description = 'Don toan bo research activity kind=paper theo duong ung dung, co xu ly minh chung va dong bo lai du lieu dan xuat.';

    public function __construct(ResearchEvidenceStorageService $evidenceStorageService)
    {
        parent::__construct();
        $this->evidenceStorageService = $evidenceStorageService;
    }

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $forceProduction = (bool) $this->option('force-production');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $activityIds = $this->normalizeActivityIds((array) $this->option('activity-id'));

        $scope = $this->buildScopeSummary($activityIds, $limit);
        $this->renderScopeSummary($scope, $execute);

        if (($scope['total_activities'] ?? 0) === 0) {
            $this->info('Khong tim thay research activity kind=paper trong pham vi hien tai.');
            return self::SUCCESS;
        }

        if (! $execute) {
            $this->warn('Dang o che do dry-run. Them --execute de thuc thi xoa that sau khi da backup DB.');
            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $forceProduction) {
            $this->error('Tu choi chay xoa that tren production khi chua co --force-production.');
            $this->line('Hay backup DB truoc, khoa cua so thao tac tao moi bai bao, roi chay lai voi --execute --force-production.');
            return self::FAILURE;
        }

        $targetIds = $this->resolveTargetActivityIds($activityIds, $limit);
        if ($targetIds === []) {
            $this->info('Khong con activity nao can xu ly.');
            return self::SUCCESS;
        }

        $stats = [
            'activities_targeted' => count($targetIds),
            'activities_deleted' => 0,
            'activities_missing' => 0,
            'db_delete_failures' => 0,
            'evidence_rows' => 0,
            'evidence_file_rows' => 0,
            'evidence_link_rows' => 0,
            'evidence_cleanup_attempted' => 0,
            'evidence_cleanup_failed' => 0,
            'hot_cache_cleanup_failed' => 0,
        ];
        $affectedPairs = [];

        Log::warning('research_activity.paper_cleanup_started', [
            'execute' => true,
            'environment' => app()->environment(),
            'target_count' => count($targetIds),
            'chunk_size' => $chunkSize,
            'limit' => $limit > 0 ? $limit : null,
            'activity_ids' => $activityIds !== [] ? $activityIds : null,
        ]);

        foreach (array_chunk($targetIds, $chunkSize) as $batchIds) {
            $activities = DB::table('research_activities as ra')
                ->leftJoin('activity_statuses as ast', 'ast.id', '=', 'ra.status_id')
                ->leftJoin('academic_years as ay', 'ay.id', '=', 'ra.academic_year_id')
                ->whereIn('ra.id', $batchIds)
                ->select([
                    'ra.id',
                    'ra.activity_code',
                    'ra.title',
                    'ra.owner_lecturer_id',
                    'ra.academic_year_id',
                    'ast.code as status_code',
                    'ay.code as academic_year_code',
                ])
                ->get()
                ->keyBy('id');

            $membersByActivity = DB::table('research_activity_members')
                ->whereIn('activity_id', $batchIds)
                ->select(['activity_id', 'lecturer_id'])
                ->get()
                ->groupBy('activity_id');

            $evidenceByActivity = DB::table('evidence_files')
                ->whereIn('activity_id', $batchIds)
                ->select(['id', 'activity_id', 'disk', 'path', 'original_name'])
                ->orderBy('id')
                ->get()
                ->groupBy('activity_id');

            foreach ($batchIds as $activityId) {
                $activity = $activities->get($activityId);
                if (! $activity) {
                    $stats['activities_missing']++;
                    continue;
                }

                $members = $membersByActivity->get($activityId, collect());
                $evidenceRows = $evidenceByActivity->get($activityId, collect());

                $this->collectAffectedLecturerYearPairs($activity, $members, $affectedPairs);

                $evidenceStats = $this->cleanupEvidenceForActivity($activity, $evidenceRows);
                foreach ($evidenceStats as $key => $value) {
                    $stats[$key] = ($stats[$key] ?? 0) + $value;
                }

                try {
                    $deleted = DB::transaction(function () use ($activityId) {
                        return DB::table('research_activities')->where('id', $activityId)->delete();
                    });
                } catch (Throwable $exception) {
                    $stats['db_delete_failures']++;
                    Log::error('research_activity.paper_cleanup_delete_failed', [
                        'activity_id' => $activityId,
                        'activity_code' => (string) ($activity->activity_code ?? ''),
                        'title' => (string) ($activity->title ?? ''),
                        'error' => $exception->getMessage(),
                    ]);
                    $this->warn('Delete fail activity #' . $activityId . ': ' . $exception->getMessage());
                    continue;
                }

                if ((int) $deleted > 0) {
                    $stats['activities_deleted']++;
                    continue;
                }

                $stats['activities_missing']++;
                Log::warning('research_activity.paper_cleanup_delete_missing', [
                    'activity_id' => $activityId,
                    'activity_code' => (string) ($activity->activity_code ?? ''),
                ]);
            }
        }

        $recomputeStats = $this->recomputeDerivedData(array_values($affectedPairs));

        Log::warning('research_activity.paper_cleanup_completed', [
            'stats' => $stats,
            'recompute' => $recomputeStats,
            'environment' => app()->environment(),
        ]);

        $this->renderExecutionSummary($stats, $recomputeStats);

        if (($stats['db_delete_failures'] ?? 0) > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeActivityIds(array $rawIds): array
    {
        $normalized = array_map(static fn ($value): int => (int) $value, $rawIds);
        $normalized = array_values(array_filter($normalized, static fn (int $id): bool => $id > 0));
        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function buildScopeSummary(array $activityIds, int $limit): array
    {
        $targetIds = $this->resolveTargetActivityIds($activityIds, $limit);
        $totalActivities = count($targetIds);

        $statusBreakdown = DB::table('research_activities as ra')
            ->join('activity_kinds as ak', 'ak.id', '=', 'ra.kind_id')
            ->leftJoin('activity_statuses as ast', 'ast.id', '=', 'ra.status_id')
            ->when($activityIds !== [], fn ($query) => $query->whereIn('ra.id', $activityIds))
            ->whereRaw('LOWER(ak.code) = ?', ['paper'])
            ->when($limit > 0, fn ($query) => $query->whereIn('ra.id', $targetIds))
            ->selectRaw("COALESCE(ast.code, 'unknown') as status_code, COUNT(*) as aggregate")
            ->groupBy('status_code')
            ->orderBy('status_code')
            ->get()
            ->map(fn ($row) => [
                'status_code' => (string) $row->status_code,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        $summary = [
            'total_activities' => $totalActivities,
            'selected_activity_ids' => $activityIds,
            'limit' => $limit,
            'status_breakdown' => $statusBreakdown,
            'orphan_paper_details' => (int) DB::table('paper_details as pd')
                ->leftJoin('research_activities as ra', 'ra.id', '=', 'pd.activity_id')
                ->whereNull('ra.id')
                ->count(),
        ];

        if ($targetIds === []) {
            $summary['samples'] = [];
            $summary['related_counts'] = [];
            return $summary;
        }

        $summary['samples'] = DB::table('research_activities as ra')
            ->leftJoin('activity_statuses as ast', 'ast.id', '=', 'ra.status_id')
            ->leftJoin('lecturers as l', 'l.id', '=', 'ra.owner_lecturer_id')
            ->whereIn('ra.id', array_slice($targetIds, 0, 10))
            ->orderBy('ra.id')
            ->select([
                'ra.id',
                'ra.activity_code',
                'ra.title',
                'ast.code as status_code',
                'l.full_name as owner_name',
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'activity_code' => (string) ($row->activity_code ?? ''),
                'title' => (string) ($row->title ?? ''),
                'status_code' => (string) ($row->status_code ?? 'unknown'),
                'owner_name' => (string) ($row->owner_name ?? ''),
            ])
            ->all();

        $relatedCounts = [
            'paper_details' => (int) DB::table('paper_details')->whereIn('activity_id', $targetIds)->count(),
            'research_activity_members' => (int) DB::table('research_activity_members')->whereIn('activity_id', $targetIds)->count(),
            'activity_status_histories' => (int) DB::table('activity_status_histories')->whereIn('activity_id', $targetIds)->count(),
            'activity_approvals' => (int) DB::table('activity_approvals')->whereIn('activity_id', $targetIds)->count(),
            'evidence_files' => (int) DB::table('evidence_files')->whereIn('activity_id', $targetIds)->count(),
            'calculation_logs' => (int) DB::table('calculation_logs')->whereIn('activity_id', $targetIds)->count(),
        ];

        if (Schema::hasTable('activity_member_approvals')) {
            $relatedCounts['activity_member_approvals'] = (int) DB::table('activity_member_approvals')
                ->whereIn('activity_id', $targetIds)
                ->count();
        }

        $summary['related_counts'] = $relatedCounts;
        $summary['evidence_file_breakdown'] = [
            'link_rows' => (int) DB::table('evidence_files')
                ->whereIn('activity_id', $targetIds)
                ->where('disk', ResearchEvidenceStorageService::LINK_DISK)
                ->count(),
            'stored_rows' => (int) DB::table('evidence_files')
                ->whereIn('activity_id', $targetIds)
                ->where('disk', '<>', ResearchEvidenceStorageService::LINK_DISK)
                ->count(),
        ];
        $summary['activities_missing_paper_detail'] = (int) DB::table('research_activities as ra')
            ->join('activity_kinds as ak', 'ak.id', '=', 'ra.kind_id')
            ->leftJoin('paper_details as pd', 'pd.activity_id', '=', 'ra.id')
            ->whereIn('ra.id', $targetIds)
            ->whereRaw('LOWER(ak.code) = ?', ['paper'])
            ->whereNull('pd.activity_id')
            ->count();

        return $summary;
    }

    /**
     * @return array<int, int>
     */
    private function resolveTargetActivityIds(array $activityIds, int $limit): array
    {
        $query = $this->targetActivitiesQuery($activityIds)
            ->orderBy('ra.id')
            ->select('ra.id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->pluck('ra.id')->map(fn ($id) => (int) $id)->all();
    }

    private function targetActivitiesQuery(array $activityIds = [])
    {
        return DB::table('research_activities as ra')
            ->join('activity_kinds as ak', 'ak.id', '=', 'ra.kind_id')
            ->whereRaw('LOWER(ak.code) = ?', ['paper'])
            ->when($activityIds !== [], fn ($query) => $query->whereIn('ra.id', $activityIds));
    }

    private function renderScopeSummary(array $scope, bool $execute): void
    {
        $this->line('Pham vi cleanup research article (kind=paper):');
        $this->line('- Mode: ' . ($execute ? 'execute' : 'dry-run'));
        $this->line('- Tong activity trong scope: ' . (int) ($scope['total_activities'] ?? 0));

        if (! empty($scope['selected_activity_ids'])) {
            $this->line('- Loc theo activity_id: ' . implode(', ', $scope['selected_activity_ids']));
        }
        if ((int) ($scope['limit'] ?? 0) > 0) {
            $this->line('- Limit dang ap dung: ' . (int) $scope['limit']);
        }

        $relatedCounts = (array) ($scope['related_counts'] ?? []);
        foreach ($relatedCounts as $label => $count) {
            $this->line('- Lien quan ' . $label . ': ' . (int) $count);
        }

        $breakdown = (array) ($scope['evidence_file_breakdown'] ?? []);
        if ($breakdown !== []) {
            $this->line('- Evidence stored rows: ' . (int) ($breakdown['stored_rows'] ?? 0));
            $this->line('- Evidence link rows: ' . (int) ($breakdown['link_rows'] ?? 0));
        }

        $this->line('- Activity thieu paper_details: ' . (int) ($scope['activities_missing_paper_detail'] ?? 0));
        $this->line('- Orphan paper_details: ' . (int) ($scope['orphan_paper_details'] ?? 0));

        $statusBreakdown = (array) ($scope['status_breakdown'] ?? []);
        if ($statusBreakdown !== []) {
            $this->line('- Breakdown theo status:');
            foreach ($statusBreakdown as $row) {
                $this->line('  * ' . $row['status_code'] . ': ' . $row['count']);
            }
        }

        $samples = (array) ($scope['samples'] ?? []);
        if ($samples !== []) {
            $this->line('- Mau 10 activity dau:');
            foreach ($samples as $sample) {
                $this->line(sprintf(
                    '  * #%d [%s] %s | owner=%s | status=%s',
                    (int) $sample['id'],
                    (string) $sample['activity_code'],
                    (string) $sample['title'],
                    (string) ($sample['owner_name'] !== '' ? $sample['owner_name'] : 'unknown'),
                    (string) $sample['status_code']
                ));
            }
        }
    }

    private function cleanupEvidenceForActivity(object $activity, Collection $evidenceRows): array
    {
        $stats = [
            'evidence_rows' => $evidenceRows->count(),
            'evidence_file_rows' => 0,
            'evidence_link_rows' => 0,
            'evidence_cleanup_attempted' => 0,
            'evidence_cleanup_failed' => 0,
            'hot_cache_cleanup_failed' => 0,
        ];

        foreach ($evidenceRows as $row) {
            $disk = trim((string) ($row->disk ?? ''));
            $path = trim((string) ($row->path ?? ''));

            if ($disk === ResearchEvidenceStorageService::LINK_DISK || $path === '') {
                $stats['evidence_link_rows']++;
                continue;
            }

            $stats['evidence_file_rows']++;
            $stats['evidence_cleanup_attempted']++;

            try {
                if ($this->evidenceStorageService->isRcloneDisk($disk)) {
                    $this->evidenceStorageService->deleteFromRclone($path);
                } else {
                    $this->evidenceStorageService->deleteFromLocalDisk($disk !== '' ? $disk : 'local', $path);
                }
            } catch (Throwable $exception) {
                $stats['evidence_cleanup_failed']++;
                Log::warning('research_activity.paper_cleanup_evidence_failed', [
                    'activity_id' => (int) $activity->id,
                    'activity_code' => (string) ($activity->activity_code ?? ''),
                    'evidence_id' => (int) ($row->id ?? 0),
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }

            if (! $this->evidenceStorageService->isRcloneDisk($disk)) {
                continue;
            }

            try {
                $this->evidenceStorageService->deleteHotCacheByColdPath($path);
            } catch (Throwable $exception) {
                $stats['hot_cache_cleanup_failed']++;
                Log::warning('research_activity.paper_cleanup_hot_cache_failed', [
                    'activity_id' => (int) $activity->id,
                    'activity_code' => (string) ($activity->activity_code ?? ''),
                    'evidence_id' => (int) ($row->id ?? 0),
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    private function collectAffectedLecturerYearPairs(object $activity, Collection $members, array &$pairs): void
    {
        $academicYearId = (int) ($activity->academic_year_id ?? 0);
        if ($academicYearId <= 0) {
            return;
        }

        $ownerLecturerId = (int) ($activity->owner_lecturer_id ?? 0);
        if ($ownerLecturerId > 0) {
            $pairs[$ownerLecturerId . ':' . $academicYearId] = [
                'lecturer_id' => $ownerLecturerId,
                'academic_year_id' => $academicYearId,
                'academic_year_code' => (string) ($activity->academic_year_code ?? ''),
            ];
        }

        foreach ($members as $member) {
            $lecturerId = (int) ($member->lecturer_id ?? 0);
            if ($lecturerId <= 0) {
                continue;
            }

            $pairs[$lecturerId . ':' . $academicYearId] = [
                'lecturer_id' => $lecturerId,
                'academic_year_id' => $academicYearId,
                'academic_year_code' => (string) ($activity->academic_year_code ?? ''),
            ];
        }
    }

    private function recomputeDerivedData(array $affectedPairs): array
    {
        $stats = [
            'affected_pairs' => count($affectedPairs),
            'lecturer_yearly_hours_updated' => 0,
            'lecturer_yearly_hours_inserted' => 0,
            'lecturer_hour_warnings_cleared' => 0,
            'hours_stage_missing' => false,
        ];

        if ($affectedPairs === []) {
            return $stats;
        }

        $hoursStageId = DB::table('approval_stages')->where('code', 'hours')->value('id');
        $hoursStageId = $hoursStageId ? (int) $hoursStageId : null;
        if (! $hoursStageId) {
            $stats['hours_stage_missing'] = true;
            Log::warning('research_activity.paper_cleanup_hours_stage_missing');
        }

        $now = now();

        foreach ($affectedPairs as $pair) {
            $lecturerId = (int) ($pair['lecturer_id'] ?? 0);
            $academicYearId = (int) ($pair['academic_year_id'] ?? 0);

            if ($lecturerId <= 0 || $academicYearId <= 0) {
                continue;
            }

            if ($hoursStageId) {
                $approvedHours = $this->resolveApprovedHoursTotal($lecturerId, $academicYearId, $hoursStageId);
                $existingId = DB::table('lecturer_yearly_hours')
                    ->where('lecturer_id', $lecturerId)
                    ->where('academic_year_id', $academicYearId)
                    ->value('id');

                if ($existingId) {
                    DB::table('lecturer_yearly_hours')
                        ->where('id', (int) $existingId)
                        ->update([
                            'hours_total' => $approvedHours,
                            'updated_at' => $now,
                        ]);
                    $stats['lecturer_yearly_hours_updated']++;
                } else {
                    DB::table('lecturer_yearly_hours')->insert([
                        'lecturer_id' => $lecturerId,
                        'academic_year_id' => $academicYearId,
                        'hours_total' => $approvedHours,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $stats['lecturer_yearly_hours_inserted']++;
                }
            }

            if (Schema::hasTable('lecturer_hour_warnings')) {
                $stats['lecturer_hour_warnings_cleared'] += DB::table('lecturer_hour_warnings')
                    ->where('lecturer_id', $lecturerId)
                    ->where('academic_year_id', $academicYearId)
                    ->delete();
            }
        }

        return $stats;
    }

    private function resolveApprovedHoursTotal(int $lecturerId, int $academicYearId, int $hoursStageId): float
    {
        return (float) DB::table('activity_approvals as aa')
            ->join('research_activities as ra', 'aa.activity_id', '=', 'ra.id')
            ->join('research_activity_members as ram', function ($join) use ($lecturerId) {
                $join->on('ram.activity_id', '=', 'ra.id')
                    ->where('ram.lecturer_id', '=', $lecturerId);
            })
            ->where('aa.stage_id', $hoursStageId)
            ->where('aa.status', 'approved')
            ->where('ra.academic_year_id', $academicYearId)
            ->sum(DB::raw('COALESCE(ram.hours_assigned, 0)'));
    }

    private function renderExecutionSummary(array $stats, array $recomputeStats): void
    {
        $this->info('Da hoan tat cleanup paper activity.');
        $this->line('- Activity targeted: ' . (int) ($stats['activities_targeted'] ?? 0));
        $this->line('- Activity deleted: ' . (int) ($stats['activities_deleted'] ?? 0));
        $this->line('- Activity missing/skipped: ' . (int) ($stats['activities_missing'] ?? 0));
        $this->line('- DB delete failures: ' . (int) ($stats['db_delete_failures'] ?? 0));
        $this->line('- Evidence rows touched: ' . (int) ($stats['evidence_rows'] ?? 0));
        $this->line('- Stored evidence rows: ' . (int) ($stats['evidence_file_rows'] ?? 0));
        $this->line('- Link evidence rows: ' . (int) ($stats['evidence_link_rows'] ?? 0));
        $this->line('- Evidence cleanup attempted: ' . (int) ($stats['evidence_cleanup_attempted'] ?? 0));
        $this->line('- Evidence cleanup failed: ' . (int) ($stats['evidence_cleanup_failed'] ?? 0));
        $this->line('- Hot cache cleanup failed: ' . (int) ($stats['hot_cache_cleanup_failed'] ?? 0));
        $this->line('- Affected lecturer/year pairs: ' . (int) ($recomputeStats['affected_pairs'] ?? 0));
        $this->line('- lecturer_yearly_hours updated: ' . (int) ($recomputeStats['lecturer_yearly_hours_updated'] ?? 0));
        $this->line('- lecturer_yearly_hours inserted: ' . (int) ($recomputeStats['lecturer_yearly_hours_inserted'] ?? 0));
        $this->line('- lecturer_hour_warnings cleared: ' . (int) ($recomputeStats['lecturer_hour_warnings_cleared'] ?? 0));

        if ((bool) ($recomputeStats['hours_stage_missing'] ?? false)) {
            $this->warn('Khong tim thay approval stage code=hours, bo qua buoc cap nhat lecturer_yearly_hours.');
        }

        if ((int) ($stats['evidence_cleanup_failed'] ?? 0) > 0 || (int) ($stats['hot_cache_cleanup_failed'] ?? 0) > 0) {
            $this->warn('Co tep minh chung/hot cache khong dọn duoc hoan toan. Xem log research_activity.paper_cleanup_* de xu ly tiep.');
        }
    }
}
