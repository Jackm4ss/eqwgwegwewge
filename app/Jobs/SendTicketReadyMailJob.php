<?php

namespace App\Jobs;

use App\Services\Auth\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketReadyMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $userId,
    ) {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(TicketDeliveryService $ticketDelivery): void
    {
        $ticketDelivery->deliverQueuedTicketReadyEmail($this->userId);
    }
}
