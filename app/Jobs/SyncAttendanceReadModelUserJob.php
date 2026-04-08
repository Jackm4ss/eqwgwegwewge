<?php

namespace App\Jobs;

use App\Services\Admin\AdminAttendanceReadModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAttendanceReadModelUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $userId,
        public readonly string $trigger = 'mutation',
    ) {}

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('admin-attendance-read-model:user:'.$this->userId))
                ->expireAfter(180)
                ->dontRelease(),
        ];
    }

    public function handle(AdminAttendanceReadModel $readModel): void
    {
        $readModel->syncUser($this->userId);
    }
}
