<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => false,
        ],
        'admin_backups' => [
            'driver' => 'local',
            'root' => storage_path('app/private/admin-backups'),
            'throw' => false,
        ],
    ],
];
