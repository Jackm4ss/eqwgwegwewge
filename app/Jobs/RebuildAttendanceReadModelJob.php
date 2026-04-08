<?php

namespace App\Jobs;

use App\Services\Admin\AdminAttendanceReadModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildAttendanceReadModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $trigger = 'reconcile',
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('admin-attendance-read-model:rebuild'))
                ->expireAfter(1800)
                ->dontRelease(),
        ];
    }

    public function handle(AdminAttendanceReadModel $readModel): void
    {
        $readModel->rebuild();
    }

    public function failed(\Throwable $exception): void
    {
        app(AdminAttendanceReadModel::class)->markFailed();
    }
}
