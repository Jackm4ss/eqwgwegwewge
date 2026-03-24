<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Mail\VerifyRegistrationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function register(array $data, string $ip): array
    {
        try {
            $user = $this->users->create([
                'full_name' => $this->normalizeName((string) $data['full_name']),
                'identity_number' => $this->normalizeIdentityNumber((string) $data['identity_number']),
                'email' => $this->normalizeEmail((string) $data['email']),
                'phone_number' => trim((string) $data['phone_number']),
                'country' => trim((string) $data['country']),
                'account_status' => 'pending_verification',
                'verification_status' => 'unverified',
                'email_verified_at' => null,
                'ticket_id' => null,
                'ticket_ready_email_sent_at' => null,
                'agreed_terms_at' => now()->toISOString(),
                'registered_ip' => $ip,
            ]);
        } catch (RegistrationConflictException $exception) {
            throw ValidationException::withMessages([
                $exception->field => [$exception->getMessage()],
            ]);
        }

        $this->sendVerification($user);

        return $user;
    }

    public function resendVerification(string $email): void
    {
        $user = $this->users->findByEmail($this->normalizeEmail($email));
        if (! $user || $user['verification_status'] === 'verified') {
            return;
        }

        $this->sendVerification($user);
    }

    private function sendVerification(array $user): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addDay(),
            [
                'id' => $user['user_id'],
                'hash' => sha1($user['email']),
            ],
        );

        Mail::to($user['email'])->send(new VerifyRegistrationMail($user, $verificationUrl));
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizeIdentityNumber(string $identityNumber): string
    {
        return strtoupper(trim($identityNumber));
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
