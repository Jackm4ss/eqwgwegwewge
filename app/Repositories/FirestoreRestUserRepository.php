<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use Illuminate\Support\Arr;
use RuntimeException;

class FirestoreRestUserRepository implements UserRepositoryInterface
{
    private const MAX_TRANSACTION_ATTEMPTS = 3;

    public function __construct(
        private readonly FirestoreRestApi $api,
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

        for ($attempt = 1; $attempt <= self::MAX_TRANSACTION_ATTEMPTS; $attempt++) {
            $transaction = $this->beginTransaction();
            $committed = false;

            try {
                $emailIndexPath = $this->emailIndexPath($email);
                $phoneIndexPath = $phoneNumber !== '' ? $this->phoneIndexPath($phoneNumber) : null;
                $identityIndexPath = $this->identityIndexPath($identityType, $identityCountry, $identityNumber);

                $documents = $this->api()->batchGet(array_values(array_filter([
                    $emailIndexPath,
                    $phoneIndexPath,
                    $identityIndexPath,
                ])), $transaction);

                if ($documents[$emailIndexPath] !== null) {
                    throw new RegistrationConflictException('email', 'Email already registered.');
                }

                if ($phoneIndexPath !== null && ($documents[$phoneIndexPath] ?? null) !== null) {
                    throw new RegistrationConflictException('phone_number', 'Phone number already registered.');
                }

                if ($documents[$identityIndexPath] !== null) {
                    throw new RegistrationConflictException('identity_number', 'This identity document is already registered.');
                }

                $writes = [
                    $this->api()->makeSetWrite($this->userPath($payload['user_id']), $storagePayload, false),
                    $this->api()->makeSetWrite($emailIndexPath, [
                        'user_id' => $payload['user_id'],
                        'normalized_email' => $email,
                        'created_at' => $storagePayload['created_at'],
                    ], false),
                    $this->api()->makeSetWrite($identityIndexPath, [
                        'user_id' => $payload['user_id'],
                        'identity_type' => $identityType,
                        'identity_country' => $identityCountry,
                        'normalized_identity_number' => $identityNumber,
                        'normalized_identity_key' => $this->identityLookupKey($identityType, $identityCountry, $identityNumber),
                        'created_at' => $storagePayload['created_at'],
                    ], false),
                ];

                if ($phoneIndexPath !== null) {
                    $writes[] = $this->api()->makeSetWrite(
                        $phoneIndexPath,
                        $this->buildPhoneIndexPayload($payload),
                        false,
                    );
                }

                $this->api()->commit($writes, $transaction);

                $committed = true;

                return $payload;
            } catch (RegistrationConflictException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                if ($this->shouldRetryTransaction($exception, $attempt)) {
                    $this->pauseBeforeRetry($attempt);
                    continue;
                }

                $this->throwConflictIfIndexesExist($email, $phoneNumber, $identityType, $identityCountry, $identityNumber, $exception);
                throw $exception;
            } finally {
                if (! $committed) {
                    $this->api()->rollbackQuietly($transaction);
                }
            }
        }

        throw new RuntimeException('Firestore transaction could not be completed after retries.');
    }

    public function findByEmail(string $email): ?array
    {
        $indexDocument = $this->api()->getDocument(
            $this->emailIndexPath($this->normalizeEmail($email))
        );

        if ($indexDocument === null) {
            return null;
        }

        $index = $this->api()->decodeDocument($indexDocument);

        return $this->findById((string) $index['user_id']);
    }

    public function findByPhoneNumber(string $phoneNumber): ?array
    {
        $normalizedPhoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if ($normalizedPhoneNumber === '') {
            return null;
        }

        $indexDocument = $this->api()->getDocument(
            $this->phoneIndexPath($normalizedPhoneNumber)
        );

        if ($indexDocument !== null) {
            $index = $this->api()->decodeDocument($indexDocument);

            return $this->findById((string) $index['user_id']);
        }

        $documents = $this->api()->runQuery([
            'from' => [
                ['collectionId' => (string) config('firebase.users_collection', 'users')],
            ],
            'where' => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => 'phone_number'],
                    'op' => 'EQUAL',
                    'value' => ['stringValue' => $normalizedPhoneNumber],
                ],
            ],
            'limit' => 1,
        ]);

        if ($documents === []) {
            return null;
        }

        return $this->timestamps->normalizeFromStorage(
            $this->api()->decodeDocument($documents[0])
        );
    }

    public function findByIdentityDocument(string $identityType, string $identityCountry, string $identityNumber): ?array
    {
        $indexDocument = $this->api()->getDocument(
            $this->identityIndexPath(
                $this->normalizeIdentityType($identityType),
                $this->normalizeCountry($identityCountry),
                $this->normalizeIdentityNumber($identityNumber)
            )
        );

        if ($indexDocument === null) {
            return null;
        }

        $index = $this->api()->decodeDocument($indexDocument);

        return $this->findById((string) $index['user_id']);
    }

    public function findById(string $id): ?array
    {
        $document = $this->api()->getDocument($this->userPath($id));

        return $document
            ? $this->timestamps->normalizeFromStorage($this->api()->decodeDocument($document))
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

        $this->api()->commit([
            $this->api()->makeSetWrite($this->userPath($id), $storagePayload, true),
        ]);

        return $payload;
    }

    public function activateAndIssueTicket(string $id, array $ticketData): array
    {
        for ($attempt = 1; $attempt <= self::MAX_TRANSACTION_ATTEMPTS; $attempt++) {
            $transaction = $this->beginTransaction();
            $committed = false;

            try {
                $userPath = $this->userPath($id);
                $userDocuments = $this->api()->batchGet([$userPath], $transaction);
                $userDocument = $userDocuments[$userPath] ?? null;

                if ($userDocument === null) {
                    throw new RuntimeException('User not found.');
                }

                $user = $this->timestamps->normalizeFromStorage($this->api()->decodeDocument($userDocument));
                $existingTicketId = (string) ($user['ticket_id'] ?? '');
                $ticketPath = $existingTicketId !== '' ? $this->ticketPath($existingTicketId) : null;
                $ticketDocument = null;

                if ($ticketPath !== null) {
                    $ticketDocuments = $this->api()->batchGet([$ticketPath], $transaction);
                    $ticketDocument = $ticketDocuments[$ticketPath] ?? null;
                }

                $ticket = $ticketDocument
                    ? $this->timestamps->normalizeFromStorage($this->api()->decodeDocument($ticketDocument))
                    : null;
                $ticketWasCreated = false;
                $writes = [];

                if (! $ticket) {
                    $ticket = $this->buildTicketPayload($user, $ticketData, $existingTicketId);
                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketPath((string) $ticket['ticket_id']),
                        $this->timestamps->prepareForStorage($ticket),
                        false,
                    );
                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketCodeIndexPath((string) ($ticket['ticket_code'] ?? '')),
                        $this->buildTicketCodeIndexPayload($ticket),
                        false,
                    );
                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketEntryCodeIndexPath((string) ($ticket['entry_code'] ?? '')),
                        $this->buildTicketEntryCodeIndexPayload($ticket),
                        false,
                    );
                    $ticketWasCreated = true;
                } else {
                    $originalTicket = $ticket;
                    $ticket = $this->synchronizeExistingTicketPayload($ticket, $ticketData, $user, $existingTicketId);

                    if ($this->ticketPayloadNeedsSync($originalTicket, $ticket)) {
                        $writes[] = $this->api()->makeSetWrite(
                            $this->ticketPath((string) $ticket['ticket_id']),
                            $this->timestamps->prepareForStorage($ticket),
                            true,
                        );
                    }

                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketCodeIndexPath((string) ($ticket['ticket_code'] ?? '')),
                        $this->buildTicketCodeIndexPayload($ticket),
                        false,
                    );
                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketEntryCodeIndexPath((string) ($ticket['entry_code'] ?? '')),
                        $this->buildTicketEntryCodeIndexPayload($ticket),
                        false,
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

                $writes[] = $this->api()->makeSetWrite(
                    $userPath,
                    $this->timestamps->prepareForStorage(array_merge($user, $userUpdate)),
                    true,
                );

                $this->api()->commit($writes, $transaction);
                $committed = true;

                return [
                    'user' => array_merge($user, $userUpdate),
                    'ticket' => $ticket,
                    'ticket_was_created' => $ticketWasCreated,
                ];
            } catch (\Throwable $exception) {
                if ($this->shouldRetryTransaction($exception, $attempt)) {
                    $this->pauseBeforeRetry($attempt);
                    continue;
                }

                throw $exception;
            } finally {
                if (! $committed) {
                    $this->api()->rollbackQuietly($transaction);
                }
            }
        }

        throw new RuntimeException('Firestore transaction could not be completed after retries.');
    }

    public function findTicketById(string $ticketId): ?array
    {
        $document = $this->api()->getDocument($this->ticketPath($ticketId));

        return $document
            ? $this->timestamps->normalizeFromStorage($this->api()->decodeDocument($document))
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

    private function beginTransaction(): string
    {
        return $this->api()->beginTransaction();
    }

    private function api(): FirestoreRestApi
    {
        if (! $this->api->available()) {
            throw new RuntimeException('Firestore REST storage is unavailable.');
        }

        return $this->api;
    }

    private function throwConflictIfIndexesExist(
        string $email,
        string $phoneNumber,
        string $identityType,
        string $identityCountry,
        string $identityNumber,
        \Throwable $exception,
    ): void {
        try {
            if ($this->api()->getDocument($this->emailIndexPath($email)) !== null) {
                throw new RegistrationConflictException('email', 'Email already registered.');
            }

            if ($phoneNumber !== '' && $this->api()->getDocument($this->phoneIndexPath($phoneNumber)) !== null) {
                throw new RegistrationConflictException('phone_number', 'Phone number already registered.');
            }

            if ($this->api()->getDocument($this->identityIndexPath($identityType, $identityCountry, $identityNumber)) !== null) {
                throw new RegistrationConflictException('identity_number', 'This identity document is already registered.');
            }
        } catch (RegistrationConflictException $conflict) {
            throw $conflict;
        } catch (\Throwable) {
            // Fall through and keep the original exception.
        }
    }

    private function userPath(string $id): string
    {
        return (string) config('firebase.users_collection', 'users').'/'.$id;
    }

    private function ticketPath(string $id): string
    {
        return (string) config('firebase.tickets_collection', 'tickets').'/'.$id;
    }

    private function emailIndexPath(string $email): string
    {
        return (string) config('firebase.user_email_index_collection', 'user_email_index')
            .'/'.hash('sha256', $email);
    }

    private function phoneIndexPath(string $phoneNumber): string
    {
        return (string) config('firebase.user_phone_index_collection', 'user_phone_index')
            .'/'.hash('sha256', $this->normalizePhoneNumber($phoneNumber));
    }

    private function ticketCodeIndexPath(string $ticketCode): string
    {
        return (string) config('firebase.ticket_code_index_collection', 'ticket_code_index')
            .'/'.hash('sha256', strtoupper(trim($ticketCode)));
    }

    private function ticketEntryCodeIndexPath(string $entryCode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $entryCode) ?? '');

        return (string) config('firebase.ticket_entry_code_index_collection', 'ticket_entry_code_index')
            .'/'.hash('sha256', $normalized);
    }

    private function identityIndexPath(string $identityType, string $identityCountry, string $identityNumber): string
    {
        return (string) config('firebase.user_identity_index_collection', 'user_identity_index')
            .'/'.hash('sha256', $this->identityLookupKey($identityType, $identityCountry, $identityNumber));
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

    private function shouldRetryTransaction(\Throwable $exception, int $attempt): bool
    {
        if ($attempt >= self::MAX_TRANSACTION_ATTEMPTS) {
            return false;
        }

        if (! $exception instanceof RuntimeException) {
            return false;
        }

        $message = $exception->getMessage();

        return $exception->getCode() === 409
            || str_contains($message, 'ABORTED')
            || str_contains($message, 'Too much contention');
    }

    private function pauseBeforeRetry(int $attempt): void
    {
        usleep(100000 * $attempt);
    }
}
