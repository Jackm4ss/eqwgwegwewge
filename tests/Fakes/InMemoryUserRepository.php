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
        $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number']);
        $emailHash = hash('sha256', $email);
        $identityHash = hash('sha256', $identityNumber);

        if (isset($this->emailIndexes[$emailHash])) {
            throw new RegistrationConflictException('email', 'Email already registered.');
        }

        if (isset($this->identityIndexes[$identityHash])) {
            throw new RegistrationConflictException('identity_number', 'NIK / Passport already registered.');
        }

        $payload = array_merge($data, [
            'user_id' => $data['user_id'] ?? 'user-'.(count($this->users) + 1),
            'email' => $email,
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
            'normalized_identity_number' => $identityNumber,
        ];

        return $payload;
    }

    public function findByEmail(string $email): ?array
    {
        $userId = $this->emailIndexes[hash('sha256', $this->normalizeEmail($email))]['user_id'] ?? null;

        return $userId ? $this->findById($userId) : null;
    }

    public function findById(string $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    public function findByIdentityNumber(string $identityNumber): ?array
    {
        $userId = $this->identityIndexes[hash('sha256', $this->normalizeIdentityNumber($identityNumber))]['user_id'] ?? null;

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
            $ticket = $this->tickets[$existingTicketId];
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

    public function identityIndexExists(string $identityNumber): bool
    {
        return isset($this->identityIndexes[hash('sha256', $this->normalizeIdentityNumber($identityNumber))]);
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
}
