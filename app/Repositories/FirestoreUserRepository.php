<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Services\Firebase\FirebaseClientFactory;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Illuminate\Support\Arr;
use RuntimeException;

class FirestoreUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly FirebaseClientFactory $factory)
    {
    }

    public function create(array $data): array
    {
        $email = $this->normalizeEmail((string) $data['email']);
        $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number']);
        $now = now()->toISOString();

        $payload = array_merge($data, [
            'user_id' => $data['user_id'] ?? (string) str()->uuid(),
            'email' => $email,
            'identity_number' => $identityNumber,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);

        $client = $this->client();
        $userDocument = $this->userDocument($client, $payload['user_id']);
        $emailIndexDocument = $this->emailIndexDocument($client, $email);
        $identityIndexDocument = $this->identityIndexDocument($client, $identityNumber);

        $client->runTransaction(function (Transaction $transaction) use (
            $payload,
            $email,
            $identityNumber,
            $userDocument,
            $emailIndexDocument,
            $identityIndexDocument,
        ) {
            if ($transaction->snapshot($emailIndexDocument)->exists()) {
                throw new RegistrationConflictException('email', 'Email already registered.');
            }

            if ($transaction->snapshot($identityIndexDocument)->exists()) {
                throw new RegistrationConflictException('identity_number', 'NIK / Passport already registered.');
            }

            $transaction->create($userDocument, $payload);
            $transaction->create($emailIndexDocument, [
                'user_id' => $payload['user_id'],
                'normalized_email' => $email,
                'created_at' => $payload['created_at'],
            ]);
            $transaction->create($identityIndexDocument, [
                'user_id' => $payload['user_id'],
                'normalized_identity_number' => $identityNumber,
                'created_at' => $payload['created_at'],
            ]);
        });

        return $payload;
    }

    public function findByEmail(string $email): ?array
    {
        $indexSnapshot = $this->emailIndexDocument(
            $this->client(),
            $this->normalizeEmail($email),
        )->snapshot();

        if (! $indexSnapshot->exists()) {
            return null;
        }

        return $this->findById((string) $indexSnapshot['user_id']);
    }

    public function findByIdentityNumber(string $identityNumber): ?array
    {
        $indexSnapshot = $this->identityIndexDocument(
            $this->client(),
            $this->normalizeIdentityNumber($identityNumber),
        )->snapshot();

        if (! $indexSnapshot->exists()) {
            return null;
        }

        return $this->findById((string) $indexSnapshot['user_id']);
    }

    public function findById(string $id): ?array
    {
        $snapshot = $this->userDocument($this->client(), $id)->snapshot();

        return $snapshot->exists() ? $snapshot->data() : null;
    }

    public function update(string $id, array $data): array
    {
        $existing = $this->findById($id);

        if (! $existing) {
            throw new RuntimeException('User not found.');
        }

        $protectedFields = ['email', 'identity_number', 'user_id', 'created_at'];
        $changedProtectedFields = array_intersect(array_keys($data), $protectedFields);

        if ($changedProtectedFields !== []) {
            throw new RuntimeException('Protected user fields cannot be updated via this operation.');
        }

        $payload = array_merge($existing, Arr::except($data, ['user_id', 'created_at']));
        $payload['updated_at'] = now()->toISOString();

        $this->userDocument($this->client(), $id)->set($payload, ['merge' => true]);

        return $payload;
    }

    public function activateAndIssueTicket(string $id, array $ticketData): array
    {
        $client = $this->client();
        $userDocument = $this->userDocument($client, $id);

        return $client->runTransaction(function (Transaction $transaction) use ($client, $ticketData, $userDocument) {
            $userSnapshot = $transaction->snapshot($userDocument);

            if (! $userSnapshot->exists()) {
                throw new RuntimeException('User not found.');
            }

            $user = $userSnapshot->data();
            $existingTicketId = (string) ($user['ticket_id'] ?? '');
            $ticketWasCreated = false;

            $ticket = $existingTicketId !== ''
                ? $this->findExistingTicketInsideTransaction($transaction, $this->ticketDocument($client, $existingTicketId))
                : null;

            if (! $ticket) {
                $ticket = $this->buildTicketPayload($user, $ticketData, $existingTicketId);
                $transaction->create($this->ticketDocument($client, (string) $ticket['ticket_id']), $ticket);
                $ticketWasCreated = true;
            }

            $verifiedAt = (string) ($user['email_verified_at'] ?? $ticket['activated_at']);
            if ($verifiedAt === '') {
                $verifiedAt = now()->toISOString();
            }

            $userUpdate = [
                'account_status' => 'active',
                'verification_status' => 'verified',
                'email_verified_at' => $verifiedAt,
                'ticket_id' => $ticket['ticket_id'],
                'updated_at' => now()->toISOString(),
            ];

            $transaction->set($userDocument, $userUpdate, ['merge' => true]);

            return [
                'user' => array_merge($user, $userUpdate),
                'ticket' => $ticket,
                'ticket_was_created' => $ticketWasCreated,
            ];
        });
    }

    public function findTicketById(string $ticketId): ?array
    {
        $snapshot = $this->ticketDocument($this->client(), $ticketId)->snapshot();

        return $snapshot->exists() ? $snapshot->data() : null;
    }

    public function findTicketByUserId(string $userId): ?array
    {
        $user = $this->findById($userId);

        if (! $user || empty($user['ticket_id'])) {
            return null;
        }

        return $this->findTicketById((string) $user['ticket_id']);
    }

    private function client(): FirestoreClient
    {
        $client = $this->factory->make();

        if (! $client) {
            throw new RuntimeException('Firestore storage is unavailable.');
        }

        return $client;
    }

    private function userDocument(FirestoreClient $client, string $id): DocumentReference
    {
        return $client->collection((string) config('firebase.users_collection', 'users'))->document($id);
    }

    private function ticketDocument(FirestoreClient $client, string $id): DocumentReference
    {
        return $client->collection((string) config('firebase.tickets_collection', 'tickets'))->document($id);
    }

    private function emailIndexDocument(FirestoreClient $client, string $email): DocumentReference
    {
        return $client->collection((string) config('firebase.user_email_index_collection', 'user_email_index'))
            ->document(hash('sha256', $email));
    }

    private function identityIndexDocument(FirestoreClient $client, string $identityNumber): DocumentReference
    {
        return $client->collection((string) config('firebase.user_identity_index_collection', 'user_identity_index'))
            ->document(hash('sha256', $identityNumber));
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizeIdentityNumber(string $identityNumber): string
    {
        return strtoupper(trim($identityNumber));
    }

    private function findExistingTicketInsideTransaction(
        Transaction $transaction,
        DocumentReference $ticketDocument,
    ): ?array {
        $ticketSnapshot = $transaction->snapshot($ticketDocument);

        return $ticketSnapshot->exists() ? $ticketSnapshot->data() : null;
    }

    private function buildTicketPayload(array $user, array $ticketData, string $existingTicketId): array
    {
        $payload = $ticketData;
        $payload['ticket_id'] = $existingTicketId !== '' ? $existingTicketId : $ticketData['ticket_id'];
        $payload['user_id'] = $user['user_id'];
        $payload['created_at'] = $ticketData['created_at'] ?? now()->toISOString();
        $payload['updated_at'] = $ticketData['updated_at'] ?? now()->toISOString();

        return $payload;
    }
}
