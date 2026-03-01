<?php

namespace App\Support;

class AuditLogObjectPresenter
{
    public static function present(
        ?string $targetType,
        ?string $targetId,
        ?string $targetDisplay,
        ?string $requestPath,
        ?string $actionCode
    ): array {
        $display = self::normalize($targetDisplay);
        $request = self::normalize($requestPath);
        $type = self::normalize($targetType);
        $code = strtoupper(trim((string) ($actionCode ?? '')));

        if (self::looksLikeApiPath($display)) {
            return self::mapFromPath($display, $code);
        }

        if ((! $display || $display === '-') && self::looksLikeApiPath($request)) {
            return self::mapFromPath($request, $code);
        }

        if ($type === 'export') {
            return self::mapFromPath($display ?: $request, $code);
        }

        if ($display && str_contains($display, '=')) {
            return self::mapFromKeyValueDisplay($display);
        }

        $fromType = self::mapFromTargetType($type, $display, $targetId);
        if ($fromType !== null) {
            return $fromType;
        }

        if ($display && ! self::looksLikeApiPath($display)) {
            return [
                'display' => $display,
                'type' => self::inferTypeFromActionCode($code),
            ];
        }

        if (str_starts_with($code, 'LOGIN') || str_starts_with($code, 'LOGOUT')) {
            return [
                'display' => 'Hệ thống SPNC',
                'type' => 'AUTH',
            ];
        }

        return [
            'display' => 'Tác vụ hệ thống',
            'type' => 'SYSTEM',
        ];
    }

    private static function mapFromTargetType(?string $type, ?string $display, ?string $targetId): ?array
    {
        if (! $type) {
            return null;
        }

        $normalizedDisplay = $display ?: null;
        $suffix = $normalizedDisplay ? ': ' . $normalizedDisplay : ($targetId ? ' #' . $targetId : '');

        return match ($type) {
            'research_activity' => [
                'display' => 'Công trình nghiên cứu' . $suffix,
                'type' => 'WORKFLOW',
            ],
            'lecturer_profile' => [
                'display' => 'Hồ sơ giảng viên' . $suffix,
                'type' => 'PROFILE',
            ],
            'lecturer' => [
                'display' => 'Giảng viên' . $suffix,
                'type' => 'PROFILE',
            ],
            'department' => [
                'display' => 'Bộ môn/đơn vị' . $suffix,
                'type' => 'CATALOG',
            ],
            'faculty' => [
                'display' => 'Khoa' . $suffix,
                'type' => 'CATALOG',
            ],
            default => null,
        };
    }

    private static function mapFromKeyValueDisplay(string $display): array
    {
        if (str_starts_with($display, 'academic_year_id=')) {
            return [
                'display' => 'Năm học áp dụng',
                'type' => 'CATALOG',
            ];
        }

        return [
            'display' => 'Cấu hình hệ thống',
            'type' => 'CATALOG',
        ];
    }

    private static function mapFromPath(?string $path, string $actionCode): array
    {
        $normalizedPath = strtolower(trim((string) $path));
        $format = str_contains($normalizedPath, '/pdf') ? 'PDF' : (str_contains($normalizedPath, '/excel') ? 'Excel' : null);
        $suffix = $format ? " ({$format})" : '';

        if (str_contains($normalizedPath, '/reports/lecturers')) {
            return ['display' => 'Báo cáo thống kê nhân sự' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_contains($normalizedPath, '/reports/research')) {
            return ['display' => 'Báo cáo thống kê công trình NCKH' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_contains($normalizedPath, '/reports/hour-research')) {
            return ['display' => 'Báo cáo thống kê giờ NCKH' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_contains($normalizedPath, '/works/lecturers/summary/export') || str_contains($normalizedPath, '/faculty/works/export')) {
            return ['display' => 'Thống kê công trình NCKH theo giảng viên' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_contains($normalizedPath, '/hours/lecturers/summary/export')) {
            return ['display' => 'Thống kê giờ NCKH theo giảng viên' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_contains($normalizedPath, '/export/')) {
            return ['display' => 'Xuất báo cáo hệ thống' . $suffix, 'type' => 'EXPORT'];
        }

        if (str_starts_with($normalizedPath, '/api/')) {
            return ['display' => 'Tác vụ hệ thống', 'type' => self::inferTypeFromActionCode($actionCode)];
        }

        return ['display' => 'Tác vụ hệ thống', 'type' => self::inferTypeFromActionCode($actionCode)];
    }

    private static function inferTypeFromActionCode(string $actionCode): string
    {
        if (str_contains($actionCode, 'EXPORT')) {
            return 'EXPORT';
        }

        if (str_starts_with($actionCode, 'LOGIN') || str_starts_with($actionCode, 'LOGOUT')) {
            return 'AUTH';
        }

        if (str_contains($actionCode, 'HOURS') || str_contains($actionCode, 'APPROV') || str_contains($actionCode, 'REJECT')) {
            return 'WORKFLOW';
        }

        if (str_contains($actionCode, 'WORK_') || str_contains($actionCode, 'RESEARCH')) {
            return 'WORKFLOW';
        }

        if (str_contains($actionCode, 'CATALOG') || str_contains($actionCode, 'ACADEMIC_YEAR') || str_contains($actionCode, 'QUOTA')) {
            return 'CATALOG';
        }

        if (str_contains($actionCode, 'PROFILE') || str_contains($actionCode, 'LECTURER')) {
            return 'PROFILE';
        }

        return 'SYSTEM';
    }

    private static function looksLikeApiPath(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        $trimmed = trim($value);
        return str_starts_with($trimmed, '/api/')
            || str_starts_with($trimmed, 'api/')
            || str_starts_with($trimmed, 'http://')
            || str_starts_with($trimmed, 'https://');
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }
}
