<?php

namespace App\Services\Scanner;

use App\Contracts\UserRepositoryInterface;
use App\Models\ScannerStation;
use App\Models\StaffUser;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use App\Services\Tickets\TicketQrCodeService;
use Carbon\CarbonImmutable;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ScannerService
{
    private const MAX_TRANSACTION_ATTEMPTS = 3;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
        private readonly FirebaseClientFactory $factory,
        private readonly FirestoreRestApi $restApi,
        private readonly FirestoreTimestampNormalizer $timestamps,
    ) {
    }

    public function scan(string $qrPayload, StaffUser $operator, ScannerStation $station, ?string $requestIp = null): array
    {
        $scannedAt = CarbonImmutable::now(config('app.timezone'));
        $scanDate = $scannedAt->toDateString();
        $baseContext = $this->baseScanContext($operator, $station, $scanDate, $scannedAt, $requestIp);
        $payloadInspection = $this->ticketQrCodeService->inspectPayload($qrPayload);
        $parsedPayload = $payloadInspection['parsed'] ?? null;

        if ($parsedPayload === null) {
            $parserReason = (string) data_get($payloadInspection, 'debug.parser_reason', 'invalid_format');

            return $this->appendStandaloneScanLog(array_merge($baseContext, [
                'result' => 'invalid',
                'result_reason' => $parserReason !== '' ? $parserReason : 'invalid_format',
            ]), $payloadInspection['debug'] ?? []);
        }

        $userId = (string) $parsedPayload['user_id'];
        $user = $this->users->findById($userId);

        if ($user === null) {
            return $this->appendStandaloneScanLog(array_merge($baseContext, [
                'user_id' => $userId,
                'result' => 'invalid',
                'result_reason' => 'user_not_found',
            ]));
        }

        $participant = $this->participantSummary($user);
        $ticket = $this->users->findTicketByUserId($userId);

        if ($ticket === null) {
            return $this->appendStandaloneScanLog(array_merge($baseContext, [
                'user_id' => $userId,
                'result' => 'invalid',
                'result_reason' => 'ticket_not_found',
            ]), [], $participant);
        }

        $ticketContext = $this->ticketLogContext($ticket);
        $ticketToken = trim((string) ($ticket['qr_token'] ?? ''));

        if ($ticketToken === '' || ! hash_equals($ticketToken, (string) $parsedPayload['qr_token'])) {
            return $this->appendStandaloneScanLog(array_merge($baseContext, $ticketContext, [
                'user_id' => $userId,
                'result' => 'invalid',
                'result_reason' => 'token_mismatch',
            ]), [], $participant);
        }

        if (strtolower((string) ($ticket['status'] ?? 'inactive')) !== 'active') {
            return $this->appendStandaloneScanLog(array_merge($baseContext, $ticketContext, [
                'user_id' => $userId,
                'result' => 'invalid',
                'result_reason' => 'ticket_inactive',
            ]), [], $participant);
        }

        $qrExpiresAt = trim((string) ($ticket['qr_expires_at'] ?? ''));
        if (! $this->ticketQrCodeService->isWithinEventWindow($scannedAt)
            || ($qrExpiresAt !== '' && $scannedAt->gt(CarbonImmutable::parse($qrExpiresAt, config('app.timezone'))))) {
            return $this->appendStandaloneScanLog(array_merge($baseContext, $ticketContext, [
                'user_id' => $userId,
                'result' => 'expired',
                'result_reason' => 'outside_event_window',
            ]), [], $participant);
        }

        return $this->usingRest()
            ? $this->scanUsingRest($userId, $ticket, $baseContext, $participant)
            : $this->scanUsingGrpc($userId, $ticket, $baseContext, $participant);
    }

    public function todayStats(ScannerStation $station): array
    {
        $today = CarbonImmutable::now(config('app.timezone'))->toDateString();
        $totals = $this->blankTodayStats($station, $today);

        try {
            $logs = $this->queryScanLogs([
                'scan_date' => $today,
                'station_id' => (string) $station->station_id,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Scanner daily stats query failed', [
                'station_id' => (string) $station->station_id,
                'scanner_id' => (string) $station->scanner_id,
                'scan_date' => $today,
                'error' => $exception->getMessage(),
            ]);

            return $totals;
        }

        $totals['stats_available'] = true;

        foreach ($logs as $log) {
            $result = strtolower((string) ($log['result'] ?? 'invalid'));
            $totals['total_scans']++;

            if ($result === 'valid') {
                $totals['valid_scans']++;
            } elseif ($result === 'duplicate') {
                $totals['duplicate_scans']++;
            } elseif ($result === 'expired') {
                $totals['expired_scans']++;
            } else {
                $totals['invalid_scans']++;
            }

            $scannedAt = (string) ($log['scanned_at'] ?? '');
            if ($scannedAt !== '' && ($totals['last_scanned_at'] === null || $scannedAt > $totals['last_scanned_at'])) {
                $totals['last_scanned_at'] = $scannedAt;
                $totals['last_result'] = $result;
            }
        }

        return $totals;
    }

    private function blankTodayStats(ScannerStation $station, string $today): array
    {
        return [
            'scan_date' => $today,
            'station_id' => (string) $station->station_id,
            'scanner_id' => (string) $station->scanner_id,
            'scanner_name' => (string) $station->scanner_name,
            'gate_id' => (string) $station->gate_id,
            'gate_name' => (string) $station->gate_name,
            'total_scans' => 0,
            'valid_scans' => 0,
            'duplicate_scans' => 0,
            'invalid_scans' => 0,
            'expired_scans' => 0,
            'last_scanned_at' => null,
            'last_result' => null,
            'stats_available' => false,
        ];
    }

    public function currentStationIdFromSession(): ?int
    {
        $value = session('staff_station_id');

        return is_numeric($value) ? (int) $value : null;
    }

    private function scanUsingRest(string $userId, array $ticket, array $baseContext, array $participant): array
    {
        for ($attempt = 1; $attempt <= self::MAX_TRANSACTION_ATTEMPTS; $attempt++) {
            $transaction = $this->restApi->beginTransaction();
            $committed = false;

            try {
                $scanId = (string) Str::ulid();
                $attendancePath = $this->attendanceDailyPath((string) $baseContext['scan_date'], $userId);
                $ticketPath = $this->ticketPath((string) $ticket['ticket_id']);
                $scanLogPath = $this->scanLogPath($scanId);
                $documents = $this->restApi->batchGet([$attendancePath, $ticketPath], $transaction);
                $attendanceDocument = $documents[$attendancePath] ?? null;
                $ticketDocument = $documents[$ticketPath] ?? null;

                if ($ticketDocument === null) {
                    $log = array_merge($baseContext, $this->ticketLogContext($ticket), [
                        'scan_id' => $scanId,
                        'user_id' => $userId,
                        'result' => 'invalid',
                        'result_reason' => 'ticket_not_found',
                    ]);

                    $this->restApi->commit([
                        $this->restApi->makeSetWrite($scanLogPath, $this->timestamps->prepareForStorage($log), false),
                    ], $transaction);
                    $committed = true;

                    return $this->responseFromScanLog($log, [], $participant);
                }

                $currentTicket = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($ticketDocument));
                $currentToken = trim((string) ($currentTicket['qr_token'] ?? ''));
                $logBase = array_merge($baseContext, $this->ticketLogContext($currentTicket), [
                    'scan_id' => $scanId,
                    'user_id' => $userId,
                ]);

                if ($currentToken === '' || ! hash_equals($currentToken, (string) ($ticket['qr_token'] ?? ''))) {
                    $log = array_merge($logBase, [
                        'result' => 'invalid',
                        'result_reason' => 'token_rotated',
                    ]);

                    $this->restApi->commit([
                        $this->restApi->makeSetWrite($scanLogPath, $this->timestamps->prepareForStorage($log), false),
                    ], $transaction);
                    $committed = true;

                    return $this->responseFromScanLog($log, [], $participant);
                }

                if ($attendanceDocument !== null) {
                    $log = array_merge($logBase, [
                        'result' => 'duplicate',
                        'result_reason' => 'already_scanned_today',
                    ]);

                    $this->restApi->commit([
                        $this->restApi->makeSetWrite($scanLogPath, $this->timestamps->prepareForStorage($log), false),
                    ], $transaction);
                    $committed = true;

                    return $this->responseFromScanLog($log, [], $participant);
                }

                $attendanceRecord = [
                    'attendance_id' => (string) Str::ulid(),
                    'user_id' => $userId,
                    'ticket_id' => $currentTicket['ticket_id'] ?? null,
                    'ticket_code' => $currentTicket['ticket_code'] ?? null,
                    'scan_date' => $baseContext['scan_date'],
                    'scanned_at' => $baseContext['scanned_at'],
                    'operator_id' => $baseContext['operator_id'],
                    'operator_name' => $baseContext['operator_name'],
                    'station_id' => $baseContext['station_id'],
                    'scanner_id' => $baseContext['scanner_id'],
                    'scanner_name' => $baseContext['scanner_name'],
                    'gate_id' => $baseContext['gate_id'],
                    'gate_name' => $baseContext['gate_name'],
                    'created_at' => $baseContext['scanned_at'],
                    'updated_at' => $baseContext['scanned_at'],
                ];
                $ticketUpdate = array_merge($currentTicket, [
                    'attendance_status' => 'checked_in',
                    'checked_in_at' => $baseContext['scanned_at'],
                    'last_scanned_at' => $baseContext['scanned_at'],
                    'last_valid_scan_at' => $baseContext['scanned_at'],
                    'last_valid_scan_date' => $baseContext['scan_date'],
                    'updated_at' => $baseContext['scanned_at'],
                ]);
                $log = array_merge($logBase, [
                    'result' => 'valid',
                    'result_reason' => 'first_scan_today',
                ]);

                $this->restApi->commit([
                    $this->restApi->makeSetWrite($attendancePath, $this->timestamps->prepareForStorage($attendanceRecord), false),
                    $this->restApi->makeSetWrite($ticketPath, $this->timestamps->prepareForStorage($ticketUpdate), true),
                    $this->restApi->makeSetWrite($scanLogPath, $this->timestamps->prepareForStorage($log), false),
                ], $transaction);
                $committed = true;

                return $this->responseFromScanLog($log, [], $participant);
            } catch (\Throwable $exception) {
                if ($this->shouldRetryTransaction($exception, $attempt)) {
                    $this->pauseBeforeRetry($attempt);
                    continue;
                }

                throw $exception;
            } finally {
                if (! $committed) {
                    $this->restApi->rollbackQuietly($transaction);
                }
            }
        }

        throw new RuntimeException('Scanner transaction could not be completed after retries.');
    }

    private function scanUsingGrpc(string $userId, array $ticket, array $baseContext, array $participant): array
    {
        $client = $this->client();

        return $client->runTransaction(function (Transaction $transaction) use ($baseContext, $client, $ticket, $userId) {
            $scanId = (string) Str::ulid();
            $attendanceReference = $this->documentReference(
                $client,
                $this->attendanceDailyPath((string) $baseContext['scan_date'], $userId)
            );
            $ticketReference = $this->documentReference($client, $this->ticketPath((string) $ticket['ticket_id']));
            $scanLogReference = $this->documentReference($client, $this->scanLogPath($scanId));
            $attendanceSnapshot = $transaction->snapshot($attendanceReference);
            $ticketSnapshot = $transaction->snapshot($ticketReference);

            if (! $ticketSnapshot->exists()) {
                $log = array_merge($baseContext, $this->ticketLogContext($ticket), [
                    'scan_id' => $scanId,
                    'user_id' => $userId,
                    'result' => 'invalid',
                    'result_reason' => 'ticket_not_found',
                ]);
                $transaction->create($scanLogReference, $this->timestamps->prepareForStorage($log));

                return $this->responseFromScanLog($log, [], $participant);
            }

            $currentTicket = $this->timestamps->normalizeFromStorage($ticketSnapshot->data());
            $currentToken = trim((string) ($currentTicket['qr_token'] ?? ''));
            $logBase = array_merge($baseContext, $this->ticketLogContext($currentTicket), [
                'scan_id' => $scanId,
                'user_id' => $userId,
            ]);

            if ($currentToken === '' || ! hash_equals($currentToken, (string) ($ticket['qr_token'] ?? ''))) {
                $log = array_merge($logBase, [
                    'result' => 'invalid',
                    'result_reason' => 'token_rotated',
                ]);
                $transaction->create($scanLogReference, $this->timestamps->prepareForStorage($log));

                return $this->responseFromScanLog($log, [], $participant);
            }

            if ($attendanceSnapshot->exists()) {
                $log = array_merge($logBase, [
                    'result' => 'duplicate',
                    'result_reason' => 'already_scanned_today',
                ]);
                $transaction->create($scanLogReference, $this->timestamps->prepareForStorage($log));

                return $this->responseFromScanLog($log, [], $participant);
            }

            $attendanceRecord = [
                'attendance_id' => (string) Str::ulid(),
                'user_id' => $userId,
                'ticket_id' => $currentTicket['ticket_id'] ?? null,
                'ticket_code' => $currentTicket['ticket_code'] ?? null,
                'scan_date' => $baseContext['scan_date'],
                'scanned_at' => $baseContext['scanned_at'],
                'operator_id' => $baseContext['operator_id'],
                'operator_name' => $baseContext['operator_name'],
                'station_id' => $baseContext['station_id'],
                'scanner_id' => $baseContext['scanner_id'],
                'scanner_name' => $baseContext['scanner_name'],
                'gate_id' => $baseContext['gate_id'],
                'gate_name' => $baseContext['gate_name'],
                'created_at' => $baseContext['scanned_at'],
                'updated_at' => $baseContext['scanned_at'],
            ];
            $ticketUpdate = array_merge($currentTicket, [
                'attendance_status' => 'checked_in',
                'checked_in_at' => $baseContext['scanned_at'],
                'last_scanned_at' => $baseContext['scanned_at'],
                'last_valid_scan_at' => $baseContext['scanned_at'],
                'last_valid_scan_date' => $baseContext['scan_date'],
                'updated_at' => $baseContext['scanned_at'],
            ]);
            $log = array_merge($logBase, [
                'result' => 'valid',
                'result_reason' => 'first_scan_today',
            ]);

            $transaction->create($attendanceReference, $this->timestamps->prepareForStorage($attendanceRecord));
            $transaction->set($ticketReference, $this->timestamps->prepareForStorage($ticketUpdate));
            $transaction->create($scanLogReference, $this->timestamps->prepareForStorage($log));

            return $this->responseFromScanLog($log, [], $participant);
        });
    }

    private function appendStandaloneScanLog(array $log, array $debug = [], array $participant = []): array
    {
        $payload = array_merge([
            'scan_id' => (string) Str::ulid(),
            'ticket_id' => null,
            'ticket_code' => null,
            'qr_version' => null,
        ], $log);
        $documentPath = $this->scanLogPath((string) $payload['scan_id']);

        if ($this->usingRest()) {
            $this->restApi->commit([
                $this->restApi->makeSetWrite($documentPath, $this->timestamps->prepareForStorage($payload), false),
            ]);
        } else {
            $this->documentReference($this->client(), $documentPath)
                ->create($this->timestamps->prepareForStorage($payload));
        }

        return $this->responseFromScanLog($payload, $debug, $participant);
    }

    private function responseFromScanLog(array $log, array $debug = [], array $participant = []): array
    {
        $response = [
            'status' => (string) ($log['result'] ?? 'invalid'),
            'reason' => (string) ($log['result_reason'] ?? 'unknown'),
            'user_id' => $log['user_id'] ?? null,
            'ticket_code' => $log['ticket_code'] ?? null,
            'scan_date' => $log['scan_date'] ?? null,
            'scanned_at' => $log['scanned_at'] ?? null,
            'gate_name' => $log['gate_name'] ?? null,
            'scanner_name' => $log['scanner_name'] ?? null,
            'operator_name' => $log['operator_name'] ?? null,
            'participant_name' => null,
            'participant_phone_number' => null,
            'participant_identity_label' => null,
            'participant_identity_number' => null,
        ];

        if ($participant !== []) {
            $response = array_merge($response, $participant);
        }

        if (config('app.debug') && $debug !== []) {
            $response['debug'] = $this->sanitizeDebugPayload($debug);
        }

        return $response;
    }

    private function participantSummary(?array $user): array
    {
        if (! is_array($user) || $user === []) {
            return [];
        }

        $identityType = strtolower(trim((string) ($user['identity_type'] ?? 'passport')));

        return [
            'participant_name' => $this->nullableParticipantField((string) ($user['full_name'] ?? '')),
            'participant_phone_number' => $this->nullableParticipantField((string) ($user['phone_number'] ?? '')),
            'participant_identity_label' => $identityType === 'national_id' ? 'National ID' : 'Passport',
            'participant_identity_number' => $this->nullableParticipantField((string) ($user['identity_number'] ?? '')),
        ];
    }

    private function nullableParticipantField(string $value): ?string
    {
        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function sanitizeDebugPayload(array $debug): array
    {
        $allowedKeys = [
            'received_payload',
            'received_payload_hex',
            'normalized_payload',
            'normalized_payload_hex',
            'colon_count',
            'prefix_guess',
            'parser_status',
            'parser_reason',
        ];

        $sanitized = [];

        foreach ($allowedKeys as $key) {
            if (! array_key_exists($key, $debug)) {
                continue;
            }

            $value = $debug[$key];

            if (is_string($value)) {
                $sanitized[$key] = mb_substr($value, 0, 512);
                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private function queryScanLogs(array $filters, ?int $limit = null): array
    {
        if ($this->usingRest()) {
            $documents = $this->restApi->runQuery(
                $this->buildScanLogStructuredQuery($filters, $limit)
            );

            return array_map(function (array $document): array {
                $decoded = $this->timestamps->normalizeFromStorage($this->restApi->decodeDocument($document));
                $decoded['__id'] = $this->documentIdFromName((string) ($document['name'] ?? ''));

                return $decoded;
            }, $documents);
        }

        $query = $this->client()->collection($this->scanLogsCollection());

        foreach ($filters as $field => $value) {
            $query = $query->where($field, '=', $value);
        }

        if ($limit !== null) {
            $query = $query->limit($limit);
        }

        $rows = [];

        foreach ($query->documents() as $snapshot) {
            if (! $snapshot->exists()) {
                continue;
            }

            $decoded = $this->timestamps->normalizeFromStorage($snapshot->data());
            $decoded['__id'] = $snapshot->id();
            $rows[] = $decoded;
        }

        return $rows;
    }

    private function buildScanLogStructuredQuery(array $filters, ?int $limit = null): array
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

        return [
            'from' => [
                ['collectionId' => $this->scanLogsCollection()],
            ],
            'where' => count($clauses) === 1
                ? $clauses[0]
                : [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $clauses,
                    ],
            ],
        ];

        if ($limit !== null) {
            $query['limit'] = max(1, $limit);
        }

        return $query;
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

    private function baseScanContext(
        StaffUser $operator,
        ScannerStation $station,
        string $scanDate,
        CarbonImmutable $scannedAt,
        ?string $requestIp,
    ): array {
        return [
            'operator_id' => (string) $operator->getKey(),
            'operator_name' => (string) $operator->name,
            'station_id' => (string) $station->station_id,
            'scanner_id' => (string) $station->scanner_id,
            'scanner_name' => (string) $station->scanner_name,
            'gate_id' => (string) $station->gate_id,
            'gate_name' => (string) $station->gate_name,
            'scan_date' => $scanDate,
            'scanned_at' => $scannedAt->toISOString(),
            'request_ip' => $requestIp,
        ];
    }

    private function ticketLogContext(array $ticket): array
    {
        return [
            'ticket_id' => $ticket['ticket_id'] ?? null,
            'ticket_code' => $ticket['ticket_code'] ?? null,
            'qr_version' => $ticket['qr_version'] ?? null,
        ];
    }

    private function attendanceDailyCollection(): string
    {
        return (string) config('firebase.attendance_daily_collection', 'attendance_daily');
    }

    private function scanLogsCollection(): string
    {
        return (string) config('firebase.scan_logs_collection', 'scan_logs');
    }

    private function ticketsCollection(): string
    {
        return (string) config('firebase.tickets_collection', 'tickets');
    }

    private function attendanceDailyPath(string $scanDate, string $userId): string
    {
        return $this->attendanceDailyCollection().'/'.$scanDate.':'.$userId;
    }

    private function ticketPath(string $ticketId): string
    {
        return $this->ticketsCollection().'/'.$ticketId;
    }

    private function scanLogPath(string $scanId): string
    {
        return $this->scanLogsCollection().'/'.$scanId;
    }

    private function usingRest(): bool
    {
        return (string) config('firebase.transport', 'grpc') === 'rest';
    }

    private function client(): FirestoreClient
    {
        $client = $this->factory->make();

        if ($client === null) {
            throw new RuntimeException('Firestore storage is unavailable.');
        }

        return $client;
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

    private function documentIdFromName(string $documentName): string
    {
        $segments = array_values(array_filter(explode('/', trim($documentName, '/'))));

        return (string) end($segments);
    }
}
