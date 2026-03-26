<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Services\Tickets\TicketQrCodeService;

class VerificationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
        private readonly TicketDeliveryService $ticketDelivery,
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

        $result['user'] = $this->ticketDelivery->sendIfNeeded($id, $result['user'], $result['ticket']);

        return $result;
    }
}
