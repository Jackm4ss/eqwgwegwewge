<?php

namespace Tests\Unit;

use App\Mail\ParticipantUpdateMail;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminParticipantNotificationService;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class AdminParticipantNotificationServiceTest extends TestCase
{
    public function test_send_profile_updated_sends_email_when_tracked_fields_change(): void
    {
        Mail::fake();

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldReceive('signedTicketUrl')
            ->once()
            ->with('ticket-123')
            ->andReturn('https://example.test/tickets/ticket-123');

        $service = new AdminParticipantNotificationService(new AdminAnalyticsService, $qrService);

        $service->sendProfileUpdated(
            beforeUser: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'pending_verification',
                'verification_status' => 'unverified',
            ],
            afterUser: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ],
        );

        Mail::assertSent(ParticipantUpdateMail::class, function (ParticipantUpdateMail $mail): bool {
            return $mail->hasTo('alya@example.test')
                && $mail->updateType === 'profile_update'
                && count($mail->changes) === 2
                && $mail->ticketUrl === 'https://example.test/tickets/ticket-123';
        });
    }

    public function test_send_profile_updated_skips_email_when_no_tracked_change_exists(): void
    {
        Mail::fake();

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldNotReceive('signedTicketUrl');

        $service = new AdminParticipantNotificationService(new AdminAnalyticsService, $qrService);

        $payload = [
            'full_name' => 'Alya Putri',
            'email' => 'alya@example.test',
            'country' => 'ID',
            'account_status' => 'active',
            'verification_status' => 'verified',
        ];

        $service->sendProfileUpdated($payload, $payload, null);

        Mail::assertNothingSent();
    }

    public function test_send_qr_regenerated_sends_email_with_latest_qr_payload(): void
    {
        Mail::fake();

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldReceive('signedTicketUrl')
            ->once()
            ->with('ticket-123')
            ->andReturn('https://example.test/tickets/ticket-123');
        $qrService->shouldReceive('payloadForTicket')
            ->once()
            ->with([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-NEW',
                'qr_version' => 'v2',
            ])
            ->andReturn('payload-123');
        $qrService->shouldReceive('renderPngBinary')
            ->once()
            ->with('payload-123', 240)
            ->andReturn('qr-binary');

        $service = new AdminParticipantNotificationService(new AdminAnalyticsService, $qrService);

        $service->sendQrRegenerated(
            user: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-NEW',
                'qr_version' => 'v2',
            ],
        );

        Mail::assertSent(ParticipantUpdateMail::class, function (ParticipantUpdateMail $mail): bool {
            return $mail->hasTo('alya@example.test')
                && $mail->updateType === 'qr_regenerated'
                && $mail->ticketUrl === 'https://example.test/tickets/ticket-123'
                && $mail->qrPngBinary === 'qr-binary';
        });
    }

    public function test_send_participant_deleted_sends_email_without_ticket_link(): void
    {
        Mail::fake();

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldNotReceive('signedTicketUrl');
        $qrService->shouldNotReceive('payloadForTicket');
        $qrService->shouldNotReceive('renderPngBinary');

        $service = new AdminParticipantNotificationService(new AdminAnalyticsService, $qrService);

        $service->sendParticipantDeleted(
            user: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ],
        );

        Mail::assertSent(ParticipantUpdateMail::class, function (ParticipantUpdateMail $mail): bool {
            return $mail->hasTo('alya@example.test')
                && $mail->updateType === 'participant_deleted'
                && $mail->ticketUrl === null
                && $mail->qrPngBinary === null
                && ($mail->ticket['ticket_code'] ?? null) === 'TICKET-123';
        });
    }
}
