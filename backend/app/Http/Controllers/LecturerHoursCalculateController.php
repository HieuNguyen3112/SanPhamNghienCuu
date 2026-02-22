<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerHoursCalculateIndexRequest;
use App\Http\Requests\Lecturer\LecturerHoursCalculateSubmitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerHoursCalculateController extends Controller
{
    public function index(LecturerHoursCalculateIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $assistantStageId = $this->resolveStageId('assistant');
        $managerStageId = $this->resolveStageId('manager');
        if (! $assistantStageId || ! $managerStageId) {
            return response()->json(['message' => 'approval stages not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $hoursStageId = $this->resolveStageId('hours') ?? 0;
        $approvedStatusId = $this->resolveStatusId('approved');

        $filters = $request->validated();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 12)));

        $query = $this->baseQuery($lecturer->id, $assistantStageId, $managerStageId, $hoursStageId, $approvedStatusId);
        $this->applyFilters($query, $filters);

        $query->orderByDesc('ra.updated_at');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            $hoursState = $this->resolveHoursRequestState($row->hours_approval_status ?? null);
            return [
                'activity_id' => (int) $row->activity_id,
                'activity_code' => $row->activity_code,
                'academic_year_code' => $row->academic_year_code,
                'title' => $row->title,
                'kind_name' => $row->kind_name,
                'member_role_name' => $row->member_role_name,
                'hours_assigned' => $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
                'activity_status_code' => $row->activity_status_code,
                'assistant_approval_status' => $row->assistant_approval_status,
                'manager_approval_status' => $row->manager_approval_status,
                'hours_request_state' => $hoursState,
            ];
        })->all();

        $approvedCount = $this->approvedCount($lecturer->id, $assistantStageId, $managerStageId, $approvedStatusId);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'summary' => [
                    'approved_count' => $approvedCount,
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activityId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $assistantStageId = $this->resolveStageId('assistant');
        $managerStageId = $this->resolveStageId('manager');
        if (! $assistantStageId || ! $managerStageId) {
            return response()->json(['message' => 'approval stages not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $hoursStageId = $this->resolveStageId('hours') ?? 0;
        $approvedStatusId = $this->resolveStatusId('approved');

        $row = $this->detailQuery($lecturer->id, $assistantStageId, $managerStageId, $hoursStageId, $approvedStatusId)
            ->where('ra.id', $activityId)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'work not found'], Response::HTTP_NOT_FOUND);
        }

        $ruleSummary = $this->buildRuleSummary((int) $row->kind_id, $row->type_id ? (int) $row->type_id : null);

        $payload = [
            'activity_id' => (int) $row->activity_id,
            'title' => $row->title,
            'kind_name' => $row->kind_name,
            'academic_year_code' => $row->academic_year_code,
            'publication_or_unit' => $this->resolvePublicationOrUnit($row),
            'member_role_name' => $row->member_role_name,
            'contribution_share' => $row->contribution_share !== null ? (float) $row->contribution_share : null,
            'rule_summary' => $ruleSummary,
            'hours_for_lecturer' => $row->hours_assigned !== null ? (float) $row->hours_assigned : null,
            'activity_status_code' => $row->activity_status_code,
            'assistant_approval_status' => $row->assistant_approval_status,
            'manager_approval_status' => $row->manager_approval_status,
            'hours_request_state' => $this->resolveHoursRequestState($row->hours_approval_status ?? null),
        ];

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $payload,
        ], Response::HTTP_OK);
    }

    public function submit(LecturerHoursCalculateSubmitRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $assistantStageId = $this->resolveStageId('assistant');
        $managerStageId = $this->resolveStageId('manager');
        $hoursStageId = $this->resolveStageId('hours');
        $approvedStatusId = $this->resolveStatusId('approved');

        if (! $assistantStageId || ! $managerStageId || ! $hoursStageId) {
            return response()->json(['message' => 'approval stages not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activityIds = array_values(array_unique($request->validated()['activity_ids']));
        $eligibleIds = $this->eligibleActivityIds($lecturer->id, $assistantStageId, $managerStageId, $approvedStatusId, $activityIds);

        if (empty($eligibleIds)) {
            return response()->json([
                'message' => 'no eligible works found',
                'data' => [
                    'submitted_count' => 0,
                    'skipped_count' => count($activityIds),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $submittedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($eligibleIds, $hoursStageId, $now, &$submittedCount, &$skippedCount, $lecturer) {
            $existing = DB::table('activity_approvals')
                ->where('stage_id', $hoursStageId)
                ->whereIn('activity_id', $eligibleIds)
                ->get()
                ->keyBy('activity_id');

            foreach ($eligibleIds as $activityId) {
                $row = $existing[$activityId] ?? null;
                if (! $row) {
                    DB::table('activity_approvals')->insert([
                        'activity_id' => $activityId,
                        'stage_id' => $hoursStageId,
                        'status' => 'pending',
                        'decided_by_user_id' => null,
                        'decided_at' => null,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $submittedCount++;
                    continue;
                }

                if ($row->status === 'rejected') {
                    DB::table('activity_approvals')
                        ->where('id', $row->id)
                        ->update([
                            'status' => 'pending',
                            'decided_by_user_id' => null,
                            'decided_at' => null,
                            'note' => null,
                            'updated_at' => $now,
                        ]);
                    $submittedCount++;
                    continue;
                }

                $skippedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'submitted',
            'data' => [
                'submitted_count' => $submittedCount,
                'skipped_count' => $skippedCount,
            ],
        ], Response::HTTP_OK);
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function resolveStageId(string $code): ?int
    {
        $id = DB::table('approval_stages')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveStatusId(string $code): ?int
    {
        $id = DB::table('activity_statuses')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function baseQuery(
        int $lecturerId,
        int $assistantStageId,
        int $managerStageId,
        int $hoursStageId,
        ?int $approvedStatusId
    ) {
        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($assistantStageId) {
                $join->on('aa_assistant.activity_id', '=', 'ra.id')
                    ->where('aa_assistant.stage_id', '=', $assistantStageId);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($managerStageId) {
                $join->on('aa_manager.activity_id', '=', 'ra.id')
                    ->where('aa_manager.stage_id', '=', $managerStageId);
            })
            ->leftJoin('activity_approvals as aa_hours', function ($join) use ($hoursStageId) {
                $join->on('aa_hours.activity_id', '=', 'ra.id')
                    ->where('aa_hours.stage_id', '=', $hoursStageId);
            })
            ->where('ram.lecturer_id', $lecturerId)
            ->where(function ($query) {
                $query->whereColumn('ra.owner_lecturer_id', 'ram.lecturer_id')
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->where('ra.status_id', $approvedStatusId)

            ->select([
                'ra.id as activity_id',
                'ra.activity_code',
                'ra.title',
                'ra.kind_id',
                'ra.type_id',
                'ak.name as kind_name',
                'mr.name as member_role_name',
                'ram.hours_assigned',
                'ram.contribution_share',
                'ast.code as activity_status_code',
                'aa_assistant.status as assistant_approval_status',
                'aa_manager.status as manager_approval_status',
                'aa_hours.status as hours_approval_status',
                'ay.code as academic_year_code',
                'ra.updated_at',
            ]);
    }

    private function detailQuery(
        int $lecturerId,
        int $assistantStageId,
        int $managerStageId,
        int $hoursStageId,
        ?int $approvedStatusId
    ) {
        return $this->baseQuery($lecturerId, $assistantStageId, $managerStageId, $hoursStageId, $approvedStatusId)
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->addSelect([
                'pd.journal_name',
                'bd.publisher',
                'prd.project_code',
                'cd.conference_name',
                'cd.location',
            ]);
    }

    private function applyFilters($query, array $filters): void
    {
        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            if ($status === 'not_submitted') {
                $query->whereNull('aa_hours.status');
            } else {
                $query->where('aa_hours.status', $status);
            }
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhere('ra.activity_code', 'like', $keyword);
            });
        }
    }

    private function approvedCount(
        int $lecturerId,
        int $assistantStageId,
        int $managerStageId,
        ?int $approvedStatusId
    ): int {
        $query = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($assistantStageId) {
                $join->on('aa_assistant.activity_id', '=', 'ra.id')
                    ->where('aa_assistant.stage_id', '=', $assistantStageId);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($managerStageId) {
                $join->on('aa_manager.activity_id', '=', 'ra.id')
                    ->where('aa_manager.stage_id', '=', $managerStageId);
            })
            ->where('ram.lecturer_id', $lecturerId)
            ->where(function ($query) {
                $query->whereColumn('ra.owner_lecturer_id', 'ram.lecturer_id')
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->where(function ($query) use ($approvedStatusId) {
                $query->where(function ($sub) {
                    $sub->where('aa_assistant.status', 'approved')
                        ->where('aa_manager.status', 'approved');
                });
                if ($approvedStatusId) {
                    $query->orWhere('ra.status_id', $approvedStatusId);
                }
            });

        return (int) $query->distinct('ra.id')->count('ra.id');
    }

    private function resolveHoursRequestState(?string $hoursStatus): string
    {
        if (! $hoursStatus) {
            return 'eligible';
        }

        return match ($hoursStatus) {
            'approved' => 'hours_approved',
            'rejected' => 'rejected',
            default => 'submitted',
        };
    }

    private function eligibleActivityIds(
        int $lecturerId,
        int $assistantStageId,
        int $managerStageId,
        ?int $approvedStatusId,
        array $activityIds
    ): array {
        if (empty($activityIds)) {
            return [];
        }

        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->leftJoin('activity_approvals as aa_assistant', function ($join) use ($assistantStageId) {
                $join->on('aa_assistant.activity_id', '=', 'ra.id')
                    ->where('aa_assistant.stage_id', '=', $assistantStageId);
            })
            ->leftJoin('activity_approvals as aa_manager', function ($join) use ($managerStageId) {
                $join->on('aa_manager.activity_id', '=', 'ra.id')
                    ->where('aa_manager.stage_id', '=', $managerStageId);
            })
            ->where('ram.lecturer_id', $lecturerId)
            ->where(function ($query) {
                $query->whereColumn('ra.owner_lecturer_id', 'ram.lecturer_id')
                    ->orWhere('ram.confirmation_status', 'accepted');
            })
            ->whereIn('ra.id', $activityIds)
            ->where(function ($query) use ($approvedStatusId) {
                $query->where(function ($sub) {
                    $sub->where('aa_assistant.status', 'approved')
                        ->where('aa_manager.status', 'approved');
                });
                if ($approvedStatusId) {
                    $query->orWhere('ra.status_id', $approvedStatusId);
                }
            })
            ->distinct()
            ->pluck('ra.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function resolvePublicationOrUnit(object $row): string
    {
        $candidates = [
            $row->journal_name ?? null,
            $row->publisher ?? null,
            $row->project_code ?? null,
            $row->conference_name ?? null,
            $row->location ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return '-';
    }

    private function buildRuleSummary(int $kindId, ?int $typeId): string
    {
        $ruleQuery = DB::table('hour_rules')
            ->where('kind_id', $kindId)
            ->where('is_active', 1)
            ->where(function ($query) use ($typeId) {
                if ($typeId === null) {
                    $query->whereNull('type_id');
                } else {
                    $query->where('type_id', $typeId);
                }
            })
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('version');

        $rule = $ruleQuery->first();
        if (! $rule) {
            return 'Rule not configured.';
        }

        $parts = [
            'distribution=' . $rule->distribution_strategy,
        ];

        if ($rule->hours_total_per_activity !== null) {
            $parts[] = 'total=' . $rule->hours_total_per_activity;
        }
        if ($rule->hours_per_occurrence !== null) {
            $parts[] = 'per_occurrence=' . $rule->hours_per_occurrence;
        }
        if ($rule->principal_fraction !== null) {
            $parts[] = 'principal_fraction=' . $rule->principal_fraction;
        }
        if ($rule->others_fraction_total !== null) {
            $parts[] = 'others_fraction_total=' . $rule->others_fraction_total;
        }
        if ($rule->max_occurrences_per_year !== null) {
            $parts[] = 'max_occurrences=' . $rule->max_occurrences_per_year;
        }

        return 'Rule: ' . implode(', ', $parts);
    }
}
