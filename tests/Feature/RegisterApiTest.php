<?php

namespace Tests\Feature;

use App\Contracts\UserRepositoryInterface;
use App\Jobs\SendTicketReadyMailJob;
use App\Mail\TicketReadyMail;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        config([
            'services.recaptcha.enabled' => false,
            'services.recaptcha.site_key' => null,
            'services.recaptcha.secret_key' => null,
            'registration.redis_mode' => 'disabled',
        ]);
        $this->repository = new InMemoryUserRepository;
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
                'identity_type',
                'identity_number',
                'agreeTerms',
            ]);
    }

    public function test_register_creates_ticket_ready_user_and_sends_ticket_email(): void
    {
        Mail::fake();

        $payload = $this->validPayload();

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Registration successful. Your QR ticket has been sent to your email.',
                'status' => 'ticket_ready',
                'email_sent' => true,
            ]);

        $user = $this->repository->firstUser();

        $this->assertNotNull($user);
        $this->assertSame('active', $user['account_status']);
        $this->assertSame('verified', $user['verification_status']);
        $this->assertNotNull($user['ticket_id']);
        $this->assertNotEmpty($user['ticket_ready_email_sent_at']);
        $this->assertSame('test@example.com', $user['email']);
        $this->assertSame('+62', $user['phone_country_code']);
        $this->assertSame('8123456789', $user['phone_national_number']);
        $this->assertSame('+628123456789', $user['phone_number']);
        $this->assertSame('passport', $user['identity_type']);
        $this->assertSame('A1234567', $user['identity_number']);
        $this->assertTrue($this->repository->emailIndexExists($payload['email']));
        $this->assertTrue($this->repository->identityIndexExists(
            $payload['identity_type'],
            $payload['country'],
            $payload['identity_number'],
        ));

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($payload) {
            return $mail->hasTo(strtolower($payload['email']));
        });
    }

    public function test_register_rejects_duplicate_email_with_structured_error(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $duplicatePayload = array_merge($this->validPayload(), [
            'identity_number' => 'A7654321',
            'phone_national_number' => '8123456790',
        ]);

        $response = $this->postJson('/api/register', $duplicatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertCount(1, $this->repository->users);
        $this->assertCount(1, $this->repository->tickets);
    }

    public function test_register_rejects_duplicate_identity_number_with_structured_error(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $duplicatePayload = array_merge($this->validPayload(), [
            'email' => 'other@example.com',
            'phone_national_number' => '8123456791',
        ]);

        $response = $this->postJson('/api/register', $duplicatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_number']);

        $this->assertCount(1, $this->repository->users);
        $this->assertCount(1, $this->repository->tickets);
    }

    public function test_register_rejects_duplicate_phone_number_with_structured_error(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $duplicatePayload = array_merge($this->validPayload(), [
            'email' => 'other@example.com',
            'identity_number' => 'A7654321',
        ]);

        $response = $this->postJson('/api/register', $duplicatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);

        $this->assertCount(1, $this->repository->users);
        $this->assertCount(1, $this->repository->tickets);
    }

    public function test_register_allows_same_identity_number_for_different_identity_types(): void
    {
        Mail::fake();

        $this->postJson('/api/register', array_merge($this->validPayload(), [
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
        ]))->assertCreated();

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'email' => 'mykad@example.com',
            'country' => 'MY',
            'identity_type' => 'national_id',
            'identity_number' => 'A1234567',
            'phone_national_number' => '8123456792',
        ]));

        $response->assertCreated();
        $this->assertCount(2, $this->repository->users);
    }

    public function test_register_allows_same_passport_number_for_different_countries(): void
    {
        Mail::fake();

        $this->postJson('/api/register', array_merge($this->validPayload(), [
            'identity_type' => 'passport',
            'country' => 'ID',
            'identity_number' => 'A1234567',
        ]))->assertCreated();

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'email' => 'singapore@example.com',
            'identity_type' => 'passport',
            'country' => 'SG',
            'identity_number' => 'A1234567',
            'phone_national_number' => '8123456793',
        ]));

        $response->assertCreated();
        $this->assertCount(2, $this->repository->users);
    }

    public function test_register_rejects_invalid_identity_type(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'identity_type' => 'mykad',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_type']);
    }

    public function test_register_rejects_national_id_for_non_malaysian_registrants(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'country' => 'ID',
            'identity_type' => 'national_id',
            'identity_number' => '3174010101010101',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_type']);

        $this->assertCount(0, $this->repository->users);
    }

    public function test_register_rejects_passport_for_malaysian_registrants(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'country' => 'MY',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_type']);

        $this->assertCount(0, $this->repository->users);
    }

    public function test_register_success_response_matches_frontend_contract(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure(['message', 'status', 'delivery_status', 'email_sent', 'ticket_url', 'ticket_qr_url', 'ticket_code'])
            ->assertJsonMissing(['redirect']);
    }

    public function test_register_queues_ticket_email_when_registration_redis_mode_is_required(): void
    {
        Mail::fake();
        Queue::fake();

        config([
            'registration.redis_mode' => 'required',
            'queue.default' => 'redis',
            'cache.default' => 'redis',
        ]);

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated()
            ->assertJson([
                'message' => 'Registration successful. Your ticket is ready and the email copy is being prepared now.',
                'status' => 'ticket_ready',
                'delivery_status' => 'queued',
                'email_sent' => true,
            ]);

        $user = $this->repository->firstUser();

        $this->assertNotNull($user);
        $this->assertNotEmpty($user['ticket_ready_email_queued_at']);
        $this->assertNull($user['ticket_ready_email_sent_at']);

        Queue::assertPushed(SendTicketReadyMailJob::class, function (SendTicketReadyMailJob $job) use ($user): bool {
            return $job->userId === $user['user_id'];
        });

        Mail::assertNothingSent();
    }

    public function test_register_accepts_legacy_combined_phone_number_payload(): void
    {
        Mail::fake();

        $payload = $this->validPayload();
        unset($payload['phone_country_code'], $payload['phone_national_number']);
        $payload['phone_number'] = '+628123456789';

        $this->postJson('/api/register', $payload)->assertCreated();

        $user = $this->repository->firstUser();

        $this->assertSame('+628123456789', $user['phone_number']);
        $this->assertArrayNotHasKey('phone_country_code', $user);
        $this->assertArrayNotHasKey('phone_national_number', $user);
    }

    public function test_register_persists_first_touch_traffic_attribution(): void
    {
        Mail::fake();

        $payload = array_merge($this->validPayload(), [
            'traffic_source' => 'threads',
            'traffic_source_detail' => 'threads-bio',
            'traffic_medium' => 'social',
            'traffic_campaign' => 'songkran-launch',
            'traffic_referrer_host' => 'www.threads.net',
            'traffic_landing_path' => '/register?utm_source=threads&utm_campaign=songkran-launch',
            'traffic_captured_at' => '2026-03-30T08:15:00Z',
        ]);

        $this->postJson('/api/register', $payload)->assertCreated();

        $user = $this->repository->firstUser();

        $this->assertSame('threads', $user['traffic_source']);
        $this->assertSame('threads-bio', $user['traffic_source_detail']);
        $this->assertSame('social', $user['traffic_medium']);
        $this->assertSame('songkran-launch', $user['traffic_campaign']);
        $this->assertSame('www.threads.net', $user['traffic_referrer_host']);
        $this->assertSame('/register?utm_source=threads&utm_campaign=songkran-launch', $user['traffic_landing_path']);
        $this->assertSame('2026-03-30T08:15:00Z', $user['traffic_captured_at']);
    }

    public function test_register_requires_recaptcha_token_when_recaptcha_is_enabled(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recaptcha_token']);

        Http::assertNothingSent();
    }

    public function test_register_rejects_invalid_recaptcha_token_when_enabled(): void
    {
        Mail::fake();

        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'recaptcha_token' => 'invalid-token',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recaptcha_token']);

        $this->assertCount(0, $this->repository->users);
    }

    public function test_register_accepts_valid_recaptcha_token_when_enabled(): void
    {
        Mail::fake();

        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->postJson('/api/register', array_merge($this->validPayload(), [
            'recaptcha_token' => 'valid-token',
        ]));

        $response->assertCreated()
            ->assertJson([
                'message' => 'Registration successful. Your QR ticket has been sent to your email.',
                'status' => 'ticket_ready',
                'email_sent' => true,
            ]);

        $this->assertCount(1, $this->repository->users);
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
            'agreeTerms' => true,
        ];
    }
}
