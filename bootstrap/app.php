<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureEmailVerifiedForLogin;
use App\Jobs\RefreshAttendanceReadModelMetaJob;
use App\Jobs\RefreshUserManagementReadModelMetaJob;
use App\Jobs\RebuildAttendanceReadModelJob;
use App\Jobs\RebuildUserManagementReadModelJob;
use App\Support\AppRouting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('admin:warm-dashboard-snapshot')->everyMinute()->withoutOverlapping();

        $eventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
        $eventEndDate = trim((string) config('admin.event.end_date', ''));
        $postEventMaintenanceWindowOpen = true;

        if ($eventEndDate !== '') {
            try {
                $postEventMaintenanceWindowOpen = CarbonImmutable::now($eventTimezone)->toDateString()
                    <= CarbonImmutable::parse($eventEndDate, $eventTimezone)->toDateString();
            } catch (\Throwable) {
                $postEventMaintenanceWindowOpen = false;
            }
        }

        // Avoid expensive Firestore maintenance after the event has ended.
        if (! $postEventMaintenanceWindowOpen) {
            return;
        }

        $schedule->command('admin:backup-data')->everySixHours();
        $schedule->command('admin:warm-cache')->everyFifteenMinutes()->withoutOverlapping();

        if ((bool) config('admin.user_management.read_model.enabled', false)) {
            $metaRefreshMinutes = max(1, (int) config('admin.user_management.read_model.meta_refresh_minutes', 3));
            $reconcileMinutes = max(5, (int) config('admin.user_management.read_model.reconcile_minutes', 15));
            $queue = (string) config('admin.user_management.read_model.rebuild_queue', 'admin-sync-low');
            $connection = (string) config('admin.user_management.read_model.queue_connection', config('queue.default', 'sync'));

            $schedule->job(
                new RefreshUserManagementReadModelMetaJob('scheduled_meta_refresh'),
                $queue,
                $connection,
            )
                ->name('admin-user-management-read-model-meta-refresh')
                ->cron(sprintf('*/%d * * * *', $metaRefreshMinutes))
                ->withoutOverlapping();

            $schedule->job(
                new RebuildUserManagementReadModelJob('scheduled_reconcile'),
                $queue,
                $connection,
            )
                ->name('admin-user-management-read-model-reconcile')
                ->cron(sprintf('*/%d * * * *', $reconcileMinutes))
                ->withoutOverlapping();
        }

        if ((bool) config('admin.attendance.read_model.enabled', false)) {
            $metaRefreshMinutes = max(1, (int) config('admin.attendance.read_model.meta_refresh_minutes', 3));
            $reconcileMinutes = max(5, (int) config('admin.attendance.read_model.reconcile_minutes', 15));
            $queue = (string) config('admin.attendance.read_model.rebuild_queue', 'admin-sync-low');
            $connection = (string) config('admin.attendance.read_model.queue_connection', config('queue.default', 'sync'));

            $schedule->job(
                new RefreshAttendanceReadModelMetaJob('scheduled_meta_refresh'),
                $queue,
                $connection,
            )
                ->name('admin-attendance-read-model-meta-refresh')
                ->cron(sprintf('*/%d * * * *', $metaRefreshMinutes))
                ->withoutOverlapping();

            $schedule->job(
                new RebuildAttendanceReadModelJob('scheduled_reconcile'),
                $queue,
                $connection,
            )
                ->name('admin-attendance-read-model-reconcile')
                ->cron(sprintf('*/%d * * * *', $reconcileMinutes))
                ->withoutOverlapping();
        }
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
