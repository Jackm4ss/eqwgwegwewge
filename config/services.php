<?php

return [
    'recaptcha' => [
        'enabled' => (bool) env('RECAPTCHA_ENABLED', false),
        'version' => env('RECAPTCHA_VERSION', 'v3'),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'expected_action' => env('RECAPTCHA_EXPECTED_ACTION', 'register'),
        'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.5),
        'verify_url' => env('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),
    ],
];
