<?php

$collectionPrefix = env('FIREBASE_COLLECTION_PREFIX', '');
$resolveCollection = fn (string $key, string $default): string => env($key) ?: $collectionPrefix.$default;

return [
    'transport' => env('FIREBASE_TRANSPORT', 'grpc'),
    'api_base_url' => env('FIREBASE_API_BASE_URL', 'https://firestore.googleapis.com/v1'),
    'timeout_seconds' => (int) env('FIREBASE_TIMEOUT_SECONDS', 30),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'database' => env('FIREBASE_DATABASE', '(default)'),
    'collection_prefix' => $collectionPrefix,
    'users_collection' => $resolveCollection('FIREBASE_USERS_COLLECTION', 'users'),
    'tickets_collection' => $resolveCollection('FIREBASE_TICKETS_COLLECTION', 'tickets'),
    'ticket_code_index_collection' => $resolveCollection('FIREBASE_TICKET_CODE_INDEX_COLLECTION', 'ticket_code_index'),
    'ticket_entry_code_index_collection' => $resolveCollection('FIREBASE_TICKET_ENTRY_CODE_INDEX_COLLECTION', 'ticket_entry_code_index'),
    'user_email_index_collection' => $resolveCollection('FIREBASE_USER_EMAIL_INDEX_COLLECTION', 'user_email_index'),
    'user_phone_index_collection' => $resolveCollection('FIREBASE_USER_PHONE_INDEX_COLLECTION', 'user_phone_index'),
    'user_identity_index_collection' => $resolveCollection('FIREBASE_USER_IDENTITY_INDEX_COLLECTION', 'user_identity_index'),
    'attendance_daily_collection' => $resolveCollection('FIREBASE_ATTENDANCE_DAILY_COLLECTION', 'attendance_daily'),
    'scan_logs_collection' => $resolveCollection('FIREBASE_SCAN_LOGS_COLLECTION', 'scan_logs'),
    'admin_activity_logs_collection' => $resolveCollection('FIREBASE_ADMIN_ACTIVITY_LOGS_COLLECTION', 'admin_activity_logs'),
    'fallback_local' => (bool) env('FIREBASE_FALLBACK_LOCAL', env('APP_ENV') === 'local'),
];
