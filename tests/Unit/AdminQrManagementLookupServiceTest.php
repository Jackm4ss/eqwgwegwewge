<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminQrManagementLookupService;
use App\Services\Tickets\TicketQrCodeService;
use Mockery;
use Tests\TestCase;

class AdminQrManagementLookupServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_lookup_builds_attendance_progress_from_participant_scan_logs(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')
            ->once()
            ->with('joki@example.test')
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Joki',
                'email' => 'joki@example.test',
                'country' => 'NZ',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
            ]);
        $users->shouldReceive('findTicketById')
            ->once()
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
                'attendance_status' => 'checked_in',
                'entry_code' => 'ABCD1234',
            ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findScanLogsByUserIds')
            ->once()
            ->with(['user-123'])
            ->andReturn([
                [
                    'user_id' => 'user-123',
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                    'scan_date' => '2026-04-09',
                    'result' => 'success',
                ],
                [
                    'user_id' => 'user-123',
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                    'scan_date' => '2026-04-10',
                    'result' => 'success',
                ],
                [
                    'user_id' => 'user-123',
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                    'scan_date' => '2026-04-10',
                    'result' => 'duplicate',
                ],
            ]);

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldReceive('signedTicketUrl')
            ->once()
            ->with('ticket-123')
            ->andReturn('https://example.test/ticket-123');

        $service = new AdminQrManagementLookupService(
            $users,
            $repository,
            new AdminAnalyticsService,
            $qrService,
        );

        $result = $service->lookup([
            'search_type' => 'email',
            'email' => 'joki@example.test',
        ]);

        $this->assertTrue($result['found']);
        $this->assertSame(2, $result['participant']['attendance_days_count']);
        $this->assertSame(11, $result['participant']['attendance_total_days']);
        $this->assertSame(18, $result['participant']['attendance_progress_percent']);
    }

    public function test_lookup_defaults_attendance_total_days_to_event_window_when_scan_logs_are_empty(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')
            ->once()
            ->with('joki@example.test')
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Joki',
                'email' => 'joki@example.test',
                'country' => 'NZ',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
            ]);
        $users->shouldReceive('findTicketById')
            ->once()
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
                'attendance_status' => 'not_checked_in',
                'entry_code' => 'ABCD1234',
            ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findScanLogsByUserIds')
            ->once()
            ->with(['user-123'])
            ->andReturn([]);

        $qrService = Mockery::mock(TicketQrCodeService::class);
        $qrService->shouldReceive('signedTicketUrl')
            ->once()
            ->with('ticket-123')
            ->andReturn('https://example.test/ticket-123');

        $service = new AdminQrManagementLookupService(
            $users,
            $repository,
            new AdminAnalyticsService,
            $qrService,
        );

        $result = $service->lookup([
            'search_type' => 'email',
            'email' => 'joki@example.test',
        ]);

        $this->assertTrue($result['found']);
        $this->assertSame(0, $result['participant']['attendance_days_count']);
        $this->assertSame(11, $result['participant']['attendance_total_days']);
        $this->assertSame(0, $result['participant']['attendance_progress_percent']);
    }
}
