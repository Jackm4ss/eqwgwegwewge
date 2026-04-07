<?php

namespace App\Console;

use App\Jobs\RefreshUserManagementReadModelMetaJob;
use App\Jobs\RebuildUserManagementReadModelJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
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
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
