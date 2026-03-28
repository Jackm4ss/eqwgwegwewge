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

    public function test_valid_verification_activates_pending_account_creates_ticket_and_redirects_to_signed_ticket_page(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();
        $verificationUrl = $this->verificationUrl($user['user_id'], $user['email']);

        $response = $this->get($verificationUrl);

        $ticket = $this->repository->findTicketByUserId($user['user_id']);
        $expectedTicketUrl = app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']);

        $response->assertRedirect($expectedTicketUrl);

        $verifiedUser = $this->repository->findById($user['user_id']);

        $this->assertSame('active', $verifiedUser['account_status']);
        $this->assertSame('verified', $verifiedUser['verification_status']);
        $this->assertNotNull($verifiedUser['email_verified_at']);
        $this->assertNotNull($verifiedUser['ticket_ready_email_sent_at']);
        $this->assertNotNull($ticket);
        $this->assertSame('active', $ticket['status']);
        $this->assertSame('v2', $ticket['qr_version']);
        $this->assertSame('esf2', $ticket['qr_format']);
        $this->assertCount(1, $this->repository->tickets);

        Mail::assertQueued(TicketReadyMail::class, function (TicketReadyMail $mail) use ($user) {
            return $mail->hasTo($user['email']);
        });
    }

    public function test_verification_is_idempotent_and_does_not_create_duplicate_ticket(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();
        $verificationUrl = $this->verificationUrl($user['user_id'], $user['email']);

        $this->get($verificationUrl)->assertRedirect();
        $firstTicket = $this->repository->findTicketByUserId($user['user_id']);

        $this->get($verificationUrl)->assertRedirect(
            app(TicketQrCodeService::class)->signedTicketUrl($firstTicket['ticket_id'])
        );

        $secondTicket = $this->repository->findTicketByUserId($user['user_id']);

        $this->assertSame($firstTicket['ticket_id'], $secondTicket['ticket_id']);
        $this->assertCount(1, $this->repository->tickets);
        Mail::assertQueued(TicketReadyMail::class, 1);
    }

    public function test_invalid_or_expired_verification_link_returns_forbidden(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            [
                'id' => $user['user_id'],
                'hash' => sha1($user['email']),
            ],
        );

        $this->get($expiredUrl)->assertForbidden();
    }

    public function test_ticket_page_requires_valid_signature(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $this->get("/ticket/{$ticket['ticket_id']}")->assertForbidden();
    }

    public function test_signed_ticket_page_renders_ticket_details(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $signedTicketUrl = app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']);

        $this->get($signedTicketUrl)
            ->assertOk()
            ->assertSee('TEST USER')
            ->assertSee($user['identity_number'])
            ->assertSee('Download Ticket');
    }

    public function test_signed_ticket_qr_route_renders_png(): void
    {
        Mail::fake();

        $user = $this->createPendingUser();

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $signedTicketQrUrl = app(TicketQrCodeService::class)->signedTicketQrUrl($ticket['ticket_id']);

        $this->get($signedTicketQrUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function createPendingUser(): array
    {
        return $this->repository->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'phone_number' => '+628123456789',
            'country' => 'ID',
            'identity_country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
            'account_status' => 'pending_verification',
            'verification_status' => 'unverified',
            'email_verified_at' => null,
            'ticket_id' => null,
            'ticket_ready_email_sent_at' => null,
            'agreed_terms_at' => now()->toISOString(),
            'registered_ip' => '127.0.0.1',
        ]);
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
}
