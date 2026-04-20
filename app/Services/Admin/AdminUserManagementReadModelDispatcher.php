<?php

namespace App\Services\Admin;

use App\Jobs\RefreshUserManagementReadModelMetaJob;
use App\Jobs\RebuildUserManagementReadModelJob;
use App\Jobs\SyncUserManagementReadModelJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AdminUserManagementReadModelDispatcher
{
    public function __construct(
        private readonly AdminUserManagementReadModel $readModel,
    ) {}

    public function syncUser(string $userId, string $trigger = 'mutation'): void
    {
        if (! $this->readModel->enabled() || trim($userId) === '') {
            return;
        }

        try {
            SyncUserManagementReadModelJob::dispatch($userId, false, $trigger)
                ->onConnection((string) config('admin.user_management.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.user_management.read_model.sync_queue', 'admin-sync-high'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the user management read model sync job.', [
                'user_id' => $userId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function removeUser(string $userId, string $trigger = 'mutation'): void
    {
        if (! $this->readModel->enabled() || trim($userId) === '') {
            return;
        }

        try {
            SyncUserManagementReadModelJob::dispatch($userId, true, $trigger)
                ->onConnection((string) config('admin.user_management.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.user_management.read_model.sync_queue', 'admin-sync-high'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the user management read model removal job.', [
                'user_id' => $userId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function requestRebuild(string $trigger = 'reconcile'): void
    {
        if (! $this->readModel->enabled() || ! $this->lowPriorityMaintenanceEnabled()) {
            return;
        }

        $this->readModel->markRebuilding();

        try {
            RebuildUserManagementReadModelJob::dispatch($trigger)
                ->onConnection((string) config('admin.user_management.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.user_management.read_model.rebuild_queue', 'admin-sync-low'));
        } catch (\Throwable $exception) {
            $this->readModel->markFailed();

            Log::warning('Unable to dispatch the user management read model rebuild job.', [
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function requestMetaRefresh(string $trigger = 'meta_refresh'): void
    {
        if (! $this->readModel->enabled() || ! $this->lowPriorityMaintenanceEnabled()) {
            return;
        }

        try {
            RefreshUserManagementReadModelMetaJob::dispatch($trigger)
                ->onConnection((string) config('admin.user_management.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.user_management.read_model.rebuild_queue', 'admin-sync-low'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the user management read model meta refresh job.', [
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function lowPriorityMaintenanceEnabled(): bool
    {
        $eventEndDate = trim((string) config('admin.event.end_date', ''));

        if ($eventEndDate === '') {
            return true;
        }

        $eventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));

        try {
            return CarbonImmutable::now($eventTimezone)->toDateString()
                <= CarbonImmutable::parse($eventEndDate, $eventTimezone)->toDateString();
        } catch (\Throwable) {
            return false;
        }
    }
}
