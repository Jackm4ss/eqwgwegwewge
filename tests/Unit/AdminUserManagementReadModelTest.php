<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminUserManagementReadModel;
use App\Services\Admin\AdminUserManagementSyncStatusFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
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

        $this->assertNull($filteredPage);
        $this->assertTrue($readModel->supportsFilters([]));
        $this->assertFalse($readModel->supportsFilters(['q' => 'joki']));
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
}
