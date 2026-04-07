<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminUserManagementReadModel;
use App\Services\Admin\AdminUserManagementSyncStatusFactory;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class AdminUserManagementReadModelTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    public function test_rebuild_populates_projection_page_overview_and_sync_status(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
            'admin.user_management.read_model.fresh_within_seconds' => 15,
            'admin.user_management.read_model.degraded_after_seconds' => 60,
            'admin.user_management.read_model.fallback_after_seconds' => 300,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-10T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Joki',
                    'email' => 'joki@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'pending_verification',
                    'verification_status' => 'unverified',
                    'created_at' => '2026-04-09T08:00:00Z',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'attendance_status' => 'checked_in',
                    'checked_in_at' => '2026-04-10T08:30:00Z',
                    'status' => 'active',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-001',
                    'result' => 'success',
                    'scan_date' => '2026-04-10',
                    'scanned_at' => '2026-04-10T08:30:00Z',
                ],
            ]);

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $payload = $readModel->rebuild();
        $page = $readModel->page([]);
        $filteredPage = $readModel->page(['q' => 'joki']);

        $this->assertCount(2, $payload['directory']);
        $this->assertNotNull($page);
        $this->assertSame(2, $page['users']->total());
        $this->assertSame('user-1', $page['users']->items()[0]['user_id']);
        $this->assertSame(1, $page['users']->items()[0]['attendance_days_count']);
        $this->assertSame(11, $page['users']->items()[0]['attendance_total_days']);
        $this->assertSame(9, $page['users']->items()[0]['attendance_progress_percent']);
        $this->assertSame(2, $page['overview']['total_users']);
        $this->assertSame(1, $page['overview']['verified_users']);
        $this->assertSame(1, $page['overview']['checked_in_users']);
        $this->assertSame('fresh', $page['sync_status']['state']);

        $this->assertNotNull($filteredPage);
        $this->assertSame(1, $filteredPage['users']->total());
        $this->assertSame('user-2', $filteredPage['users']->items()[0]['user_id']);
        $this->assertTrue($readModel->supportsFilters([]));
        $this->assertTrue($readModel->supportsFilters(['q' => 'joki']));
    }

    public function test_remove_user_updates_cached_projection_without_reloading_firestore(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-10T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Joki',
                    'email' => 'joki@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-09T08:00:00Z',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $readModel->rebuild();
        $readModel->removeUser('user-1');
        $page = $readModel->page([]);

        $this->assertNotNull($page);
        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-2', $page['users']->items()[0]['user_id']);
        $this->assertSame(1, $page['overview']['total_users']);
    }

    public function test_page_can_filter_by_attendance_status_using_projection_rows(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-10T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Joki',
                    'email' => 'joki@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-09T08:00:00Z',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'attendance_status' => 'checked_in',
                    'status' => 'active',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $readModel->rebuild();
        $page = $readModel->page(['attendance_status' => 'checked_in']);

        $this->assertTrue($readModel->supportsFilters(['attendance_status' => 'checked_in']));
        $this->assertNotNull($page);
        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-1', $page['users']->items()[0]['user_id']);
        $this->assertSame(1, $page['overview']['checked_in_users']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][1]['count']);
    }

    public function test_page_can_filter_by_search_query_using_projection_rows(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-10T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Joki',
                    'email' => 'joki@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-09T08:00:00Z',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'attendance_status' => 'checked_in',
                    'status' => 'active',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $readModel->rebuild();
        $page = $readModel->page(['q' => 'joki']);

        $this->assertTrue($readModel->supportsFilters(['q' => 'joki']));
        $this->assertNotNull($page);
        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-2', $page['users']->items()[0]['user_id']);
    }

    public function test_refresh_meta_from_projection_updates_large_projection_overview_without_full_rebuild(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'cache.default' => 'array',
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
            'admin.user_management.inline_meta_sync_max_rows' => 1,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '901231101234',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-10T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_id' => 'ticket-2',
                    'full_name' => 'Joki',
                    'email' => 'joki@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'created_at' => '2026-04-09T08:00:00Z',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-001',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-002',
                    'attendance_status' => 'not_checked_in',
                    'status' => 'active',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-1')
            ->andReturn([
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'identity_type' => 'national_id',
                'identity_number' => '901231101234',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-10T08:00:00Z',
            ]);
        $repository->shouldReceive('findTicket')
            ->once()
            ->with('ticket-1')
            ->andReturn([
                'ticket_id' => 'ticket-1',
                'user_id' => 'user-1',
                'ticket_code' => 'TICKET-001',
                'attendance_status' => 'checked_in',
                'status' => 'active',
            ]);
        $repository->shouldReceive('findAttendanceDailyByUserIds')
            ->once()
            ->with(['user-1'])
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-001',
                    'result' => 'success',
                    'scan_date' => '2026-04-10',
                ],
            ]);

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $readModel->rebuild();
        $this->assertSame(0, $readModel->page([])['overview']['checked_in_users']);

        $readModel->syncUser('user-1');
        $this->assertSame(0, $readModel->page([])['overview']['checked_in_users']);

        $readModel->refreshMetaFromProjection();
        $this->assertSame(1, $readModel->page([])['overview']['checked_in_users']);
    }

    public function test_sync_user_skips_inline_meta_refresh_for_large_redis_projection(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
            'admin.user_management.inline_meta_sync_max_rows' => 2000,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-1')
            ->andReturn([
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'identity_type' => 'national_id',
                'identity_number' => '901231101234',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-10T08:00:00Z',
            ]);
        $repository->shouldReceive('findTicket')
            ->once()
            ->with('ticket-1')
            ->andReturn([
                'ticket_id' => 'ticket-1',
                'user_id' => 'user-1',
                'ticket_code' => 'TICKET-001',
                'attendance_status' => 'checked_in',
                'status' => 'active',
            ]);
        $repository->shouldReceive('findAttendanceDailyByUserIds')
            ->once()
            ->with(['user-1'])
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-001',
                    'scan_date' => '2026-04-10',
                ],
            ]);

        $redisStore = Mockery::mock(RedisStore::class);
        Cache::shouldReceive('getStore')->andReturn($redisStore);
        Cache::shouldReceive('add')->once()->andReturn(true);
        Cache::shouldReceive('forget')->once();
        Cache::shouldReceive('flush')->zeroOrMoreTimes();

        $redisConnection = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redisConnection);
        $redisConnection->shouldReceive('hset')->once();
        $redisConnection->shouldReceive('zadd')->once();
        $redisConnection->shouldReceive('zcard')->once()->andReturn(16016);
        $redisConnection->shouldReceive('set')
            ->once()
            ->withArgs(function (string $key, string $value): bool {
                return str_contains($key, 'admin:user-management:read-model:sync')
                    && str_contains($value, '"state":"fresh"');
            });
        $redisConnection->shouldNotReceive('hgetall');

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $readModel->syncUser('user-1');
    }

    public function test_page_without_filters_serves_live_overview_from_large_redis_projection(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
            'admin.user_management.inline_meta_sync_max_rows' => 1,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);

        $redisStore = Mockery::mock(RedisStore::class);
        Cache::shouldReceive('getStore')->zeroOrMoreTimes()->andReturn($redisStore);
        Cache::shouldReceive('flush')->zeroOrMoreTimes();

        $meta = [
            'overview' => [
                'total_users' => 1,
                'verified_users' => 1,
                'checked_in_users' => 0,
                'follow_up_users' => 0,
                'countries_count' => 1,
                'verified_rate' => 100,
                'checked_in_rate' => 0,
                'follow_up_rate' => 0,
            ],
            'filter_options' => [
                'countries' => [
                    ['value' => 'MY', 'label' => 'Malaysia', 'count' => 1],
                ],
                'verification_statuses' => [
                    ['value' => 'verified', 'label' => 'Verified', 'count' => 1],
                    ['value' => 'pending_verification', 'label' => 'Pending Verification', 'count' => 0],
                    ['value' => 'unverified', 'label' => 'Unverified', 'count' => 0],
                ],
                'identity_types' => [
                    ['value' => 'national_id', 'label' => 'Malaysia IC (MyKad)', 'count' => 1],
                    ['value' => 'passport', 'label' => 'Passport', 'count' => 0],
                ],
                'attendance_statuses' => [
                    ['value' => 'checked_in', 'label' => 'Checked In', 'count' => 0],
                    ['value' => 'not_checked_in', 'label' => 'Not Checked In', 'count' => 1],
                ],
                'email_typo_statuses' => [
                    ['value' => 'suspected', 'label' => 'Suspected Typo', 'count' => 0],
                    ['value' => 'clean', 'label' => 'Looks Valid', 'count' => 1],
                ],
            ],
        ];
        $sync = [
            'source' => 'read_model',
            'state' => 'fresh',
            'last_synced_at_utc' => '2026-04-10T09:00:00Z',
        ];
        $rows = [
            'user-1' => [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'identity_type' => 'national_id',
                'identity_number' => '901231101234',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'attendance_status' => 'checked_in',
                'ticket_code' => 'TICKET-001',
                'created_at' => '2026-04-10T08:00:00Z',
                'email_typo_status' => 'clean',
                'email_typo_suspected' => false,
                'email_typo_suggestion' => null,
                'email_typo_reason' => null,
            ],
            'user-2' => [
                'user_id' => 'user-2',
                'ticket_id' => 'ticket-2',
                'full_name' => 'Joki',
                'email' => 'joki@example.test',
                'country' => 'ID',
                'country_label' => 'Indonesia',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'attendance_status' => 'not_checked_in',
                'ticket_code' => 'TICKET-002',
                'created_at' => '2026-04-09T08:00:00Z',
                'email_typo_status' => 'clean',
                'email_typo_suspected' => false,
                'email_typo_suggestion' => null,
                'email_typo_reason' => null,
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
            ->with('admin:user-management:read-model:order:v1')
            ->andReturn(2);
        $redisConnection->shouldReceive('zrevrange')
            ->twice()
            ->andReturn(
                ['user-1', 'user-2'],
                [],
            );
        $redisConnection->shouldReceive('hmget')
            ->once()
            ->with('admin:user-management:read-model:rows:v1', ['user-1', 'user-2'])
            ->andReturn([
                json_encode($rows['user-1'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                json_encode($rows['user-2'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            ]);
        $redisConnection->shouldNotReceive('hgetall');

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $page = $readModel->page([]);

        $this->assertNotNull($page);
        $this->assertSame(2, $page['users']->total());
        $this->assertSame('user-1', $page['users']->items()[0]['user_id']);
        $this->assertSame(2, $page['overview']['total_users']);
        $this->assertSame(2, $page['overview']['verified_users']);
        $this->assertSame(1, $page['overview']['checked_in_users']);
        $this->assertSame(0, $page['overview']['follow_up_users']);
        $this->assertSame(0, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][1]['count']);
    }

    public function test_page_filters_redis_projection_without_loading_all_rows_into_memory(): void
    {
        CarbonImmutable::setTestNow('2026-04-10 09:00:00 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.event.start_date' => '2026-04-09',
            'admin.event.end_date' => '2026-04-19',
            'admin.user_management.read_model.enabled' => true,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);

        $redisStore = Mockery::mock(RedisStore::class);
        Cache::shouldReceive('getStore')->zeroOrMoreTimes()->andReturn($redisStore);
        Cache::shouldReceive('flush')->zeroOrMoreTimes();

        $meta = [
            'overview' => [
                'total_users' => 2,
                'verified_users' => 2,
                'checked_in_users' => 1,
                'follow_up_users' => 0,
                'countries_count' => 2,
                'verified_rate' => 100,
                'checked_in_rate' => 50,
                'follow_up_rate' => 0,
            ],
            'filter_options' => [
                'countries' => [
                    ['value' => 'ID', 'label' => 'Indonesia', 'count' => 1],
                    ['value' => 'MY', 'label' => 'Malaysia', 'count' => 1],
                ],
                'verification_statuses' => [
                    ['value' => 'verified', 'label' => 'Verified', 'count' => 2],
                    ['value' => 'pending_verification', 'label' => 'Pending Verification', 'count' => 0],
                    ['value' => 'unverified', 'label' => 'Unverified', 'count' => 0],
                ],
                'identity_types' => [
                    ['value' => 'national_id', 'label' => 'Malaysia IC (MyKad)', 'count' => 1],
                    ['value' => 'passport', 'label' => 'Passport', 'count' => 1],
                ],
                'attendance_statuses' => [
                    ['value' => 'checked_in', 'label' => 'Checked In', 'count' => 1],
                    ['value' => 'not_checked_in', 'label' => 'Not Checked In', 'count' => 1],
                ],
                'email_typo_statuses' => [
                    ['value' => 'suspected', 'label' => 'Suspected Typo', 'count' => 0],
                    ['value' => 'clean', 'label' => 'Looks Valid', 'count' => 2],
                ],
            ],
        ];
        $sync = [
            'source' => 'read_model',
            'state' => 'fresh',
            'last_synced_at_utc' => '2026-04-10T09:00:00Z',
        ];
        $rows = [
            'user-1' => [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'identity_type' => 'national_id',
                'identity_number' => '901231101234',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'attendance_status' => 'checked_in',
                'ticket_code' => 'TICKET-001',
                'created_at' => '2026-04-10T08:00:00Z',
                'email_typo_status' => 'clean',
                'email_typo_suspected' => false,
                'email_typo_suggestion' => null,
                'email_typo_reason' => null,
            ],
            'user-2' => [
                'user_id' => 'user-2',
                'ticket_id' => 'ticket-2',
                'full_name' => 'Joki',
                'email' => 'joki@example.test',
                'country' => 'ID',
                'country_label' => 'Indonesia',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'attendance_status' => 'not_checked_in',
                'ticket_code' => 'TICKET-002',
                'created_at' => '2026-04-09T08:00:00Z',
                'email_typo_status' => 'clean',
                'email_typo_suspected' => false,
                'email_typo_suggestion' => null,
                'email_typo_reason' => null,
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
        $redisConnection->shouldReceive('zrevrange')
            ->twice()
            ->andReturn(
                ['user-1', 'user-2'],
                [],
            );
        $redisConnection->shouldReceive('hmget')
            ->once()
            ->with('admin:user-management:read-model:rows:v1', ['user-1', 'user-2'])
            ->andReturn([
                json_encode($rows['user-1'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                json_encode($rows['user-2'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            ]);
        $redisConnection->shouldNotReceive('hgetall');

        $readModel = new AdminUserManagementReadModel(
            $repository,
            new AdminAnalyticsService,
            new AdminUserManagementSyncStatusFactory,
        );

        $page = $readModel->page(['q' => 'joki']);

        $this->assertNotNull($page);
        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-2', $page['users']->items()[0]['user_id']);
        $this->assertSame(1, $page['overview']['total_users']);
        $this->assertSame('fresh', $page['sync_status']['state']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(1, $page['filter_options']['attendance_statuses'][1]['count']);
    }
}
