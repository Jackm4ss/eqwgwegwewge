<?php

return [
    'path' => trim((string) env('ADMIN_PANEL_PATH', 'admin'), '/'),
    'bootstrap_password' => env('ADMIN_BOOTSTRAP_PASSWORD'),
    'seed_count' => (int) env('ADMIN_SEED_COUNT', 8),
    'seed_email_domain' => env('BOOTSTRAP_EMAIL_DOMAIN', 'songkran.local'),
    'dashboard_days' => (int) env('ADMIN_DASHBOARD_DAYS', 7),
    'per_page' => (int) env('ADMIN_PER_PAGE', 10),
    'report_default_days' => (int) env('ADMIN_REPORT_DEFAULT_DAYS', 7),
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
];
