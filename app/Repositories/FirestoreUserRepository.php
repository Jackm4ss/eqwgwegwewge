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
        $phoneNumber = $this->normalizePhoneNumber((string) ($data['phone_number'] ?? ''));
        $identityType = $this->normalizeIdentityType((string) $data['identity_type']);
        $identityCountry = $this->normalizeCountry((string) ($data['identity_country'] ?? $data['country'] ?? ''));
        $identityNumber = $this->normalizeIdentityNumber((string) $data['identity_number']);
        $now = now()->toISOString();

        $payload = array_merge($data, [
            'user_id' => $data['user_id'] ?? (string) str()->uuid(),
            'email' => $email,
            'phone_number' => $phoneNumber,
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
        $phoneIndexDocument = $phoneNumber !== ''
            ? $this->phoneIndexDocument($client, $phoneNumber)
            : null;
        $identityIndexDocument = $this->identityIndexDocument($client, $identityType, $identityCountry, $identityNumber);

        $client->runTransaction(function (Transaction $transaction) use (
            $payload,
            $storagePayload,
            $email,
            $phoneNumber,
            $identityType,
            $identityCountry,
            $identityNumber,
            $userDocument,
            $emailIndexDocument,
            $phoneIndexDocument,
            $identityIndexDocument,
        ) {
            if ($transaction->snapshot($emailIndexDocument)->exists()) {
                throw new RegistrationConflictException('email', 'Email already registered.');
            }

            if ($phoneIndexDocument !== null && $transaction->snapshot($phoneIndexDocument)->exists()) {
                throw new RegistrationConflictException('phone_number', 'Phone number already registered.');
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
            if ($phoneIndexDocument !== null) {
                $transaction->create($phoneIndexDocument, $this->buildPhoneIndexPayload($payload));
            }
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

    public function findByPhoneNumber(string $phoneNumber): ?array
    {
        $normalizedPhoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if ($normalizedPhoneNumber === '') {
            return null;
        }

        $indexSnapshot = $this->phoneIndexDocument(
            $this->client(),
            $normalizedPhoneNumber,
        )->snapshot();

        if ($indexSnapshot->exists()) {
            return $this->findById((string) $indexSnapshot['user_id']);
        }

        $query = $this->client()
            ->collection((string) config('firebase.users_collection', 'users'))
            ->where('phone_number', '=', $normalizedPhoneNumber)
            ->limit(1);

        foreach ($query->documents() as $documentSnapshot) {
            if (! $documentSnapshot->exists()) {
                continue;
            }

            return $this->timestamps->normalizeFromStorage($documentSnapshot->data());
        }

        return null;
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
                $ticketCodeIndexDocument = $this->ticketCodeIndexDocument($client, (string) ($ticket['ticket_code'] ?? ''));
                $ticketEntryCodeIndexDocument = $this->ticketEntryCodeIndexDocument($client, (string) ($ticket['entry_code'] ?? ''));
                $transaction->create(
                    $this->ticketDocument($client, (string) $ticket['ticket_id']),
                    $this->timestamps->prepareForStorage($ticket),
                );
                $transaction->create(
                    $ticketCodeIndexDocument,
                    $this->buildTicketCodeIndexPayload($ticket),
                );
                $transaction->create(
                    $ticketEntryCodeIndexDocument,
                    $this->buildTicketEntryCodeIndexPayload($ticket),
                );
                $ticketWasCreated = true;
            } else {
                $originalTicket = $ticket;
                $ticket = $this->synchronizeExistingTicketPayload($ticket, $ticketData, $user, $existingTicketId);
                $ticketCodeIndexDocument = $this->ticketCodeIndexDocument($client, (string) ($ticket['ticket_code'] ?? ''));
                $ticketEntryCodeIndexDocument = $this->ticketEntryCodeIndexDocument($client, (string) ($ticket['entry_code'] ?? ''));

                if ($this->ticketPayloadNeedsSync($originalTicket, $ticket)) {
                    $transaction->set(
                        $this->ticketDocument($client, (string) $ticket['ticket_id']),
                        $this->timestamps->prepareForStorage($ticket),
                        ['merge' => true],
                    );
                }

                $transaction->set(
                    $ticketCodeIndexDocument,
                    $this->buildTicketCodeIndexPayload($ticket),
                );
                $transaction->set(
                    $ticketEntryCodeIndexDocument,
                    $this->buildTicketEntryCodeIndexPayload($ticket),
                );
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

    private function phoneIndexDocument(FirestoreClient $client, string $phoneNumber): DocumentReference
    {
        return $client->collection((string) config('firebase.user_phone_index_collection', 'user_phone_index'))
            ->document(hash('sha256', $this->normalizePhoneNumber($phoneNumber)));
    }

    private function ticketCodeIndexDocument(FirestoreClient $client, string $ticketCode): DocumentReference
    {
        return $client->collection((string) config('firebase.ticket_code_index_collection', 'ticket_code_index'))
            ->document(hash('sha256', strtoupper(trim($ticketCode))));
    }

    private function ticketEntryCodeIndexDocument(FirestoreClient $client, string $entryCode): DocumentReference
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $entryCode) ?? '');

        return $client->collection((string) config('firebase.ticket_entry_code_index_collection', 'ticket_entry_code_index'))
            ->document(hash('sha256', $normalized));
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

    private function buildPhoneIndexPayload(array $payload): array
    {
        $phoneNumber = $this->normalizePhoneNumber((string) ($payload['phone_number'] ?? ''));

        return [
            'user_id' => (string) ($payload['user_id'] ?? ''),
            'normalized_phone_number' => $phoneNumber,
            'created_at' => $payload['created_at'] ?? now()->toISOString(),
            'updated_at' => $payload['updated_at'] ?? now()->toISOString(),
        ];
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

    private function buildTicketCodeIndexPayload(array $ticket): array
    {
        return [
            'ticket_id' => (string) ($ticket['ticket_id'] ?? ''),
            'user_id' => (string) ($ticket['user_id'] ?? ''),
            'ticket_code' => strtoupper(trim((string) ($ticket['ticket_code'] ?? ''))),
            'entry_code' => strtoupper(trim((string) ($ticket['entry_code'] ?? ''))),
            'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
            'created_at' => $ticket['created_at'] ?? now()->toISOString(),
            'updated_at' => $ticket['updated_at'] ?? now()->toISOString(),
        ];
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
            $attendanceStatus = filled($ticket['checked_in_at'] ?? null) || filled($ticket['last_scanned_at'] ?? null)
                ? 'checked_in'
                : 'not_checked_in';
        }

        $payload = array_merge($ticket, [
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
            'updated_at' => $ticket['updated_at'] ?? $ticketData['updated_at'] ?? now()->toISOString(),
            'attendance_status' => $attendanceStatus,
            'checked_in_at' => $ticket['checked_in_at'] ?? null,
            'last_scanned_at' => $ticket['last_scanned_at'] ?? null,
        ]);

        if ($this->ticketPayloadNeedsSync($ticket, $payload)) {
            $payload['updated_at'] = now()->toISOString();
        }

        return $payload;
    }

    private function ticketPayloadNeedsSync(array $existing, array $payload): bool
    {
        foreach ([
            'ticket_id',
            'user_id',
            'event_code',
            'ticket_code',
            'entry_code',
            'entry_code_display',
            'status',
            'qr_version',
            'activated_at',
            'created_at',
            'attendance_status',
            'checked_in_at',
            'last_scanned_at',
        ] as $field) {
            if (($existing[$field] ?? null) !== ($payload[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function formatEntryCodeDisplay(string $entryCode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $entryCode) ?? '');

        if ($normalized === '') {
            return '';
        }

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }

    private function buildTicketEntryCodeIndexPayload(array $ticket): array
    {
        $entryCode = strtoupper(trim((string) ($ticket['entry_code'] ?? '')));

        return [
            'ticket_id' => (string) ($ticket['ticket_id'] ?? ''),
            'user_id' => (string) ($ticket['user_id'] ?? ''),
            'ticket_code' => strtoupper(trim((string) ($ticket['ticket_code'] ?? ''))),
            'entry_code' => $entryCode,
            'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
            'created_at' => $ticket['created_at'] ?? now()->toISOString(),
            'updated_at' => $ticket['updated_at'] ?? now()->toISOString(),
        ];
    }
}
