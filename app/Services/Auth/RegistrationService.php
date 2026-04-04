<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Services\Admin\AdminPanelService;
use App\Mail\VerifyRegistrationMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketDeliveryService $ticketDelivery,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {}

    public function register(array $data, string $ip): array
    {
        try {
            $country = $this->normalizeCountry((string) $data['country']);
            $identityType = $this->normalizeIdentityType((string) $data['identity_type']);
            $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number'], $identityType);
            $phoneNumber = $this->normalizePhoneNumber((string) $data['phone_number']);

            $this->ensureIdentityTypeAllowedForCountry($identityType, $country);
            $this->ensureIdentityNumberAllowedForCountry($identityNumber, $identityType, $country);
            $this->ensurePhoneNumberIsAvailable($phoneNumber);

            $payload = [
                'full_name' => $this->normalizeName((string) $data['full_name']),
                'identity_type' => $identityType,
                'identity_number' => $identityNumber,
                'email' => $this->normalizeEmail((string) $data['email']),
                'phone_number' => $phoneNumber,
                'country' => $country,
                'identity_country' => $country,
                'account_status' => 'active',
                'verification_status' => 'verified',
                'email_verified_at' => now()->toISOString(),
                'ticket_id' => null,
                'ticket_ready_email_queued_at' => null,
                'ticket_ready_email_sent_at' => null,
                'ticket_ready_email_failed_at' => null,
                'ticket_ready_email_last_error' => null,
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

            $this->appendTrafficAttribution($payload, $data);

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
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY, true);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY, true);

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

    private function normalizeIdentityNumber(string $identityNumber, string $identityType): string
    {
        if ($identityType === 'national_id') {
            return preg_replace('/\D+/', '', $identityNumber) ?? '';
        }

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

    private function normalizeOptionalValue(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function normalizeTrafficToken(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($normalized, '-');
    }

    private function appendTrafficAttribution(array &$payload, array $data): void
    {
        $trafficSource = $this->normalizeTrafficToken($data['traffic_source'] ?? '');
        if ($trafficSource !== '') {
            $payload['traffic_source'] = $trafficSource;
        }

        $trafficSourceDetail = $this->normalizeOptionalValue($data['traffic_source_detail'] ?? '');
        if ($trafficSourceDetail !== '') {
            $payload['traffic_source_detail'] = $trafficSourceDetail;
        }

        $trafficMedium = $this->normalizeTrafficToken($data['traffic_medium'] ?? '');
        if ($trafficMedium !== '') {
            $payload['traffic_medium'] = $trafficMedium;
        }

        $trafficCampaign = $this->normalizeOptionalValue($data['traffic_campaign'] ?? '');
        if ($trafficCampaign !== '') {
            $payload['traffic_campaign'] = $trafficCampaign;
        }

        $trafficReferrerHost = strtolower($this->normalizeOptionalValue($data['traffic_referrer_host'] ?? ''));
        if ($trafficReferrerHost !== '') {
            $payload['traffic_referrer_host'] = $trafficReferrerHost;
        }

        $trafficLandingPath = $this->normalizeOptionalValue($data['traffic_landing_path'] ?? '');
        if ($trafficLandingPath !== '') {
            $payload['traffic_landing_path'] = $trafficLandingPath;
        }

        $trafficCapturedAt = $this->normalizeOptionalValue($data['traffic_captured_at'] ?? '');
        if ($trafficCapturedAt !== '') {
            $payload['traffic_captured_at'] = $trafficCapturedAt;
        }
    }

    private function ensureIdentityTypeAllowedForCountry(string $identityType, string $country): void
    {
        if ($country === 'MY' && $identityType !== 'national_id') {
            throw ValidationException::withMessages([
                'identity_type' => ['Untuk pendaftar dari Malaysia, gunakan Malaysia IC (MyKad) sebagai identitas utama.'],
            ]);
        }

        if ($country !== 'MY' && $identityType !== 'passport') {
            throw ValidationException::withMessages([
                'identity_type' => ['Untuk pendaftar luar Malaysia, gunakan Passport sebagai identitas utama.'],
            ]);
        }
    }

    private function ensureIdentityNumberAllowedForCountry(string $identityNumber, string $identityType, string $country): void
    {
        if ($country === 'MY' && $identityType === 'national_id' && ! preg_match('/^\d{12}$/', $identityNumber)) {
            throw ValidationException::withMessages([
                'identity_number' => ['Malaysia IC (MyKad) harus tepat 12 digit.'],
            ]);
        }
    }

    private function ensurePhoneNumberIsAvailable(string $phoneNumber): void
    {
        if ($phoneNumber === '') {
            return;
        }

        if ($this->users->findByPhoneNumber($phoneNumber) !== null) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number already registered.'],
            ]);
        }
    }
}
