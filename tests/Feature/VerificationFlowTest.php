<?php

namespace Tests\Feature;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class VerificationFlowTest extends TestCase
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

    public function test_existing_verification_link_redirects_to_already_issued_ticket_page(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $verificationUrl = $this->verificationUrl($user['user_id'], $user['email']);

        $response = $this->get($verificationUrl);

        $expectedTicketUrl = app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']);

        $response->assertRedirect($expectedTicketUrl);

        $sameUser = $this->repository->findById($user['user_id']);

        $this->assertSame('active', $sameUser['account_status']);
        $this->assertSame('verified', $sameUser['verification_status']);
        $this->assertNotNull($sameUser['email_verified_at']);
        $this->assertNotNull($sameUser['ticket_ready_email_sent_at']);
        $this->assertNotNull($ticket);
        $this->assertSame('active', $ticket['status']);
        $this->assertCount(1, $this->repository->tickets);

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($user) {
            return $mail->hasTo($user['email']);
        });
    }

    public function test_verification_link_is_idempotent_and_does_not_create_duplicate_ticket(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();
        $verificationUrl = $this->verificationUrl($user['user_id'], $user['email']);

        $this->get($verificationUrl)->assertRedirect();
        $firstTicket = $this->repository->findTicketByUserId($user['user_id']);

        $this->get($verificationUrl)->assertRedirect(
            app(TicketQrCodeService::class)->signedTicketUrl($firstTicket['ticket_id'])
        );

        $secondTicket = $this->repository->findTicketByUserId($user['user_id']);

        $this->assertSame($firstTicket['ticket_id'], $secondTicket['ticket_id']);
        $this->assertCount(1, $this->repository->tickets);
        Mail::assertSent(TicketReadyMail::class, 1);
    }

    public function test_invalid_or_expired_verification_link_returns_forbidden(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();

        $tamperedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            [
                'id' => $user['user_id'],
                'hash' => sha1($user['email']),
            ],
        );

        $this->get($tamperedUrl)->assertForbidden();
    }

    public function test_ticket_page_requires_valid_signature(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $this->get("/ticket/{$ticket['ticket_id']}")->assertForbidden();
    }

    public function test_signed_ticket_page_renders_ticket_details(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $signedTicketUrl = app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']);

        $this->get($signedTicketUrl)
            ->assertOk()
            ->assertSee($ticket['ticket_code'])
            ->assertSee($user['email']);
    }

    private function verificationUrl(string $userId, string $email): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addDay(),
            [
                'id' => $userId,
                'hash' => sha1($email),
            ],
        );
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
