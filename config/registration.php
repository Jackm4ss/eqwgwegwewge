<?php

$defaultRedisMode = env('APP_ENV', 'production') === 'production'
    ? 'required'
    : 'disabled';

$configuredRedisMode = (string) env('REGISTRATION_REDIS_MODE', $defaultRedisMode);

return [
    'redis_mode' => in_array($configuredRedisMode, ['disabled', 'required'], true)
        ? $configuredRedisMode
        : $defaultRedisMode,
    'email_queue' => (string) env('REGISTRATION_EMAIL_QUEUE', 'registration-emails'),
];
