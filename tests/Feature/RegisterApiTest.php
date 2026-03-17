<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    public function test_register_requires_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'password', 'g-recaptcha-response']);
    }
}
