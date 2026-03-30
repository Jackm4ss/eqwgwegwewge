<?php

namespace App\Http\Middleware;

use App\Contracts\UserRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

class EnsureEmailVerifiedForLogin
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $email = (string) $request->input('email', '');
        if ($email !== '') {
            $user = $this->users->findByEmail($email);
            if ($user && ($user['verification_status'] ?? 'unverified') !== 'verified') {
                return response()->json([
                    'message' => 'Akun Anda belum aktif. Silakan verifikasi email terlebih dahulu.',
                    'can_resend' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
