<?php

namespace Tests\Feature;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class ResendVerificationApiTest extends TestCase
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
        ]);
        $this->repository = new InMemoryUserRepository();
        $this->app->instance(UserRepositoryInterface::class, $this->repository);
    }

    public function test_resend_endpoint_resends_ticket_email_for_active_user(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $response = $this->postJson('/api/email/resend-verification', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()->assertJson([
            'message' => 'If your account exists, your ticket email has been sent.',
        ]);

        $user = $this->repository->firstUser();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $this->assertSame('active', $user['account_status']);
        $this->assertSame('verified', $user['verification_status']);
        $this->assertNotNull($ticket);
        $this->assertCount(1, $this->repository->tickets);
        Mail::assertSent(TicketReadyMail::class, 2);
    }

    public function test_resend_endpoint_activates_legacy_pending_user_and_sends_ticket_email(): void
    {
        Mail::fake();

        $user = $this->repository->create([
            'full_name' => 'Legacy Pending User',
            'identity_type' => 'passport',
            'identity_number' => 'L1234567',
            'email' => 'legacy@example.com',
            'phone_number' => '+60123456789',
            'phone_country_code' => '+60',
            'phone_national_number' => '123456789',
            'country' => 'MY',
            'identity_country' => 'MY',
            'account_status' => 'pending_verification',
            'verification_status' => 'unverified',
            'email_verified_at' => null,
            'ticket_id' => null,
            'ticket_ready_email_sent_at' => null,
            'agreed_terms_at' => now()->toISOString(),
            'registered_ip' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/email/resend-verification', [
            'email' => $user['email'],
        ]);

        $response->assertOk()->assertJson([
            'message' => 'If your account exists, your ticket email has been sent.',
        ]);

        $updatedUser = $this->repository->findById($user['user_id']);
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $this->assertSame('active', $updatedUser['account_status']);
        $this->assertSame('verified', $updatedUser['verification_status']);
        $this->assertNotNull($updatedUser['email_verified_at']);
        $this->assertNotNull($updatedUser['ticket_id']);
        $this->assertNotNull($updatedUser['ticket_ready_email_sent_at']);
        $this->assertNotNull($ticket);
        $this->assertSame($updatedUser['ticket_id'], $ticket['ticket_id']);

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($user) {
            return $mail->hasTo($user['email']);
        });
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Test User',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
            'email' => 'test@example.com',
            'phone_number' => '+628123456789',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'country' => 'MY',
            'agreeTerms' => true,
        ];
    }
}
