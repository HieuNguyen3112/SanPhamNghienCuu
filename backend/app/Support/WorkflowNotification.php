<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\WorkflowDatabaseNotification;
use Illuminate\Support\Facades\DB;

class WorkflowNotification
{
    public static function makePayload(
        string $eventKey,
        string $title,
        string $message,
        string $targetUrl,
        array $extra = []
    ): array {
        return array_merge([
            'event_key' => $eventKey,
            'title' => $title,
            'message' => $message,
            'target_url' => $targetUrl,
        ], $extra);
    }

    public static function notifyLecturer(int $lecturerId, array $payload): void
    {
        if ($lecturerId <= 0) {
            return;
        }

        $userId = DB::table('lecturers')
            ->where('id', $lecturerId)
            ->value('user_id');

        if (! $userId) {
            return;
        }

        $user = User::find((int) $userId);
        if (! $user) {
            return;
        }

        $user->notify(new WorkflowDatabaseNotification($payload));
    }

    public static function notifyLecturers(array $lecturerIds, array $payload): void
    {
        $lecturerIds = collect($lecturerIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($lecturerIds)) {
            return;
        }

        $userIds = DB::table('lecturers')
            ->whereIn('id', $lecturerIds)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->notify(new WorkflowDatabaseNotification($payload));
        }
    }

    public static function notifyFacultyBoardByActivityId(
        int $activityId,
        array $payload,
        ?int $excludeUserId = null
    ): void {
        if ($activityId <= 0) {
            return;
        }

        $facultyId = DB::table('research_activities as ra')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->join('departments as d', 'l.department_id', '=', 'd.id')
            ->where('ra.id', $activityId)
            ->value('d.faculty_id');

        if (! $facultyId) {
            return;
        }

        self::notifyFacultyBoardByFacultyId((int) $facultyId, $payload, $excludeUserId);
    }

    public static function notifyFacultyBoardByFacultyId(
        int $facultyId,
        array $payload,
        ?int $excludeUserId = null
    ): void {
        if ($facultyId <= 0) {
            return;
        }

        $userIds = DB::table('users as u')
            ->join('lecturers as l', 'l.user_id', '=', 'u.id')
            ->join('departments as d', 'l.department_id', '=', 'd.id')
            ->join('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'u.id')
                    ->where('mhr.model_type', User::class);
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('d.faculty_id', $facultyId)
            ->whereIn('r.name', ['DEPARTMENT_BOARD', 'DL'])
            ->when($excludeUserId, function ($query, $excluded) {
                $query->where('u.id', '<>', (int) $excluded);
            })
            ->distinct()
            ->pluck('u.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->notify(new WorkflowDatabaseNotification($payload));
        }
    }
}
