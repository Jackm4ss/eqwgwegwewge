<?php

namespace Tests\Unit;

use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use App\Services\Tickets\TicketQrCodeService;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Query;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class AdminFirestoreRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'firebase.transport' => 'rest',
            'app.timezone' => 'Asia/Jakarta',
        ]);

        Carbon::setTestNow('2026-03-29 10:00:00');
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY);
        Mockery::close();

        parent::tearDown();
    }

    public function test_reset_qr_code_clears_current_attendance_gate_before_saving_ticket(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $userDocument = ['name' => 'users/user-123', 'fields' => []];
        $ticketDocument = ['name' => 'tickets/ticket-123', 'fields' => []];
        $user = [
            'user_id' => 'user-123',
            'ticket_id' => 'ticket-123',
        ];
        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET-123',
            'last_scanned_at' => '2026-03-29T08:15:00Z',
        ];
        $updatedTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET-123',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'updated_at' => '2026-03-29T10:00:00Z',
        ];
        $attendancePath = 'attendance_daily/'.hash('sha256', '2026-03-29:ticket-123');

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('txn-1');
        $restApi->shouldReceive('batchGet')->once()->with(['users/user-123'], 'txn-1')->andReturn([
            'users/user-123' => $userDocument,
        ]);
        $restApi->shouldReceive('batchGet')->once()->with(['tickets/ticket-123'], 'txn-1')->andReturn([
            'tickets/ticket-123' => $ticketDocument,
        ]);
        $restApi->shouldReceive('decodeDocument')->once()->with($userDocument)->andReturn($user);
        $restApi->shouldReceive('decodeDocument')->once()->with($ticketDocument)->andReturn($ticket);
        $timestamps->shouldReceive('normalizeFromStorage')->twice()->andReturnUsing(fn (array $payload): array => $payload);
        $ticketQrCodeService->shouldReceive('resetAttendanceAttributes')->once()->with($ticket)->andReturn($updatedTicket);
        $restApi->shouldReceive('makeDeleteWrite')->once()->with($attendancePath)->andReturn(['delete_attendance']);
        $timestamps->shouldReceive('prepareForStorage')->once()->with($updatedTicket)->andReturn($updatedTicket);
        $restApi->shouldReceive('makeSetWrite')->once()->with('tickets/ticket-123', $updatedTicket, true)->andReturn(['set_ticket']);
        $restApi->shouldReceive('commit')->once()->with([
            ['delete_attendance'],
            ['set_ticket'],
        ], 'txn-1');

        $result = $repository->resetQrCode('user-123');

        $this->assertSame($updatedTicket, $result['ticket']);
        $this->assertSame($user, $result['user']);
    }

    public function test_regenerate_qr_code_rotates_indexes_and_clears_current_attendance_gate(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $userDocument = ['name' => 'users/user-123', 'fields' => []];
        $ticketDocument = ['name' => 'tickets/ticket-123', 'fields' => []];
        $user = [
            'user_id' => 'user-123',
            'ticket_id' => 'ticket-123',
        ];
        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'OLDTICKET123',
            'entry_code' => 'ABCD2345',
            'entry_code_display' => 'ABCD-2345',
            'created_at' => '2026-03-20T08:00:00Z',
            'updated_at' => '2026-03-29T08:15:00Z',
            'last_scanned_at' => '2026-03-29T08:15:00Z',
        ];
        $regeneratedTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'NEWTICKET456',
            'entry_code' => 'WXYZ6789',
            'entry_code_display' => 'WXYZ-6789',
            'created_at' => '2026-03-20T08:00:00Z',
            'updated_at' => '2026-03-29T10:00:00Z',
            'regenerated_at' => '2026-03-29T10:00:00Z',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
        ];
        $attendancePath = 'attendance_daily/'.hash('sha256', '2026-03-29:ticket-123');
        $newTicketCodeIndexPath = 'ticket_code_index/'.hash('sha256', 'NEWTICKET456');
        $oldTicketCodeIndexPath = 'ticket_code_index/'.hash('sha256', 'OLDTICKET123');
        $newEntryCodeIndexPath = 'ticket_entry_code_index/'.hash('sha256', 'WXYZ6789');
        $oldEntryCodeIndexPath = 'ticket_entry_code_index/'.hash('sha256', 'ABCD2345');
        $newIndexPayload = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'NEWTICKET456',
            'entry_code' => 'WXYZ6789',
            'entry_code_display' => 'WXYZ-6789',
            'created_at' => '2026-03-20T08:00:00Z',
            'updated_at' => '2026-03-29T10:00:00Z',
        ];

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('txn-2');
        $restApi->shouldReceive('batchGet')->once()->with(['users/user-123'], 'txn-2')->andReturn([
            'users/user-123' => $userDocument,
        ]);
        $restApi->shouldReceive('batchGet')->once()->with(['tickets/ticket-123'], 'txn-2')->andReturn([
            'tickets/ticket-123' => $ticketDocument,
        ]);
        $restApi->shouldReceive('decodeDocument')->once()->with($userDocument)->andReturn($user);
        $restApi->shouldReceive('decodeDocument')->once()->with($ticketDocument)->andReturn($ticket);
        $timestamps->shouldReceive('normalizeFromStorage')->twice()->andReturnUsing(fn (array $payload): array => $payload);
        $ticketQrCodeService->shouldReceive('regenerateTicketAttributes')->once()->with($ticket)->andReturn($regeneratedTicket);
        $restApi->shouldReceive('makeSetWrite')->once()->with($newTicketCodeIndexPath, $newIndexPayload, false)->andReturn(['set_ticket_code_index']);
        $restApi->shouldReceive('makeSetWrite')->once()->with($newEntryCodeIndexPath, $newIndexPayload, false)->andReturn(['set_entry_code_index']);
        $restApi->shouldReceive('makeDeleteWrite')->once()->with($oldTicketCodeIndexPath)->andReturn(['delete_old_ticket_code_index']);
        $restApi->shouldReceive('makeDeleteWrite')->once()->with($oldEntryCodeIndexPath)->andReturn(['delete_old_entry_code_index']);
        $restApi->shouldReceive('makeDeleteWrite')->once()->with($attendancePath)->andReturn(['delete_attendance']);
        $timestamps->shouldReceive('prepareForStorage')->once()->with($regeneratedTicket)->andReturn($regeneratedTicket);
        $restApi->shouldReceive('makeSetWrite')->once()->with('tickets/ticket-123', $regeneratedTicket, true)->andReturn(['set_ticket']);
        $restApi->shouldReceive('commit')->once()->with([
            ['set_ticket_code_index'],
            ['set_entry_code_index'],
            ['delete_old_ticket_code_index'],
            ['delete_old_entry_code_index'],
            ['delete_attendance'],
            ['set_ticket'],
        ], 'txn-2');

        $result = $repository->regenerateQrCode('user-123');

        $this->assertSame($regeneratedTicket, $result['ticket']);
        $this->assertSame($user, $result['user']);
    }

    public function test_record_scanner_attendance_marks_admin_user_management_cache_stale_on_success(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 1],
            'filter_options' => [],
        ]);

        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $ticket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET-123',
            'entry_code_display' => 'ABCD-1234',
        ];
        $user = [
            'user_id' => 'user-123',
            'full_name' => 'Alya',
        ];
        $ticketDocument = ['name' => 'tickets/ticket-123', 'fields' => []];
        $scanLogInput = [
            'scanner_id' => 'scanner-post:gate-a',
            'scanner_name' => 'Gate A',
            'scanner_role' => 'staff',
            'scan_mode' => 'qr',
            'operator_admin_id' => 'admin-1',
            'operator_email' => 'staff@example.test',
            'scanned_at' => '2026-03-29T09:00:00Z',
            'scan_date' => '2026-03-29',
        ];
        $expectedStoredTicket = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'TICKET-123',
            'entry_code_display' => 'ABCD-1234',
            'attendance_status' => 'checked_in',
            'checked_in_at' => '2026-03-29T09:00:00Z',
            'last_scanned_at' => '2026-03-29T09:00:00Z',
            'updated_at' => '2026-03-29T09:00:00Z',
        ];
        $expectedScanLog = [
            'ticket_id' => 'ticket-123',
            'ticket_code' => 'TICKET-123',
            'user_id' => 'user-123',
            'entry_code_display' => 'ABCD-1234',
            'scanner_id' => 'scanner-post:gate-a',
            'scanner_name' => 'Gate A',
            'scanner_role' => 'staff',
            'scan_mode' => 'qr',
            'operator_admin_id' => 'admin-1',
            'operator_email' => 'staff@example.test',
            'ip_address' => null,
            'scanned_at' => '2026-03-29T09:00:00Z',
            'scan_date' => '2026-03-29',
            'result' => 'success',
        ];
        $attendancePayload = [
            'attendance_id' => hash('sha256', 'ticket-123:2026-03-29'),
            'ticket_id' => 'ticket-123',
            'ticket_code' => 'TICKET-123',
            'user_id' => 'user-123',
            'scan_date' => '2026-03-29',
            'scanner_id' => 'scanner-post:gate-a',
            'scanner_name' => 'Gate A',
            'scanner_role' => 'staff',
            'operator_admin_id' => 'admin-1',
            'operator_email' => 'staff@example.test',
            'scan_mode' => 'qr',
            'first_scanned_at' => '2026-03-29T09:00:00Z',
            'created_at' => '2026-03-29T09:00:00Z',
            'updated_at' => '2026-03-29T09:00:00Z',
        ];
        $attendancePath = 'attendance_daily/'.hash('sha256', '2026-03-29:ticket-123');

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('beginTransaction')->once()->andReturn('txn-3');
        $restApi->shouldReceive('batchGet')->once()->with([
            'tickets/ticket-123',
            $attendancePath,
        ], 'txn-3')->andReturn([
            'tickets/ticket-123' => $ticketDocument,
        ]);
        $restApi->shouldReceive('decodeDocument')->once()->with($ticketDocument)->andReturn($ticket);
        $timestamps->shouldReceive('normalizeFromStorage')->once()->with($ticket)->andReturn($ticket);
        $timestamps->shouldReceive('prepareForStorage')->once()->with($expectedStoredTicket)->andReturn($expectedStoredTicket);
        $restApi->shouldReceive('makeSetWrite')->once()->with('tickets/ticket-123', $expectedStoredTicket, true)->andReturn(['set_ticket']);
        $timestamps->shouldReceive('prepareForStorage')->once()->with($attendancePayload)->andReturn($attendancePayload);
        $restApi->shouldReceive('makeSetWrite')->once()->with($attendancePath, $attendancePayload, false)->andReturn(['set_attendance']);
        $timestamps->shouldReceive('prepareForStorage')->once()->with(Mockery::on(function (array $payload) use ($expectedScanLog): bool {
            if (! is_string($payload['scan_id'] ?? null) || trim((string) ($payload['scan_id'] ?? '')) === '') {
                return false;
            }

            foreach ($expectedScanLog as $key => $value) {
                if (($payload[$key] ?? null) !== $value) {
                    return false;
                }
            }

            return true;
        }))->andReturnUsing(fn (array $payload): array => $payload);
        $restApi->shouldReceive('makeSetWrite')->once()->with(
            Mockery::pattern('/^scan_logs\/[0-9A-HJKMNP-TV-Z]{26}$/'),
            Mockery::on(function (array $payload) use ($expectedScanLog): bool {
                if (! is_string($payload['scan_id'] ?? null) || trim((string) ($payload['scan_id'] ?? '')) === '') {
                    return false;
                }

                foreach ($expectedScanLog as $key => $value) {
                    if (($payload[$key] ?? null) !== $value) {
                        return false;
                    }
                }

                return true;
            }),
            false,
        )->andReturn(['set_scan_log']);
        $restApi->shouldReceive('commit')->once()->with([
            ['set_ticket'],
            ['set_attendance'],
            ['set_scan_log'],
        ], 'txn-3');

        $result = $repository->recordScannerAttendance($ticket, $user, $scanLogInput);

        $this->assertSame('success', $result['result']);
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
    }

    public function test_paginate_admin_activity_logs_uses_firestore_range_query_for_date_filters(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $expectedFrom = '2026-03-28T17:00:00.000000Z';
        $expectedTo = '2026-03-30T16:59:59.999999Z';

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('runQuery')
            ->once()
            ->withArgs(function (array $structuredQuery) use ($expectedFrom, $expectedTo): bool {
                $filters = data_get($structuredQuery, 'where.compositeFilter.filters', []);

                return data_get($structuredQuery, 'from.0.collectionId') === 'admin_activity_logs'
                    && data_get($structuredQuery, 'limit') === 20
                    && data_get($structuredQuery, 'offset') === 20
                    && data_get($filters, '0.fieldFilter.field.fieldPath') === 'created_at'
                    && data_get($filters, '0.fieldFilter.op') === 'GREATER_THAN_OR_EQUAL'
                    && data_get($filters, '0.fieldFilter.value.timestampValue') === $expectedFrom
                    && data_get($filters, '1.fieldFilter.field.fieldPath') === 'created_at'
                    && data_get($filters, '1.fieldFilter.op') === 'LESS_THAN_OR_EQUAL'
                    && data_get($filters, '1.fieldFilter.value.timestampValue') === $expectedTo
                    && data_get($structuredQuery, 'orderBy.0.field.fieldPath') === 'created_at'
                    && data_get($structuredQuery, 'orderBy.1.field.fieldPath') === '__name__';
            })
            ->andReturn([]);
        $restApi->shouldReceive('runCountQuery')
            ->once()
            ->withArgs(function (array $structuredQuery, string $alias) use ($expectedFrom, $expectedTo): bool {
                $filters = data_get($structuredQuery, 'where.compositeFilter.filters', []);

                return $alias === 'count'
                    && data_get($structuredQuery, 'from.0.collectionId') === 'admin_activity_logs'
                    && ! array_key_exists('limit', $structuredQuery)
                    && ! array_key_exists('offset', $structuredQuery)
                    && ! array_key_exists('orderBy', $structuredQuery)
                    && data_get($filters, '0.fieldFilter.value.timestampValue') === $expectedFrom
                    && data_get($filters, '1.fieldFilter.value.timestampValue') === $expectedTo;
            })
            ->andReturn(84);

        $result = $repository->paginateAdminActivityLogs([
            'from' => '2026-03-29',
            'to' => '2026-03-30',
        ], 2, 20);

        $this->assertSame([
            'items' => [],
            'total' => 84,
        ], $result);
    }

    public function test_paginate_scan_logs_uses_firestore_range_query_for_attendance_filters(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $expectedFrom = '2026-03-28T16:00:00.000000Z';
        $expectedTo = '2026-03-30T15:59:59.999999Z';

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('runQuery')
            ->once()
            ->withArgs(function (array $structuredQuery) use ($expectedFrom, $expectedTo): bool {
                $filters = data_get($structuredQuery, 'where.compositeFilter.filters', []);

                return data_get($structuredQuery, 'from.0.collectionId') === 'scan_logs'
                    && data_get($structuredQuery, 'limit') === 10
                    && data_get($structuredQuery, 'offset') === 10
                    && data_get($structuredQuery, 'orderBy.0.field.fieldPath') === 'scanned_at'
                    && data_get($structuredQuery, 'orderBy.1.field.fieldPath') === '__name__'
                    && data_get($filters, '0.fieldFilter.field.fieldPath') === 'scanner_name'
                    && data_get($filters, '0.fieldFilter.value.stringValue') === 'Gate AB'
                    && data_get($filters, '1.fieldFilter.field.fieldPath') === 'scanned_at'
                    && data_get($filters, '1.fieldFilter.op') === 'GREATER_THAN_OR_EQUAL'
                    && data_get($filters, '1.fieldFilter.value.timestampValue') === $expectedFrom
                    && data_get($filters, '2.fieldFilter.field.fieldPath') === 'scanned_at'
                    && data_get($filters, '2.fieldFilter.op') === 'LESS_THAN_OR_EQUAL'
                    && data_get($filters, '2.fieldFilter.value.timestampValue') === $expectedTo;
            })
            ->andReturn([]);
        $restApi->shouldReceive('runCountQuery')
            ->once()
            ->withArgs(function (array $structuredQuery, string $alias) use ($expectedFrom, $expectedTo): bool {
                $filters = data_get($structuredQuery, 'where.compositeFilter.filters', []);

                return $alias === 'count'
                    && data_get($structuredQuery, 'from.0.collectionId') === 'scan_logs'
                    && ! array_key_exists('limit', $structuredQuery)
                    && ! array_key_exists('offset', $structuredQuery)
                    && ! array_key_exists('orderBy', $structuredQuery)
                    && data_get($filters, '0.fieldFilter.value.stringValue') === 'Gate AB'
                    && data_get($filters, '1.fieldFilter.value.timestampValue') === $expectedFrom
                    && data_get($filters, '2.fieldFilter.value.timestampValue') === $expectedTo;
            })
            ->andReturn(42);

        $result = $repository->paginateScanLogs([
            'scanner_post' => 'Gate AB',
            'from' => '2026-03-29',
            'to' => '2026-03-30',
        ], 2, 10);

        $this->assertSame([
            'items' => [],
            'total' => 42,
        ], $result);
    }

    public function test_count_scan_logs_falls_back_to_collection_listing_when_rest_aggregation_fails(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $documents = [
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-100', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-200', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-300', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-400', 'fields' => []],
        ];
        $decodedRows = [
            [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
            ],
            [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T02:00:00.000000Z',
            ],
            [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T03:00:00.000000Z',
            ],
            [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-30T01:00:00.000000Z',
            ],
        ];

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('runCountQuery')
            ->once()
            ->andThrow(new \RuntimeException('Aggregation query failed.', 400));
        $restApi->shouldReceive('listAllDocuments')
            ->once()
            ->withArgs(fn (string $collectionPath, ?string $orderBy = null): bool => $collectionPath === 'scan_logs' && $orderBy === null)
            ->andReturn($documents);
        $restApi->shouldReceive('decodeDocument')
            ->times(4)
            ->andReturn(...$decodedRows);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(4)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $count = $repository->countScanLogs([
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ]);

        $this->assertSame(2, $count);
    }

    public function test_paginate_scan_logs_falls_back_to_collection_listing_when_rest_query_fails(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $documents = [
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-100', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-200', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-300', 'fields' => []],
            ['name' => 'projects/event-songkran-festival/databases/(default)/documents/scan_logs/scan-400', 'fields' => []],
        ];
        $decodedRows = [
            [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
                'ticket_code' => 'TICKET-100',
            ],
            [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T04:00:00.000000Z',
                'ticket_code' => 'TICKET-200',
            ],
            [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T03:00:00.000000Z',
                'ticket_code' => 'TICKET-300',
            ],
            [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T05:00:00.000000Z',
                'ticket_code' => 'TICKET-400',
            ],
        ];

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('runQuery')
            ->once()
            ->andThrow(new \RuntimeException('Structured query failed.', 400));
        $restApi->shouldReceive('listAllDocuments')
            ->once()
            ->withArgs(fn (string $collectionPath, ?string $orderBy = null): bool => $collectionPath === 'scan_logs' && $orderBy === null)
            ->andReturn($documents);
        $restApi->shouldReceive('decodeDocument')
            ->times(4)
            ->andReturn(...$decodedRows);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(4)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $result = $repository->paginateScanLogs([
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ], 1, 2);

        $this->assertSame(3, $result['total']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('scan-200', $result['items'][0]['__id']);
        $this->assertSame('scan-300', $result['items'][1]['__id']);
    }

    public function test_count_scan_logs_falls_back_to_collection_listing_when_grpc_aggregation_fails(): void
    {
        config(['firebase.transport' => 'grpc']);

        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $client = Mockery::mock(FirestoreClient::class);
        $filteredQuery = Mockery::mock();
        $fallbackCollection = Mockery::mock();

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService, $factory);

        $factory->shouldReceive('make')->atLeast()->once()->andReturn($client);
        $client->shouldReceive('collection')->with('scan_logs')->andReturn($filteredQuery, $fallbackCollection);
        $filteredQuery->shouldReceive('where')->once()->with('scanner_name', '=', 'Gate A')->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '>=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '<=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('count')->once()->andThrow(new \Exception('gRPC aggregation failed.', 9));
        $fallbackCollection->shouldReceive('documents')->once()->andReturn([
            $this->fakeDocumentSnapshot('scan-100', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-200', [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T02:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-300', [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T03:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-400', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-30T01:00:00.000000Z',
            ]),
        ]);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(4)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $count = $repository->countScanLogs([
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ]);

        $this->assertSame(2, $count);
    }

    public function test_paginate_scan_logs_falls_back_to_collection_listing_when_grpc_query_fails(): void
    {
        config(['firebase.transport' => 'grpc']);

        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $client = Mockery::mock(FirestoreClient::class);
        $filteredQuery = Mockery::mock();
        $fallbackCollection = Mockery::mock();

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService, $factory);

        $factory->shouldReceive('make')->atLeast()->once()->andReturn($client);
        $client->shouldReceive('collection')->with('scan_logs')->andReturn($filteredQuery, $fallbackCollection);
        $filteredQuery->shouldReceive('where')->once()->with('scanner_name', '=', 'Gate A')->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '>=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '<=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with('scanned_at', Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with(Query::DOCUMENT_ID, Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('offset')->once()->with(0)->andReturnSelf();
        $filteredQuery->shouldReceive('limit')->once()->with(2)->andReturnSelf();
        $filteredQuery->shouldReceive('documents')->once()->andThrow(new \Exception('gRPC structured query failed.', 9));
        $fallbackCollection->shouldReceive('documents')->once()->andReturn([
            $this->fakeDocumentSnapshot('scan-100', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
                'ticket_code' => 'TICKET-100',
            ]),
            $this->fakeDocumentSnapshot('scan-200', [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T04:00:00.000000Z',
                'ticket_code' => 'TICKET-200',
            ]),
            $this->fakeDocumentSnapshot('scan-300', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T03:00:00.000000Z',
                'ticket_code' => 'TICKET-300',
            ]),
            $this->fakeDocumentSnapshot('scan-400', [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T05:00:00.000000Z',
                'ticket_code' => 'TICKET-400',
            ]),
        ]);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(4)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $result = $repository->paginateScanLogs([
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ], 1, 2);

        $this->assertSame(3, $result['total']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('scan-200', $result['items'][0]['__id']);
        $this->assertSame('scan-300', $result['items'][1]['__id']);
    }

    public function test_query_scan_logs_falls_back_to_collection_listing_when_grpc_query_fails(): void
    {
        config(['firebase.transport' => 'grpc']);

        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $client = Mockery::mock(FirestoreClient::class);
        $filteredQuery = Mockery::mock();
        $fallbackCollection = Mockery::mock();

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService, $factory);

        $factory->shouldReceive('make')->atLeast()->once()->andReturn($client);
        $client->shouldReceive('collection')->with('scan_logs')->andReturn($filteredQuery, $fallbackCollection);
        $filteredQuery->shouldReceive('where')->once()->with('scanner_name', '=', 'Gate A')->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '>=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('where')->once()->with('scanned_at', '<=', Mockery::on(
            fn ($value): bool => $value instanceof \DateTimeInterface
        ))->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with('scanned_at', Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with(Query::DOCUMENT_ID, Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('documents')->once()->andThrow(new \Exception('gRPC query failed.', 9));
        $fallbackCollection->shouldReceive('documents')->once()->andReturn([
            $this->fakeDocumentSnapshot('scan-100', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-200', [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T04:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-300', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T03:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-400', [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T05:00:00.000000Z',
            ]),
        ]);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(4)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $result = $repository->queryScanLogs([
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ]);

        $this->assertCount(3, $result);
        $this->assertSame('scan-200', $result[0]['__id']);
        $this->assertSame('scan-300', $result[1]['__id']);
        $this->assertSame('scan-100', $result[2]['__id']);
    }

    public function test_latest_scan_log_falls_back_to_collection_listing_when_grpc_query_fails(): void
    {
        config(['firebase.transport' => 'grpc']);

        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $client = Mockery::mock(FirestoreClient::class);
        $filteredQuery = Mockery::mock();
        $fallbackCollection = Mockery::mock();

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService, $factory);

        $factory->shouldReceive('make')->atLeast()->once()->andReturn($client);
        $client->shouldReceive('collection')->with('scan_logs')->andReturn($filteredQuery, $fallbackCollection);
        $filteredQuery->shouldReceive('where')->once()->with('scanner_name', '=', 'Gate A')->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with('scanned_at', Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('orderBy')->once()->with(Query::DOCUMENT_ID, Query::DIR_DESCENDING)->andReturnSelf();
        $filteredQuery->shouldReceive('limit')->once()->with(1)->andReturnSelf();
        $filteredQuery->shouldReceive('documents')->once()->andThrow(new \Exception('gRPC latest query failed.', 9));
        $fallbackCollection->shouldReceive('documents')->once()->andReturn([
            $this->fakeDocumentSnapshot('scan-100', [
                'scanner_name' => 'Gate A',
                'result' => 'success',
                'scanned_at' => '2026-03-29T01:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-200', [
                'scanner_name' => 'Gate A',
                'result' => 'duplicate',
                'scanned_at' => '2026-03-29T04:00:00.000000Z',
            ]),
            $this->fakeDocumentSnapshot('scan-300', [
                'scanner_name' => 'Gate B',
                'result' => 'success',
                'scanned_at' => '2026-03-29T05:00:00.000000Z',
            ]),
        ]);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->times(3)
            ->andReturnUsing(fn (array $payload): array => $payload);

        $result = $repository->latestScanLog([
            'scanner_post' => 'Gate A',
        ]);

        $this->assertSame('scan-200', $result['__id']);
        $this->assertSame('2026-03-29T04:00:00.000000Z', $result['scanned_at']);
    }

    public function test_latest_non_future_scan_log_date_prefers_non_future_scan_log(): void
    {
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);

        $repository = $this->makeRepository($restApi, $timestamps, $ticketQrCodeService);

        $document = ['name' => 'scan_logs/scan-current', 'fields' => []];
        $expectedTo = '2026-03-29T15:59:59.999999Z';

        $restApi->shouldReceive('available')->atLeast()->once()->andReturnTrue();
        $restApi->shouldReceive('runQuery')
            ->once()
            ->withArgs(function (array $structuredQuery) use ($expectedTo): bool {
                $filters = data_get($structuredQuery, 'where.0.fieldFilter', [])
                    ?: data_get($structuredQuery, 'where.fieldFilter', []);

                return data_get($structuredQuery, 'from.0.collectionId') === 'scan_logs'
                    && data_get($structuredQuery, 'limit') === 1
                    && data_get($structuredQuery, 'orderBy.0.field.fieldPath') === 'scanned_at'
                    && data_get($filters, 'field.fieldPath') === 'scanned_at'
                    && data_get($filters, 'op') === 'LESS_THAN_OR_EQUAL'
                    && data_get($filters, 'value.timestampValue') === $expectedTo;
            })
            ->andReturn([$document]);
        $restApi->shouldReceive('decodeDocument')
            ->once()
            ->with($document)
            ->andReturn([
                'scan_date' => '2026-03-29',
                'scanned_at' => '2026-03-29T09:00:00Z',
            ]);
        $timestamps->shouldReceive('normalizeFromStorage')
            ->once()
            ->andReturnUsing(fn (array $payload): array => $payload);

        $this->assertSame('2026-03-29', $repository->latestNonFutureScanLogDate());
    }

    private function makeRepository(
        FirestoreRestApi $restApi,
        FirestoreTimestampNormalizer $timestamps,
        TicketQrCodeService $ticketQrCodeService,
        ?FirebaseClientFactory $factory = null,
    ): AdminFirestoreRepository {
        $factory ??= Mockery::mock(FirebaseClientFactory::class);
        $factory->shouldIgnoreMissing();

        return new AdminFirestoreRepository($factory, $restApi, $timestamps, $ticketQrCodeService);
    }

    private function fakeDocumentSnapshot(string $id, array $data): object
    {
        return new class($id, $data)
        {
            public function __construct(
                private readonly string $id,
                private readonly array $data,
            ) {}

            public function exists(): bool
            {
                return true;
            }

            public function data(): array
            {
                return $this->data;
            }

            public function id(): string
            {
                return $this->id;
            }
        };
    }
}
