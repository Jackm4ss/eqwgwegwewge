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
        private readonly TicketDeliveryService $ticketDelivery,
        private readonly \App\Services\Tickets\TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function register(array $data, string $ip): array
    {
        try {
            $country = $this->normalizeCountry((string) $data['country']);
            $identityType = $this->normalizeIdentityType((string) $data['identity_type']);

            $this->ensureIdentityTypeAllowedForCountry($identityType, $country);

            $payload = [
                'full_name' => $this->normalizeName((string) $data['full_name']),
                'identity_type' => $identityType,
                'identity_number' => $this->normalizeIdentityNumber((string) $data['identity_number']),
                'email' => $this->normalizeEmail((string) $data['email']),
                'phone_number' => $this->normalizePhoneNumber((string) $data['phone_number']),
                'country' => $country,
                'identity_country' => $country,
                'account_status' => 'active',
                'verification_status' => 'verified',
                'email_verified_at' => now()->toISOString(),
                'ticket_id' => null,
                'ticket_ready_email_sent_at' => null,
                'agreed_terms_at' => now()->toISOString(),
                'registered_ip' => $ip,
            ];

            $phoneCountryCode = $this->normalizePhoneCountryCode((string) ($data['phone_country_code'] ?? ''));
            if ($phoneCountryCode !== '') {
                $payload['phone_country_code'] = $phoneCountryCode;
            }

            $phoneNationalNumber = $this->normalizePhoneNationalNumber((string) ($data['phone_national_number'] ?? ''));
            if ($phoneNationalNumber !== '') {
                $payload['phone_national_number'] = $phoneNationalNumber;
            }

            $user = $this->users->create($payload);
        } catch (RegistrationConflictException $exception) {
            throw ValidationException::withMessages([
                $exception->field => [$exception->getMessage()],
            ]);
        }

        $result = $this->users->activateAndIssueTicket(
            (string) $user['user_id'],
            $this->ticketQrCodeService->makeTicketAttributes((string) $user['user_id']),
        );
        $delivery = $this->ticketDelivery->sendIfNeeded(
            (string) $user['user_id'],
            $result['user'],
            $result['ticket'],
        );
        $result['user'] = $delivery['user'];
        $result['delivery'] = $delivery['delivery'];

        return $result;
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

    private function normalizeIdentityType(string $identityType): string
    {
        return strtolower(trim($identityType));
    }

    private function normalizeCountry(string $country): string
    {
        return strtoupper(trim($country));
    }

    private function normalizePhoneCountryCode(string $phoneCountryCode): string
    {
        $digits = preg_replace('/\D+/', '', $phoneCountryCode) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }

    private function normalizePhoneNationalNumber(string $phoneNationalNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNationalNumber) ?? '';

        return ltrim($digits, '0');
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    private function ensureIdentityTypeAllowedForCountry(string $identityType, string $country): void
    {
        if ($country !== 'MY' && $identityType !== 'passport') {
            throw ValidationException::withMessages([
                'identity_type' => ['Untuk pendaftar luar Malaysia, gunakan Passport sebagai identitas utama.'],
            ]);
        }
    }
}
