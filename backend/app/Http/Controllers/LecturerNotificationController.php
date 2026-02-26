<?php

namespace App\Http\Controllers;

use App\Support\NotificationPayloadFormatter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\Response;

class LecturerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json([
                'message' => 'Không tìm thấy hồ sơ giảng viên.',
            ], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $unreadOnly = filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN);
        $readState = strtolower(trim((string) $request->query('read_state', 'all')));
        $eventKeys = $this->parseCsv((string) $request->query('event_keys', ''));
        if (empty($eventKeys)) {
            $singleEventKey = trim((string) $request->query('event_key', ''));
            if ($singleEventKey !== '') {
                $eventKeys = [$singleEventKey];
            }
        }
        $academicYear = trim((string) $request->query('academic_year', ''));

        $query = $user->notifications()->orderByDesc('created_at');
        if ($unreadOnly || $readState === 'unread') {
            $query->whereNull('read_at');
        } elseif ($readState === 'read') {
            $query->whereNotNull('read_at');
        }

        if (! empty($eventKeys)) {
            $query->where(function ($builder) use ($eventKeys) {
                foreach ($eventKeys as $eventKey) {
                    $safe = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $eventKey);
                    $builder->orWhere('data', 'like', '%"event_key":"' . $safe . '"%');
                }
            });
        }

        if ($academicYear !== '') {
            $safeAcademicYear = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $academicYear);
            $query->where(function ($builder) use ($safeAcademicYear) {
                $builder
                    ->orWhere('data', 'like', '%"academic_year":"' . $safeAcademicYear . '"%')
                    ->orWhere('data', 'like', '%"academic_year_code":"' . $safeAcademicYear . '"%');
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = collect($paginator->items())
            ->map(fn (DatabaseNotification $notification) => NotificationPayloadFormatter::toPayload($notification))
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'unread_count' => (int) $user->unreadNotifications()->count(),
            ],
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, string $notificationId)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json([
                'message' => 'Không tìm thấy hồ sơ giảng viên.',
            ], Response::HTTP_NOT_FOUND);
        }

        $deleted = (int) $user->notifications()
            ->where('id', $notificationId)
            ->delete();

        if ($deleted <= 0) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Đã xóa thông báo.',
            'data' => [
                'deleted_count' => $deleted,
            ],
        ], Response::HTTP_OK);
    }

    public function destroyRead(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json([
                'message' => 'Không tìm thấy hồ sơ giảng viên.',
            ], Response::HTTP_NOT_FOUND);
        }

        $eventKeys = $this->parseCsv((string) $request->query('event_keys', ''));
        if (empty($eventKeys)) {
            $singleEventKey = trim((string) $request->query('event_key', ''));
            if ($singleEventKey !== '') {
                $eventKeys = [$singleEventKey];
            }
        }

        $query = $user->notifications()->whereNotNull('read_at');

        if (! empty($eventKeys)) {
            $query->where(function ($builder) use ($eventKeys) {
                foreach ($eventKeys as $eventKey) {
                    $safe = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $eventKey);
                    $builder->orWhere('data', 'like', '%"event_key":"' . $safe . '"%');
                }
            });
        }

        $deletedCount = (int) $query->delete();

        return response()->json([
            'message' => 'Đã xóa các thông báo đã đọc.',
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ], Response::HTTP_OK);
    }

    public function markRead(Request $request, string $notificationId)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json([
                'message' => 'Không tìm thấy hồ sơ giảng viên.',
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
            $notification->refresh();
        }

        return response()->json([
            'message' => 'Đã đánh dấu đã đọc.',
            'data' => NotificationPayloadFormatter::toPayload($notification),
        ], Response::HTTP_OK);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json([
                'message' => 'Không tìm thấy hồ sơ giảng viên.',
            ], Response::HTTP_NOT_FOUND);
        }

        $query = $user->unreadNotifications();
        $markedCount = (int) $query->count();
        if ($markedCount > 0) {
            $query->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.',
            'data' => [
                'marked_count' => $markedCount,
            ],
        ], Response::HTTP_OK);
    }

    private function parseCsv(string $value): array
    {
        $parts = array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, explode(',', $value)));

        return array_values(array_unique($parts));
    }
}
