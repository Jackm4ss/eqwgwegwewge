<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Mail\TicketReadyMail;
use App\Services\Auth\VerificationService;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class VerificationServiceTest extends TestCase
{
    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryUserRepository;
        $this->app->instance(UserRepositoryInterface::class, $this->repository);
    }

    public function test_verify_backfills_legacy_ticket_with_scanner_fields_without_rotating_existing_ticket_code(): void
    {
        Mail::fake();

        $user = $this->repository->create([
            'full_name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'phone_number' => '+628123456780',
            'country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'B1234567',
            'identity_country' => 'ID',
            'account_status' => 'pending_verification',
            'verification_status' => 'unverified',
            'email_verified_at' => null,
            'ticket_id' => 'legacy-ticket',
            'ticket_ready_email_sent_at' => null,
        ]);

        $this->repository->tickets['legacy-ticket'] = [
            'ticket_id' => 'legacy-ticket',
            'user_id' => $user['user_id'],
            'ticket_code' => 'legacycode123',
            'created_at' => '2026-03-01T10:00:00Z',
        ];

        $result = app(VerificationService::class)->verify($user['user_id'], sha1($user['email']));

        $this->assertNotNull($result);

        $ticket = $this->repository->findTicketById('legacy-ticket');
        $updatedUser = $this->repository->findById($user['user_id']);

        $this->assertSame('legacy-ticket', $ticket['ticket_id']);
        $this->assertSame(strtoupper('legacycode123'), $ticket['ticket_code']);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $ticket['entry_code']);
        $this->assertSame(
            substr($ticket['entry_code'], 0, 4).'-'.substr($ticket['entry_code'], 4, 4),
            $ticket['entry_code_display'],
        );
        $this->assertSame('active', $ticket['status']);
        $this->assertSame('v1', $ticket['qr_version']);
        $this->assertSame('not_checked_in', $ticket['attendance_status']);
        $this->assertSame('legacy-ticket', $updatedUser['ticket_id']);
        $this->assertSame('verified', $updatedUser['verification_status']);
        $this->assertNotEmpty($updatedUser['ticket_ready_email_sent_at']);
        $this->assertCount(1, $this->repository->tickets);

        Mail::assertSent(TicketReadyMail::class, function (TicketReadyMail $mail) use ($ticket): bool {
            return ($mail->ticket['ticket_id'] ?? null) === $ticket['ticket_id']
                && ($mail->ticket['ticket_code'] ?? null) === $ticket['ticket_code']
                && ($mail->ticket['entry_code_display'] ?? null) === $ticket['entry_code_display'];
        });
    }
}
