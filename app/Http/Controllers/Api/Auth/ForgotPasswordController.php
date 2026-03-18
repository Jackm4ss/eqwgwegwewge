<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Email not found.',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($request->email));

        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));

        session(['reset_email' => $request->email]);

        return redirect()->route('reset.verify');
    }

    public function resendResetLink(Request $request)
    {
        $email = session('reset_email');

        if (! $email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Reset email session not found. Please submit again.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Email not found.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($email));

        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));

        return back()->with('status', 'Reset link has been resent.');
    }
}