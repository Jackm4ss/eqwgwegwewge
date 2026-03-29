<?php

namespace Tests\Fakes;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use Illuminate\Support\Arr;
use RuntimeException;

class InMemoryUserRepository implements UserRepositoryInterface
{
    public array $users = [];

    public array $tickets = [];

    public array $emailIndexes = [];

    public array $identityIndexes = [];

    public function create(array $data): array
    {
        $email = $this->normalizeEmail((string) $data['email']);
        $identityType = $this->normalizeIdentityType((string) $data['identity_type']);
        $identityCountry = $this->normalizeCountry((string) ($data['identity_country'] ?? $data['country'] ?? ''));
        $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number']);
        $emailHash = hash('sha256', $email);
        $identityHash = hash('sha256', $this->identityLookupKey($identityType, $identityCountry, $identityNumber));

        if (isset($this->emailIndexes[$emailHash])) {
            throw new RegistrationConflictException('email', 'Email already registered.');
        }

        if (isset($this->identityIndexes[$identityHash])) {
            throw new RegistrationConflictException('identity_number', 'This identity document is already registered.');
        }

        $payload = array_merge($data, [
            'user_id' => $data['user_id'] ?? 'user-'.(count($this->users) + 1),
            'email' => $email,
            'identity_type' => $identityType,
            'identity_country' => $identityCountry,
            'identity_number' => $identityNumber,
            'created_at' => $data['created_at'] ?? now()->toISOString(),
            'updated_at' => $data['updated_at'] ?? now()->toISOString(),
        ]);

        $this->users[$payload['user_id']] = $payload;
        $this->emailIndexes[$emailHash] = [
            'user_id' => $payload['user_id'],
            'normalized_email' => $email,
        ];
        $this->identityIndexes[$identityHash] = [
            'user_id' => $payload['user_id'],
            'identity_type' => $identityType,
            'identity_country' => $identityCountry,
            'normalized_identity_number' => $identityNumber,
        ];

        return $payload;
    }

    public function findByEmail(string $email): ?array
    {
        $userId = $this->emailIndexes[hash('sha256', $this->normalizeEmail($email))]['user_id'] ?? null;

        return $userId ? $this->findById($userId) : null;
    }

    public function findByPhoneNumber(string $phoneNumber): ?array
    {
        $normalizedPhoneNumber = $this->normalizePhoneNumber($phoneNumber);

        foreach ($this->users as $user) {
            if ($this->normalizePhoneNumber((string) ($user['phone_number'] ?? '')) !== $normalizedPhoneNumber) {
                continue;
            }

            return $user;
        }

        return null;
    }

    public function findById(string $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    public function findByIdentityDocument(string $identityType, string $identityCountry, string $identityNumber): ?array
    {
        $userId = $this->identityIndexes[
            hash('sha256', $this->identityLookupKey($identityType, $identityCountry, $identityNumber))
        ]['user_id'] ?? null;

        return $userId ? $this->findById($userId) : null;
    }

    public function update(string $id, array $data): array
    {
        $existing = $this->findById($id);

        if (! $existing) {
            throw new RuntimeException('User not found.');
        }

        $payload = array_merge($existing, Arr::except($data, ['user_id', 'created_at']));
        $payload['updated_at'] = now()->toISOString();
        $this->users[$id] = $payload;

        return $payload;
    }

    public function activateAndIssueTicket(string $id, array $ticketData): array
    {
        $user = $this->findById($id);

        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        $existingTicketId = (string) ($user['ticket_id'] ?? '');
        $ticketWasCreated = false;

        if ($existingTicketId !== '' && isset($this->tickets[$existingTicketId])) {
            $ticket = $this->synchronizeExistingTicketPayload(
                $this->tickets[$existingTicketId],
                $ticketData,
                $user,
                $existingTicketId,
            );
            $this->tickets[$ticket['ticket_id']] = $ticket;
        } else {
            $ticket = array_merge($ticketData, [
                'ticket_id' => $existingTicketId !== '' ? $existingTicketId : $ticketData['ticket_id'],
                'user_id' => $id,
            ]);
            $this->tickets[$ticket['ticket_id']] = $ticket;
            $ticketWasCreated = true;
        }

        $userUpdate = [
            'account_status' => 'active',
            'verification_status' => 'verified',
            'email_verified_at' => $user['email_verified_at'] ?? $ticket['activated_at'],
            'ticket_id' => $ticket['ticket_id'],
            'updated_at' => now()->toISOString(),
        ];

        $this->users[$id] = array_merge($user, $userUpdate);

        return [
            'user' => $this->users[$id],
            'ticket' => $ticket,
            'ticket_was_created' => $ticketWasCreated,
        ];
    }

    public function findTicketById(string $ticketId): ?array
    {
        return $this->tickets[$ticketId] ?? null;
    }

    public function findTicketByUserId(string $userId): ?array
    {
        $user = $this->findById($userId);

        if (! $user || empty($user['ticket_id'])) {
            return null;
        }

        return $this->findTicketById((string) $user['ticket_id']);
    }

    public function emailIndexExists(string $email): bool
    {
        return isset($this->emailIndexes[hash('sha256', $this->normalizeEmail($email))]);
    }

    public function identityIndexExists(string $identityType, string $identityCountry, string $identityNumber): bool
    {
        return isset($this->identityIndexes[
            hash('sha256', $this->identityLookupKey($identityType, $identityCountry, $identityNumber))
        ]);
    }

    public function firstUser(): ?array
    {
        return array_values($this->users)[0] ?? null;
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

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }

    private function identityLookupKey(string $identityType, string $identityCountry, string $identityNumber): string
    {
        return implode(':', [
            $this->normalizeIdentityType($identityType),
            $this->normalizeCountry($identityCountry),
            $this->normalizeIdentityNumber($identityNumber),
        ]);
    }

    private function synchronizeExistingTicketPayload(
        array $ticket,
        array $ticketData,
        array $user,
        string $existingTicketId,
    ): array {
        $ticketCode = strtoupper(trim((string) ($ticket['ticket_code'] ?? '')));
        if ($ticketCode === '') {
            $ticketCode = strtoupper(trim((string) ($ticketData['ticket_code'] ?? '')));
        }

        $entryCode = strtoupper(trim((string) ($ticket['entry_code'] ?? '')));
        if ($entryCode === '') {
            $entryCode = strtoupper(trim((string) ($ticketData['entry_code'] ?? '')));
        }

        $entryCodeDisplay = trim((string) ($ticket['entry_code_display'] ?? ''));
        if ($entryCodeDisplay === '' && $entryCode !== '') {
            $entryCodeDisplay = $this->formatEntryCodeDisplay($entryCode);
        }

        $attendanceStatus = strtolower(trim((string) ($ticket['attendance_status'] ?? '')));
        if ($attendanceStatus === '') {
            $attendanceStatus = ! empty($ticket['checked_in_at']) || ! empty($ticket['last_scanned_at'])
                ? 'checked_in'
                : 'not_checked_in';
        }

        return array_merge($ticket, [
            'ticket_id' => $existingTicketId !== '' ? $existingTicketId : (string) ($ticket['ticket_id'] ?? $ticketData['ticket_id'] ?? ''),
            'user_id' => (string) ($ticket['user_id'] ?? $user['user_id'] ?? ''),
            'event_code' => (string) ($ticket['event_code'] ?? $ticketData['event_code'] ?? config('event.code', 'SONGKRAN2026')),
            'ticket_code' => $ticketCode,
            'entry_code' => $entryCode,
            'entry_code_display' => $entryCodeDisplay,
            'status' => (string) ($ticket['status'] ?? $ticketData['status'] ?? 'active'),
            'qr_version' => (string) ($ticket['qr_version'] ?? $ticketData['qr_version'] ?? 'v1'),
            'activated_at' => (string) ($ticket['activated_at'] ?? $ticketData['activated_at'] ?? $user['email_verified_at'] ?? now()->toISOString()),
            'created_at' => $ticket['created_at'] ?? $ticketData['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'attendance_status' => $attendanceStatus,
            'checked_in_at' => $ticket['checked_in_at'] ?? null,
            'last_scanned_at' => $ticket['last_scanned_at'] ?? null,
        ]);
    }

    private function formatEntryCodeDisplay(string $entryCode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entryCode) ?? '');

        if ($normalized === '') {
            return '';
        }

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }
}
