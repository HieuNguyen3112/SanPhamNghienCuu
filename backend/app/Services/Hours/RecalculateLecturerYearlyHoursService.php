<?php

namespace App\Services\Hours;

use Illuminate\Support\Facades\DB;

class RecalculateLecturerYearlyHoursService
{
    private ?int $hoursStageIdCache = null;

    public function recalculate(?int $lecturerId = null, ?int $academicYearId = null): array
    {
        $timestamp = now();
        $totalsByPair = $this->approvedHoursTotalsByPair(
            $lecturerId ? [$lecturerId] : [],
            $academicYearId ? [$academicYearId] : []
        );

        $scopePairs = $this->buildScopePairsForRecalculate(
            $lecturerId,
            $academicYearId,
            $totalsByPair
        );

        return $this->syncPairs($scopePairs, $totalsByPair, $timestamp);
    }

    public function recalculateForActivities(array $activityIds): array
    {
        $activityIds = collect($activityIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($activityIds === []) {
            return [
                'total_pairs' => 0,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
            ];
        }

        $scopePairs = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ra.id', '=', 'ram.activity_id')
            ->whereIn('ram.activity_id', $activityIds)
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->select([
                'ram.lecturer_id',
                'ra.academic_year_id',
            ])
            ->distinct()
            ->get()
            ->map(fn($row) => [
                'lecturer_id' => (int) $row->lecturer_id,
                'academic_year_id' => (int) $row->academic_year_id,
            ])
            ->values()
            ->all();

        if ($scopePairs === []) {
            return [
                'total_pairs' => 0,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
            ];
        }

        $lecturerIds = collect($scopePairs)
            ->pluck('lecturer_id')
            ->unique()
            ->values()
            ->all();

        $academicYearIds = collect($scopePairs)
            ->pluck('academic_year_id')
            ->unique()
            ->values()
            ->all();

        $totalsByPair = $this->approvedHoursTotalsByPair($lecturerIds, $academicYearIds);

        return $this->syncPairs($scopePairs, $totalsByPair, now());
    }

    private function buildScopePairsForRecalculate(
        ?int $lecturerId,
        ?int $academicYearId,
        array $totalsByPair
    ): array {
        $existingQuery = DB::table('lecturer_yearly_hours')
            ->select(['lecturer_id', 'academic_year_id']);

        if ($lecturerId) {
            $existingQuery->where('lecturer_id', $lecturerId);
        }

        if ($academicYearId) {
            $existingQuery->where('academic_year_id', $academicYearId);
        }

        $existingPairs = $existingQuery
            ->get()
            ->map(fn($row) => [
                'lecturer_id' => (int) $row->lecturer_id,
                'academic_year_id' => (int) $row->academic_year_id,
            ])
            ->values()
            ->all();

        $derivedPairs = collect(array_keys($totalsByPair))
            ->map(function ($key) {
                [$lecturerId, $academicYearId] = explode(':', $key);

                return [
                    'lecturer_id' => (int) $lecturerId,
                    'academic_year_id' => (int) $academicYearId,
                ];
            })
            ->values()
            ->all();

        return $this->uniquePairs(array_merge($existingPairs, $derivedPairs));
    }

    private function syncPairs(array $scopePairs, array $totalsByPair, $timestamp): array
    {
        $scopePairs = $this->uniquePairs($scopePairs);
        if ($scopePairs === []) {
            return [
                'total_pairs' => 0,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
            ];
        }

        $lecturerIds = collect($scopePairs)
            ->pluck('lecturer_id')
            ->unique()
            ->values()
            ->all();

        $academicYearIds = collect($scopePairs)
            ->pluck('academic_year_id')
            ->unique()
            ->values()
            ->all();

        $existingRows = DB::table('lecturer_yearly_hours')
            ->whereIn('lecturer_id', $lecturerIds)
            ->whereIn('academic_year_id', $academicYearIds)
            ->select(['id', 'lecturer_id', 'academic_year_id', 'hours_total'])
            ->get()
            ->keyBy(fn($row) => $this->pairKey((int) $row->lecturer_id, (int) $row->academic_year_id));

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach ($scopePairs as $pair) {
            $lecturerId = (int) $pair['lecturer_id'];
            $academicYearId = (int) $pair['academic_year_id'];
            $pairKey = $this->pairKey($lecturerId, $academicYearId);
            $hoursTotal = (float) ($totalsByPair[$pairKey] ?? 0.0);

            $existing = $existingRows->get($pairKey);
            if ($existing) {
                $currentHours = (float) ($existing->hours_total ?? 0.0);
                if ($this->sameDecimal($currentHours, $hoursTotal)) {
                    $unchanged++;
                    continue;
                }

                DB::table('lecturer_yearly_hours')
                    ->where('id', (int) $existing->id)
                    ->update([
                        'hours_total' => $hoursTotal,
                        'updated_at' => $timestamp,
                    ]);
                $updated++;
                continue;
            }

            DB::table('lecturer_yearly_hours')->insert([
                'lecturer_id' => $lecturerId,
                'academic_year_id' => $academicYearId,
                'hours_total' => $hoursTotal,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            $created++;
        }

        return [
            'total_pairs' => count($scopePairs),
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
        ];
    }

    private function approvedHoursTotalsByPair(array $lecturerIds = [], array $academicYearIds = []): array
    {
        $hoursStageId = $this->resolveHoursStageId();
        if (! $hoursStageId) {
            return [];
        }

        $query = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ra.id', '=', 'ram.activity_id')
            ->leftJoin('activity_member_approvals as ama_hours', function ($join) use ($hoursStageId) {
                $join->on('ama_hours.activity_id', '=', 'ra.id')
                    ->on('ama_hours.lecturer_id', '=', 'ram.lecturer_id')
                    ->where('ama_hours.stage_id', '=', $hoursStageId);
            })
            ->leftJoin('activity_approvals as aa_hours', function ($join) use ($hoursStageId) {
                $join->on('aa_hours.activity_id', '=', 'ra.id')
                    ->where('aa_hours.stage_id', '=', $hoursStageId);
            })
            ->whereNotNull('ra.academic_year_id')
            ->where(function ($query) {
                $query->where('ram.confirmation_status', 'accepted')
                    ->orWhereColumn('ram.lecturer_id', 'ra.owner_lecturer_id');
            })
            ->whereRaw("COALESCE(ama_hours.status, aa_hours.status) = 'approved'");

        if ($lecturerIds !== []) {
            $query->whereIn('ram.lecturer_id', $lecturerIds);
        }

        if ($academicYearIds !== []) {
            $query->whereIn('ra.academic_year_id', $academicYearIds);
        }

        $rows = $query
            ->selectRaw('ram.lecturer_id as lecturer_id')
            ->selectRaw('ra.academic_year_id as academic_year_id')
            ->selectRaw('COALESCE(SUM(COALESCE(ram.hours_assigned, 0)), 0) as hours_total')
            ->groupBy('ram.lecturer_id', 'ra.academic_year_id')
            ->get();

        $totalsByPair = [];
        foreach ($rows as $row) {
            $key = $this->pairKey((int) $row->lecturer_id, (int) $row->academic_year_id);
            $totalsByPair[$key] = (float) $row->hours_total;
        }

        return $totalsByPair;
    }

    private function uniquePairs(array $pairs): array
    {
        $seen = [];
        $result = [];

        foreach ($pairs as $pair) {
            $lecturerId = (int) ($pair['lecturer_id'] ?? 0);
            $academicYearId = (int) ($pair['academic_year_id'] ?? 0);
            if ($lecturerId <= 0 || $academicYearId <= 0) {
                continue;
            }

            $key = $this->pairKey($lecturerId, $academicYearId);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = [
                'lecturer_id' => $lecturerId,
                'academic_year_id' => $academicYearId,
            ];
        }

        return $result;
    }

    private function resolveHoursStageId(): ?int
    {
        if ($this->hoursStageIdCache !== null) {
            return $this->hoursStageIdCache;
        }

        $id = DB::table('approval_stages')
            ->where('code', 'hours')
            ->value('id');

        $this->hoursStageIdCache = $id ? (int) $id : null;

        return $this->hoursStageIdCache;
    }

    private function pairKey(int $lecturerId, int $academicYearId): string
    {
        return $lecturerId . ':' . $academicYearId;
    }

    private function sameDecimal(float $left, float $right): bool
    {
        return abs($left - $right) < 0.0001;
    }
}
