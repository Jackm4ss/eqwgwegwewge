<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Mail\VerifyRegistrationMail;
use App\Services\Security\RecaptchaService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RecaptchaService $recaptcha,
    ) {
    }

    public function register(array $data, string $ip): array
    {
        if ($this->users->findByEmail($data['email'])) {
            throw new \InvalidArgumentException('Email already registered.');
        }

        if ($this->users->findByIdentityNumber($data['identity_number'])) {
            throw new \InvalidArgumentException('NIK / Passport already registered.');
        }

        $user = $this->users->create([
            'full_name' => trim($data['full_name']),
            'identity_number' => trim($data['identity_number']),
            'email' => strtolower(trim($data['email'])),
            'phone_number' => trim($data['phone_number']),
            'country' => trim($data['country']),
            'password_hash' => bcrypt(\Illuminate\Support\Str::random(32)),
            'account_status' => 'pending_verification',
            'verification_status' => 'unverified',
            'email_verified_at' => null,
            'captcha_passed' => true,
            'registered_from_subdomain' => env('REGISTER_SUBDOMAIN', 'register.songkremfestival.my'),
        ]);

        $this->sendVerification($user);

        return $user;
    }

    public function resendVerification(string $email): void
    {
        $user = $this->users->findByEmail(strtolower($email));
        if (! $user || $user['verification_status'] === 'verified') {
            return;
        }

        $this->sendVerification($user);
    }

    private function sendVerification(array $user): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user['user_id'],
                'hash' => sha1($user['email']),
            ],
        );

        Mail::to($user['email'])->send(new VerifyRegistrationMail($user, $verificationUrl));
    }
}
