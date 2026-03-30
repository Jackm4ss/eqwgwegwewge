<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Services\Tickets\TicketQrCodeService;

class ForgotQrLookupService
{
    private const PHONE_DIAL_CODES = [
        'AU' => '+61',
        'BN' => '+673',
        'KH' => '+855',
        'CN' => '+86',
        'FR' => '+33',
        'DE' => '+49',
        'HK' => '+852',
        'IN' => '+91',
        'ID' => '+62',
        'JP' => '+81',
        'LA' => '+856',
        'MY' => '+60',
        'MM' => '+95',
        'NL' => '+31',
        'NZ' => '+64',
        'PH' => '+63',
        'SG' => '+65',
        'KR' => '+82',
        'TH' => '+66',
        'AE' => '+971',
        'GB' => '+44',
        'US' => '+1',
        'VN' => '+84',
    ];

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function lookup(array $data): array
    {
        $user = match ((string) $data['search_type']) {
            'email' => $this->users->findByEmail((string) $data['email']),
            'phone' => $this->users->findByPhoneNumber((string) $data['phone_number']),
            'passport', 'ic' => $this->users->findByIdentityDocument(
                (string) $data['identity_type'],
                (string) $data['country'],
                (string) $data['identity_number'],
            ),
            default => null,
        };

        if (! $user) {
            return $this->notFoundResponse();
        }

        $ticket = $this->users->findTicketByUserId((string) $user['user_id']);

        if (! $ticket || empty($ticket['ticket_id'])) {
            return $this->notFoundResponse();
        }

        $phoneParts = $this->resolvePhoneParts($user);

        return [
            'found' => true,
            'participant' => [
                'full_name' => (string) ($user['full_name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'phone_country_code' => $phoneParts['phone_country_code'],
                'phone_national_number' => $phoneParts['phone_national_number'],
                'phone_number' => $phoneParts['phone_number'],
                'country' => strtoupper((string) ($user['country'] ?? '')),
                'identity_type' => strtolower((string) ($user['identity_type'] ?? '')),
                'identity_number' => (string) ($user['identity_number'] ?? ''),
                'account_status' => (string) ($user['account_status'] ?? ''),
                'verification_status' => (string) ($user['verification_status'] ?? ''),
                'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
                'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
            ],
            'ticket_url' => $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']),
            'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
            'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
        ];
    }

    private function notFoundResponse(): array
    {
        return [
            'found' => false,
            'message' => 'Data peserta tidak ditemukan.',
        ];
    }

    private function resolvePhoneParts(array $user): array
    {
        $phoneCountryCode = trim((string) ($user['phone_country_code'] ?? ''));
        $phoneNationalNumber = trim((string) ($user['phone_national_number'] ?? ''));
        $phoneNumber = $this->normalizePhoneNumber((string) ($user['phone_number'] ?? ''));
        $country = strtoupper((string) ($user['country'] ?? ''));

        if ($phoneNumber === '' && $phoneCountryCode !== '' && $phoneNationalNumber !== '') {
            $phoneNumber = $phoneCountryCode.$phoneNationalNumber;
        }

        if ($phoneCountryCode === '' && $country !== '') {
            $dialCode = self::PHONE_DIAL_CODES[$country] ?? '';

            if ($dialCode !== '' && str_starts_with($phoneNumber, $dialCode)) {
                $phoneCountryCode = $dialCode;
            }
        }

        if ($phoneNationalNumber === '' && $phoneNumber !== '') {
            if ($phoneCountryCode !== '' && str_starts_with($phoneNumber, $phoneCountryCode)) {
                $phoneNationalNumber = ltrim(substr($phoneNumber, strlen($phoneCountryCode)), '0');
            } else {
                $phoneNationalNumber = ltrim(ltrim($phoneNumber, '+'), '0');
            }
        }

        return [
            'phone_country_code' => $phoneCountryCode,
            'phone_national_number' => $phoneNationalNumber,
            'phone_number' => $phoneNumber,
        ];
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }
}
