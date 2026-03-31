<?php

return [
    'mode' => env('APP_ROUTING_MODE', 'path'),
    'public_url' => env('FRONTEND_HOMEPAGE_URL', env('APP_URL')),
    'register_url' => env('REGISTER_APP_URL', env('FRONTEND_HOMEPAGE_URL', env('APP_URL'))),
    'admin_url' => env('ADMIN_APP_URL'),
    'staff_url' => env('STAFF_APP_URL'),
];
