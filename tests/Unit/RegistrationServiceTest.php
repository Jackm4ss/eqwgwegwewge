<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Auth\RegistrationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryUserRepository;
        $this->app->instance(UserRepositoryInterface::class, $this->repository);
    }

    public function test_register_issues_scanner_compatible_ticket_for_new_participant(): void
    {
        Mail::fake();

        $result = app(RegistrationService::class)->register([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'phone_number' => '+628123456789',
            'country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
            'traffic_source' => 'tiktok',
            'traffic_source_detail' => 'tiktok-bio',
            'traffic_medium' => 'social',
            'traffic_campaign' => 'songkran-2026',
            'traffic_referrer_host' => 'www.tiktok.com',
            'traffic_landing_path' => '/register?utm_source=tiktok',
            'traffic_captured_at' => '2026-03-30T08:15:00Z',
        ], '127.0.0.1');

        $ticket = $result['ticket'];
        $user = $result['user'];

        $this->assertSame('active', $user['account_status']);
        $this->assertSame('verified', $user['verification_status']);
        $this->assertSame($ticket['ticket_id'], $user['ticket_id']);
        $this->assertNotEmpty($user['ticket_ready_email_sent_at']);
        $this->assertSame(1, count($this->repository->tickets));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $ticket['ticket_code']);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $ticket['entry_code']);
        $this->assertSame(
            substr($ticket['entry_code'], 0, 4).'-'.substr($ticket['entry_code'], 4, 4),
            $ticket['entry_code_display'],
        );
        $this->assertSame('active', $ticket['status']);
        $this->assertSame('v1', $ticket['qr_version']);
        $this->assertSame('tiktok', $user['traffic_source']);
        $this->assertSame('tiktok-bio', $user['traffic_source_detail']);
        $this->assertSame('social', $user['traffic_medium']);
        $this->assertSame('songkran-2026', $user['traffic_campaign']);
        $this->assertSame('www.tiktok.com', $user['traffic_referrer_host']);
        $this->assertSame('/register?utm_source=tiktok', $user['traffic_landing_path']);
        $this->assertSame('2026-03-30T08:15:00Z', $user['traffic_captured_at']);

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($ticket): bool {
            return ($mail->ticket['ticket_code'] ?? null) === $ticket['ticket_code']
                && ($mail->ticket['entry_code_display'] ?? null) === $ticket['entry_code_display'];
        });
    }

    public function test_register_rejects_passport_for_malaysian_registrants(): void
    {
        Mail::fake();

        $this->expectException(ValidationException::class);

        app(RegistrationService::class)->register([
            'full_name' => 'Malaysia Passport User',
            'email' => 'malaysia-passport@example.com',
            'phone_country_code' => '+60',
            'phone_national_number' => '123456789',
            'phone_number' => '+60123456789',
            'country' => 'MY',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
        ], '127.0.0.1');
    }
}
