<?php

return [
    'path' => trim((string) env('ADMIN_PANEL_PATH', 'admin'), '/'),
    'bootstrap_password' => env('ADMIN_BOOTSTRAP_PASSWORD'),
    'seed_count' => (int) env('ADMIN_SEED_COUNT', 8),
    'seed_email_domain' => env('BOOTSTRAP_EMAIL_DOMAIN', 'songkran.local'),
    'dashboard_days' => (int) env('ADMIN_DASHBOARD_DAYS', 7),
    'dashboard' => [
        'attendance_analytics_enabled' => env('ADMIN_DASHBOARD_ATTENDANCE_ANALYTICS_ENABLED', false),
        'snapshot_enabled' => env('ADMIN_DASHBOARD_SNAPSHOT_ENABLED', true),
        'snapshot_fresh_seconds' => max(5, (int) env('ADMIN_DASHBOARD_SNAPSHOT_FRESH_SECONDS', 30)),
        'snapshot_ttl_seconds' => max(6, (int) env('ADMIN_DASHBOARD_SNAPSHOT_TTL_SECONDS', 900)),
        'snapshot_refresh_lock_seconds' => max(10, (int) env('ADMIN_DASHBOARD_SNAPSHOT_REFRESH_LOCK_SECONDS', 180)),
    ],
    'warm_cache' => [
        'optimized_enabled' => env('ADMIN_WARM_CACHE_OPTIMIZED_ENABLED', true),
    ],
    'per_page' => (int) env('ADMIN_PER_PAGE', 10),
    'report_default_days' => (int) env('ADMIN_REPORT_DEFAULT_DAYS', 7),
    'presence' => [
        'heartbeat_seconds' => max(15, (int) env('ADMIN_PRESENCE_HEARTBEAT_SECONDS', 45)),
        'ttl_seconds' => max(30, (int) env('ADMIN_PRESENCE_TTL_SECONDS', 120)),
    ],
    'event' => [
        'timezone' => env('ADMIN_EVENT_TIMEZONE', env('EVENT_TIMEZONE', env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'))),
        'start_date' => env('ADMIN_EVENT_START_DATE', env('EVENT_START_DATE', '2026-04-09')),
        'end_date' => env('ADMIN_EVENT_END_DATE', env('EVENT_END_DATE', '2026-04-19')),
    ],
    'backup' => [
        'disk' => env('ADMIN_BACKUP_DISK', 'admin_backups'),
        'path' => trim((string) env('ADMIN_BACKUP_PATH', 'admin-backups'), '/'),
        'bucket' => env('ADMIN_BACKUP_GCS_BUCKET'),
        'prefix' => trim((string) env('ADMIN_BACKUP_GCS_PREFIX', 'event-system'), '/'),
    ],
    'future_urls' => [
        'admin' => env('ADMIN_APP_URL'),
        'landing' => env('FRONTEND_HOMEPAGE_URL', env('APP_URL')),
        'register' => env('REGISTER_APP_URL'),
        'staff' => env('STAFF_APP_URL'),
    ],
    'user_management' => [
        'inline_snapshot_sync_max_rows' => max(0, (int) env('ADMIN_USER_MANAGEMENT_INLINE_SNAPSHOT_SYNC_MAX_ROWS', 2000)),
        'inline_meta_sync_max_rows' => max(0, (int) env('ADMIN_USER_MANAGEMENT_INLINE_META_SYNC_MAX_ROWS', 2000)),
        'read_model' => [
            'enabled' => env('ADMIN_USER_MANAGEMENT_READ_MODEL_ENABLED', false),
            'fresh_within_seconds' => max(5, (int) env('ADMIN_USER_MANAGEMENT_SYNC_SLA_SECONDS', 15)),
            'degraded_after_seconds' => max(
                (int) env('ADMIN_USER_MANAGEMENT_SYNC_SLA_SECONDS', 15) + 1,
                (int) env('ADMIN_USER_MANAGEMENT_DEGRADED_AFTER_SECONDS', 60),
            ),
            'fallback_after_seconds' => max(
                (int) env('ADMIN_USER_MANAGEMENT_DEGRADED_AFTER_SECONDS', 60) + 1,
                (int) env('ADMIN_USER_MANAGEMENT_FALLBACK_AFTER_SECONDS', 300),
            ),
            'queue_connection' => env('ADMIN_USER_MANAGEMENT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
            'sync_queue' => env('ADMIN_USER_MANAGEMENT_SYNC_QUEUE', 'admin-sync-high'),
            'rebuild_queue' => env('ADMIN_USER_MANAGEMENT_REBUILD_QUEUE', 'admin-sync-low'),
            'meta_refresh_minutes' => max(1, (int) env('ADMIN_USER_MANAGEMENT_META_REFRESH_MINUTES', 3)),
            'reconcile_minutes' => max(5, (int) env('ADMIN_USER_MANAGEMENT_RECONCILE_MINUTES', 15)),
        ],
    ],
    'attendance' => [
        'inline_meta_sync_max_rows' => max(0, (int) env('ADMIN_ATTENDANCE_INLINE_META_SYNC_MAX_ROWS', 4000)),
        'read_model' => [
            'enabled' => env('ADMIN_ATTENDANCE_READ_MODEL_ENABLED', false),
            'fresh_within_seconds' => max(5, (int) env('ADMIN_ATTENDANCE_SYNC_SLA_SECONDS', 15)),
            'degraded_after_seconds' => max(
                (int) env('ADMIN_ATTENDANCE_SYNC_SLA_SECONDS', 15) + 1,
                (int) env('ADMIN_ATTENDANCE_DEGRADED_AFTER_SECONDS', 60),
            ),
            'fallback_after_seconds' => max(
                (int) env('ADMIN_ATTENDANCE_DEGRADED_AFTER_SECONDS', 60) + 1,
                (int) env('ADMIN_ATTENDANCE_FALLBACK_AFTER_SECONDS', 300),
            ),
            'queue_connection' => env('ADMIN_ATTENDANCE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
            'sync_queue' => env('ADMIN_ATTENDANCE_SYNC_QUEUE', 'admin-sync-high'),
            'rebuild_queue' => env('ADMIN_ATTENDANCE_REBUILD_QUEUE', 'admin-sync-low'),
            'meta_refresh_minutes' => max(1, (int) env('ADMIN_ATTENDANCE_META_REFRESH_MINUTES', 3)),
            'reconcile_minutes' => max(5, (int) env('ADMIN_ATTENDANCE_RECONCILE_MINUTES', 15)),
        ],
    ],
];
