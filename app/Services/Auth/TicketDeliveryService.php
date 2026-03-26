<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Log;
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
        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);

        if (! empty($ticketReadyEmailSentAt)) {
            return [
                'user' => $user,
                'delivery' => [
                    'status' => 'already_sent',
                    'email_sent' => true,
                    'ticket_url' => $ticketUrl,
                ],
            ];
        }

        $qrPngBinary = $this->ticketQrCodeService->renderPngBinary(
            $this->ticketQrCodeService->payloadForTicket($ticket),
            240,
        );

        try {
            Mail::to($user['email'])->send(
                new TicketReadyMail($user, $ticket, $ticketUrl, $qrPngBinary)
            );
        } catch (\Throwable $throwable) {
            Log::warning('Ticket ready email delivery failed', [
                'user_id' => $userId,
                'email' => $user['email'] ?? null,
                'ticket_id' => $ticket['ticket_id'] ?? null,
                'error' => $throwable->getMessage(),
            ]);

            return [
                'user' => $user,
                'delivery' => [
                    'status' => 'failed',
                    'email_sent' => false,
                    'ticket_url' => $ticketUrl,
                ],
            ];
        }

        $updatedUser = $this->users->update($userId, [
            'ticket_ready_email_sent_at' => now()->toISOString(),
        ]);

        return [
            'user' => $updatedUser,
            'delivery' => [
                'status' => 'sent',
                'email_sent' => true,
                'ticket_url' => $ticketUrl,
            ],
        ];
    }
}
