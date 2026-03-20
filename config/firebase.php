<?php

return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'database' => env('FIREBASE_DATABASE', 'default'),
    'users_collection' => env('FIREBASE_USERS_COLLECTION', 'users'),
    'fallback_local' => (bool) env('FIREBASE_FALLBACK_LOCAL', env('APP_ENV') === 'local'),
];
