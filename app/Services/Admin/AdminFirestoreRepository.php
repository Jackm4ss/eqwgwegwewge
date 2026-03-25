<?php

namespace App\Services\Admin;

use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use App\Services\Tickets\TicketQrCodeService;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Query;
use Google\Cloud\Firestore\Transaction;
use Illuminate\Support\Str;
use RuntimeException;

class AdminFirestoreRepository
{
    public function __construct(
        private readonly FirebaseClientFactory $factory,
        private readonly FirestoreRestApi $restApi,
        private readonly FirestoreTimestampNormalizer $timestamps,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {}

    public function available(): bool
    {
        return $this->usingRest()
            ? $this->restApi->available()
            : $this->factory->make() !== null;
    }

    public function allUsers(): array
    {
        return $this->listCollectionDocuments($this->usersCollection());
    }

    public function allTickets(): array
    {
        return $this->listCollectionDocuments($this->ticketsCollection());
    }

    public function allScanLogs(): array
    {
        return $this->listCollectionDocuments($this->scanLogsCollection());
    }

    public function allAdminActivityLogs(): array
    {
        return $this->listCollectionDocuments($this->adminActivityLogsCollection());
    }

    public function findUser(string $userId): ?array
    {
        return $this->getDocument($this->userPath($userId));
    }

    public function findTicket(string $ticketId): ?array
    {
        return $this->getDocument($this->ticketPath($ticketId));
    }

    public function paginateUsers(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $normalizedFilters = $this->normalizeUserListingFilters($filters);

        return $this->usingRest()
            ? $this->paginateUsersUsingRest($normalizedFilters, $offset, $perPage)
            : $this->paginateUsersUsingGrpc($normalizedFilters, $offset, $perPage);
    }

    public function countUsers(array $filters = []): int
    {
        $normalizedFilters = $this->normalizeUserListingFilters($filters);

        if (! $this->available()) {
            return 0;
        }

        return $this->usingRest()
            ? $this->countUsersUsingRest($normalizedFilters)
            : $this->countUsersUsingGrpc($normalizedFilters);
    }

    public function countTickets(array $filters = []): int
    {
        $normalizedFilters = $this->normalizeTicketListingFilters($filters);

        if (! $this->available()) {
            return 0;
        }

        return $this->usingRest()
            ? $this->countTicketsUsingRest($normalizedFilters)
            : $this->countTicketsUsingGrpc($normalizedFilters);
    }

    public function findTicketsByIds(array $ticketIds): array
    {
        $ticketIds = array_values(array_unique(array_filter(array_map(
            fn (mixed $ticketId): string => trim((string) $ticketId),
            $ticketIds,
        ))));

        if ($ticketIds === [] || ! $this->available()) {
            return [];
        }

        if ($this->usingRest()) {
            $documents = $this->restApi->batchGet(array_map(
                fn (string $ticketId): string => $this->ticketPath($ticketId),
                $ticketIds,
            ));

            $rows = [];

            foreach ($documents as $document) {
                if (! is_array($document)) {
                    continue;
                }

                $decoded = $this->timestamps->normalizeFromStorage(
                    $this->restApi->decodeDocument($document)
                );
                $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));
                $decoded['__path'] = $this->documentPathFromName((string) ($document['name'] ?? ''));
                $rows[] = $decoded;
            }

            return $rows;
        }

        $rows = [];

        foreach ($ticketIds as $ticketId) {
            $snapshot = $this->documentReference($this->client(), $this->ticketPath($ticketId))->snapshot();

            if (! $snapshot->exists()) {
                continue;
            }

            $decoded = $this->timestamps->normalizeFromStorage($snapshot->data());
            $decoded['__id'] = $snapshot->id();
            $decoded['__path'] = $this->ticketPath($snapshot->id());
            $rows[] = $decoded;
        }

        return $rows;
    }

    public function findScanLogsByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map(
            fn (mixed $userId): string => trim((string) $userId),
            $userIds,
        ))));

        if ($userIds === [] || ! $this->available()) {
            return [];
        }

        return $this->usingRest()
            ? $this->findScanLogsByUserIdsUsingRest($userIds)
            : $this->findScanLogsByUserIdsUsingGrpc($userIds);
    }

    public function updateUserByAdmin(string $userId, array $attributes): array
    {
        return $this->usingRest()
            ? $this->updateUserByAdminUsingRest($userId, $attributes)
            : $this->updateUserByAdminUsingGrpc($userId, $attributes);
    }

    public function deleteUserByAdmin(string $userId): array
    {
        return $this->usingRest()
            ? $this->deleteUserByAdminUsingRest($userId)
            : $this->deleteUserByAdminUsingGrpc($userId);
    }

    public function resetQrCode(string $userId): array
    {
        return $this->usingRest()
            ? $this->resetQrCodeUsingRest($userId)
            : $this->resetQrCodeUsingGrpc($userId);
    }

    public function regenerateQrCode(string $userId): array
    {
        return $this->usingRest()
            ? $this->regenerateQrCodeUsingRest($userId)
            : $this->regenerateQrCodeUsingGrpc($userId);
    }

    public function appendAdminActivityLog(array $entry): array
    {
        $payload = array_merge([
            'log_id' => (string) Str::ulid(),
            'admin_id' => null,
            'admin_email' => null,
            'action_type' => 'unknown',
            'target_type' => null,
            'target_id' => null,
            'metadata' => [],
            'ip_address' => null,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ], $entry);

        if (! $this->available()) {
            return $payload;
        }

        $this->setDocument(
            $this->adminActivityLogPath((string) $payload['log_id']),
            $payload,
        );

        return $payload;
    }

    public function snapshotCollections(?array $collections = null): array
    {
        $collections ??= [
            $this->usersCollection(),
            $this->ticketsCollection(),
            $this->scanLogsCollection(),
            $this->adminActivityLogsCollection(),
            $this->emailIndexCollection(),
            $this->identityIndexCollection(),
        ];

        $snapshot = [];

        foreach ($collections as $collection) {
            $snapshot[$collection] = $this->listCollectionDocuments($collection);
        }

        return $snapshot;
    }

    private function updateUserByAdminUsingRest(string $userId, array $attributes): array
    {
        $this->ensureMutationAvailable();

        $transaction = $this->restApi->beginTransaction();
        $committed = false;

        try {
            $userPath = $this->userPath($userId);
            $documents = $this->restApi->batchGet([$userPath], $transaction);
            $userDocument = $documents[$userPath] ?? null;

            if ($userDocument === null) {
                throw new RuntimeException('Participant not found.');
            }

            $existing = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($userDocument));
            $payload = $this->mergeEditableUserAttributes($existing, $attributes);

            $emailPath = $this->emailIndexPath((string) $payload['email']);
            $identityPath = $this->identityIndexPath(
                (string) $payload['identity_type'],
                (string) $payload['identity_country'],
                (string) $payload['identity_number'],
            );

            $currentEmailPath = $this->emailIndexPath((string) $existing['email']);
            $currentIdentityPath = $this->identityIndexPath(
                (string) $existing['identity_type'],
                (string) $existing['identity_country'],
                (string) $existing['identity_number'],
            );

            $indexDocuments = $this->restApi->batchGet(array_values(array_unique([
                $emailPath,
                $identityPath,
            ])), $transaction);

            $this->assertIndexIsAvailable(
                isset($indexDocuments[$emailPath]) && is_array($indexDocuments[$emailPath])
                    ? $this->restApi->decodeDocument($indexDocuments[$emailPath])
                    : null,
                $userId,
                'Email already registered.',
            );
            $this->assertIndexIsAvailable(
                isset($indexDocuments[$identityPath]) && is_array($indexDocuments[$identityPath])
                    ? $this->restApi->decodeDocument($indexDocuments[$identityPath])
                    : null,
                $userId,
                'This identity document is already registered.',
            );

            $writes = [
                $this->restApi->makeSetWrite($userPath, $this->timestamps->prepareForStorage($payload), true),
                $this->restApi->makeSetWrite($emailPath, [
                    'user_id' => $userId,
                    'normalized_email' => $payload['email'],
                    'created_at' => $existing['created_at'] ?? now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ]),
                $this->restApi->makeSetWrite($identityPath, [
                    'user_id' => $userId,
                    'identity_type' => $payload['identity_type'],
                    'identity_country' => $payload['identity_country'],
                    'normalized_identity_number' => $payload['identity_number'],
                    'normalized_identity_key' => $this->identityLookupKey(
                        (string) $payload['identity_type'],
                        (string) $payload['identity_country'],
                        (string) $payload['identity_number'],
                    ),
                    'created_at' => $existing['created_at'] ?? now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ]),
            ];

            if ($currentEmailPath !== $emailPath) {
                $writes[] = $this->restApi->makeDeleteWrite($currentEmailPath, true);
            }

            if ($currentIdentityPath !== $identityPath) {
                $writes[] = $this->restApi->makeDeleteWrite($currentIdentityPath, true);
            }

            $this->restApi->commit($writes, $transaction);
            $committed = true;

            return $payload;
        } finally {
            if (! $committed) {
                $this->restApi->rollbackQuietly($transaction);
            }
        }
    }

    private function updateUserByAdminUsingGrpc(string $userId, array $attributes): array
    {
        $client = $this->client();
        $userReference = $this->documentReference($client, $this->userPath($userId));

        return $client->runTransaction(function (Transaction $transaction) use ($attributes, $client, $userId, $userReference) {
            $userSnapshot = $transaction->snapshot($userReference);

            if (! $userSnapshot->exists()) {
                throw new RuntimeException('Participant not found.');
            }

            $existing = $this->timestamps->normalizeFromStorage($userSnapshot->data());
            $payload = $this->mergeEditableUserAttributes($existing, $attributes);

            $emailReference = $this->documentReference($client, $this->emailIndexPath((string) $payload['email']));
            $identityReference = $this->documentReference($client, $this->identityIndexPath(
                (string) $payload['identity_type'],
                (string) $payload['identity_country'],
                (string) $payload['identity_number'],
            ));

            $currentEmailReference = $this->documentReference($client, $this->emailIndexPath((string) $existing['email']));
            $currentIdentityReference = $this->documentReference($client, $this->identityIndexPath(
                (string) $existing['identity_type'],
                (string) $existing['identity_country'],
                (string) $existing['identity_number'],
            ));

            $this->assertIndexIsAvailable(
                $this->snapshotDataOrNull($transaction->snapshot($emailReference)),
                $userId,
                'Email already registered.'
            );

            $this->assertIndexIsAvailable(
                $this->snapshotDataOrNull($transaction->snapshot($identityReference)),
                $userId,
                'This identity document is already registered.'
            );

            $transaction->set($userReference, $this->timestamps->prepareForStorage($payload));
            $transaction->set($emailReference, [
                'user_id' => $userId,
                'normalized_email' => $payload['email'],
                'created_at' => $existing['created_at'] ?? now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);
            $transaction->set($identityReference, [
                'user_id' => $userId,
                'identity_type' => $payload['identity_type'],
                'identity_country' => $payload['identity_country'],
                'normalized_identity_number' => $payload['identity_number'],
                'normalized_identity_key' => $this->identityLookupKey(
                    (string) $payload['identity_type'],
                    (string) $payload['identity_country'],
                    (string) $payload['identity_number'],
                ),
                'created_at' => $existing['created_at'] ?? now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);

            if ($this->emailIndexPath((string) $existing['email']) !== $this->emailIndexPath((string) $payload['email'])) {
                $transaction->delete($currentEmailReference);
            }

            if ($this->identityIndexPath(
                (string) $existing['identity_type'],
                (string) $existing['identity_country'],
                (string) $existing['identity_number'],
            ) !== $this->identityIndexPath(
                (string) $payload['identity_type'],
                (string) $payload['identity_country'],
                (string) $payload['identity_number'],
            )) {
                $transaction->delete($currentIdentityReference);
            }

            return $payload;
        });
    }

    private function deleteUserByAdminUsingRest(string $userId): array
    {
        $this->ensureMutationAvailable();

        $transaction = $this->restApi->beginTransaction();
        $committed = false;

        try {
            $userPath = $this->userPath($userId);
            $userDocuments = $this->restApi->batchGet([$userPath], $transaction);
            $userDocument = $userDocuments[$userPath] ?? null;

            if ($userDocument === null) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($userDocument));
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $ticketPath = $ticketId !== '' ? $this->ticketPath($ticketId) : null;
            $emailPath = $this->emailIndexPath((string) ($user['email'] ?? ''));
            $identityPath = $this->identityIndexPath(
                (string) ($user['identity_type'] ?? 'passport'),
                (string) ($user['identity_country'] ?? $user['country'] ?? ''),
                (string) ($user['identity_number'] ?? ''),
            );

            $relatedPaths = array_values(array_filter([
                $ticketPath,
                $emailPath,
                $identityPath,
            ]));
            $relatedDocuments = $relatedPaths !== []
                ? $this->restApi->batchGet($relatedPaths, $transaction)
                : [];

            $ticketDocument = $ticketPath !== null ? ($relatedDocuments[$ticketPath] ?? null) : null;
            $ticket = $ticketDocument !== null
                ? $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($ticketDocument))
                : null;

            $writes = [
                $this->restApi->makeDeleteWrite($userPath, true),
            ];

            if ($ticketPath !== null && $ticketDocument !== null) {
                $writes[] = $this->restApi->makeDeleteWrite($ticketPath, true);
            }

            if (isset($relatedDocuments[$emailPath])) {
                $writes[] = $this->restApi->makeDeleteWrite($emailPath);
            }

            if (isset($relatedDocuments[$identityPath])) {
                $writes[] = $this->restApi->makeDeleteWrite($identityPath);
            }

            $this->restApi->commit($writes, $transaction);
            $committed = true;

            return [
                'user' => $user,
                'ticket' => $ticket,
            ];
        } finally {
            if (! $committed) {
                $this->restApi->rollbackQuietly($transaction);
            }
        }
    }

    private function deleteUserByAdminUsingGrpc(string $userId): array
    {
        $client = $this->client();
        $userReference = $this->documentReference($client, $this->userPath($userId));

        return $client->runTransaction(function (Transaction $transaction) use ($client, $userReference) {
            $userSnapshot = $transaction->snapshot($userReference);

            if (! $userSnapshot->exists()) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($userSnapshot->data());
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $ticketReference = $ticketId !== ''
                ? $this->documentReference($client, $this->ticketPath($ticketId))
                : null;
            $ticketSnapshot = $ticketReference !== null
                ? $transaction->snapshot($ticketReference)
                : null;
            $ticket = $ticketSnapshot !== null && $ticketSnapshot->exists()
                ? $this->timestamps->normalizeFromStorage($ticketSnapshot->data())
                : null;

            $emailReference = $this->documentReference($client, $this->emailIndexPath((string) ($user['email'] ?? '')));
            $identityReference = $this->documentReference($client, $this->identityIndexPath(
                (string) ($user['identity_type'] ?? 'passport'),
                (string) ($user['identity_country'] ?? $user['country'] ?? ''),
                (string) ($user['identity_number'] ?? ''),
            ));

            $transaction->delete($userReference);

            if ($ticketReference !== null) {
                $transaction->delete($ticketReference);
            }

            $transaction->delete($emailReference);
            $transaction->delete($identityReference);

            return [
                'user' => $user,
                'ticket' => $ticket,
            ];
        });
    }

    private function resetQrCodeUsingRest(string $userId): array
    {
        $this->ensureMutationAvailable();

        $transaction = $this->restApi->beginTransaction();
        $committed = false;

        try {
            $userPath = $this->userPath($userId);
            $documents = $this->restApi->batchGet([$userPath], $transaction);
            $userDocument = $documents[$userPath] ?? null;

            if ($userDocument === null) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($userDocument));
            $ticketId = (string) ($user['ticket_id'] ?? '');

            if ($ticketId === '') {
                throw new RuntimeException('Participant does not have a ticket yet.');
            }

            $ticketPath = $this->ticketPath($ticketId);
            $ticketDocuments = $this->restApi->batchGet([$ticketPath], $transaction);
            $ticketDocument = $ticketDocuments[$ticketPath] ?? null;

            if ($ticketDocument === null) {
                throw new RuntimeException('Ticket not found.');
            }

            $ticket = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($ticketDocument));
            $updatedTicket = $this->ticketQrCodeService->resetAttendanceAttributes($ticket);

            $this->restApi->commit([
                $this->restApi->makeSetWrite($ticketPath, $this->timestamps->prepareForStorage($updatedTicket), true),
            ], $transaction);

            $committed = true;

            return [
                'user' => $user,
                'ticket' => $updatedTicket,
            ];
        } finally {
            if (! $committed) {
                $this->restApi->rollbackQuietly($transaction);
            }
        }
    }

    private function resetQrCodeUsingGrpc(string $userId): array
    {
        $client = $this->client();
        $userReference = $this->documentReference($client, $this->userPath($userId));

        return $client->runTransaction(function (Transaction $transaction) use ($client, $userReference) {
            $userSnapshot = $transaction->snapshot($userReference);

            if (! $userSnapshot->exists()) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($userSnapshot->data());
            $ticketId = (string) ($user['ticket_id'] ?? '');

            if ($ticketId === '') {
                throw new RuntimeException('Participant does not have a ticket yet.');
            }

            $ticketReference = $this->documentReference($client, $this->ticketPath($ticketId));
            $ticketSnapshot = $transaction->snapshot($ticketReference);

            if (! $ticketSnapshot->exists()) {
                throw new RuntimeException('Ticket not found.');
            }

            $ticket = $this->timestamps->normalizeFromStorage($ticketSnapshot->data());
            $updatedTicket = $this->ticketQrCodeService->resetAttendanceAttributes($ticket);

            $transaction->set($ticketReference, $this->timestamps->prepareForStorage($updatedTicket));

            return [
                'user' => $user,
                'ticket' => $updatedTicket,
            ];
        });
    }

    private function regenerateQrCodeUsingRest(string $userId): array
    {
        $this->ensureMutationAvailable();

        $transaction = $this->restApi->beginTransaction();
        $committed = false;

        try {
            $userPath = $this->userPath($userId);
            $documents = $this->restApi->batchGet([$userPath], $transaction);
            $userDocument = $documents[$userPath] ?? null;

            if ($userDocument === null) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($userDocument));
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $writes = [];

            if ($ticketId !== '') {
                $ticketPath = $this->ticketPath($ticketId);
                $ticketDocuments = $this->restApi->batchGet([$ticketPath], $transaction);
                $ticketDocument = $ticketDocuments[$ticketPath] ?? null;
                $ticket = $ticketDocument !== null
                    ? $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($ticketDocument))
                    : null;
            } else {
                $ticketPath = null;
                $ticket = null;
            }

            if ($ticket === null) {
                $ticket = $this->ticketQrCodeService->makeTicketAttributes($userId);
                $ticketPath = $this->ticketPath((string) $ticket['ticket_id']);
                $user['ticket_id'] = $ticket['ticket_id'];
                $user['updated_at'] = now()->toISOString();
                $writes[] = $this->restApi->makeSetWrite(
                    $userPath,
                    $this->timestamps->prepareForStorage($user),
                    true,
                );
            } else {
                $ticket = $this->ticketQrCodeService->regenerateTicketAttributes($ticket);
            }

            $writes[] = $this->restApi->makeSetWrite(
                $ticketPath,
                $this->timestamps->prepareForStorage($ticket),
                $ticketId !== '',
            );

            $this->restApi->commit($writes, $transaction);
            $committed = true;

            return [
                'user' => $user,
                'ticket' => $ticket,
            ];
        } finally {
            if (! $committed) {
                $this->restApi->rollbackQuietly($transaction);
            }
        }
    }

    private function regenerateQrCodeUsingGrpc(string $userId): array
    {
        $client = $this->client();
        $userReference = $this->documentReference($client, $this->userPath($userId));

        return $client->runTransaction(function (Transaction $transaction) use ($client, $userReference, $userId) {
            $userSnapshot = $transaction->snapshot($userReference);

            if (! $userSnapshot->exists()) {
                throw new RuntimeException('Participant not found.');
            }

            $user = $this->timestamps->normalizeFromStorage($userSnapshot->data());
            $ticketId = (string) ($user['ticket_id'] ?? '');

            if ($ticketId !== '') {
                $ticketReference = $this->documentReference($client, $this->ticketPath($ticketId));
                $ticketSnapshot = $transaction->snapshot($ticketReference);
                $ticket = $ticketSnapshot->exists()
                    ? $this->timestamps->normalizeFromStorage($ticketSnapshot->data())
                    : null;
            } else {
                $ticketReference = null;
                $ticket = null;
            }

            if ($ticket === null) {
                $ticket = $this->ticketQrCodeService->makeTicketAttributes($userId);
                $ticketReference = $this->documentReference($client, $this->ticketPath((string) $ticket['ticket_id']));
                $user['ticket_id'] = $ticket['ticket_id'];
                $user['updated_at'] = now()->toISOString();
                $transaction->set($userReference, $this->timestamps->prepareForStorage($user));
            } else {
                $ticket = $this->ticketQrCodeService->regenerateTicketAttributes($ticket);
            }

            $transaction->set($ticketReference, $this->timestamps->prepareForStorage($ticket));

            return [
                'user' => $user,
                'ticket' => $ticket,
            ];
        });
    }

    private function findScanLogsByUserIdsUsingRest(array $userIds): array
    {
        $rows = [];
        $seenPaths = [];

        foreach (array_chunk($userIds, 10) as $userIdChunk) {
            $where = count($userIdChunk) === 1
                ? [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'user_id'],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => $userIdChunk[0]],
                    ],
                ]
                : [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'user_id'],
                        'op' => 'IN',
                        'value' => [
                            'arrayValue' => [
                                'values' => array_map(
                                    fn (string $userId): array => ['stringValue' => $userId],
                                    $userIdChunk,
                                ),
                            ],
                        ],
                    ],
                ];

            $documents = $this->restApi->runQuery([
                'from' => [
                    ['collectionId' => $this->scanLogsCollection()],
                ],
                'where' => $where,
            ]);

            foreach ($documents as $document) {
                $path = $this->documentPathFromName((string) ($document['name'] ?? ''));

                if ($path === '' || isset($seenPaths[$path])) {
                    continue;
                }

                $seenPaths[$path] = true;

                $decoded = $this->timestamps->normalizeFromStorage(
                    $this->restApi->decodeDocument($document)
                );
                $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));
                $decoded['__path'] = $path;
                $rows[] = $decoded;
            }
        }

        return $rows;
    }

    private function findScanLogsByUserIdsUsingGrpc(array $userIds): array
    {
        $rows = [];
        $seenPaths = [];

        foreach (array_chunk($userIds, 10) as $userIdChunk) {
            $query = $this->client()->collection($this->scanLogsCollection());
            $query = count($userIdChunk) === 1
                ? $query->where('user_id', '=', $userIdChunk[0])
                : $query->where('user_id', 'in', $userIdChunk);

            foreach ($query->documents() as $documentSnapshot) {
                if (! $documentSnapshot->exists()) {
                    continue;
                }

                $path = $this->scanLogsCollection().'/'.$documentSnapshot->id();

                if (isset($seenPaths[$path])) {
                    continue;
                }

                $seenPaths[$path] = true;

                $decoded = $this->timestamps->normalizeFromStorage($documentSnapshot->data());
                $decoded['__id'] = $documentSnapshot->id();
                $decoded['__path'] = $path;
                $rows[] = $decoded;
            }
        }

        return $rows;
    }

    private function paginateUsersUsingRest(array $filters, int $offset, int $limit): array
    {
        $structuredQuery = $this->buildStructuredQuery(
            $this->usersCollection(),
            $this->buildEqualityFilters($filters),
            $limit,
            $offset,
        );

        $documents = $this->restApi->runQuery($structuredQuery);

        return [
            'items' => array_map(function (array $document): array {
                $decoded = $this->timestamps->normalizeFromStorage(
                    $this->restApi->decodeDocument($document)
                );
                $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));
                $decoded['__path'] = $this->documentPathFromName((string) ($document['name'] ?? ''));

                return $decoded;
            }, $documents),
            'total' => $this->countUsersUsingRest($filters),
        ];
    }

    private function paginateUsersUsingGrpc(array $filters, int $offset, int $limit): array
    {
        $query = $this->client()->collection($this->usersCollection());

        foreach ($filters as $field => $value) {
            $query = $query->where($field, '=', $value);
        }

        $query = $query
            ->orderBy('created_at', Query::DIR_DESCENDING)
            ->orderBy(Query::DOCUMENT_ID, Query::DIR_DESCENDING)
            ->offset($offset)
            ->limit($limit);

        $rows = [];

        foreach ($query->documents() as $documentSnapshot) {
            if (! $documentSnapshot->exists()) {
                continue;
            }

            $row = $this->timestamps->normalizeFromStorage($documentSnapshot->data());
            $row['__id'] = $documentSnapshot->id();
            $row['__path'] = $this->usersCollection().'/'.$documentSnapshot->id();
            $rows[] = $row;
        }

        return [
            'items' => $rows,
            'total' => $this->countUsersUsingGrpc($filters),
        ];
    }

    private function countUsersUsingRest(array $filters): int
    {
        $structuredQuery = $this->buildStructuredQuery(
            $this->usersCollection(),
            $this->buildEqualityFilters($filters),
            withOrdering: false,
        );

        return $this->restApi->runCountQuery($structuredQuery, 'count');
    }

    private function countUsersUsingGrpc(array $filters): int
    {
        $query = $this->client()->collection($this->usersCollection());

        foreach ($filters as $field => $value) {
            $query = $query->where($field, '=', $value);
        }

        return (int) $query->count();
    }

    private function countTicketsUsingRest(array $filters): int
    {
        $structuredQuery = $this->buildStructuredQuery(
            $this->ticketsCollection(),
            $this->buildEqualityFilters($filters),
            withOrdering: false,
        );

        return $this->restApi->runCountQuery($structuredQuery, 'count');
    }

    private function countTicketsUsingGrpc(array $filters): int
    {
        $query = $this->client()->collection($this->ticketsCollection());

        foreach ($filters as $field => $value) {
            $query = $query->where($field, '=', $value);
        }

        return (int) $query->count();
    }

    private function buildStructuredQuery(
        string $collection,
        array $filters,
        ?int $limit = null,
        ?int $offset = null,
        bool $withOrdering = true,
    ): array {
        $query = [
            'from' => [
                ['collectionId' => $collection],
            ],
        ];

        if ($filters !== []) {
            if (count($filters) === 1) {
                $query['where'] = $filters[0];
            } else {
                $query['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $filters,
                    ],
                ];
            }
        }

        if ($withOrdering) {
            $query['orderBy'] = [
                [
                    'field' => ['fieldPath' => 'created_at'],
                    'direction' => 'DESCENDING',
                ],
                [
                    'field' => ['fieldPath' => '__name__'],
                    'direction' => 'DESCENDING',
                ],
            ];
        }

        if ($offset !== null && $offset > 0) {
            $query['offset'] = $offset;
        }

        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $query;
    }

    private function buildEqualityFilters(array $filters): array
    {
        $clauses = [];

        foreach ($filters as $field => $value) {
            $clauses[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $field],
                    'op' => 'EQUAL',
                    'value' => $this->encodeStructuredQueryValue($value),
                ],
            ];
        }

        return $clauses;
    }

    private function encodeStructuredQueryValue(mixed $value): array
    {
        return match (true) {
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_string($value) => ['stringValue' => $value],
            is_null($value) => ['nullValue' => 'NULL_VALUE'],
            default => throw new RuntimeException('Unsupported Firestore query value encountered.'),
        };
    }

    private function normalizeUserListingFilters(array $filters): array
    {
        $normalized = [];

        if (filled($filters['country'] ?? null)) {
            $normalized['country'] = $this->normalizeCountry((string) $filters['country']);
        }

        if (filled($filters['verification_status'] ?? null)) {
            $normalized['verification_status'] = strtolower(trim((string) $filters['verification_status']));
        }

        if (filled($filters['account_status'] ?? null)) {
            $normalized['account_status'] = strtolower(trim((string) $filters['account_status']));
        }

        return $normalized;
    }

    private function normalizeTicketListingFilters(array $filters): array
    {
        $normalized = [];

        if (filled($filters['attendance_status'] ?? null)) {
            $normalized['attendance_status'] = strtolower(trim((string) $filters['attendance_status']));
        }

        return $normalized;
    }

    private function listCollectionDocuments(string $collection): array
    {
        if (! $this->available()) {
            return [];
        }

        if ($this->usingRest()) {
            return array_map(function (array $document) {
                $decoded = $this->timestamps->normalizeFromStorage(
                    $this->restApi->decodeDocument($document)
                );

                $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));
                $decoded['__path'] = $this->documentPathFromName((string) ($document['name'] ?? ''));

                return $decoded;
            }, $this->restApi->listAllDocuments($collection));
        }

        $rows = [];
        foreach ($this->client()->collection($collection)->documents() as $documentSnapshot) {
            if (! $documentSnapshot->exists()) {
                continue;
            }

            $row = $this->timestamps->normalizeFromStorage($documentSnapshot->data());
            $row['__id'] = $documentSnapshot->id();
            $row['__path'] = $collection.'/'.$documentSnapshot->id();
            $rows[] = $row;
        }

        return $rows;
    }

    private function getDocument(string $documentPath): ?array
    {
        if (! $this->available()) {
            return null;
        }

        if ($this->usingRest()) {
            $document = $this->restApi->getDocument($documentPath);

            if ($document === null) {
                return null;
            }

            $decoded = $this->timestamps->normalizeFromStorage(
                $this->restApi->decodeDocument($document)
            );
            $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));
            $decoded['__path'] = $this->documentPathFromName((string) ($document['name'] ?? ''));

            return $decoded;
        }

        $snapshot = $this->documentReference($this->client(), $documentPath)->snapshot();

        if (! $snapshot->exists()) {
            return null;
        }

        $decoded = $this->timestamps->normalizeFromStorage($snapshot->data());
        $decoded['__id'] = $snapshot->id();
        $decoded['__path'] = trim($documentPath, '/');

        return $decoded;
    }

    private function setDocument(string $documentPath, array $fields): void
    {
        $this->ensureMutationAvailable();

        if ($this->usingRest()) {
            $this->restApi->commit([
                $this->restApi->makeSetWrite($documentPath, $this->timestamps->prepareForStorage($fields)),
            ]);

            return;
        }

        $this->documentReference($this->client(), $documentPath)
            ->set($this->timestamps->prepareForStorage($fields));
    }

    private function assertIndexIsAvailable(?array $indexDocument, string $userId, string $message): void
    {
        if ($indexDocument === null) {
            return;
        }

        $existingUserId = (string) ($indexDocument['user_id'] ?? '');
        if ($existingUserId !== '' && $existingUserId !== $userId) {
            throw new RuntimeException($message);
        }
    }

    private function mergeEditableUserAttributes(array $existing, array $attributes): array
    {
        $country = $this->normalizeCountry((string) ($attributes['country'] ?? $existing['country'] ?? ''));
        $identityType = $this->normalizeIdentityType((string) ($attributes['identity_type'] ?? $existing['identity_type'] ?? 'passport'));
        $identityNumber = $this->normalizeIdentityNumber((string) ($attributes['identity_number'] ?? $existing['identity_number'] ?? ''));
        $email = $this->normalizeEmail((string) ($attributes['email'] ?? $existing['email'] ?? ''));
        $fullName = $this->normalizeName((string) ($attributes['full_name'] ?? $existing['full_name'] ?? ''));
        $phoneCountryCode = $this->normalizePhoneCountryCode((string) ($attributes['phone_country_code'] ?? $existing['phone_country_code'] ?? ''));
        $phoneNationalNumber = $this->normalizePhoneNationalNumber((string) ($attributes['phone_national_number'] ?? $existing['phone_national_number'] ?? ''));
        $phoneNumberInput = (string) ($attributes['phone_number'] ?? $existing['phone_number'] ?? '');
        $phoneNumber = $phoneCountryCode !== '' && $phoneNationalNumber !== ''
            ? $phoneCountryCode.$phoneNationalNumber
            : $this->normalizePhoneNumber($phoneNumberInput);
        $verificationStatus = (string) ($attributes['verification_status'] ?? $existing['verification_status'] ?? 'unverified');

        $payload = array_merge($existing, [
            'full_name' => $fullName,
            'email' => $email,
            'phone_country_code' => $phoneCountryCode !== '' ? $phoneCountryCode : null,
            'phone_national_number' => $phoneNationalNumber !== '' ? $phoneNationalNumber : null,
            'phone_number' => $phoneNumber,
            'country' => $country,
            'identity_country' => $country,
            'identity_type' => $identityType,
            'identity_number' => $identityNumber,
            'account_status' => (string) ($attributes['account_status'] ?? $existing['account_status'] ?? 'pending_verification'),
            'verification_status' => $verificationStatus,
            'updated_at' => now()->toISOString(),
        ]);

        $payload['email_verified_at'] = $verificationStatus === 'verified'
            ? (string) ($existing['email_verified_at'] ?? now()->toISOString())
            : null;

        $payload['search_blob'] = $this->buildUserSearchBlob($payload);

        return $payload;
    }

    private function buildUserSearchBlob(array $payload): string
    {
        return trim(implode(' ', array_filter([
            $this->normalizeName((string) ($payload['full_name'] ?? '')),
            $this->normalizeEmail((string) ($payload['email'] ?? '')),
            $this->normalizeIdentityNumber((string) ($payload['identity_number'] ?? '')),
            $this->normalizeCountry((string) ($payload['country'] ?? '')),
        ])));
    }

    private function snapshotDataOrNull(mixed $snapshot): ?array
    {
        if ($snapshot === null || ! method_exists($snapshot, 'exists') || ! $snapshot->exists()) {
            return null;
        }

        return $this->timestamps->normalizeFromStorage($snapshot->data());
    }

    private function documentReference(FirestoreClient $client, string $documentPath): DocumentReference
    {
        $segments = array_values(array_filter(explode('/', trim($documentPath, '/'))));

        if ($segments === [] || count($segments) % 2 !== 0) {
            throw new RuntimeException('Invalid Firestore document path: '.$documentPath);
        }

        $reference = $client->collection(array_shift($segments))->document((string) array_shift($segments));

        while ($segments !== []) {
            $reference = $reference
                ->collection((string) array_shift($segments))
                ->document((string) array_shift($segments));
        }

        return $reference;
    }

    private function usingRest(): bool
    {
        return (string) config('firebase.transport', 'grpc') === 'rest';
    }

    private function ensureMutationAvailable(): void
    {
        if (! $this->available()) {
            throw new RuntimeException('Firestore storage is unavailable.');
        }
    }

    private function client(): FirestoreClient
    {
        $client = $this->factory->make();

        if ($client === null) {
            throw new RuntimeException('Firestore storage is unavailable.');
        }

        return $client;
    }

    private function usersCollection(): string
    {
        return (string) config('firebase.users_collection', 'users');
    }

    private function ticketsCollection(): string
    {
        return (string) config('firebase.tickets_collection', 'tickets');
    }

    private function emailIndexCollection(): string
    {
        return (string) config('firebase.user_email_index_collection', 'user_email_index');
    }

    private function identityIndexCollection(): string
    {
        return (string) config('firebase.user_identity_index_collection', 'user_identity_index');
    }

    private function scanLogsCollection(): string
    {
        return (string) config('firebase.scan_logs_collection', 'scan_logs');
    }

    private function adminActivityLogsCollection(): string
    {
        return (string) config('firebase.admin_activity_logs_collection', 'admin_activity_logs');
    }

    private function userPath(string $userId): string
    {
        return $this->usersCollection().'/'.$userId;
    }

    private function ticketPath(string $ticketId): string
    {
        return $this->ticketsCollection().'/'.$ticketId;
    }

    private function adminActivityLogPath(string $logId): string
    {
        return $this->adminActivityLogsCollection().'/'.$logId;
    }

    private function emailIndexPath(string $email): string
    {
        return $this->emailIndexCollection().'/'.hash('sha256', $this->normalizeEmail($email));
    }

    private function identityIndexPath(string $identityType, string $country, string $identityNumber): string
    {
        return $this->identityIndexCollection().'/'.hash('sha256', $this->identityLookupKey(
            $identityType,
            $country,
            $identityNumber,
        ));
    }

    private function identityLookupKey(string $identityType, string $country, string $identityNumber): string
    {
        return implode(':', [
            $this->normalizeIdentityType($identityType),
            $this->normalizeCountry($country),
            $this->normalizeIdentityNumber($identityNumber),
        ]);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    private function normalizeCountry(string $country): string
    {
        return strtoupper(trim($country));
    }

    private function normalizeIdentityType(string $identityType): string
    {
        return strtolower(trim($identityType));
    }

    private function normalizeIdentityNumber(string $identityNumber): string
    {
        return strtoupper(trim($identityNumber));
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

    private function documentIdFromName(string $documentName): string
    {
        $segments = array_values(array_filter(explode('/', trim($documentName, '/'))));

        return (string) end($segments);
    }

    private function documentPathFromName(string $documentName): string
    {
        $databaseName = 'projects/'.(string) config('firebase.project_id').'/databases/'.(string) config('firebase.database', '(default)').'/documents/';

        return str_starts_with($documentName, $databaseName)
            ? substr($documentName, strlen($databaseName))
            : $documentName;
    }
}
