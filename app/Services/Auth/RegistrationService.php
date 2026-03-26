<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
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
                'account_status' => 'pending_verification',
                'verification_status' => 'unverified',
                'email_verified_at' => null,
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

        $this->sendTicketEmail((string) $user['user_id'], $result);

        return $result;
    }

    public function resendVerification(string $email): void
    {
        $user = $this->users->findByEmail($this->normalizeEmail($email));

        if (! $user) {
            return;
        }

        $result = $this->users->activateAndIssueTicket(
            (string) $user['user_id'],
            $this->ticketQrCodeService->makeTicketAttributes((string) $user['user_id']),
        );

        $this->sendTicketEmail((string) $user['user_id'], $result, true);
    }

    private function sendTicketEmail(string $userId, array &$result, bool $force = false): void
    {
        $ticketReadyEmailSentAt = $result['user']['ticket_ready_email_sent_at'] ?? null;

        if (! $force && ! empty($ticketReadyEmailSentAt)) {
            return;
        }

        $ticket = $result['ticket'];
        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);
        $qrPngBinary = $this->ticketQrCodeService->renderPngBinary(
            $this->ticketQrCodeService->payloadForTicket($ticket),
            240,
        );

        Mail::to($result['user']['email'])->send(
            new TicketReadyMail($result['user'], $ticket, $ticketUrl, $qrPngBinary)
        );

        $result['user'] = $this->users->update($userId, [
            'ticket_ready_email_sent_at' => now()->toISOString(),
        ]);
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
