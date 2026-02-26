<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchHoursCatalogController extends Controller
{
    private const DISTRIBUTION_STRATEGIES = [
        'equal_all_members',
        'principal_fraction_others_equal',
        'per_lecturer_fixed',
    ];

    public function meta()
    {
        $academicYears = DB::table('academic_years')
            ->select(['id', 'code', 'start_date', 'end_date', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $kinds = DB::table('activity_kinds')
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (object) [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $this->mapKindDisplayName($row->code, $row->name),
            ]);

        $types = DB::table('activity_types')
            ->select(['id', 'kind_id', 'code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (object) [
                'id' => (int) $row->id,
                'kind_id' => (int) $row->kind_id,
                'code' => $row->code,
                'name' => $this->mapTypeDisplayName($row->code, $row->name),
            ]);

        $statuses = [
            ['key' => 'ACTIVE', 'label' => 'Đang áp dụng'],
            ['key' => 'INACTIVE', 'label' => 'Ngừng áp dụng'],
        ];

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'academic_years' => $academicYears,
                'activity_kinds' => $kinds,
                'activity_types' => $types,
                'statuses' => $statuses,
            ],
        ], Response::HTTP_OK);
    }

    // ===== Hour rules =====
    public function listHourRules(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', 'string', 'max:20'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        [$page, $perPage] = $this->resolvePagination($validated);
        $status = $this->normalizeStatusFilter($validated['status'] ?? null);

        $lockedSub = DB::table('calculation_logs')
            ->select('rule_id', DB::raw('COUNT(*) as logs_count'))
            ->groupBy('rule_id');

        $query = DB::table('hour_rules as hr')
            ->leftJoin('activity_kinds as ak', 'ak.id', '=', 'hr.kind_id')
            ->leftJoin('activity_types as at', 'at.id', '=', 'hr.type_id')
            ->leftJoinSub($lockedSub, 'cl', 'cl.rule_id', '=', 'hr.id')
            ->select([
                'hr.*',
                DB::raw('COALESCE(cl.logs_count, 0) as logs_count'),
                'ak.code as kind_code',
                'ak.name as kind_name',
                'at.code as type_code',
                'at.name as type_name',
            ]);

        $academicYearId = $validated['academic_year_id'] ?? null;
        if ($academicYearId) {
            $year = DB::table('academic_years')
                ->select(['start_date', 'end_date'])
                ->where('id', $academicYearId)
                ->first();
            if ($year) {
                $query->whereBetween('hr.effective_from', [$year->start_date, $year->end_date]);
            }
        }

        if ($status !== null) {
            $query->where('hr.is_active', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $like = '%' . $keyword . '%';
                $sub->where('ak.name', 'like', $like)
                    ->orWhere('ak.code', 'like', $like)
                    ->orWhere('at.name', 'like', $like)
                    ->orWhere('at.code', 'like', $like);
            });
        }

        $query->orderByDesc('hr.updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->hourRulePayload($row);
        });
    }

    public function storeHourRule(Request $request)
    {
        $data = $this->validateHourRule($request);

        if (! $this->validateTypeForKind($data['type_id'] ?? null, $data['kind_id'])) {
            return response()->json([
                'message' => 'Loại chi tiết không thuộc nhóm công trình đã chọn.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->existsHourRuleDuplicate($data, null)) {
            return response()->json([
                'message' => 'Đã tồn tại quy đổi cho cùng loại công trình và thời gian áp dụng.',
            ], Response::HTTP_CONFLICT);
        }

        $now = now();
        $id = DB::table('hour_rules')->insertGetId([
            'kind_id' => $data['kind_id'],
            'type_id' => $data['type_id'] ?? null,
            'distribution_strategy' => $data['distribution_strategy'],
            'hours_total_per_activity' => $data['hours_total_per_activity'] ?? null,
            'hours_per_occurrence' => $data['hours_per_occurrence'] ?? null,
            'principal_fraction' => $data['principal_fraction'] ?? null,
            'others_fraction_total' => $data['others_fraction_total'] ?? null,
            'max_occurrences_per_year' => $data['max_occurrences_per_year'] ?? null,
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'version' => $data['version'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = $this->hourRuleQuery()->where('hr.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'HOURS_RULE_CREATED',
            'action_label' => 'Create research hours rule',
            'target_type' => 'hour_rule',
            'target_id' => $id,
            'target_display' => $this->hourRuleTargetDisplay($data),
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->hourRulePayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateHourRule(Request $request, int $id)
    {
        $existing = DB::table('hour_rules')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->validateHourRule($request);

        if (! $this->validateTypeForKind($data['type_id'] ?? null, $data['kind_id'])) {
            return response()->json([
                'message' => 'Loại chi tiết không thuộc nhóm công trình đã chọn.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $locked = $this->isHourRuleLocked($id);
        if ($locked && $this->hasHourRuleChanges($existing, $data)) {
            return response()->json([
                'message' => 'Quy đổi đã được sử dụng, không thể chỉnh sửa.',
            ], Response::HTTP_CONFLICT);
        }

        if ($this->existsHourRuleDuplicate($data, $id)) {
            return response()->json([
                'message' => 'Đã tồn tại quy đổi cho cùng loại công trình và thời gian áp dụng.',
            ], Response::HTTP_CONFLICT);
        }

        DB::table('hour_rules')
            ->where('id', $id)
            ->update([
                'kind_id' => $data['kind_id'],
                'type_id' => $data['type_id'] ?? null,
                'distribution_strategy' => $data['distribution_strategy'],
                'hours_total_per_activity' => $data['hours_total_per_activity'] ?? null,
                'hours_per_occurrence' => $data['hours_per_occurrence'] ?? null,
                'principal_fraction' => $data['principal_fraction'] ?? null,
                'others_fraction_total' => $data['others_fraction_total'] ?? null,
                'max_occurrences_per_year' => $data['max_occurrences_per_year'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'version' => $data['version'],
                'updated_at' => now(),
            ]);

        $row = $this->hourRuleQuery()->where('hr.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'HOURS_RULE_UPDATED',
            'action_label' => 'Update research hours rule',
            'target_type' => 'hour_rule',
            'target_id' => $id,
            'target_display' => $this->hourRuleTargetDisplay($data),
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->hourRulePayload($row),
        ], Response::HTTP_OK);
    }

    public function updateHourRuleStatus(Request $request, int $id)
    {
        $existing = DB::table('hour_rules')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('hour_rules')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = $this->hourRuleQuery()->where('hr.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => $data['is_active'] ? 'HOURS_RULE_ACTIVATED' : 'HOURS_RULE_DEACTIVATED',
            'action_label' => $data['is_active'] ? 'Activate research hours rule' : 'Deactivate research hours rule',
            'target_type' => 'hour_rule',
            'target_id' => $id,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->hourRulePayload($row),
        ], Response::HTTP_OK);
    }

    // ===== Workload quotas =====
    public function listWorkloadQuotas(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', 'string', 'max:20'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        [$page, $perPage] = $this->resolvePagination($validated);
        $status = $this->normalizeStatusFilter($validated['status'] ?? null);

        $usageSub = DB::table('lecturer_yearly_hours')
            ->select('academic_year_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('academic_year_id');

        $query = DB::table('workload_quotas as wq')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'wq.academic_year_id')
            ->leftJoinSub($usageSub, 'uy', 'uy.academic_year_id', '=', 'wq.academic_year_id')
            ->select([
                'wq.*',
                'ay.is_active as year_is_active',
                DB::raw('COALESCE(uy.usage_count, 0) as usage_count'),
            ]);

        if (! empty($validated['academic_year_id'])) {
            $query->where('wq.academic_year_id', $validated['academic_year_id']);
        }

        if ($status !== null) {
            $query->where('ay.is_active', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $like = '%' . $keyword . '%';
                $sub->where('ay.code', 'like', $like)
                    ->orWhere('wq.notes', 'like', $like);
            });
        }

        $query->orderByDesc('wq.updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workloadQuotaPayload($row);
        });
    }

    public function storeWorkloadQuota(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'required_hours' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = DB::table('workload_quotas')
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Định mức cho năm học này đã tồn tại.',
            ], Response::HTTP_CONFLICT);
        }

        $now = now();
        $id = DB::table('workload_quotas')->insertGetId([
            'academic_year_id' => $data['academic_year_id'],
            'required_hours' => $data['required_hours'],
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = $this->workloadQuotaQuery()->where('wq.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'HOURS_QUOTA_CREATED',
            'action_label' => 'Create research hours quota',
            'target_type' => 'workload_quota',
            'target_id' => $id,
            'target_display' => 'academic_year_id=' . $data['academic_year_id'],
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->workloadQuotaPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateWorkloadQuota(Request $request, int $id)
    {
        $existing = DB::table('workload_quotas')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'required_hours' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $locked = $this->isAcademicYearLocked((int) $existing->academic_year_id);
        if ($locked && (float) $data['required_hours'] !== (float) $existing->required_hours) {
            return response()->json([
                'message' => 'Định mức đã được sử dụng, không thể chỉnh sửa.',
            ], Response::HTTP_CONFLICT);
        }

        DB::table('workload_quotas')
            ->where('id', $id)
            ->update([
                'required_hours' => $data['required_hours'],
                'notes' => $data['notes'] ?? null,
                'updated_at' => now(),
            ]);

        $row = $this->workloadQuotaQuery()->where('wq.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'HOURS_QUOTA_UPDATED',
            'action_label' => 'Update research hours quota',
            'target_type' => 'workload_quota',
            'target_id' => $id,
            'target_display' => 'academic_year_id=' . $existing->academic_year_id,
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->workloadQuotaPayload($row),
        ], Response::HTTP_OK);
    }

    // ===== Academic years =====
    public function listAcademicYears(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        [$page, $perPage] = $this->resolvePagination($validated);
        $status = $this->normalizeStatusFilter($validated['status'] ?? null);

        $usageSub = DB::table('lecturer_yearly_hours')
            ->select('academic_year_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('academic_year_id');

        $query = DB::table('academic_years as ay')
            ->leftJoinSub($usageSub, 'uy', 'uy.academic_year_id', '=', 'ay.id')
            ->select([
                'ay.*',
                DB::raw('COALESCE(uy.usage_count, 0) as usage_count'),
            ]);

        if ($status !== null) {
            $query->where('ay.is_active', $status);
        }

        if ($keyword !== '') {
            $query->where('ay.code', 'like', '%' . $keyword . '%');
        }

        $query->orderByDesc('ay.updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->academicYearPayload($row);
        });
    }

    public function storeAcademicYear(Request $request)
    {
        $data = $this->validateAcademicYear($request, null);

        $id = null;
        DB::transaction(function () use ($data, &$id) {
            $now = now();
            if ($data['is_active']) {
                DB::table('academic_years')->update(['is_active' => false]);
            }

            $id = DB::table('academic_years')->insertGetId([
                'code' => $data['code'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => (bool) $data['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $row = $this->academicYearQuery()->where('ay.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'ACADEMIC_YEAR_CREATED',
            'action_label' => 'Create academic year',
            'target_type' => 'academic_year',
            'target_id' => $id,
            'target_display' => $data['code'],
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->academicYearPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateAcademicYear(Request $request, int $id)
    {
        $existing = DB::table('academic_years')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->validateAcademicYear($request, $id);

        $locked = $this->isAcademicYearLocked($id);
        if ($locked && $this->hasAcademicYearChanges($existing, $data)) {
            return response()->json([
                'message' => 'Năm học đã được sử dụng, không thể chỉnh sửa.',
            ], Response::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($data, $id) {
            if ($data['is_active']) {
                DB::table('academic_years')->update(['is_active' => false]);
            }

            DB::table('academic_years')
                ->where('id', $id)
                ->update([
                    'code' => $data['code'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'is_active' => (bool) $data['is_active'],
                    'updated_at' => now(),
                ]);
        });

        $row = $this->academicYearQuery()->where('ay.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'ACADEMIC_YEAR_UPDATED',
            'action_label' => 'Update academic year',
            'target_type' => 'academic_year',
            'target_id' => $id,
            'target_display' => $data['code'],
            'changes' => $data,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->academicYearPayload($row),
        ], Response::HTTP_OK);
    }

    public function applyAcademicYear(Request $request, int $id)
    {
        $existing = DB::table('academic_years')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($id) {
            DB::table('academic_years')->update(['is_active' => false]);
            DB::table('academic_years')->where('id', $id)->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
        });

        $row = $this->academicYearQuery()->where('ay.id', $id)->first();

        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => 'ACADEMIC_YEAR_APPLIED',
            'action_label' => 'Apply academic year',
            'target_type' => 'academic_year',
            'target_id' => $id,
            'target_display' => $existing->code,
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->academicYearPayload($row),
        ], Response::HTTP_OK);
    }

    private function resolvePagination(array $validated): array
    {
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        return [$page, $perPage];
    }

    private function normalizeStatusFilter(?string $status): ?bool
    {
        $value = strtolower(trim((string) $status));
        if ($value === '' || $value === 'all') {
            return null;
        }
        if ($value === 'active') {
            return true;
        }
        if ($value === 'inactive') {
            return false;
        }
        if ($value === '1') {
            return true;
        }
        if ($value === '0') {
            return false;
        }

        return null;
    }

    private function paginateResponse($query, int $page, int $perPage, callable $mapFn)
    {
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = $mapFn($row);
        }

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
            ],
        ], Response::HTTP_OK);
    }

    private function hourRuleQuery()
    {
        $lockedSub = DB::table('calculation_logs')
            ->select('rule_id', DB::raw('COUNT(*) as logs_count'))
            ->groupBy('rule_id');

        return DB::table('hour_rules as hr')
            ->leftJoinSub($lockedSub, 'cl', 'cl.rule_id', '=', 'hr.id')
            ->select(['hr.*', DB::raw('COALESCE(cl.logs_count, 0) as logs_count')]);
    }

    private function workloadQuotaQuery()
    {
        $usageSub = DB::table('lecturer_yearly_hours')
            ->select('academic_year_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('academic_year_id');

        return DB::table('workload_quotas as wq')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'wq.academic_year_id')
            ->leftJoinSub($usageSub, 'uy', 'uy.academic_year_id', '=', 'wq.academic_year_id')
            ->select([
                'wq.*',
                'ay.is_active as year_is_active',
                DB::raw('COALESCE(uy.usage_count, 0) as usage_count'),
            ]);
    }

    private function academicYearQuery()
    {
        $usageSub = DB::table('lecturer_yearly_hours')
            ->select('academic_year_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('academic_year_id');

        return DB::table('academic_years as ay')
            ->leftJoinSub($usageSub, 'uy', 'uy.academic_year_id', '=', 'ay.id')
            ->select([
                'ay.*',
                DB::raw('COALESCE(uy.usage_count, 0) as usage_count'),
            ]);
    }

    private function hourRulePayload(object $row): array
    {
        $isLocked = ((int) ($row->logs_count ?? 0)) > 0;

        return [
            'id' => (int) $row->id,
            'kind_id' => (int) $row->kind_id,
            'type_id' => $row->type_id ? (int) $row->type_id : null,
            'distribution_strategy' => $row->distribution_strategy,
            'hours_total_per_activity' => $row->hours_total_per_activity !== null ? (string) $row->hours_total_per_activity : null,
            'hours_per_occurrence' => $row->hours_per_occurrence !== null ? (string) $row->hours_per_occurrence : null,
            'principal_fraction' => $row->principal_fraction !== null ? (string) $row->principal_fraction : null,
            'others_fraction_total' => $row->others_fraction_total !== null ? (string) $row->others_fraction_total : null,
            'max_occurrences_per_year' => $row->max_occurrences_per_year !== null ? (int) $row->max_occurrences_per_year : null,
            'effective_from' => $row->effective_from,
            'effective_to' => $row->effective_to,
            'is_active' => (bool) $row->is_active,
            'version' => (int) $row->version,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'is_locked' => $isLocked,
        ];
    }

    private function workloadQuotaPayload(object $row): array
    {
        $isLocked = ((int) ($row->usage_count ?? 0)) > 0;

        return [
            'id' => (int) $row->id,
            'academic_year_id' => (int) $row->academic_year_id,
            'required_hours' => (string) $row->required_hours,
            'notes' => $row->notes,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'is_active' => (bool) ($row->year_is_active ?? false),
            'is_locked' => $isLocked,
        ];
    }

    private function academicYearPayload(object $row): array
    {
        $isLocked = ((int) ($row->usage_count ?? 0)) > 0;

        return [
            'id' => (int) $row->id,
            'code' => $row->code,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'is_locked' => $isLocked,
        ];
    }

    private function validateHourRule(Request $request): array
    {
        return $request->validate([
            'kind_id' => ['required', 'integer', 'exists:activity_kinds,id'],
            'type_id' => ['nullable', 'integer', 'exists:activity_types,id'],
            'distribution_strategy' => ['required', 'string', Rule::in(self::DISTRIBUTION_STRATEGIES)],
            'hours_total_per_activity' => ['nullable', 'numeric', 'min:0'],
            'hours_per_occurrence' => ['nullable', 'numeric', 'min:0'],
            'principal_fraction' => ['nullable', 'numeric', 'min:0'],
            'others_fraction_total' => ['nullable', 'numeric', 'min:0'],
            'max_occurrences_per_year' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'version' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function validateAcademicYear(Request $request, ?int $ignoreId): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:9',
                Rule::unique('academic_years', 'code')->ignore($ignoreId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $errors = [];
        if ($start->month !== 9 || $start->day !== 1) {
            $errors['start_date'] = ['Năm học phải bắt đầu từ ngày 01/09.'];
        }
        if ($end->month !== 8 || $end->day !== 31) {
            $errors['end_date'] = ['Năm học phải kết thúc vào ngày 31/08 năm sau.'];
        }

        if ($end->year !== ($start->year + 1)) {
            $errors['end_date'][] = 'Năm học phải kéo dài từ tháng 09 năm nay đến tháng 08 năm sau.';
        }

        if (! preg_match('/^\d{4}-\d{4}$/', (string) $data['code'])) {
            $errors['code'] = ['Mã năm học phải có định dạng YYYY-YYYY.'];
        } else {
            [$startYearFromCode, $endYearFromCode] = array_map('intval', explode('-', (string) $data['code']));
            if ($endYearFromCode !== ($startYearFromCode + 1)) {
                $errors['code'][] = 'Mã năm học phải theo cặp năm liên tiếp, ví dụ 2025-2026.';
            }

            if ($start->year !== $startYearFromCode || $end->year !== $endYearFromCode) {
                $errors['code'][] = 'Mã năm học phải khớp với khoảng ngày bắt đầu/kết thúc.';
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    private function isHourRuleLocked(int $id): bool
    {
        return DB::table('calculation_logs')
            ->where('rule_id', $id)
            ->exists();
    }

    private function isAcademicYearLocked(int $academicYearId): bool
    {
        return DB::table('lecturer_yearly_hours')
            ->where('academic_year_id', $academicYearId)
            ->exists();
    }

    private function hasHourRuleChanges(object $existing, array $data): bool
    {
        return (int) $existing->kind_id !== (int) $data['kind_id']
            || (int) ($existing->type_id ?? 0) !== (int) ($data['type_id'] ?? 0)
            || (string) $existing->distribution_strategy !== (string) $data['distribution_strategy']
            || (string) ($existing->hours_total_per_activity ?? '') !== (string) ($data['hours_total_per_activity'] ?? '')
            || (string) ($existing->hours_per_occurrence ?? '') !== (string) ($data['hours_per_occurrence'] ?? '')
            || (string) ($existing->principal_fraction ?? '') !== (string) ($data['principal_fraction'] ?? '')
            || (string) ($existing->others_fraction_total ?? '') !== (string) ($data['others_fraction_total'] ?? '')
            || (string) ($existing->max_occurrences_per_year ?? '') !== (string) ($data['max_occurrences_per_year'] ?? '')
            || (string) $existing->effective_from !== (string) $data['effective_from']
            || (string) ($existing->effective_to ?? '') !== (string) ($data['effective_to'] ?? '')
            || (int) $existing->version !== (int) $data['version'];
    }

    private function hasAcademicYearChanges(object $existing, array $data): bool
    {
        return (string) $existing->code !== (string) $data['code']
            || (string) $existing->start_date !== (string) $data['start_date']
            || (string) $existing->end_date !== (string) $data['end_date'];
    }

    private function validateTypeForKind(?int $typeId, int $kindId): bool
    {
        if ($typeId === null) {
            return true;
        }

        $foundKindId = DB::table('activity_types')
            ->where('id', $typeId)
            ->value('kind_id');

        return (int) $foundKindId === (int) $kindId;
    }

    private function existsHourRuleDuplicate(array $data, ?int $ignoreId): bool
    {
        $query = DB::table('hour_rules')
            ->where('kind_id', $data['kind_id'])
            ->where('version', $data['version'])
            ->where('effective_from', $data['effective_from']);

        if ($data['type_id'] === null) {
            $query->whereNull('type_id');
        } else {
            $query->where('type_id', $data['type_id']);
        }

        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    private function hourRuleTargetDisplay(array $data): string
    {
        $parts = [
            'kind_id=' . $data['kind_id'],
            'type_id=' . ($data['type_id'] ?? 'null'),
            'effective_from=' . $data['effective_from'],
        ];

        return implode(', ', $parts);
    }

    private function mapKindDisplayName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'paper' => 'Bài báo khoa học',
            'book' => 'Sách, giáo trình',
            'project' => 'Đề tài KH&CN',
            'conference' => 'Hội nghị, hội thảo',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
    }

    private function mapTypeDisplayName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'hdgsnn_900' => 'Bài báo HDGSNN 1-2 điểm (900 giờ)',
            'hdgsnn_600' => 'Bài báo HDGSNN >= 1 điểm (600 giờ)',
            'hdgsnn_300' => 'Bài báo có ISSN/ISBN (300 giờ)',
            'textbook' => 'Giáo trình',
            'reference' => 'Tài liệu tham khảo',
            'bo', 'ministry' => 'Đề tài cấp Bộ (2 năm)',
            'coso', 'university' => 'Đề tài cấp Trường (1 năm)',
            'report' => 'Báo cáo hội thảo',
            'attend' => 'Tham dự hội thảo',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
    }
}
