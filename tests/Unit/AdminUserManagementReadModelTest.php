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
}
