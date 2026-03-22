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
        'rclone_binary' => env('SPNC_RCLONE_BINARY', env('SPNC_BACKUP_RCLONE_BINARY', 'rclone')),
        'repository' => env('SPNC_BACKUP_REPOSITORY', ''),
        'password' => env('SPNC_BACKUP_PASSWORD', ''),
        'compression' => env('SPNC_BACKUP_COMPRESSION', 'auto'),
        'rclone_config_path' => env(
            'SPNC_RCLONE_CONFIG',
            env('SPNC_BACKUP_RCLONE_CONFIG', 'storage/app/rclone/rclone.conf')
        ),
    ],

    'verification' => [
        'enabled' => filter_var(env('SPNC_BACKUP_VERIFY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'time' => env('SPNC_BACKUP_VERIFY_TIME', '04:00'),
        'read_data_subset' => env('SPNC_BACKUP_VERIFY_READ_DATA_SUBSET', '1/20'),
    ],

    'network' => [
        'http_proxy' => env('SPNC_BACKUP_HTTP_PROXY', ''),
        'https_proxy' => env('SPNC_BACKUP_HTTPS_PROXY', ''),
        'no_proxy' => env('SPNC_BACKUP_NO_PROXY', ''),
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
        'snapshot_cache_file' => env('SPNC_BACKUP_SNAPSHOT_CACHE_FILE', 'backup-snapshots/cache.json'),
    ],

    'exports' => [
        'enabled' => filter_var(env('SPNC_BACKUP_EXPORT_ENABLED', true), FILTER_VALIDATE_BOOL),
        'sync_to_drive' => filter_var(env('SPNC_BACKUP_EXPORT_SYNC_TO_DRIVE', true), FILTER_VALIDATE_BOOL),
        'pdf_enabled' => filter_var(env('SPNC_BACKUP_EXPORT_PDF_ENABLED', true), FILTER_VALIDATE_BOOL),
        'folder_name' => env('SPNC_BACKUP_EXPORT_FOLDER_NAME', 'exports'),
        // Cho phép override đích export. Ví dụ:
        // - rclone:drive:spnc-backups/exports
        // - C:\\mount\\spnc-backups\\exports
        // Để trống -> tự suy ra từ SPNC_BACKUP_REPOSITORY.
        'target' => env('SPNC_BACKUP_EXPORT_TARGET', ''),
        'local_dir' => env('SPNC_BACKUP_EXPORT_LOCAL_DIR', 'backup-exports/items'),
        'index_file' => env('SPNC_BACKUP_EXPORT_INDEX_FILE', 'backup-exports/index.json'),
    ],

    'snapshot_cache' => [
        // Danh sach snapshot duoc cache de API list khong goi restic moi request.
        'max_items' => max(10, (int) env('SPNC_BACKUP_SNAPSHOT_CACHE_MAX_ITEMS', 200)),
        'stale_after_seconds' => max(30, (int) env('SPNC_BACKUP_SNAPSHOT_CACHE_STALE_SECONDS', 900)),
        'refresh_timeout_seconds' => max(60, (int) env('SPNC_BACKUP_SNAPSHOT_REFRESH_TIMEOUT_SECONDS', 900)),
        'auto_refresh' => filter_var(env('SPNC_BACKUP_SNAPSHOT_AUTO_REFRESH', true), FILTER_VALIDATE_BOOL),
        'refresh_interval_minutes' => min(59, max(5, (int) env('SPNC_BACKUP_SNAPSHOT_REFRESH_INTERVAL_MINUTES', 10))),
    ],

    'schedule' => [
        // Carbon dayOfWeek: 0=CN, 1=T2, ... 6=T7.
        // Mặc định giữ cadence cố định ban đầu: Thứ 2 + Thứ 5.
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
        'confirm_phrase' => env('SPNC_BACKUP_RESTORE_CONFIRM_PHRASE', 'RESTORE'),
        'legacy_confirm_phrase' => env('SPNC_BACKUP_RESTORE_CONFIRM_PHRASE_LEGACY', 'KHOI_PHUC_DU_LIEU'),
        'allow_live_restore' => filter_var(env('SPNC_BACKUP_ALLOW_LIVE_RESTORE', false), FILTER_VALIDATE_BOOL),
        'allow_live_restore_in_production' => filter_var(
            env('SPNC_BACKUP_ALLOW_LIVE_RESTORE_IN_PRODUCTION', false),
            FILTER_VALIDATE_BOOL
        ),
    ],

    'runs' => [
        'log_max_lines' => max(10, (int) env('SPNC_BACKUP_RUN_LOG_MAX_LINES', 80)),
        'log_max_chars' => max(80, (int) env('SPNC_BACKUP_RUN_LOG_MAX_CHARS', 500)),
        'queue_stale_after_seconds' => max(30, (int) env('SPNC_BACKUP_RUN_QUEUE_STALE_SECONDS', 300)),
        'running_stale_after_seconds' => max(120, (int) env('SPNC_BACKUP_RUN_RUNNING_STALE_SECONDS', 14400)),
        'stale_grace_seconds' => max(0, (int) env('SPNC_BACKUP_RUN_STALE_GRACE_SECONDS', 30)),
        'queue_stale_after_by_operation' => [
            'snapshot_refresh' => max(30, (int) env('SPNC_BACKUP_SNAPSHOT_QUEUE_STALE_SECONDS', 180)),
            'forget' => max(30, (int) env('SPNC_BACKUP_FORGET_QUEUE_STALE_SECONDS', 90)),
        ],
        'running_stale_after_by_operation' => [
            'snapshot_refresh' => max(60, (int) env('SPNC_BACKUP_SNAPSHOT_REFRESH_TIMEOUT_SECONDS', 900)),
            'forget' => max(120, (int) env('SPNC_BACKUP_FORGET_RUNNING_STALE_SECONDS', 1800)),
            'backup' => max(300, (int) env('SPNC_BACKUP_BACKUP_RUNNING_STALE_SECONDS', 3600)),
            'backup_postprocess' => max(300, (int) env('SPNC_BACKUP_POSTPROCESS_RUNNING_STALE_SECONDS', 7200)),
            'backup_check' => max(300, (int) env('SPNC_BACKUP_VERIFY_RUNNING_STALE_SECONDS', 5400)),
            'prune' => max(300, (int) env('SPNC_BACKUP_PRUNE_RUNNING_STALE_SECONDS', 7200)),
            'restore' => max(300, (int) env('SPNC_BACKUP_RESTORE_RUNNING_STALE_SECONDS', 7200)),
        ],
    ],
];
