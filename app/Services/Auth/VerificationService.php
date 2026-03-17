<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;

class VerificationService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
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

        if (($user['verification_status'] ?? null) !== 'verified') {
            $user = $this->users->update($id, [
                'verification_status' => 'verified',
                'email_verified_at' => now()->toISOString(),
                'account_status' => 'active',
            ]);
        }

        return $user;
    }
}
