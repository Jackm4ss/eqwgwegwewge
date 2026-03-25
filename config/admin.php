<?php

return [
    'path' => trim((string) env('ADMIN_PANEL_PATH', 'admin'), '/'),
    'bootstrap_password' => env('ADMIN_BOOTSTRAP_PASSWORD'),
    'seed_count' => (int) env('ADMIN_SEED_COUNT', 8),
    'dashboard_days' => (int) env('ADMIN_DASHBOARD_DAYS', 7),
    'per_page' => (int) env('ADMIN_PER_PAGE', 10),
    'report_default_days' => (int) env('ADMIN_REPORT_DEFAULT_DAYS', 7),
    'event' => [
        'start_date' => env('ADMIN_EVENT_START_DATE', '2026-04-09'),
        'end_date' => env('ADMIN_EVENT_END_DATE', '2026-04-19'),
    ],
    'backup' => [
        'disk' => env('ADMIN_BACKUP_DISK', 'admin_backups'),
        'path' => trim((string) env('ADMIN_BACKUP_PATH', 'admin-backups'), '/'),
        'bucket' => env('ADMIN_BACKUP_GCS_BUCKET'),
        'prefix' => trim((string) env('ADMIN_BACKUP_GCS_PREFIX', 'event-system'), '/'),
    ],
    'future_urls' => [
        'admin' => env('ADMIN_APP_URL'),
        'register' => env('REGISTER_APP_URL'),
        'staff' => env('STAFF_APP_URL'),
    ],
];
