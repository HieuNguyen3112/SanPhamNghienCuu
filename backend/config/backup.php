<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Engine
    |--------------------------------------------------------------------------
    |
    | SPNC ưu tiên restic + rclone vì có mã hóa phía client, deduplication và
    | snapshot gia tăng phù hợp cho dữ liệu minh chứng dung lượng lớn.
    |
    */
    'engine' => env('SPNC_BACKUP_ENGINE', 'restic'),

    'restic' => [
        'binary' => env('SPNC_BACKUP_RESTIC_BINARY', 'restic'),
        'rclone_binary' => env('SPNC_BACKUP_RCLONE_BINARY', 'rclone'),
        'repository' => env('SPNC_BACKUP_REPOSITORY', ''),
        'password' => env('SPNC_BACKUP_PASSWORD', ''),
        'compression' => env('SPNC_BACKUP_COMPRESSION', 'auto'),
        'check_after_backup' => filter_var(env('SPNC_BACKUP_CHECK_AFTER_BACKUP', true), FILTER_VALIDATE_BOOL),
        'rclone_config_path' => env('SPNC_BACKUP_RCLONE_CONFIG', ''),
    ],

    'network' => [
        'http_proxy' => env('SPNC_BACKUP_HTTP_PROXY', env('HTTP_PROXY', '')),
        'https_proxy' => env('SPNC_BACKUP_HTTPS_PROXY', env('HTTPS_PROXY', '')),
        'no_proxy' => env('SPNC_BACKUP_NO_PROXY', env('NO_PROXY', '')),
    ],

    'mysql' => [
        'mysqldump_binary' => env('SPNC_BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
        'mysql_binary' => env('SPNC_BACKUP_MYSQL_BINARY', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Scope
    |--------------------------------------------------------------------------
    |
    | include: dữ liệu nhạy cảm bắt buộc sao lưu.
    | exclude: dữ liệu tạm/cache/log không cần đưa lên cloud.
    |
    */
    'paths' => [
        'include' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SPNC_BACKUP_INCLUDE_PATHS', 'storage/app/evidence,storage/app/public'))
        ))),
        'exclude' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SPNC_BACKUP_EXCLUDE_PATHS', 'storage/framework,storage/logs,backend/vendor,frontend/node_modules,backend/node_modules,.git'))
        ))),
        'state_dir' => env('SPNC_BACKUP_STATE_DIR', 'backup-runs'),
        'workspace_dir' => env('SPNC_BACKUP_WORKSPACE_DIR', 'backup-workspace'),
        'restore_dir' => env('SPNC_BACKUP_RESTORE_DIR', 'backup-restores'),
        'download_dir' => env('SPNC_BACKUP_DOWNLOAD_DIR', 'backup-downloads'),
    ],

    'schedule' => [
        // 1: Monday, 4: Thursday (Carbon dayOfWeek format)
        'days' => array_values(array_filter(array_map(
            static function ($value): int {
                return (int) trim((string) $value);
            },
            explode(',', (string) env('SPNC_BACKUP_SCHEDULE_DAYS', '1,4'))
        ), static fn (int $day): bool => $day >= 0 && $day <= 6)),
        'time' => env('SPNC_BACKUP_SCHEDULE_TIME', '02:00'),
        'prune_time' => env('SPNC_BACKUP_PRUNE_TIME', '03:00'),
        'timezone' => env('SPNC_BACKUP_SCHEDULE_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    ],

    'retention' => [
        'keep_last' => max(1, (int) env('SPNC_BACKUP_KEEP_LAST', 12)),
        'keep_weekly' => max(1, (int) env('SPNC_BACKUP_KEEP_WEEKLY', 8)),
        'keep_monthly' => max(1, (int) env('SPNC_BACKUP_KEEP_MONTHLY', 6)),
    ],

    'restore' => [
        'confirm_phrase' => env('SPNC_BACKUP_RESTORE_CONFIRM_PHRASE', 'KHOI_PHUC_DU_LIEU'),
        'allow_live_restore' => filter_var(env('SPNC_BACKUP_ALLOW_LIVE_RESTORE', false), FILTER_VALIDATE_BOOL),
    ],
];
