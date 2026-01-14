<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (DB::table('audit_logs')->exists()) {
            return;
        }

        $now = Carbon::now();

        $users = User::with('roles')->get();

        $departmentFacultyMap = DB::table('departments')
            ->pluck('faculty_id', 'id')
            ->all();

        $userFacultyMap = DB::table('lecturers as l')
            ->join('departments as d', 'd.id', '=', 'l.department_id')
            ->whereNotNull('l.user_id')
            ->pluck('d.faculty_id', 'l.user_id')
            ->all();

        $lecturerMap = DB::table('lecturers')
            ->select('id', 'full_name', 'department_id', 'user_id')
            ->get()
            ->keyBy('id');

        $researchActivities = DB::table('research_activities')
            ->select('id', 'title')
            ->limit(50)
            ->get()
            ->values();

        $actions = [
            [
                'group' => 'auth',
                'code' => 'LOGIN_SUCCESS',
                'label' => 'Đăng nhập thành công',
                'severity' => 'normal',
                'result' => 'success',
                'method' => 'POST',
                'path' => '/login',
                'http_status' => 200,
            ],
            [
                'group' => 'auth',
                'code' => 'LOGIN_FAILED',
                'label' => 'Đăng nhập thất bại',
                'severity' => 'important',
                'result' => 'failure',
                'method' => 'POST',
                'path' => '/login',
                'http_status' => 401,
                'error' => 'Sai mật khẩu',
            ],
            [
                'group' => 'security',
                'code' => 'FORBIDDEN_ACCESS',
                'label' => 'Truy cập trái quyền',
                'severity' => 'dangerous',
                'result' => 'failure',
                'method' => 'GET',
                'path' => '/api/hour-rules',
                'http_status' => 403,
                'error' => '403 Forbidden',
            ],
            [
                'group' => 'security',
                'code' => 'EXPORT_DATA',
                'label' => 'Xuất dữ liệu',
                'severity' => 'dangerous',
                'result' => 'success',
                'method' => 'GET',
                'path' => '/api/exports/research-activities',
                'http_status' => 200,
            ],
            [
                'group' => 'lecturer',
                'code' => 'LECTURER_PROFILE_UPDATED',
                'label' => 'Cập nhật hồ sơ giảng viên',
                'severity' => 'normal',
                'result' => 'success',
                'method' => 'PUT',
                'path' => '/api/profile/contact',
                'http_status' => 200,
                'changes' => [
                    ['field' => 'phone', 'before' => '0909xxxx', 'after' => '0912xxxx'],
                    ['field' => 'academic_rank_id', 'before' => null, 'after' => '2'],
                ],
            ],
            [
                'group' => 'research',
                'code' => 'RESEARCH_CREATED',
                'label' => 'Kê khai công trình nghiên cứu',
                'severity' => 'normal',
                'result' => 'success',
                'method' => 'POST',
                'path' => '/api/research-activities',
                'http_status' => 201,
            ],
            [
                'group' => 'approval',
                'code' => 'RESEARCH_APPROVED_STAGE1',
                'label' => 'BCN duyệt công trình (cấp 1)',
                'severity' => 'important',
                'result' => 'success',
                'method' => 'POST',
                'path' => '/api/approvals/assistant',
                'http_status' => 200,
                'changes' => [
                    ['field' => 'status', 'before' => 'submitted', 'after' => 'approved_stage1'],
                ],
            ],
            [
                'group' => 'config',
                'code' => 'ACADEMIC_YEAR_UPDATED',
                'label' => 'Cập nhật cấu hình năm học',
                'severity' => 'important',
                'result' => 'success',
                'method' => 'PUT',
                'path' => '/api/academic-years/1',
                'http_status' => 200,
                'changes' => [
                    ['field' => 'is_active', 'before' => 'false', 'after' => 'true'],
                ],
            ],
        ];

        $entries = [];
        $total = 120;

        for ($i = 0; $i < $total; $i++) {
            $action = $actions[array_rand($actions)];
            $occurredAt = $now->copy()
                ->subDays(random_int(0, 45))
                ->subMinutes(random_int(0, 1440));

            $actor = null;
            if ($users->isNotEmpty() && random_int(0, 100) >= 10) {
                $actor = $users->random();
            }

            $actorRoles = null;
            if ($actor) {
                $actorRoles = $actor->getRoleNames()->values()->all();
            }

            $facultyId = null;
            if ($actor && $actor->id && isset($userFacultyMap[$actor->id])) {
                $facultyId = $userFacultyMap[$actor->id];
            }

            $targetType = null;
            $targetId = null;
            $targetDisplay = null;
            $targetRouteName = null;
            $targetRouteParams = null;

            if (in_array($action['group'], ['research', 'approval'], true) && $researchActivities->isNotEmpty()) {
                $activity = $researchActivities->random();
                $targetType = 'research_activity';
                $targetId = (string) $activity->id;
                $targetDisplay = 'Công trình: ' . $activity->title;
                $targetRouteName = $action['group'] === 'approval'
                    ? 'works.facapprovals'
                    : 'works.personal';
                $targetRouteParams = ['id' => $activity->id];
            } elseif ($action['group'] === 'lecturer' && $lecturerMap->isNotEmpty()) {
                $lecturer = $lecturerMap->random();
                $targetType = 'lecturer';
                $targetId = (string) $lecturer->id;
                $targetDisplay = 'Giảng viên: ' . $lecturer->full_name;
                $departmentId = $lecturer->department_id;
                if ($departmentId && isset($departmentFacultyMap[$departmentId])) {
                    $facultyId = $departmentFacultyMap[$departmentId];
                }
            } elseif ($action['group'] === 'config') {
                $targetType = 'system';
                $targetDisplay = 'Cấu hình hệ thống';
            } elseif ($action['group'] === 'security') {
                $targetType = 'system';
                $targetDisplay = 'Hệ thống SPNC';
            } else {
                $targetType = 'system';
                $targetDisplay = 'Hệ thống SPNC';
            }

            $entries[] = [
                'occurred_at' => $occurredAt,
                'severity' => $action['severity'],
                'action_group' => $action['group'],
                'action_code' => $action['code'],
                'action_label' => $action['label'],
                'actor_user_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'actor_roles_snapshot' => $actorRoles
                    ? json_encode($actorRoles, JSON_UNESCAPED_UNICODE)
                    : null,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_display' => $targetDisplay,
                'target_route_name' => $targetRouteName,
                'target_route_params' => $targetRouteParams
                    ? json_encode($targetRouteParams, JSON_UNESCAPED_UNICODE)
                    : null,
                'result_status' => $action['result'],
                'result_error_message' => $action['error'] ?? null,
                'faculty_id' => $facultyId,
                'ip' => '113.161.' . random_int(10, 250) . '.' . random_int(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0',
                'request_method' => $action['method'] ?? null,
                'request_path' => $action['path'] ?? null,
                'request_http_status' => $action['http_status'] ?? null,
                'changes' => ! empty($action['changes'])
                    ? json_encode($action['changes'], JSON_UNESCAPED_UNICODE)
                    : null,
                'note' => $action['result'] === 'failure' ? 'Yêu cầu bị từ chối' : null,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ];
        }

        DB::table('audit_logs')->insert($entries);
    }
}
