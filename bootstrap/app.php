<?php

use App\Http\Middleware\EnsureEmailVerifiedForLogin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('admin:backup-data')->everySixHours();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'email.verified.login' => EnsureEmailVerifiedForLogin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            '/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

return $app;
