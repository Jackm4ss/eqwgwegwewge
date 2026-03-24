<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Illuminate\Support\Arr;
use RuntimeException;

class FirestoreUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly FirebaseClientFactory $factory,
        private readonly FirestoreTimestampNormalizer $timestamps,
    )
    {
    }

    public function create(array $data): array
    {
        $email = $this->normalizeEmail((string) $data['email']);
        $identityType = $this->normalizeIdentityType((string) $data['identity_type']);
        $identityCountry = $this->normalizeCountry((string) ($data['identity_country'] ?? $data['country'] ?? ''));
        $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number']);
        $now = now()->toISOString();

        $payload = array_merge($data, [
            'user_id' => $data['user_id'] ?? (string) str()->uuid(),
            'email' => $email,
            'identity_type' => $identityType,
            'identity_country' => $identityCountry,
            'identity_number' => $identityNumber,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);
        $storagePayload = $this->timestamps->prepareForStorage($payload);

        $client = $this->client();
        $userDocument = $this->userDocument($client, $payload['user_id']);
        $emailIndexDocument = $this->emailIndexDocument($client, $email);
        $identityIndexDocument = $this->identityIndexDocument($client, $identityType, $identityCountry, $identityNumber);

        $client->runTransaction(function (Transaction $transaction) use (
            $payload,
            $storagePayload,
            $email,
            $identityType,
            $identityCountry,
            $identityNumber,
            $userDocument,
            $emailIndexDocument,
            $identityIndexDocument,
        ) {
            if ($transaction->snapshot($emailIndexDocument)->exists()) {
                throw new RegistrationConflictException('email', 'Email already registered.');
            }

            if ($transaction->snapshot($identityIndexDocument)->exists()) {
                throw new RegistrationConflictException('identity_number', 'This identity document is already registered.');
            }

            $transaction->create($userDocument, $storagePayload);
            $transaction->create($emailIndexDocument, [
                'user_id' => $payload['user_id'],
                'normalized_email' => $email,
                'created_at' => $storagePayload['created_at'],
            ]);
            $transaction->create($identityIndexDocument, [
                'user_id' => $payload['user_id'],
                'identity_type' => $identityType,
                'identity_country' => $identityCountry,
                'normalized_identity_number' => $identityNumber,
                'normalized_identity_key' => $this->identityLookupKey($identityType, $identityCountry, $identityNumber),
                'created_at' => $storagePayload['created_at'],
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

    public function findByIdentityDocument(string $identityType, string $identityCountry, string $identityNumber): ?array
    {
        $indexSnapshot = $this->identityIndexDocument(
            $this->client(),
            $this->normalizeIdentityType($identityType),
            $this->normalizeCountry($identityCountry),
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

        return $snapshot->exists()
            ? $this->timestamps->normalizeFromStorage($snapshot->data())
            : null;
    }

    public function update(string $id, array $data): array
    {
        $existing = $this->findById($id);

        if (! $existing) {
            throw new RuntimeException('User not found.');
        }

        $protectedFields = ['email', 'identity_type', 'identity_country', 'identity_number', 'user_id', 'created_at'];
        $changedProtectedFields = array_intersect(array_keys($data), $protectedFields);

        if ($changedProtectedFields !== []) {
            throw new RuntimeException('Protected user fields cannot be updated via this operation.');
        }

        $payload = array_merge($existing, Arr::except($data, ['user_id', 'created_at']));
        $payload['updated_at'] = now()->toISOString();
        $storagePayload = $this->timestamps->prepareForStorage($payload);

        $this->userDocument($this->client(), $id)->set($storagePayload, ['merge' => true]);

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

            $user = $this->timestamps->normalizeFromStorage($userSnapshot->data());
            $existingTicketId = (string) ($user['ticket_id'] ?? '');
            $ticketWasCreated = false;

            $ticket = $existingTicketId !== ''
                ? $this->findExistingTicketInsideTransaction($transaction, $this->ticketDocument($client, $existingTicketId))
                : null;

            if (! $ticket) {
                $ticket = $this->buildTicketPayload($user, $ticketData, $existingTicketId);
                $transaction->create(
                    $this->ticketDocument($client, (string) $ticket['ticket_id']),
                    $this->timestamps->prepareForStorage($ticket),
                );
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

            $transaction->set(
                $userDocument,
                $this->timestamps->prepareForStorage($userUpdate),
                ['merge' => true]
            );

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

        return $snapshot->exists()
            ? $this->timestamps->normalizeFromStorage($snapshot->data())
            : null;
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

    private function identityIndexDocument(
        FirestoreClient $client,
        string $identityType,
        string $identityCountry,
        string $identityNumber,
    ): DocumentReference
    {
        return $client->collection((string) config('firebase.user_identity_index_collection', 'user_identity_index'))
            ->document(hash('sha256', $this->identityLookupKey($identityType, $identityCountry, $identityNumber)));
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

    private function identityLookupKey(string $identityType, string $identityCountry, string $identityNumber): string
    {
        return implode(':', [
            $this->normalizeIdentityType($identityType),
            $this->normalizeCountry($identityCountry),
            $this->normalizeIdentityNumber($identityNumber),
        ]);
    }

    private function findExistingTicketInsideTransaction(
        Transaction $transaction,
        DocumentReference $ticketDocument,
    ): ?array {
        $ticketSnapshot = $transaction->snapshot($ticketDocument);

        return $ticketSnapshot->exists()
            ? $this->timestamps->normalizeFromStorage($ticketSnapshot->data())
            : null;
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
