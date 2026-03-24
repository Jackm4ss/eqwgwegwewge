<?php

namespace Tests\Unit;

use App\Services\Firebase\FirestoreRestApi;
use ReflectionMethod;
use Tests\TestCase;

class FirestoreRestApiTest extends TestCase
{
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
}
