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

    public function test_valid_verification_activates_account_creates_ticket_and_redirects_to_signed_ticket_page(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();

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
        $this->assertCount(1, $this->repository->tickets);

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($user, $ticket) {
            $mail->assertSeeInHtml('Entry Code');
            $mail->assertSeeInHtml($ticket['entry_code_display']);

            return $mail->hasTo($user['email']);
        });
    }

    public function test_verification_is_idempotent_and_does_not_create_duplicate_ticket(): void
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

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $this->get("/ticket/{$ticket['ticket_id']}")->assertForbidden();
    }

    public function test_signed_ticket_page_renders_ticket_details(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $signedTicketUrl = app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']);

        $this->get($signedTicketUrl)
            ->assertOk()
            ->assertSee(mb_strtoupper($user['full_name']))
            ->assertSee($user['identity_number'])
            ->assertSee($ticket['entry_code_display'])
            ->assertSee('Entry Code')
            ->assertSee('Thank you for your registration.');
    }

    public function test_signed_ticket_qr_route_renders_png(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validPayload())->assertCreated();
        $user = $this->repository->firstUser();

        $this->get($this->verificationUrl($user['user_id'], $user['email']))->assertRedirect();
        $ticket = $this->repository->findTicketByUserId($user['user_id']);

        $signedTicketQrUrl = app(TicketQrCodeService::class)->signedTicketQrUrl($ticket['ticket_id']);

        $this->get($signedTicketQrUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
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
