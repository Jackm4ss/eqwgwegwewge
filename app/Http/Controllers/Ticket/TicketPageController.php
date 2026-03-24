<?php

namespace App\Http\Controllers\Ticket;

use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\Tickets\TicketQrCodeService;

class TicketPageController extends Controller
{
    public function __invoke(
        string $ticketId,
        UserRepositoryInterface $users,
        TicketQrCodeService $ticketQrCodeService,
    ) {
        $ticket = $users->findTicketById($ticketId);
        abort_if(! $ticket, 404);

        $user = $users->findById((string) $ticket['user_id']);
        abort_if(! $user, 404);

        return view('tickets.show', [
            'ticket' => $ticket,
            'user' => $user,
            'qrSvg' => $ticketQrCodeService->renderSvg(
                $ticketQrCodeService->payloadForTicket($ticket),
                320,
            ),
        ]);
    }
}
