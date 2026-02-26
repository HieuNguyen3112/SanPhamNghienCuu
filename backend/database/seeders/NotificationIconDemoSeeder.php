<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\WorkflowDatabaseNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationIconDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('notifications')) {
            return;
        }

        $now = now();

        $userIdByLecturerCode = [
            'GV-001' => $this->resolveUserIdByLecturerCode('GV-001'),
            'GV-002' => $this->resolveUserIdByLecturerCode('GV-002'),
        ];

        $seedCases = [
            [
                'seed_key' => 'gv1-participation-invitation',
                'lecturer_code' => 'GV-001',
                'event_key' => 'participation_invitation',
                'title' => 'Lời mời tham gia công trình',
                'message' => 'Bạn nhận được lời mời tham gia công trình "Giáo trình Thị giác máy tính".',
                'target_url' => '/declarations/participatier',
                'extra' => [
                    'activity_id' => 41,
                ],
            ],
            [
                'seed_key' => 'gv1-participation-accepted',
                'lecturer_code' => 'GV-001',
                'event_key' => 'participation_accepted',
                'title' => 'Đồng nghiệp đã xác nhận tham gia',
                'message' => 'Giảng viên Trần Thị B đã xác nhận tham gia công trình "Giáo trình Thị giác máy tính".',
                'target_url' => '/declarations/participatier',
                'extra' => [
                    'activity_id' => 41,
                ],
            ],
            [
                'seed_key' => 'gv1-participation-rejected',
                'lecturer_code' => 'GV-001',
                'event_key' => 'participation_rejected',
                'title' => 'Đồng nghiệp đã từ chối tham gia',
                'message' => 'Giảng viên Phạm Quốc C đã từ chối tham gia công trình "Giáo trình Thị giác máy tính".',
                'target_url' => '/declarations/participatier',
                'extra' => [
                    'activity_id' => 41,
                ],
            ],
            [
                'seed_key' => 'gv1-work-returned',
                'lecturer_code' => 'GV-001',
                'event_key' => 'work_revision_requested',
                'title' => 'Công trình cần bổ sung hồ sơ',
                'message' => 'Công trình "Nghiên cứu ứng dụng AI trong giáo dục" đã được trả lại để bổ sung minh chứng.',
                'target_url' => '/works/personal?activity_id=55',
                'extra' => [
                    'activity_id' => 55,
                ],
            ],
            [
                'seed_key' => 'gv1-work-approved',
                'lecturer_code' => 'GV-001',
                'event_key' => 'work_approved',
                'title' => 'Công trình đã được duyệt',
                'message' => 'Công trình "Nghiên cứu ứng dụng AI trong giáo dục" đã được khoa duyệt.',
                'target_url' => '/works/personal?activity_id=55',
                'extra' => [
                    'activity_id' => 55,
                ],
            ],
            [
                'seed_key' => 'gv1-hours-approved',
                'lecturer_code' => 'GV-001',
                'event_key' => 'hours_approved',
                'title' => 'Giờ NCKH đã được duyệt',
                'message' => 'Công trình "Nghiên cứu ứng dụng AI trong giáo dục" đã được duyệt giờ: 48 giờ.',
                'target_url' => '/hours/personal',
                'extra' => [
                    'activity_id' => 55,
                    'hours_value' => 48,
                ],
            ],
            [
                'seed_key' => 'gv1-hours-warning-missing',
                'lecturer_code' => 'GV-001',
                'event_key' => 'hours_warning',
                'title' => 'Cảnh báo thiếu giờ NCKH',
                'message' => 'Bạn đang thiếu 62 giờ NCKH so với định mức năm học 2025-2026.',
                'target_url' => '/hours/personal_warnings',
                'extra' => [
                    'type_key' => 'missing_hours',
                    'academic_year' => '2025-2026',
                ],
            ],
            [
                'seed_key' => 'gv1-hours-warning-near',
                'lecturer_code' => 'GV-001',
                'event_key' => 'hours_warning',
                'title' => 'Cảnh báo sắp hết hạn kê khai giờ',
                'message' => 'Đợt tính giờ hiện tại sắp hết hạn. Vui lòng hoàn tất kê khai và gửi duyệt.',
                'target_url' => '/hours/personal_warnings',
                'extra' => [
                    'type_key' => 'deadline_near',
                    'academic_year' => '2025-2026',
                ],
                'is_read' => true,
            ],
            [
                'seed_key' => 'gv1-hours-warning-expired',
                'lecturer_code' => 'GV-001',
                'event_key' => 'hours_warning',
                'title' => 'Cảnh báo đã quá hạn kê khai giờ',
                'message' => 'Đợt tính giờ đã quá hạn. Vui lòng liên hệ khoa để được hỗ trợ.',
                'target_url' => '/hours/personal_warnings',
                'extra' => [
                    'type_key' => 'deadline_passed',
                    'academic_year' => '2025-2026',
                ],
            ],
            [
                'seed_key' => 'gv2-hours-approved',
                'lecturer_code' => 'GV-002',
                'event_key' => 'hours_approved',
                'title' => 'Giờ NCKH đã được duyệt',
                'message' => 'Công trình "Báo cáo hội thảo STEM 2025" đã được duyệt giờ: 25 giờ.',
                'target_url' => '/hours/personal',
                'extra' => [
                    'activity_id' => 76,
                    'hours_value' => 25,
                ],
            ],
        ];

        $index = 0;
        foreach ($seedCases as $case) {
            $lecturerCode = (string) ($case['lecturer_code'] ?? '');
            $userId = $userIdByLecturerCode[$lecturerCode] ?? null;
            if (! $userId) {
                continue;
            }

            $createdAt = $now->copy()->subMinutes(2 + ($index * 3));
            $readAt = ! empty($case['is_read']) ? $createdAt->copy()->addMinute() : null;
            $payload = array_merge([
                'event_key' => (string) $case['event_key'],
                'title' => (string) $case['title'],
                'message' => (string) $case['message'],
                'target_url' => (string) $case['target_url'],
            ], (array) ($case['extra'] ?? []));

            DB::table('notifications')->updateOrInsert(
                ['id' => $this->deterministicUuid((string) $case['seed_key'] . '-' . $userId)],
                [
                    'type' => WorkflowDatabaseNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $userId,
                    'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'read_at' => $readAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            $index++;
        }
    }

    private function resolveUserIdByLecturerCode(string $lecturerCode): ?int
    {
        $userId = DB::table('lecturers')
            ->where('code', $lecturerCode)
            ->value('user_id');

        if ($userId) {
            return (int) $userId;
        }

        $fallbackEmailByCode = [
            'GV-001' => 'gv@local.test',
            'GV-002' => 'gv2@local.test',
        ];

        $fallbackEmail = $fallbackEmailByCode[$lecturerCode] ?? null;
        if (! $fallbackEmail) {
            return null;
        }

        $fallbackUserId = DB::table('users')->where('email', $fallbackEmail)->value('id');
        return $fallbackUserId ? (int) $fallbackUserId : null;
    }

    private function deterministicUuid(string $seed): string
    {
        $hash = md5($seed);

        return sprintf(
            '%s-%s-4%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
