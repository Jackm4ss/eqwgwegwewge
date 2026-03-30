<?php

namespace Tests\Unit;

use App\Services\Firebase\FirestoreTimestampNormalizer;
use DateTimeImmutable;
use Google\Cloud\Core\Timestamp;
use Tests\TestCase;

class FirestoreTimestampNormalizerTest extends TestCase
{
    public function test_prepare_for_storage_converts_timestamp_fields_to_datetime_instances(): void
    {
        $normalizer = new FirestoreTimestampNormalizer();

        $prepared = $normalizer->prepareForStorage([
            'created_at' => '2026-03-24T02:18:00.258629Z',
            'email' => 'tester@example.com',
            'ticket' => [
                'activated_at' => '2026-03-24T02:18:00.258629Z',
            ],
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $prepared['created_at']);
        $this->assertSame('tester@example.com', $prepared['email']);
        $this->assertInstanceOf(DateTimeImmutable::class, $prepared['ticket']['activated_at']);
    }

    public function test_normalize_from_storage_converts_firestore_timestamps_to_iso_strings(): void
    {
        $normalizer = new FirestoreTimestampNormalizer();

        $normalized = $normalizer->normalizeFromStorage([
            'created_at' => new Timestamp(new DateTimeImmutable('2026-03-24T02:18:00.258629Z')),
            'ticket' => [
                'activated_at' => new Timestamp(new DateTimeImmutable('2026-03-24T02:18:00.258629Z')),
            ],
        ]);

        $this->assertSame('2026-03-24T02:18:00.258629Z', $normalized['created_at']);
        $this->assertSame('2026-03-24T02:18:00.258629Z', $normalized['ticket']['activated_at']);
    }
}
