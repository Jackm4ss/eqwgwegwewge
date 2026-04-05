<?php

use App\Support\AppRouting;
use App\Http\Middleware\EnsureEmailVerifiedForLogin;
use App\Http\Middleware\EnsureAdminRole;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('admin:backup-data')->everySixHours();
        $schedule->command('admin:warm-cache')->everyFifteenMinutes()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'email.verified.login' => EnsureEmailVerifiedForLogin::class,
            'admin.role' => EnsureAdminRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            '/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            $requestMethod = strtoupper($request->getMethod());
            $path = trim($request->path(), '/');
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if (
                $request->expectsJson()
                || $request->is('api/*')
                || ! in_array($requestMethod, ['GET', 'HEAD'], true)
                || $extension !== ''
            ) {
                return null;
            }

            return redirect()->to(AppRouting::publicUrl());
        });
    })
    ->create();

return $app;
