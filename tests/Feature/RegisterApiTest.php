<?php

namespace Tests\Feature;

use App\Contracts\UserRepositoryInterface;
use App\Mail\VerifyRegistrationMail;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->repository = new InMemoryUserRepository();
        $this->app->instance(UserRepositoryInterface::class, $this->repository);
    }

    public function test_register_requires_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'full_name',
                'email',
                'phone_number',
                'country',
                'identity_number',
                'agreeTerms',
            ]);
    }

    public function test_register_creates_pending_user_and_indexes_and_sends_verification_mail(): void
    {
        Mail::fake();

        $payload = $this->validPayload();

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Registration successful. Verification email has been sent.',
                'status' => 'pending_verification',
            ]);

        $user = $this->repository->firstUser();

        $this->assertNotNull($user);
        $this->assertSame('pending_verification', $user['account_status']);
        $this->assertSame('unverified', $user['verification_status']);
        $this->assertNull($user['ticket_id']);
        $this->assertSame('test@example.com', $user['email']);
        $this->assertSame('A1234567', $user['identity_number']);
        $this->assertTrue($this->repository->emailIndexExists($payload['email']));
        $this->assertTrue($this->repository->identityIndexExists($payload['identity_number']));

        Mail::assertSent(VerifyRegistrationMail::class, function (VerifyRegistrationMail $mail) use ($payload) {
            return $mail->hasTo(strtolower($payload['email']));
        });
    }

    public function test_register_rejects_duplicate_email_with_structured_error(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $duplicatePayload = array_merge($this->validPayload(), [
            'identity_number' => 'A7654321',
        ]);

        $response = $this->postJson('/api/register', $duplicatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertCount(1, $this->repository->users);
        $this->assertCount(0, $this->repository->tickets);
    }

    public function test_register_rejects_duplicate_identity_number_with_structured_error(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $duplicatePayload = array_merge($this->validPayload(), [
            'email' => 'other@example.com',
        ]);

        $response = $this->postJson('/api/register', $duplicatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_number']);

        $this->assertCount(1, $this->repository->users);
        $this->assertCount(0, $this->repository->tickets);
    }

    public function test_register_success_response_matches_frontend_contract(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure(['message', 'status'])
            ->assertJsonMissing(['redirect']);
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+628123456789',
            'country' => 'ID',
            'identity_number' => 'A1234567',
            'agreeTerms' => true,
        ];
    }
}
