<?php

namespace App\Services\Firebase;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirestoreRestApi
{
    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    private ?array $credentialsData = null;

    public function __construct(private readonly array $config)
    {
    }

    public function available(): bool
    {
        $credentialsPath = $this->credentialsPath();

        return $this->projectId() !== ''
            && $credentialsPath !== null
            && file_exists($credentialsPath);
    }

    public function getDocument(string $documentPath): ?array
    {
        $response = $this->http()->get($this->documentUrl($documentPath));

        if ($response->status() === 404) {
            return null;
        }

        $this->throwIfFailed($response, 'Failed to fetch Firestore document.');

        return $response->json();
    }

    public function batchGet(array $documentPaths, ?string $transaction = null): array
    {
        if ($documentPaths === []) {
            return [];
        }

        $payload = [
            'documents' => array_map(
                fn (string $documentPath) => $this->documentName($documentPath),
                $documentPaths,
            ),
        ];

        if ($transaction !== null) {
            $payload['transaction'] = $transaction;
        }

        $response = $this->http()->post($this->documentsActionUrl('batchGet'), $payload);

        $this->throwIfFailed($response, 'Failed to batch read Firestore documents.');

        return $this->parseBatchGetResponse($response->body(), $documentPaths);
    }

    public function beginTransaction(): string
    {
        $response = $this->http()->post($this->documentsActionUrl('beginTransaction'), new \stdClass());

        $this->throwIfFailed($response, 'Failed to begin Firestore transaction.');

        $transaction = (string) data_get($response->json(), 'transaction', '');

        if ($transaction === '') {
            throw new RuntimeException('Firestore transaction token was not returned.');
        }

        return $transaction;
    }

    public function commit(array $writes, ?string $transaction = null): void
    {
        $payload = ['writes' => $writes];

        if ($transaction !== null) {
            $payload['transaction'] = $transaction;
        }

        $response = $this->http()->post($this->documentsActionUrl('commit'), $payload);

        $this->throwIfFailed($response, 'Failed to commit Firestore transaction.');
    }

    public function rollbackQuietly(?string $transaction): void
    {
        if ($transaction === null || $transaction === '') {
            return;
        }

        try {
            $response = $this->http()->post($this->documentsActionUrl('rollback'), [
                'transaction' => $transaction,
            ]);

            $this->throwIfFailed($response, 'Failed to rollback Firestore transaction.');
        } catch (\Throwable) {
            // Rollback is best-effort only.
        }
    }

    public function makeSetWrite(string $documentPath, array $fields, ?bool $exists = null): array
    {
        $write = [
            'update' => [
                'name' => $this->documentName($documentPath),
                'fields' => $this->encodeFields($fields),
            ],
        ];

        if ($exists !== null) {
            $write['currentDocument'] = ['exists' => $exists];
        }

        return $write;
    }

    public function decodeDocument(array $document): array
    {
        return $this->decodeFields($document['fields'] ?? []);
    }

    private function http()
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(max(5, (int) ($this->config['timeout_seconds'] ?? 30)));
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null && time() < ($this->accessTokenExpiresAt - 60)) {
            return $this->accessToken;
        }

        $credentials = new ServiceAccountCredentials(
            [
                'https://www.googleapis.com/auth/cloud-platform',
                'https://www.googleapis.com/auth/datastore',
            ],
            $this->credentialsData(),
        );

        $token = $credentials->fetchAuthToken();
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Failed to fetch a Firestore REST access token.');
        }

        $this->accessToken = $accessToken;
        $this->accessTokenExpiresAt = time() + max(300, (int) ($token['expires_in'] ?? 3600));

        return $this->accessToken;
    }

    private function credentialsData(): array
    {
        if ($this->credentialsData !== null) {
            return $this->credentialsData;
        }

        $credentialsPath = $this->credentialsPath();
        if ($credentialsPath === null || ! file_exists($credentialsPath)) {
            throw new RuntimeException('Firestore credentials file is missing.');
        }

        $decoded = json_decode((string) file_get_contents($credentialsPath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Firestore credentials file is invalid.');
        }

        return $this->credentialsData = $decoded;
    }

    private function throwIfFailed(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $details = (string) data_get($response->json(), 'error.message', '');
        $status = (string) data_get($response->json(), 'error.status', '');

        $suffix = trim(implode(' ', array_filter([$status, $details])));
        $fullMessage = $suffix !== '' ? $message.' '.$suffix : $message;

        throw new RuntimeException($fullMessage, $response->status());
    }

    private function parseBatchGetResponse(string $body, array $documentPaths): array
    {
        $results = [];
        foreach ($documentPaths as $documentPath) {
            $results[$this->normalizeDocumentPath($documentPath)] = null;
        }

        $trimmedBody = trim($body);
        if ($trimmedBody === '') {
            return $results;
        }

        $decoded = json_decode($trimmedBody, true);

        if (is_array($decoded)) {
            $payloads = array_is_list($decoded) ? $decoded : [$decoded];
            foreach ($payloads as $payload) {
                if (is_array($payload)) {
                    $this->mergeBatchGetPayload($results, $payload);
                }
            }

            return $results;
        }

        $lines = preg_split('/\r\n|\r|\n/', $trimmedBody) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $payload = json_decode($line, true);
            if (is_array($payload)) {
                $this->mergeBatchGetPayload($results, $payload);
            }
        }

        return $results;
    }

    private function mergeBatchGetPayload(array &$results, array $payload): void
    {
        if (isset($payload['found']['name'])) {
            $path = $this->documentPathFromName((string) $payload['found']['name']);
            $results[$path] = $payload['found'];
            return;
        }

        if (isset($payload['missing'])) {
            $path = $this->documentPathFromName((string) $payload['missing']);
            $results[$path] = null;
        }
    }

    private function encodeFields(array $fields): array
    {
        $encoded = [];

        foreach ($fields as $key => $value) {
            $encoded[$key] = $this->encodeValue($value);
        }

        return $encoded;
    }

    private function encodeValue(mixed $value): array
    {
        return match (true) {
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            $value instanceof DateTimeInterface => ['timestampValue' => $this->formatTimestampValue($value)],
            is_string($value) => ['stringValue' => $value],
            is_null($value) => ['nullValue' => 'NULL_VALUE'],
            is_array($value) && $this->isAssociativeArray($value) => ['mapValue' => ['fields' => $this->encodeFields($value)]],
            is_array($value) => ['arrayValue' => ['values' => array_map(fn (mixed $item) => $this->encodeValue($item), $value)]],
            default => throw new RuntimeException('Unsupported Firestore field value encountered.'),
        };
    }

    private function decodeFields(array $fields): array
    {
        $decoded = [];

        foreach ($fields as $key => $value) {
            $decoded[$key] = $this->decodeValue($value);
        }

        return $decoded;
    }

    private function decodeValue(array $value): mixed
    {
        if (isset($value['timestampValue'])) {
            return (string) $value['timestampValue'];
        }

        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        }

        if (isset($value['booleanValue'])) {
            return (bool) $value['booleanValue'];
        }

        if (isset($value['integerValue'])) {
            return (int) $value['integerValue'];
        }

        if (isset($value['doubleValue'])) {
            return (float) $value['doubleValue'];
        }

        if (array_key_exists('nullValue', $value)) {
            return null;
        }

        if (isset($value['mapValue']['fields'])) {
            return $this->decodeFields($value['mapValue']['fields']);
        }

        if (isset($value['arrayValue']['values'])) {
            return array_map(
                fn (array $item) => $this->decodeValue($item),
                $value['arrayValue']['values'],
            );
        }

        return null;
    }

    private function isAssociativeArray(array $value): bool
    {
        return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
    }

    private function formatTimestampValue(DateTimeInterface $value): string
    {
        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.u\Z');
    }

    private function documentUrl(string $documentPath): string
    {
        return rtrim((string) ($this->config['api_base_url'] ?? 'https://firestore.googleapis.com/v1'), '/')
            .'/'.$this->databasePath().'/documents/'.$this->normalizeDocumentPath($documentPath);
    }

    private function documentsActionUrl(string $action): string
    {
        return rtrim((string) ($this->config['api_base_url'] ?? 'https://firestore.googleapis.com/v1'), '/')
            .'/'.$this->databasePath().'/documents:'.$action;
    }

    private function documentName(string $documentPath): string
    {
        return $this->databaseName().'/documents/'.$this->normalizeDocumentPath($documentPath);
    }

    private function documentPathFromName(string $documentName): string
    {
        $prefix = $this->databaseName().'/documents/';

        if (str_starts_with($documentName, $prefix)) {
            return substr($documentName, strlen($prefix));
        }

        return $documentName;
    }

    private function databasePath(): string
    {
        return 'projects/'.$this->projectId().'/databases/'.$this->database();
    }

    private function databaseName(): string
    {
        return $this->databasePath();
    }

    private function normalizeDocumentPath(string $documentPath): string
    {
        return trim($documentPath, '/');
    }

    private function projectId(): string
    {
        return trim((string) ($this->config['project_id'] ?? ''));
    }

    private function database(): string
    {
        return trim((string) ($this->config['database'] ?? '(default)'));
    }

    private function credentialsPath(): ?string
    {
        return FirebasePathResolver::credentialsPath(
            (string) ($this->config['credentials'] ?? '')
        );
    }
}
