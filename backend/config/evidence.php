<?php

return [
    'storage' => [
        // rclone: upload directly to Google Drive.
        // local : only for debugging environments without Drive access.
        'driver' => env('SPNC_EVIDENCE_STORAGE_DRIVER', 'rclone'),

        // Example target:
        // - rclone:spnc_gdrive:spnc-backups/declaration-evidence
        // Leave empty to fail fast instead of silently falling back.
        'rclone_target' => env('SPNC_EVIDENCE_DRIVE_TARGET', ''),
        'allow_backup_repository_fallback' => filter_var(
            env('SPNC_EVIDENCE_ALLOW_BACKUP_REPOSITORY_FALLBACK', false),
            FILTER_VALIDATE_BOOL
        ),

        'folder_name' => env('SPNC_EVIDENCE_DRIVE_FOLDER', 'declaration-evidence'),

        'rclone_binary' => env('SPNC_RCLONE_BINARY', env('SPNC_EVIDENCE_RCLONE_BINARY', 'rclone')),
        'rclone_config_path' => env(
            'SPNC_RCLONE_CONFIG',
            env('SPNC_EVIDENCE_RCLONE_CONFIG', 'storage/app/rclone/rclone.conf')
        ),
        'rclone_config_base64' => env(
            'SPNC_RCLONE_CONFIG_BASE64',
            env('SPNC_EVIDENCE_RCLONE_CONFIG_BASE64', '')
        ),
        'rclone_service_account_file' => env(
            'SPNC_RCLONE_SERVICE_ACCOUNT_FILE',
            env('SPNC_EVIDENCE_RCLONE_SERVICE_ACCOUNT_FILE', '')
        ),
        'rclone_service_account_json_base64' => env(
            'SPNC_RCLONE_SERVICE_ACCOUNT_JSON_BASE64',
            env('SPNC_EVIDENCE_RCLONE_SERVICE_ACCOUNT_JSON_BASE64', '')
        ),
        'rclone_drive_impersonate' => env(
            'SPNC_RCLONE_DRIVE_IMPERSONATE',
            env('SPNC_EVIDENCE_RCLONE_DRIVE_IMPERSONATE', '')
        ),
        'require_writable_rclone_config' => filter_var(
            env('SPNC_EVIDENCE_REQUIRE_WRITABLE_RCLONE_CONFIG', true),
            FILTER_VALIDATE_BOOL
        ),
        'create_folder_before_upload' => filter_var(
            env('SPNC_EVIDENCE_CREATE_FOLDER_BEFORE_UPLOAD', false),
            FILTER_VALIDATE_BOOL
        ),
        'rclone_retries' => max(1, (int) env('SPNC_EVIDENCE_RCLONE_RETRIES', 3)),
        'rclone_low_level_retries' => max(1, (int) env('SPNC_EVIDENCE_RCLONE_LOW_LEVEL_RETRIES', 5)),
        'rclone_retries_sleep_seconds' => max(1, (int) env('SPNC_EVIDENCE_RCLONE_RETRIES_SLEEP_SECONDS', 2)),
        'rclone_connect_timeout_seconds' => max(3, (int) env('SPNC_EVIDENCE_RCLONE_CONNECT_TIMEOUT_SECONDS', 15)),
        'rclone_io_timeout_seconds' => max(10, (int) env('SPNC_EVIDENCE_RCLONE_IO_TIMEOUT_SECONDS', 60)),

        // Keep proxy explicit to avoid inheriting host-level HTTP_PROXY/HTTPS_PROXY.
        'http_proxy' => env('SPNC_EVIDENCE_HTTP_PROXY', ''),
        'https_proxy' => env('SPNC_EVIDENCE_HTTPS_PROXY', ''),
        'no_proxy' => env('SPNC_EVIDENCE_NO_PROXY', ''),

        'mkdir_timeout_seconds' => max(10, (int) env('SPNC_EVIDENCE_MKDIR_TIMEOUT_SECONDS', 120)),
        'upload_timeout_seconds' => max(30, (int) env('SPNC_EVIDENCE_UPLOAD_TIMEOUT_SECONDS', 600)),
        'download_timeout_seconds' => max(30, (int) env('SPNC_EVIDENCE_DOWNLOAD_TIMEOUT_SECONDS', 600)),
        'delete_timeout_seconds' => max(10, (int) env('SPNC_EVIDENCE_DELETE_TIMEOUT_SECONDS', 180)),

        // Local fallback is only used when storage.driver=local.
        'local_disk' => env('SPNC_EVIDENCE_LOCAL_DISK', env('FILESYSTEM_DISK', 'local')),
        'local_dir' => env('SPNC_EVIDENCE_LOCAL_DIR', 'evidence/declarations'),

        // Hot storage for preview/read throughput.
        'hot_disk' => env('SPNC_EVIDENCE_HOT_DISK', 'local'),
        'hot_dir' => env('SPNC_EVIDENCE_HOT_DIR', 'evidence/hot-cache'),
        'hot_cache_ttl_days' => max(1, (int) env('SPNC_EVIDENCE_HOT_CACHE_TTL_DAYS', 14)),
        'hot_cache_max_bytes' => max(50 * 1024 * 1024, (int) env('SPNC_EVIDENCE_HOT_CACHE_MAX_BYTES', 2 * 1024 * 1024 * 1024)),
        'hot_cache_control_seconds' => max(60, (int) env('SPNC_EVIDENCE_HOT_CACHE_CONTROL_SECONDS', 3600)),
        'hot_cache_cleanup_time' => env('SPNC_EVIDENCE_HOT_CACHE_CLEANUP_TIME', '04:30'),

        // Cold sync runs in the background to avoid blocking uploads.
        'cold_sync_connection' => env('SPNC_EVIDENCE_COLD_SYNC_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'cold_sync_queue' => env('SPNC_EVIDENCE_COLD_SYNC_QUEUE', 'evidence-sync'),
        'cold_sync_after_response' => filter_var(
            env('SPNC_EVIDENCE_COLD_SYNC_AFTER_RESPONSE', true),
            FILTER_VALIDATE_BOOL
        ),
    ],
];
