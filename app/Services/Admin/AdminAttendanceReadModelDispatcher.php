<?php

namespace App\Services\Admin;

use App\Jobs\RefreshAttendanceReadModelMetaJob;
use App\Jobs\RebuildAttendanceReadModelJob;
use App\Jobs\SyncAttendanceReadModelScanJob;
use App\Jobs\SyncAttendanceReadModelUserJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AdminAttendanceReadModelDispatcher
{
    public function __construct(
        private readonly AdminAttendanceReadModel $readModel,
    ) {}

    public function syncScan(string $scanId, string $trigger = 'mutation'): void
    {
        if (! $this->readModel->enabled() || trim($scanId) === '') {
            return;
        }

        try {
            SyncAttendanceReadModelScanJob::dispatch($scanId, $trigger)
                ->onConnection((string) config('admin.attendance.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.attendance.read_model.sync_queue', 'admin-sync-high'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the attendance read model scan sync job.', [
                'scan_id' => $scanId,
                'trigger' => $trigger,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function syncUser(string $userId, string $trigger = 'mutation'): void
    {
        if (! $this->readModel->enabled() || trim($userId) === '') {
            return;
        }

        try {
            SyncAttendanceReadModelUserJob::dispatch($userId, $trigger)
                ->onConnection((string) config('admin.attendance.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.attendance.read_model.sync_queue', 'admin-sync-high'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the attendance read model user sync job.', [
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
            RebuildAttendanceReadModelJob::dispatch($trigger)
                ->onConnection((string) config('admin.attendance.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.attendance.read_model.rebuild_queue', 'admin-sync-low'));
        } catch (\Throwable $exception) {
            $this->readModel->markFailed();

            Log::warning('Unable to dispatch the attendance read model rebuild job.', [
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
            RefreshAttendanceReadModelMetaJob::dispatch($trigger)
                ->onConnection((string) config('admin.attendance.read_model.queue_connection', config('queue.default', 'sync')))
                ->onQueue((string) config('admin.attendance.read_model.rebuild_queue', 'admin-sync-low'));
        } catch (\Throwable $exception) {
            Log::warning('Unable to dispatch the attendance read model meta refresh job.', [
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
