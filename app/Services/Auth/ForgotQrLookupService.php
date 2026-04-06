<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Support\CountryCatalog;
use App\Services\Tickets\TicketQrCodeService;

class ForgotQrLookupService
{
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

        $ticket = $this->resolveTicket($user);

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
            foreach (CountryCatalog::dialCodesFor($country) as $dialCode) {
                if (str_starts_with($phoneNumber, (string) $dialCode)) {
                    $phoneCountryCode = (string) $dialCode;
                    break;
                }
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

    private function resolveTicket(array $user): ?array
    {
        $ticketId = trim((string) ($user['ticket_id'] ?? ''));

        if ($ticketId !== '') {
            $ticket = $this->users->findTicketById($ticketId);

            if ($ticket !== null) {
                return $ticket;
            }
        }

        $userId = trim((string) ($user['user_id'] ?? ''));

        return $userId !== ''
            ? $this->users->findTicketByUserId($userId)
            : null;
    }
}
