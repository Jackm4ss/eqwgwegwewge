<?php

return [
    'path' => trim((string) env('STAFF_PANEL_PATH', 'staff'), '/'),
    'bootstrap_password' => env('STAFF_BOOTSTRAP_PASSWORD'),
    'seed_count' => (int) env('STAFF_SEED_COUNT', 12),
];
