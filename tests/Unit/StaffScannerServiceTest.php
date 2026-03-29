<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Staff\StaffScannerService;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StaffScannerServiceTest extends TestCase
{
    public function test_scan_returns_invalid_for_malformed_qr_payload(): void
    {
        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $analytics = Mockery::mock(AdminAnalyticsService::class);
        $ticketQrCodeService = app(TicketQrCodeService::class);

        $repository->shouldReceive('appendScanLog')
            ->once()
            ->withArgs(function (array $entry): bool {
                return ($entry['result'] ?? null) === 'invalid'
                    && ($entry['scanner_name'] ?? null) === 'Gate A';
            })
            ->andReturn([
                'result' => 'invalid',
            ]);
        $repository->shouldReceive('allScanLogs')
            ->once()
            ->andReturn([]);

        $analytics->shouldReceive('buildAttendanceOverview')
            ->once()
            ->with([], [])
            ->andReturn([
                'history' => [],
                'daily_attendance' => [],
                'scanner_activity' => [],
            ]);

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $result = $service->scan($operator, 'Gate A', 'not-a-valid-qr', '127.0.0.1');

        $this->assertSame('invalid', $result['status']);
    }

    public function test_scan_returns_success_for_valid_ticket_payload(): void
    {
        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $analytics = Mockery::mock(AdminAnalyticsService::class);
        $ticketQrCodeService = app(TicketQrCodeService::class);

        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET123',
            'entry_code_display' => 'ABCD-2345',
            'status' => 'active',
        ];
        $user = [
            'user_id' => 'user-123',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+628123456789',
            'country' => 'ID',
        ];
        $payload = $ticketQrCodeService->payloadForTicketCode('TICKET123');

        $repository->shouldReceive('findTicketByTicketCode')
            ->once()
            ->with('TICKET123')
            ->andReturn($ticket);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-123')
            ->andReturn($user);
        $repository->shouldReceive('recordScannerAttendance')
            ->once()
            ->andReturn([
                'result' => 'success',
                'ticket' => $ticket,
                'user' => $user,
            ]);
        $repository->shouldReceive('allScanLogs')
            ->once()
            ->andReturn([]);
        $analytics->shouldReceive('buildAttendanceOverview')
            ->once()
            ->with([], [])
            ->andReturn([
                'history' => [],
                'daily_attendance' => [],
                'scanner_activity' => [],
            ]);
        $analytics->shouldReceive('countryLabel')
            ->once()
            ->with('ID')
            ->andReturn('Indonesia');

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $result = $service->scan($operator, 'Gate A', $payload, '127.0.0.1');

        $this->assertSame('success', $result['status']);
        $this->assertSame('Test User', $result['participant']['full_name']);
        $this->assertSame('test@example.com', $result['participant']['email']);
        $this->assertSame('+628123456789', $result['participant']['phone_number']);
        $this->assertSame('Indonesia', $result['participant']['country_label']);
        $this->assertSame('ABCD-2345', $result['participant']['entry_code_display']);
    }

    public function test_manual_lookup_returns_resolution_token_for_active_ticket(): void
    {
        Cache::flush();

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $analytics = Mockery::mock(AdminAnalyticsService::class);
        $ticketQrCodeService = app(TicketQrCodeService::class);

        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET123',
            'entry_code' => 'ABCD2345',
            'entry_code_display' => 'ABCD-2345',
            'status' => 'active',
        ];
        $user = [
            'user_id' => 'user-123',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'country' => 'ID',
        ];

        $repository->shouldReceive('findTicketByEntryCode')
            ->once()
            ->with('ABCD2345')
            ->andReturn($ticket);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-123')
            ->andReturn($user);
        $analytics->shouldReceive('countryLabel')
            ->once()
            ->with('ID')
            ->andReturn('Indonesia');

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $result = $service->manualLookup($operator, 'Gate A', 'ABCD-2345');

        $this->assertTrue($result['found']);
        $this->assertNotEmpty($result['resolution_token']);
        $this->assertSame('Test User', $result['participant']['full_name']);
        $this->assertSame('+628123456789', $result['participant']['phone_number']);
        $this->assertSame('Indonesia', $result['participant']['country_label']);
        $this->assertSame('ABCD-2345', $result['participant']['entry_code_display']);
    }
}
