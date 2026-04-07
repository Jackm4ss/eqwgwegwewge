<?php

namespace App\Jobs;

use App\Services\Admin\AdminUserManagementReadModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildUserManagementReadModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $trigger = 'reconcile',
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('admin-user-management-read-model:rebuild'))
                ->expireAfter(1800)
                ->dontRelease(),
        ];
    }

    public function handle(AdminUserManagementReadModel $readModel): void
    {
        $readModel->rebuild();
    }

    public function failed(\Throwable $exception): void
    {
        app(AdminUserManagementReadModel::class)->markFailed();
    }
}
