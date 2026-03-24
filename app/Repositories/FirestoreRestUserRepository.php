<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Exceptions\RegistrationConflictException;
use App\Services\Firebase\FirestoreRestApi;
use Illuminate\Support\Arr;
use RuntimeException;

class FirestoreRestUserRepository implements UserRepositoryInterface
{
    private const MAX_TRANSACTION_ATTEMPTS = 3;

    public function __construct(private readonly FirestoreRestApi $api)
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

        for ($attempt = 1; $attempt <= self::MAX_TRANSACTION_ATTEMPTS; $attempt++) {
            $transaction = $this->beginTransaction();
            $committed = false;

            try {
                $emailIndexPath = $this->emailIndexPath($email);
                $identityIndexPath = $this->identityIndexPath($identityNumber);

                $documents = $this->api()->batchGet([
                    $emailIndexPath,
                    $identityIndexPath,
                ], $transaction);

                if ($documents[$emailIndexPath] !== null) {
                    throw new RegistrationConflictException('email', 'Email already registered.');
                }

                if ($documents[$identityIndexPath] !== null) {
                    throw new RegistrationConflictException('identity_number', 'NIK / Passport already registered.');
                }

                $this->api()->commit([
                    $this->api()->makeSetWrite($this->userPath($payload['user_id']), $payload, false),
                    $this->api()->makeSetWrite($emailIndexPath, [
                        'user_id' => $payload['user_id'],
                        'normalized_email' => $email,
                        'created_at' => $payload['created_at'],
                    ], false),
                    $this->api()->makeSetWrite($identityIndexPath, [
                        'user_id' => $payload['user_id'],
                        'normalized_identity_number' => $identityNumber,
                        'created_at' => $payload['created_at'],
                    ], false),
                ], $transaction);

                $committed = true;

                return $payload;
            } catch (RegistrationConflictException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                if ($this->shouldRetryTransaction($exception, $attempt)) {
                    $this->pauseBeforeRetry($attempt);
                    continue;
                }

                $this->throwConflictIfIndexesExist($email, $identityNumber, $exception);
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

    public function findByIdentityNumber(string $identityNumber): ?array
    {
        $indexDocument = $this->api()->getDocument(
            $this->identityIndexPath($this->normalizeIdentityNumber($identityNumber))
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

        return $document ? $this->api()->decodeDocument($document) : null;
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

        $this->api()->commit([
            $this->api()->makeSetWrite($this->userPath($id), $payload, true),
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

                $user = $this->api()->decodeDocument($userDocument);
                $existingTicketId = (string) ($user['ticket_id'] ?? '');
                $ticketPath = $existingTicketId !== '' ? $this->ticketPath($existingTicketId) : null;
                $ticketDocument = null;

                if ($ticketPath !== null) {
                    $ticketDocuments = $this->api()->batchGet([$ticketPath], $transaction);
                    $ticketDocument = $ticketDocuments[$ticketPath] ?? null;
                }

                $ticket = $ticketDocument ? $this->api()->decodeDocument($ticketDocument) : null;
                $ticketWasCreated = false;
                $writes = [];

                if (! $ticket) {
                    $ticket = $this->buildTicketPayload($user, $ticketData, $existingTicketId);
                    $writes[] = $this->api()->makeSetWrite(
                        $this->ticketPath((string) $ticket['ticket_id']),
                        $ticket,
                        false,
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

                $writes[] = $this->api()->makeSetWrite(
                    $userPath,
                    array_merge($user, $userUpdate),
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

        return $document ? $this->api()->decodeDocument($document) : null;
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
        string $identityNumber,
        \Throwable $exception,
    ): void {
        try {
            if ($this->api()->getDocument($this->emailIndexPath($email)) !== null) {
                throw new RegistrationConflictException('email', 'Email already registered.');
            }

            if ($this->api()->getDocument($this->identityIndexPath($identityNumber)) !== null) {
                throw new RegistrationConflictException('identity_number', 'NIK / Passport already registered.');
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

    private function identityIndexPath(string $identityNumber): string
    {
        return (string) config('firebase.user_identity_index_collection', 'user_identity_index')
            .'/'.hash('sha256', $identityNumber);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizeIdentityNumber(string $identityNumber): string
    {
        return strtoupper(trim($identityNumber));
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
