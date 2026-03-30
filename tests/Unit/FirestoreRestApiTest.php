<?php

namespace Tests\Unit;

use App\Services\Firebase\FirestoreRestApi;
use DateTimeImmutable;
use ReflectionMethod;
use Tests\TestCase;

class FirestoreRestApiTest extends TestCase
{
    public function test_encode_fields_uses_native_timestamp_value_for_datetime_objects(): void
    {
        $api = new FirestoreRestApi([
            'project_id' => 'event-songkran-festival',
            'database' => '(default)',
        ]);

        $method = new ReflectionMethod($api, 'encodeFields');
        $method->setAccessible(true);

        $encoded = $method->invoke($api, [
            'created_at' => new DateTimeImmutable('2026-03-24T02:18:00.258629Z'),
        ]);

        $this->assertSame(
            ['timestampValue' => '2026-03-24T02:18:00.258629Z'],
            $encoded['created_at'],
        );
    }

    public function test_parse_batch_get_response_supports_json_array_payloads(): void
    {
        $api = new FirestoreRestApi([
            'project_id' => 'event-songkran-festival',
            'database' => '(default)',
        ]);

        $responseBody = <<<'JSON'
[
  {
    "found": {
      "name": "projects/event-songkran-festival/databases/(default)/documents/users/user-123",
      "fields": {
        "email": {
          "stringValue": "tester@example.com"
        }
      }
    },
    "readTime": "2026-03-24T01:30:07.265919Z"
  }
]
JSON;

        $method = new ReflectionMethod($api, 'parseBatchGetResponse');
        $method->setAccessible(true);

        $results = $method->invoke($api, $responseBody, ['users/user-123']);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('users/user-123', $results);
        $this->assertSame(
            'projects/event-songkran-festival/databases/(default)/documents/users/user-123',
            $results['users/user-123']['name']
        );
    }

    public function test_decode_document_returns_iso_string_for_native_timestamp_fields(): void
    {
        $api = new FirestoreRestApi([
            'project_id' => 'event-songkran-festival',
            'database' => '(default)',
        ]);

        $decoded = $api->decodeDocument([
            'fields' => [
                'created_at' => [
                    'timestampValue' => '2026-03-24T02:18:00.258629Z',
                ],
                'email' => [
                    'stringValue' => 'tester@example.com',
                ],
            ],
        ]);

        $this->assertSame('2026-03-24T02:18:00.258629Z', $decoded['created_at']);
        $this->assertSame('tester@example.com', $decoded['email']);
    }

    public function test_parse_run_query_response_supports_streaming_json_lines(): void
    {
        $api = new FirestoreRestApi([
            'project_id' => 'event-songkran-festival',
            'database' => '(default)',
        ]);

        $responseBody = <<<'JSON'
{"document":{"name":"projects/event-songkran-festival/databases/(default)/documents/users/user-123","fields":{"email":{"stringValue":"tester@example.com"}}},"readTime":"2026-03-24T01:30:07.265919Z"}
{"document":{"name":"projects/event-songkran-festival/databases/(default)/documents/users/user-456","fields":{"email":{"stringValue":"admin@example.com"}}},"readTime":"2026-03-24T01:30:07.265919Z"}
JSON;

        $method = new ReflectionMethod($api, 'parseRunQueryResponse');
        $method->setAccessible(true);

        $results = $method->invoke($api, $responseBody);

        $this->assertCount(2, $results);
        $this->assertSame(
            'projects/event-songkran-festival/databases/(default)/documents/users/user-123',
            $results[0]['name'],
        );
        $this->assertSame(
            'admin@example.com',
            $results[1]['fields']['email']['stringValue'],
        );
    }

    public function test_parse_aggregation_count_response_reads_integer_value_from_stream(): void
    {
        $api = new FirestoreRestApi([
            'project_id' => 'event-songkran-festival',
            'database' => '(default)',
        ]);

        $responseBody = <<<'JSON'
{"result":{"aggregateFields":{"count":{"integerValue":"37"}}},"readTime":"2026-03-24T01:30:07.265919Z"}
JSON;

        $method = new ReflectionMethod($api, 'parseAggregationCountResponse');
        $method->setAccessible(true);

        $count = $method->invoke($api, $responseBody, 'count');

        $this->assertSame(37, $count);
    }
}
