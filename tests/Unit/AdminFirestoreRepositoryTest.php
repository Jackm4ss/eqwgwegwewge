<?php

namespace Tests\Unit;

use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class AdminFirestoreRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reset_qr_code_removes_today_attendance_lock_without_touching_scan_history(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');
        config([
            'firebase.transport' => 'rest',
            'firebase.attendance_daily_collection' => 'attendance_daily',
        ]);

        $factory = Mockery::mock(FirebaseClientFactory::class);
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $user = [
            'user_id' => 'user-123',
            'ticket_id' => 'ticket-123',
        ];
        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'last_valid_scan_date' => '2026-04-12',
            'checked_in_at' => '2026-04-12T09:00:00+07:00',
        ];
        $updatedTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'attendance_status' => 'not_checked_in',
        ];

        $restApi->shouldReceive('available')->once()->andReturn(true);
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('tx-1');
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['users/user-123'], 'tx-1')
            ->andReturn(['users/user-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['tickets/ticket-123'], 'tx-1')
            ->andReturn(['tickets/ticket-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('decodeDocument')
            ->twice()
            ->andReturn($user, $ticket);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->twice()
            ->andReturn($user, $ticket);
        $ticketQrCodeService->shouldReceive('resetAttendanceAttributes')
            ->once()
            ->with($ticket)
            ->andReturn($updatedTicket);
        $timestamps->shouldReceive('prepareForStorage')
            ->once()
            ->with($updatedTicket)
            ->andReturn($updatedTicket);
        $restApi->shouldReceive('makeSetWrite')
            ->once()
            ->with('tickets/ticket-123', $updatedTicket, true)
            ->andReturn(['write' => 'ticket']);
        $restApi->shouldReceive('makeDeleteWrite')
            ->once()
            ->with('attendance_daily/2026-04-12:user-123')
            ->andReturn(['delete' => 'attendance']);
        $restApi->shouldReceive('commit')
            ->once()
            ->with([['write' => 'ticket'], ['delete' => 'attendance']], 'tx-1');

        $repository = new AdminFirestoreRepository(
            $factory,
            $restApi,
            $timestamps,
            $ticketQrCodeService,
        );

        $result = $repository->resetQrCode('user-123');

        $this->assertSame('ticket-123', $result['ticket']['ticket_id']);
        $this->assertSame('not_checked_in', $result['ticket']['attendance_status']);
    }

    public function test_reset_qr_code_clears_last_scan_day_and_today_lock_when_dates_do_not_match(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');
        config([
            'firebase.transport' => 'rest',
            'firebase.attendance_daily_collection' => 'attendance_daily',
        ]);

        $factory = Mockery::mock(FirebaseClientFactory::class);
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $user = [
            'user_id' => 'user-123',
            'ticket_id' => 'ticket-123',
        ];
        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'last_valid_scan_date' => '2026-04-11',
            'checked_in_at' => '2026-04-11T09:00:00+07:00',
        ];
        $updatedTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'attendance_status' => 'not_checked_in',
        ];

        $restApi->shouldReceive('available')->once()->andReturn(true);
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('tx-1');
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['users/user-123'], 'tx-1')
            ->andReturn(['users/user-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['tickets/ticket-123'], 'tx-1')
            ->andReturn(['tickets/ticket-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('decodeDocument')
            ->twice()
            ->andReturn($user, $ticket);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->twice()
            ->andReturn($user, $ticket);
        $ticketQrCodeService->shouldReceive('resetAttendanceAttributes')
            ->once()
            ->with($ticket)
            ->andReturn($updatedTicket);
        $timestamps->shouldReceive('prepareForStorage')
            ->once()
            ->with($updatedTicket)
            ->andReturn($updatedTicket);
        $restApi->shouldReceive('makeSetWrite')
            ->once()
            ->with('tickets/ticket-123', $updatedTicket, true)
            ->andReturn(['write' => 'ticket']);
        $restApi->shouldReceive('makeDeleteWrite')
            ->once()
            ->with('attendance_daily/2026-04-11:user-123')
            ->andReturn(['delete' => 'attendance-yesterday']);
        $restApi->shouldReceive('makeDeleteWrite')
            ->once()
            ->with('attendance_daily/2026-04-12:user-123')
            ->andReturn(['delete' => 'attendance-today']);
        $restApi->shouldReceive('commit')
            ->once()
            ->with([['write' => 'ticket'], ['delete' => 'attendance-yesterday'], ['delete' => 'attendance-today']], 'tx-1');

        $repository = new AdminFirestoreRepository(
            $factory,
            $restApi,
            $timestamps,
            $ticketQrCodeService,
        );

        $result = $repository->resetQrCode('user-123');

        $this->assertSame('ticket-123', $result['ticket']['ticket_id']);
    }

    public function test_regenerate_qr_code_removes_today_attendance_lock_and_keeps_new_qr_flow_ready(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');
        config([
            'firebase.transport' => 'rest',
            'firebase.attendance_daily_collection' => 'attendance_daily',
        ]);

        $factory = Mockery::mock(FirebaseClientFactory::class);
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $user = [
            'user_id' => 'user-123',
            'ticket_id' => 'ticket-123',
        ];
        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'last_valid_scan_date' => '2026-04-12',
            'checked_in_at' => '2026-04-12T09:00:00+07:00',
            'qr_version' => 'v2',
        ];
        $updatedTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'qr_version' => 'v3',
            'attendance_status' => 'not_checked_in',
        ];

        $restApi->shouldReceive('available')->once()->andReturn(true);
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('tx-1');
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['users/user-123'], 'tx-1')
            ->andReturn(['users/user-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('batchGet')
            ->once()
            ->with(['tickets/ticket-123'], 'tx-1')
            ->andReturn(['tickets/ticket-123' => ['fields' => ['mock' => []]]]);
        $restApi->shouldReceive('decodeDocument')
            ->twice()
            ->andReturn($user, $ticket);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->twice()
            ->andReturn($user, $ticket);
        $ticketQrCodeService->shouldReceive('regenerateTicketAttributes')
            ->once()
            ->with($ticket)
            ->andReturn($updatedTicket);
        $timestamps->shouldReceive('prepareForStorage')
            ->once()
            ->with($updatedTicket)
            ->andReturn($updatedTicket);
        $restApi->shouldReceive('makeSetWrite')
            ->once()
            ->with('tickets/ticket-123', $updatedTicket, true)
            ->andReturn(['write' => 'ticket']);
        $restApi->shouldReceive('makeDeleteWrite')
            ->once()
            ->with('attendance_daily/2026-04-12:user-123')
            ->andReturn(['delete' => 'attendance']);
        $restApi->shouldReceive('commit')
            ->once()
            ->with([['write' => 'ticket'], ['delete' => 'attendance']], 'tx-1');

        $repository = new AdminFirestoreRepository(
            $factory,
            $restApi,
            $timestamps,
            $ticketQrCodeService,
        );

        $result = $repository->regenerateQrCode('user-123');

        $this->assertSame('v3', $result['ticket']['qr_version']);
        $this->assertSame('not_checked_in', $result['ticket']['attendance_status']);
    }
}
