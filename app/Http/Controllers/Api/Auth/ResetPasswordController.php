<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function showResetForm(string $token, Request $request)
    {
        $email = $request->query('email');

        abort_if(! $email, 404);

        return view('reset.reset-password-form', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($validated['email']));
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetRecord || ! $this->isValidResetToken($resetRecord, $validated['token'])) {
            if ($resetRecord && $this->tokenExpired($resetRecord)) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();
            }

            return back()->withErrors([
                'email' => 'Invalid or expired reset link.',
            ])->withInput(['email' => $email]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'User not found.',
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'New password cannot be the same as your current password.',
            ])->withInput(['email' => $email]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        return redirect()->route('reset.sukses');
    }

    private function isValidResetToken(object $resetRecord, string $plainToken): bool
    {
        if ($this->tokenExpired($resetRecord)) {
            return false;
        }

        return ! blank($resetRecord->token) && Hash::check($plainToken, $resetRecord->token);
    }

    private function tokenExpired(object $resetRecord): bool
    {
        if (blank($resetRecord->created_at)) {
            return true;
        }

        $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes((int) config('auth.passwords.users.expire', 60));

        return now()->greaterThan($expiresAt);
    }
}
