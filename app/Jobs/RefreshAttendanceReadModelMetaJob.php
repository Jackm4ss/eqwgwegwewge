<?php

namespace App\Jobs;

use App\Services\Admin\AdminAttendanceReadModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshAttendanceReadModelMetaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $trigger = 'scheduled_meta_refresh',
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('admin-attendance-read-model:refresh-meta'))
                ->expireAfter(240)
                ->dontRelease(),
        ];
    }

    public function handle(AdminAttendanceReadModel $readModel): void
    {
        $readModel->refreshMetaFromProjection();
    }

    public function failed(\Throwable $exception): void
    {
        app(AdminAttendanceReadModel::class)->markFailed();
    }
}
