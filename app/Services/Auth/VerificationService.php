<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Mail;

class VerificationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function verify(string $id, string $hash): ?array
    {
        $user = $this->users->findById($id);

        if (! $user) {
            return null;
        }

        if (! hash_equals(sha1($user['email']), $hash)) {
            return null;
        }

        $result = $this->users->activateAndIssueTicket(
            $id,
            $this->ticketQrCodeService->makeTicketAttributes($id),
        );

        $ticket = $result['ticket'];
        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);
        $qrPngBinary = $this->ticketQrCodeService->renderPngBinary(
            $this->ticketQrCodeService->payloadForTicket($ticket),
        );

        Mail::to($result['user']['email'])->send(
            new TicketReadyMail($result['user'], $ticket, $ticketUrl, $qrPngBinary)
        );

        return $result;
    }
}
