<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    public static function log(?Request $request, array $data, ?User $actor = null): void
    {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return;
            }

            $actionCode = $data['action_code'] ?? null;
            if (! $actionCode) {
                return;
            }

            $now = now();

            $payload = [
                'occurred_at' => $data['occurred_at'] ?? $now,
                'severity' => $data['severity'] ?? 'normal',
                'action_group' => $data['action_group'] ?? 'system',
                'action_code' => $actionCode,
                'action_label' => $data['action_label'] ?? $actionCode,
                'target_type' => $data['target_type'] ?? null,
                'target_id' => self::stringify($data['target_id'] ?? null),
                'target_display' => $data['target_display'] ?? null,
                'target_route_name' => $data['target_route_name'] ?? null,
                'target_route_params' => self::encodeJson($data['target_route_params'] ?? null),
                'result_status' => $data['result_status'] ?? 'success',
                'result_error_message' => $data['result_error_message'] ?? null,
                'changes' => self::encodeJson($data['changes'] ?? null),
                'note' => $data['note'] ?? null,
                'created_at' => $data['created_at'] ?? $now,
                'updated_at' => $data['updated_at'] ?? $now,
            ];

            if ($actor) {
                $payload['actor_user_id'] = $actor->id;
                $payload['actor_name'] = $actor->name;
                $payload['actor_email'] = $actor->email;
                $payload['actor_roles_snapshot'] = self::encodeJson(
                    $actor->getRoleNames()->values()->all()
                );
            } else {
                $payload['actor_user_id'] = $data['actor_user_id'] ?? null;
                $payload['actor_name'] = $data['actor_name'] ?? null;
                $payload['actor_email'] = $data['actor_email'] ?? null;
                $payload['actor_roles_snapshot'] = self::encodeJson(
                    $data['actor_roles_snapshot'] ?? null
                );
            }

            $facultyId = $data['faculty_id'] ?? null;
            if (! $facultyId && $actor) {
                $facultyId = self::resolveFacultyId($actor->id);
            }
            $payload['faculty_id'] = $facultyId;

            if ($request) {
                $payload['ip'] = $data['ip'] ?? $request->ip();
                $payload['user_agent'] = $data['user_agent'] ?? $request->userAgent();
                $payload['request_method'] = $data['request_method'] ?? $request->method();
                $payload['request_path'] = $data['request_path'] ?? $request->getPathInfo();
            } else {
                $payload['ip'] = $data['ip'] ?? null;
                $payload['user_agent'] = $data['user_agent'] ?? null;
                $payload['request_method'] = $data['request_method'] ?? null;
                $payload['request_path'] = $data['request_path'] ?? null;
            }

            $payload['request_http_status'] = $data['request_http_status'] ?? null;

            DB::table('audit_logs')->insert($payload);
        } catch (\Throwable $e) {
            Log::warning('audit_log.write_failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function resolveFacultyId(int $userId): ?int
    {
        $departmentId = DB::table('lecturers')
            ->where('user_id', $userId)
            ->value('department_id');

        if (! $departmentId) {
            return null;
        }

        return DB::table('departments')
            ->where('id', $departmentId)
            ->value('faculty_id');
    }

    private static function encodeJson($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) && $value === []) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return null;
        }

        return $encoded;
    }

    private static function stringify($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
