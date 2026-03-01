<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

class NotificationPayloadFormatter
{
    public static function toPayload(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $eventKey = self::resolveEventKey($notification->type, $data);

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'event_key' => $eventKey,
            'title' => self::resolveTitle($eventKey, $data),
            'message' => self::resolveMessage($eventKey, $data),
            'data' => $data,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'is_unread' => $notification->read_at === null,
            'target_url' => self::resolveTargetUrl($eventKey, $data),
        ];
    }

    private static function resolveEventKey(string $type, array $data): string
    {
        $eventKey = isset($data['event_key']) ? trim((string) $data['event_key']) : '';
        if ($eventKey !== '') {
            return $eventKey;
        }

        $typeLower = strtolower($type);
        if (str_contains($typeLower, 'participationinvitationaccepted')) {
            return 'participation_accepted';
        }
        if (str_contains($typeLower, 'participationinvitationrejected')) {
            return 'participation_rejected';
        }
        if (str_contains($typeLower, 'participationinvitation')) {
            return 'participation_invitation';
        }

        return 'system_notification';
    }

    private static function resolveTitle(string $eventKey, array $data): string
    {
        $title = isset($data['title']) ? trim((string) $data['title']) : '';
        if ($title !== '') {
            return $title;
        }

        return match ($eventKey) {
            'work_approved' => 'Công trình đã được duyệt',
            'work_rejected' => 'Công trình bị từ chối',
            'hours_approved' => 'Giờ NCKH đã được duyệt',
            'hours_rejected' => 'Giờ NCKH bị từ chối',
            'hours_warning' => 'Nhắc nhở giờ NCKH',
            'work_submitted_to_faculty' => 'Có hồ sơ công trình cần duyệt',
            'hours_submitted_to_faculty' => 'Có yêu cầu duyệt giờ mới',
            'participation_invitation' => 'Thông báo xác nhận tham gia',
            'participation_accepted' => 'Thành viên đã xác nhận',
            'participation_rejected' => 'Thành viên đã từ chối',
            default => 'Thông báo hệ thống',
        };
    }

    private static function resolveMessage(string $eventKey, array $data): string
    {
        $message = isset($data['message']) ? trim((string) $data['message']) : '';
        if ($message !== '') {
            return $message;
        }

        return match ($eventKey) {
            'work_approved' => 'Công trình của bạn đã được duyệt.',
            'work_rejected' => 'Công trình của bạn đã bị từ chối.',
            'hours_approved' => 'Yêu cầu duyệt giờ của bạn đã được duyệt.',
            'hours_rejected' => 'Yêu cầu duyệt giờ của bạn đã bị từ chối.',
            'hours_warning' => 'Bạn có nhắc nhở mới về giờ nghiên cứu khoa học.',
            'work_submitted_to_faculty' => 'Có hồ sơ công trình mới đang chờ khoa duyệt.',
            'hours_submitted_to_faculty' => 'Có yêu cầu duyệt giờ mới đang chờ khoa xử lý.',
            'participation_invitation' => 'Bạn có lời mời xác nhận tham gia công trình.',
            'participation_accepted' => 'Thành viên đã xác nhận tham gia công trình.',
            'participation_rejected' => 'Thành viên đã từ chối tham gia công trình.',
            default => 'Bạn có một thông báo mới.',
        };
    }

    private static function resolveTargetUrl(string $eventKey, array $data): string
    {
        $explicitTarget = isset($data['target_url']) ? trim((string) $data['target_url']) : '';
        if ($explicitTarget !== '' && str_starts_with($explicitTarget, '/')) {
            return $explicitTarget;
        }

        $actionRoute = isset($data['action_route']) ? trim((string) $data['action_route']) : '';
        if ($actionRoute !== '' && str_starts_with($actionRoute, '/')) {
            return $actionRoute;
        }

        return match ($eventKey) {
            'work_approved', 'work_rejected' => '/works/personal',
            'hours_approved', 'hours_rejected', 'hours_warning' => '/hours/personal',
            'work_submitted_to_faculty' => '/works/facapprovals',
            'hours_submitted_to_faculty' => '/hours/facapprovals',
            'participation_invitation', 'participation_accepted', 'participation_rejected' => '/declarations/participatier',
            default => '/declarations/gateway',
        };
    }
}

