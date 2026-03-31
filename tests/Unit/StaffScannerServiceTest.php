<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Staff\StaffScannerService;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StaffScannerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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
                'scan_id' => 'scan-invalid',
                'result' => 'invalid',
                'scanner_name' => 'Gate A',
                'ticket_code' => null,
                'entry_code_display' => null,
                'scanned_at' => '2026-03-31T09:00:00Z',
            ]);
        $repository->shouldReceive('countScanLogs')
            ->times(4)
            ->andReturnUsing(function (array $filters): int {
                if (($filters['result'] ?? null) === 'invalid') {
                    return 1;
                }

                if (($filters['result'] ?? null) !== null) {
                    return 0;
                }

                return 1;
            });

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $result = $service->scan($operator, 'Gate A', 'not-a-valid-qr', '127.0.0.1');

        $this->assertSame('invalid', $result['status']);
        $this->assertSame('scan-invalid', $result['activity_item']['scan_id']);
        $this->assertSame('Gate A', $result['activity_item']['scanner_post']);
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
                'log' => [
                    'scan_id' => 'scan-success',
                    'result' => 'success',
                    'scanner_name' => 'Gate A',
                    'ticket_code' => 'TICKET123',
                    'entry_code_display' => 'ABCD-2345',
                    'scanned_at' => '2026-03-31T09:01:00Z',
                    'participant_snapshot' => [
                        'full_name' => 'Test User',
                        'email' => 'test@example.com',
                        'phone_number' => '+628123456789',
                        'country' => 'ID',
                        'country_label' => 'Indonesia',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                    ],
                ],
            ]);
        $repository->shouldReceive('countScanLogs')
            ->times(4)
            ->andReturnUsing(function (array $filters): int {
                return match ($filters['result'] ?? null) {
                    'success' => 1,
                    'duplicate' => 0,
                    'invalid' => 0,
                    default => 1,
                };
            });
        $analytics->shouldReceive('countryLabel')
            ->atLeast()
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
        $this->assertSame('scan-success', $result['activity_item']['scan_id']);
        $this->assertSame('TICKET123', $result['activity_item']['ticket_code']);
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

    public function test_history_uses_paginated_scan_logs_and_snapshot_before_legacy_fallback(): void
    {
        Carbon::setTestNow('2026-03-31 09:15:00');
        config(['admin.event.timezone' => 'Asia/Jakarta']);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $analytics = Mockery::mock(AdminAnalyticsService::class);
        $ticketQrCodeService = app(TicketQrCodeService::class);

        $repository->shouldReceive('paginateScanLogs')
            ->once()
            ->with([
                'scanner_post' => 'Gate A',
                'from' => '2026-03-31',
                'to' => '2026-03-31',
            ], 2, 5)
            ->andReturn([
                'items' => [
                    [
                        'scan_id' => 'scan-snapshot',
                        'result' => 'success',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                        'scanner_name' => 'Gate A',
                        'scanned_at' => '2026-03-31T09:10:00Z',
                        'participant_snapshot' => [
                            'full_name' => 'Snapshot User',
                            'email' => 'snapshot@example.com',
                            'phone_number' => '+628123456789',
                            'country' => 'ID',
                            'country_label' => 'Indonesia',
                            'ticket_code' => 'TICKET123',
                            'entry_code_display' => 'ABCD-2345',
                        ],
                    ],
                    [
                        'scan_id' => 'scan-legacy',
                        'result' => 'duplicate',
                        'ticket_id' => 'ticket-legacy',
                        'user_id' => 'user-legacy',
                        'ticket_code' => 'TICKET456',
                        'entry_code_display' => 'WXYZ-6789',
                        'scanner_name' => 'Gate A',
                        'scanned_at' => '2026-03-31T09:05:00Z',
                    ],
                ],
                'total' => 21,
            ]);
        $repository->shouldReceive('findTicket')
            ->once()
            ->with('ticket-legacy')
            ->andReturn([
                'ticket_id' => 'ticket-legacy',
                'user_id' => 'user-legacy',
                'ticket_code' => 'TICKET456',
                'entry_code_display' => 'WXYZ-6789',
            ]);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-legacy')
            ->andReturn([
                'user_id' => 'user-legacy',
                'full_name' => 'Legacy User',
                'email' => 'legacy@example.com',
                'phone_number' => '+66999999999',
                'country' => 'TH',
            ]);
        $analytics->shouldReceive('countryLabel')
            ->times(1)
            ->with('TH')
            ->andReturn('Thailand');

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $history = $service->history($operator, 'Gate A', 2, 5);

        $this->assertSame([
            'page' => 2,
            'per_page' => 5,
            'total' => 21,
            'has_more' => true,
            'scope_date' => '2026-03-31',
        ], $history['meta']);
        $this->assertCount(2, $history['items']);
        $this->assertSame('scan-snapshot', $history['items'][0]['scan_id']);
        $this->assertSame('Snapshot User', $history['items'][0]['participant']['full_name']);
        $this->assertSame('scan-legacy', $history['items'][1]['scan_id']);
        $this->assertSame('Thailand', $history['items'][1]['participant']['country_label']);
    }

    public function test_stats_uses_scoped_count_queries_for_today(): void
    {
        Carbon::setTestNow('2026-03-31 09:15:00');
        config(['admin.event.timezone' => 'Asia/Jakarta']);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $analytics = Mockery::mock(AdminAnalyticsService::class);
        $ticketQrCodeService = app(TicketQrCodeService::class);

        $expectedBase = [
            'scanner_post' => 'Gate A',
            'from' => '2026-03-31',
            'to' => '2026-03-31',
        ];

        $repository->shouldReceive('countScanLogs')
            ->times(4)
            ->andReturnUsing(function (array $filters) use ($expectedBase): int {
                $this->assertSame($expectedBase['scanner_post'], $filters['scanner_post'] ?? null);
                $this->assertSame($expectedBase['from'], $filters['from'] ?? null);
                $this->assertSame($expectedBase['to'], $filters['to'] ?? null);

                return match ($filters['result'] ?? null) {
                    'success' => 12,
                    'duplicate' => 4,
                    'invalid' => 3,
                    default => 19,
                };
            });

        $service = new StaffScannerService($repository, $analytics, $ticketQrCodeService);
        $operator = new Admin([
            'id' => 99,
            'email' => 'scanner01@songkran.local',
            'role' => 'scanner',
        ]);

        $stats = $service->stats($operator, 'Gate A');

        $this->assertSame([
            'total_scans' => 19,
            'successful_scans' => 12,
            'duplicate_scans' => 4,
            'invalid_scans' => 3,
        ], $stats);
    }
}
