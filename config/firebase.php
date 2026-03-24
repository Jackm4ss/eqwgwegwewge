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
    'user_email_index_collection' => $resolveCollection('FIREBASE_USER_EMAIL_INDEX_COLLECTION', 'user_email_index'),
    'user_identity_index_collection' => $resolveCollection('FIREBASE_USER_IDENTITY_INDEX_COLLECTION', 'user_identity_index'),
    'fallback_local' => (bool) env('FIREBASE_FALLBACK_LOCAL', env('APP_ENV') === 'local'),
];
