<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminAttendanceReadModel;
use App\Services\Admin\AdminAttendanceSyncStatusFactory;
use App\Services\Admin\AdminFirestoreRepository;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class AdminAttendanceReadModelTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    public function test_rebuild_populates_attendance_projection_page_overview_and_filters(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-10',
            'admin.attendance.read_model.enabled' => true,
            'admin.attendance.read_model.fresh_within_seconds' => 15,
            'admin.attendance.read_model.degraded_after_seconds' => 60,
            'admin.attendance.read_model.fallback_after_seconds' => 300,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allScanLogs')
            ->once()
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-001',
                    'entry_code_display' => 'ABCD-1234',
                    'scanner_name' => 'Gate A',
                    'scanner_role' => 'staff',
                    'scanner_id' => 'scanner-1',
                    'result' => 'success',
                    'scan_date' => '2026-04-10',
                    'scanned_at' => '2026-04-10T09:00:00Z',
                ],
                [
                    'scan_id' => 'scan-2',
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-001',
                    'entry_code_display' => 'ABCD-1234',
                    'scanner_name' => 'Gate A',
                    'scanner_role' => 'staff',
                    'scanner_id' => 'scanner-1',
                    'result' => 'duplicate',
                    'scan_date' => '2026-04-10',
                    'scanned_at' => '2026-04-10T09:05:00Z',
                ],
                [
                    'scan_id' => 'scan-3',
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'ticket_code' => 'TICKET-002',
                    'entry_code_display' => 'WXYZ-6789',
                    'scanner_name' => 'Gate B',
                    'scanner_role' => 'staff',
                    'scanner_id' => 'scanner-2',
                    'result' => 'invalid',
                    'scan_date' => '2026-04-10',
                    'scanned_at' => '2026-04-10T09:03:00Z',
                ],
            ]);
        $repository->shouldReceive('findUsersByIds')
            ->once()
            ->withArgs(function (array $userIds): bool {
                sort($userIds);

                return $userIds === ['user-1', 'user-2'];
            })
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'phone_number' => '+60123456789',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Rafi Hakim',
                    'email' => 'rafi@example.test',
                    'phone_number' => '+628123456789',
                    'country' => 'SG',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                ],
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->withArgs(function (array $ticketIds): bool {
                sort($ticketIds);

                return $ticketIds === ['ticket-1', 'ticket-2'];
            })
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'entry_code_display' => 'ABCD-1234',
                    'attendance_status' => 'checked_in',
                    'checked_in_at' => '2026-04-10T09:00:00Z',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'entry_code_display' => 'WXYZ-6789',
                    'attendance_status' => 'not_checked_in',
                    'checked_in_at' => null,
                ],
            ]);

        $readModel = new AdminAttendanceReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminAttendanceSyncStatusFactory,
        );

        $payload = $readModel->rebuild();
        $page = $readModel->page([]);
        $filteredPage = $readModel->page(['q' => 'rafi']);
        $repeatScanPage = $readModel->page(['scan_result' => 'duplicate']);
        $needsReviewPage = $readModel->page(['scan_result' => 'needs_review']);

        $this->assertCount(3, $payload['rows']);
        $this->assertNotNull($page);
        $this->assertSame(2, $page['rows']->total());
        $this->assertSame('Alya Putri', $page['rows']->items()[0]['full_name']);
        $this->assertSame('Gate A', $page['rows']->items()[0]['scanner_name']);
        $this->assertSame('duplicate', $page['rows']->items()[0]['latest_scan_result']);
        $this->assertSame(2, $page['overview']['total_attendance']);
        $this->assertSame(1, $page['overview']['checked_in']);
        $this->assertSame(1, $page['overview']['repeat_scans']);
        $this->assertSame(1, $page['overview']['needs_review']);
        $this->assertCount(2, $page['overview']['gate_counts']);
        $this->assertSame('Gate A', $page['overview']['gate_counts'][0]['label']);
        $this->assertSame(2, $page['overview']['gate_counts'][0]['count']);
        $this->assertSame('Malaysia', $page['filter_options']['countries'][0]['label']);
        $this->assertSame('Passport', $page['filter_options']['identity_types'][1]['label']);
        $this->assertSame('Checked In', $page['filter_options']['scan_results'][0]['label']);
        $this->assertSame('Needs Review', $page['filter_options']['scan_results'][1]['label']);
        $this->assertSame('Repeat Scans', $page['filter_options']['scan_results'][2]['label']);
        $this->assertSame('fresh', $page['sync_status']['state']);
        $this->assertTrue($readModel->supportsFilters([]));
        $this->assertTrue($readModel->supportsFilters(['q' => 'rafi']));
        $this->assertTrue($readModel->supportsFilters(['scan_result' => 'duplicate']));

        $this->assertNotNull($filteredPage);
        $this->assertSame(1, $filteredPage['rows']->total());
        $this->assertSame('Rafi Hakim', $filteredPage['rows']->items()[0]['full_name']);
        $this->assertSame(1, $filteredPage['overview']['needs_review']);
        $this->assertSame(0, $filteredPage['overview']['repeat_scans']);

        $this->assertNotNull($repeatScanPage);
        $this->assertSame(1, $repeatScanPage['rows']->total());
        $this->assertSame('Alya Putri', $repeatScanPage['rows']->items()[0]['full_name']);
        $this->assertSame('duplicate', $repeatScanPage['rows']->items()[0]['latest_scan_result']);

        $this->assertNotNull($needsReviewPage);
        $this->assertSame(1, $needsReviewPage['rows']->total());
        $this->assertSame('Rafi Hakim', $needsReviewPage['rows']->items()[0]['full_name']);
    }

    public function test_page_filters_redis_projection_without_loading_all_rows_into_memory(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-10',
            'admin.attendance.read_model.enabled' => true,
            'admin.attendance.read_model.fresh_within_seconds' => 15,
            'admin.attendance.read_model.degraded_after_seconds' => 60,
            'admin.attendance.read_model.fallback_after_seconds' => 300,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);

        $redisStore = Mockery::mock(RedisStore::class);
        Cache::shouldReceive('getStore')->zeroOrMoreTimes()->andReturn($redisStore);
        Cache::shouldReceive('flush')->zeroOrMoreTimes();

        $meta = [
            'overview' => [
                'total_attendance' => 2,
                'checked_in' => 1,
                'repeat_scans' => 1,
                'needs_review' => 1,
                'gate_counts' => [
                    ['label' => 'Gate A', 'count' => 2],
                    ['label' => 'Gate B', 'count' => 1],
                ],
            ],
            'filter_options' => [
                'countries' => [
                    ['value' => 'MY', 'label' => 'Malaysia', 'count' => 1],
                    ['value' => 'SG', 'label' => 'Singapore', 'count' => 1],
                ],
                'identity_types' => [
                    ['value' => 'national_id', 'label' => 'Malaysia IC (MyKad)', 'count' => 1],
                    ['value' => 'passport', 'label' => 'Passport', 'count' => 1],
                ],
                'attendance_statuses' => [
                    ['value' => 'checked_in', 'label' => 'Checked In', 'count' => 1],
                    ['value' => 'not_checked_in', 'label' => 'Not Checked In', 'count' => 1],
                ],
                'scan_results' => [
                    ['value' => 'success', 'label' => 'Checked In', 'count' => 0],
                    ['value' => 'needs_review', 'label' => 'Needs Review', 'count' => 1],
                    ['value' => 'duplicate', 'label' => 'Repeat Scans', 'count' => 1],
                ],
                'scan_posts' => [
                    ['value' => 'Gate A', 'label' => 'Gate A', 'count' => 2],
                    ['value' => 'Gate B', 'label' => 'Gate B', 'count' => 1],
                ],
            ],
        ];
        $sync = [
            'source' => 'read_model',
            'state' => 'fresh',
            'last_synced_at_utc' => '2026-04-10T09:00:00Z',
        ];
        $rows = [
            [
                'scan_id' => 'scan-1',
                'participant_key' => 'user:user-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'entry_code_display' => 'ABCD-1234',
                'attendance_days_count' => 1,
                'attendance_total_days' => 2,
                'attendance_progress_percent' => 50,
                'attendance_status' => 'checked_in',
                'checked_in_at' => '2026-04-10T09:00:00Z',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'scanner_id' => 'scanner-1',
                'scanned_at' => '2026-04-10T09:05:00Z',
                'scan_date' => '2026-04-10',
                'result' => 'duplicate',
                'identity_type' => 'national_id',
                'identity_number' => '901231101234',
                'phone_number' => '+60123456789',
                'search_blob' => 'alya putri abcd-1234 malaysia 901231101234',
            ],
            [
                'scan_id' => 'scan-2',
                'participant_key' => 'user:user-2',
                'full_name' => 'Rafi Hakim',
                'email' => 'rafi@example.test',
                'country' => 'SG',
                'country_label' => 'Singapore',
                'entry_code_display' => 'WXYZ-6789',
                'attendance_days_count' => 0,
                'attendance_total_days' => 2,
                'attendance_progress_percent' => 0,
                'attendance_status' => 'not_checked_in',
                'checked_in_at' => null,
                'scanner_name' => 'Gate B',
                'scanner_role' => 'staff',
                'scanner_id' => 'scanner-2',
                'scanned_at' => '2026-04-10T09:03:00Z',
                'scan_date' => '2026-04-10',
                'result' => 'invalid',
                'identity_type' => 'passport',
                'identity_number' => 'MYKAD-7788',
                'phone_number' => '+628123456789',
                'search_blob' => 'rafi hakim wxyz-6789 singapore mykad-7788',
            ],
        ];

        $redisConnection = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redisConnection);
        $redisConnection->shouldReceive('get')
            ->twice()
            ->andReturn(
                json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                json_encode($sync, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            );
        $redisConnection->shouldReceive('zcard')
            ->once()
            ->with('admin:attendance:read-model:order:v1')
            ->andReturn(2);
        $redisConnection->shouldReceive('zrevrange')
            ->twice()
            ->andReturn(
                ['scan-1', 'scan-2'],
                [],
            );
        $redisConnection->shouldReceive('hmget')
            ->once()
            ->with('admin:attendance:read-model:rows:v1', ['scan-1', 'scan-2'])
            ->andReturn([
                json_encode($rows[0], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                json_encode($rows[1], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            ]);
        $redisConnection->shouldNotReceive('hgetall');

        $readModel = new AdminAttendanceReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminAttendanceSyncStatusFactory,
        );

        $page = $readModel->page(['q' => 'MYKAD-7788', 'scan_result' => 'needs_review']);

        $this->assertTrue($readModel->supportsFilters(['q' => 'MYKAD-7788']));
        $this->assertNotNull($page);
        $this->assertSame(1, $page['rows']->total());
        $this->assertSame('Rafi Hakim', $page['rows']->items()[0]['full_name']);
        $this->assertSame(1, $page['overview']['total_attendance']);
        $this->assertSame(1, $page['overview']['needs_review']);
        $this->assertSame('Gate B', $page['overview']['gate_counts'][0]['label']);
        $this->assertSame('fresh', $page['sync_status']['state']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][1]['count']);
        $this->assertSame(1, $page['filter_options']['scan_results'][1]['count']);
    }
}
