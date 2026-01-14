<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLogController extends Controller
{
    private const ACTION_GROUPS = [
        'auth',
        'lecturer',
        'research',
        'approval',
        'config',
        'security',
    ];

    private const SEVERITIES = ['normal', 'important', 'dangerous'];

    private const RESULT_STATUSES = ['success', 'failure'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'string', 'in:FACULTY,GLOBAL'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'actor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_group' => ['nullable', 'string', 'in:' . implode(',', self::ACTION_GROUPS)],
            'action_code' => ['nullable', 'string', 'max:100'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'level' => ['nullable', 'string', 'in:' . implode(',', self::SEVERITIES)],
            'severity' => ['nullable', 'string', 'in:' . implode(',', self::SEVERITIES)],
            'result' => ['nullable', 'string', 'in:' . implode(',', self::RESULT_STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        $query = DB::table('audit_logs as al')
            ->leftJoin('faculties as f', 'f.id', '=', 'al.faculty_id')
            ->select('al.*', 'f.name as faculty_name');

        $scope = $validated['scope'] ?? null;
        $facultyId = $validated['faculty_id'] ?? null;
        if ($scope === 'FACULTY') {
            $facultyId = $this->resolveFacultyIdForUser($request->user());
        }

        if ($facultyId) {
            $query->where('al.faculty_id', $facultyId);
        }

        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $like = '%' . $keyword . '%';
                $sub->where('al.actor_name', 'like', $like)
                    ->orWhere('al.actor_email', 'like', $like)
                    ->orWhere('al.action_label', 'like', $like)
                    ->orWhere('al.action_code', 'like', $like)
                    ->orWhere('al.target_display', 'like', $like)
                    ->orWhere('al.target_type', 'like', $like)
                    ->orWhere('al.request_path', 'like', $like);
            });
        }

        $actorUserId = $validated['actor_user_id'] ?? $validated['user_id'] ?? null;
        if (! empty($actorUserId)) {
            $query->where('al.actor_user_id', $actorUserId);
        }

        if (! empty($validated['action_group'])) {
            $query->where('al.action_group', $validated['action_group']);
        }

        if (! empty($validated['action_code'])) {
            $query->where('al.action_code', $validated['action_code']);
        }

        $severity = $validated['severity'] ?? $validated['level'] ?? null;
        if (! empty($severity)) {
            $query->where('al.severity', $severity);
        }

        if (! empty($validated['result'])) {
            $query->where('al.result_status', $validated['result']);
        }

        if (! empty($validated['date_from'])) {
            $from = Carbon::parse($validated['date_from'])->startOfDay();
            $query->where('al.occurred_at', '>=', $from);
        }

        if (! empty($validated['date_to'])) {
            $to = Carbon::parse($validated['date_to'])->endOfDay();
            $query->where('al.occurred_at', '<=', $to);
        }

        [$sortField, $sortDir] = $this->parseSort($validated['sort'] ?? 'occurred_at:desc');
        $query->orderBy($sortField, $sortDir);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = $this->serializeAuditLogRow($row);
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

    public function show(Request $request, int $id)
    {
        $row = DB::table('audit_logs as al')
            ->leftJoin('faculties as f', 'f.id', '=', 'al.faculty_id')
            ->select('al.*', 'f.name as faculty_name')
            ->where('al.id', $id)
            ->first();

        if (! $row) {
            return response()->json([
                'message' => 'Audit log not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $this->serializeAuditLogRow($row),
        ], Response::HTTP_OK);
    }

    public function meta(Request $request)
    {
        $actors = DB::table('audit_logs as al')
            ->select('al.actor_user_id as user_id', 'al.actor_name as name', 'al.actor_email as email')
            ->whereNotNull('al.actor_user_id')
            ->groupBy('al.actor_user_id', 'al.actor_name', 'al.actor_email')
            ->orderBy('al.actor_name')
            ->get()
            ->values();

        $faculties = DB::table('faculties')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $actionCodes = DB::table('audit_logs as al')
            ->select('al.action_code as code', 'al.action_label as label', 'al.action_group as group')
            ->groupBy('al.action_code', 'al.action_label', 'al.action_group')
            ->orderBy('al.action_group')
            ->orderBy('al.action_label')
            ->get()
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'actors' => $actors,
                'faculties' => $faculties,
                'action_codes' => $actionCodes,
                'faculty_id_scoped' => $this->resolveFacultyIdForUser($request->user()),
            ],
        ], Response::HTTP_OK);
    }

    private function serializeAuditLogRow(object $row): array
    {
        $occurredAt = Carbon::parse($row->occurred_at)->toIso8601String();
        $actorRoles = $this->decodeJson($row->actor_roles_snapshot);
        $targetParams = $this->decodeJson($row->target_route_params);
        $changes = $this->decodeJson($row->changes);
        $actorDisplayName = $row->actor_name ?? $row->actor_email ?? 'Không xác định';
        $resultLabel = $row->result_status === 'success' ? 'Thành công' : 'Thất bại';

        $requestMeta = null;
        if ($row->request_method || $row->request_path || $row->request_http_status !== null) {
            $requestMeta = [
                'method' => $row->request_method,
                'path' => $row->request_path,
                'http_status' => $row->request_http_status,
            ];
        }

        return [
            'id' => (int) $row->id,
            'occurred_at' => $occurredAt,
            'severity' => $row->severity,
            'action_group' => $row->action_group,
            'action_code' => $row->action_code,
            'action_label' => $row->action_label,
            'action_label_display' => $row->action_label,
            'actor' => [
                'user_id' => $row->actor_user_id ? (int) $row->actor_user_id : null,
                'name' => $row->actor_name,
                'email' => $row->actor_email,
                'backend_roles_snapshot' => $actorRoles,
            ],
            'actor_display_name' => $actorDisplayName,
            'target' => [
                'type' => $row->target_type,
                'id' => $row->target_id,
                'display' => $row->target_display,
                'route_name' => $row->target_route_name,
                'route_params' => $targetParams,
            ],
            'result' => [
                'status' => $row->result_status,
                'error_message' => $row->result_error_message,
            ],
            'result_label' => $resultLabel,
            'faculty_id' => $row->faculty_id ? (int) $row->faculty_id : null,
            'faculty_name' => $row->faculty_name,
            'ip' => $row->ip,
            'user_agent' => $row->user_agent,
            'device_label' => $this->shortUserAgent($row->user_agent),
            'request' => $requestMeta,
            'changes' => $changes,
            'note' => $row->note,
        ];
    }

    private function decodeJson($value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return null;
    }

    private function shortUserAgent(?string $ua, int $maxLen = 120): ?string
    {
        if (! $ua) {
            return null;
        }

        if (strlen($ua) <= $maxLen) {
            return $ua;
        }

        return substr($ua, 0, $maxLen - 3) . '...';
    }

    private function parseSort(string $sort): array
    {
        $parts = explode(':', $sort, 2);
        $field = $parts[0] ?? 'occurred_at';
        $direction = strtolower($parts[1] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $fieldMap = [
            'occurred_at' => 'al.occurred_at',
            'created_at' => 'al.created_at',
        ];

        return [$fieldMap[$field] ?? 'al.occurred_at', $direction];
    }

    private function resolveFacultyIdForUser($user): ?int
    {
        if (! $user) {
            return null;
        }

        $departmentId = DB::table('lecturers')
            ->where('user_id', $user->id)
            ->value('department_id');

        if (! $departmentId) {
            return null;
        }

        return DB::table('departments')
            ->where('id', $departmentId)
            ->value('faculty_id');
    }
}
