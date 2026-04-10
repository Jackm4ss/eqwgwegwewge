<?php

$parsePosts = static function (?string $value): array {
    $items = array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', (string) $value),
    )));

    return $items !== [] ? $items : ['Gate A'];
};

return [
    'path' => trim((string) env('STAFF_PANEL_PATH', 'staff'), '/'),
    'bootstrap_password' => env('SCANNER_BOOTSTRAP_PASSWORD'),
    'seed_count' => max(1, (int) env('SCANNER_SEED_COUNT', 1)),
    'seed_email_domain' => env('BOOTSTRAP_EMAIL_DOMAIN', 'songkran.local'),
    'redis_mode' => (string) env('SCANNER_REDIS_MODE', 'disabled'),
    'posts' => $parsePosts(env('SCANNER_POSTS', 'Gate A,Gate B')),
    'session_post_key' => 'staff.scanner_post',
    'session_device_profile_key' => 'staff.scanner_device_profile',
    'manual_resolution_ttl_seconds' => max(30, (int) env('SCANNER_RESOLUTION_TTL_SECONDS', 120)),
];
