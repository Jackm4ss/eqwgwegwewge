<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailMasker;
use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash, VerificationService $service)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired verification link.');
        }

        $user = $service->verify($id, $hash);
        abort_if(! $user, 404);

        session(['verified_email' => $user['email']]);

        return redirect()->route('email.verified');
    }

    public function success()
    {
        $email = session('verified_email');

        return view('auth.email-verified', [
            'maskedEmail' => $email ? EmailMasker::mask($email) : null,
        ]);
    }
}
