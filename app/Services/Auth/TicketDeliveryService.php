<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Mail;

class TicketDeliveryService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function sendIfNeeded(string $userId, array $user, array $ticket): array
    {
        $ticketReadyEmailSentAt = $user['ticket_ready_email_sent_at'] ?? null;

        if (! empty($ticketReadyEmailSentAt)) {
            return $user;
        }

        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);
        $qrPngBinary = $this->ticketQrCodeService->renderPngBinary(
            $this->ticketQrCodeService->payloadForTicket($ticket),
            240,
        );

        Mail::to($user['email'])->send(
            new TicketReadyMail($user, $ticket, $ticketUrl, $qrPngBinary)
        );

        return $this->users->update($userId, [
            'ticket_ready_email_sent_at' => now()->toISOString(),
        ]);
    }
}
