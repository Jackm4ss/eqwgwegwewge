<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationService;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        string $id,
        string $hash,
        VerificationService $service,
        TicketQrCodeService $ticketQrCodeService,
    ) {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired verification link.');
        }

        $result = $service->verify($id, $hash);
        abort_if(! $result, 404);

        return redirect()->to(
            $ticketQrCodeService->signedTicketUrl((string) $result['ticket']['ticket_id'])
        );
    }

    public function success()
    {
        return view('auth.email-verified', [
            'maskedEmail' => null,
        ]);
    }
}
