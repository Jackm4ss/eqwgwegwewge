<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        session(['reset_email' => $email]);

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->dispatchResetLink($user, $email);
        }

        return redirect()->route('reset.verify');
    }

    public function resendResetLink(Request $request): RedirectResponse|JsonResponse
    {
        $email = session('reset_email');

        if (! $email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Reset email session not found. Please submit again.']);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->dispatchResetLink($user, $email);
        }

        $message = 'If the account exists, a reset link has been sent.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    private function dispatchResetLink(User $user, string $email): void
    {
        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $plainToken,
            'email' => $email,
        ]);

        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
    }
}
