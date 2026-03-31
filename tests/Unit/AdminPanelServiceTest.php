<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminParticipantNotificationService;
use App\Services\Scanner\ScannerGateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class AdminPanelServiceTest extends TestCase
{
    public function test_optimized_user_management_meta_counts_only_explicit_checked_in_tickets(): void
    {
        Cache::forget('admin:user-management:meta:v2');

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with([], 1, 10)
            ->andReturn([
                'items' => [],
                'total' => 0,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with([])
            ->andReturn([]);
        $repository->shouldReceive('findScanLogsByUserIds')
            ->once()
            ->with([])
            ->andReturn([]);
        $repository->shouldReceive('countUsers')
            ->andReturnUsing(function (array $filters = []): int {
                if ($filters === []) {
                    return 5;
                }

                if ($filters === ['verification_status' => 'verified']) {
                    return 0;
                }

                if ($filters === ['verification_status' => 'verified', 'account_status' => 'active']) {
                    return 0;
                }

                if (array_key_exists('country', $filters)) {
                    return 0;
                }

                return 0;
            });
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(0);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $page = $service->userManagementPage([]);

        $this->assertSame(0, $page['overview']['checked_in_users']);
        $this->assertSame(0, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(5, $page['filter_options']['attendance_statuses'][1]['count']);
    }

    public function test_activity_logs_uses_optimized_firestore_pagination_when_text_search_is_empty(): void
    {
        $filters = [
            'from' => '2026-03-29',
            'page' => 2,
            'per_page' => 500,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateAdminActivityLogs')
            ->once()
            ->with($filters, 2, 100)
            ->andReturn([
                'items' => [[
                    'admin_email' => 'admin@example.test',
                    'action_type' => 'ticket_updated',
                    'created_at' => '2026-03-29T08:00:00Z',
                ]],
                'total' => 250,
            ]);
        $repository->shouldNotReceive('queryAdminActivityLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $logs = $service->activityLogs($filters);

        $this->assertSame(250, $logs->total());
        $this->assertSame(100, $logs->perPage());
        $this->assertSame(2, $logs->currentPage());
        $this->assertSame('admin@example.test', $logs->items()[0]['admin_email']);
    }

    public function test_activity_logs_falls_back_to_in_memory_text_search_when_query_is_present(): void
    {
        $filters = [
            'q' => 'delete',
            'from' => '2026-03-29',
            'page' => 1,
            'per_page' => 25,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('queryAdminActivityLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'admin_email' => 'admin@example.test',
                    'action_type' => 'delete_user',
                    'target_type' => 'user',
                    'target_id' => 'user-1',
                    'created_at' => '2026-03-29T08:00:00Z',
                    'metadata' => [],
                ],
                [
                    'admin_email' => 'admin@example.test',
                    'action_type' => 'ticket_updated',
                    'target_type' => 'ticket',
                    'target_id' => 'ticket-1',
                    'created_at' => '2026-03-29T07:00:00Z',
                    'metadata' => [],
                ],
            ]);
        $repository->shouldNotReceive('paginateAdminActivityLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $logs = $service->activityLogs($filters);

        $this->assertSame(1, $logs->total());
        $this->assertSame('delete_user', $logs->items()[0]['action_type']);
    }

    public function test_attendance_data_hydrates_participant_details_for_history_cards(): void
    {
        config(['scanner.posts' => ['Gate AB']]);

        $log = [
            'scan_id' => 'scan-1',
            'ticket_id' => 'ticket-123',
            'ticket_code' => '01KMYH3W10ECD5BV9F8APYPHTZ',
            'user_id' => 'user-123',
            'scanner_name' => 'Gate AB',
            'scanner_role' => 'staff',
            'scanner_id' => 'scanner-post:gate-ab',
            'scanned_at' => '2026-03-30T05:15:38Z',
            'scan_date' => '2026-03-30',
            'result' => 'duplicate',
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('latestNonFutureScanLogDate')
            ->once()
            ->andReturn('2026-03-30');
        $repository->shouldReceive('paginateScanLogs')
            ->once()
            ->with([
                'scanner_post' => 'Gate AB',
                'from' => '2026-03-30',
                'to' => '2026-03-30',
            ], 1, 10)
            ->andReturn([
                'items' => [$log],
                'total' => 1,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with(['ticket-123'])
            ->andReturn([
                [
                    'ticket_id' => 'ticket-123',
                    'user_id' => 'user-123',
                    'ticket_code' => '01KMYH3W10ECD5BV9F8APYPHTZ',
                    'entry_code_display' => '2RCA-GYXF',
                ],
            ]);
        $repository->shouldReceive('findUsersByIds')
            ->once()
            ->with(['user-123'])
            ->andReturn([
                [
                    'user_id' => 'user-123',
                    'full_name' => 'wegwegwegweg',
                    'email' => 'wegwegwegweg@gmail.com',
                    'phone_number' => '+603298592389',
                    'country' => 'MY',
                ],
            ]);
        $repository->shouldReceive('queryScanLogs')
            ->once()
            ->with([
                'scanner_post' => 'Gate AB',
                'from' => '2026-03-30',
                'to' => '2026-03-30',
            ])
            ->andReturn([$log]);
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $attendance = $service->attendanceData(['scanner_post' => 'Gate AB']);
        $history = $attendance['history']->items();

        $this->assertCount(1, $history);
        $this->assertSame('2RCA-GYXF', $history[0]['entry_code_display']);
        $this->assertSame('wegwegwegweg@gmail.com', $history[0]['participant']['email']);
        $this->assertSame('wegwegwegweg', $history[0]['participant']['full_name']);
        $this->assertSame('+603298592389', $history[0]['participant']['phone_number']);
        $this->assertSame('Malaysia', $history[0]['participant']['country_label']);
        $this->assertSame('2RCA-GYXF', $history[0]['participant']['entry_code_display']);
        $this->assertCount(1, $attendance['daily_attendance']);
        $this->assertSame('Gate AB', $attendance['scanner_activity'][0]['scanner_name']);
        $this->assertSame('Gate AB', $attendance['scan_post_options'][0]['value']);
    }

    public function test_attendance_data_defaults_to_latest_non_future_scan_date_when_opened_without_date_filters(): void
    {
        CarbonImmutable::setTestNow('2026-03-30 12:00:00');

        try {
            config(['scanner.posts' => ['Gate AB']]);

            $log = [
                'scan_id' => 'scan-current',
                'ticket_id' => 'ticket-current',
                'ticket_code' => 'CURRENT-1',
                'user_id' => 'user-current',
                'scanner_name' => 'Gate AB',
                'scanner_role' => 'staff',
                'scanner_id' => 'scanner-post:gate-ab',
                'scanned_at' => '2026-03-30T05:15:38Z',
                'scan_date' => '2026-03-30',
                'result' => 'duplicate',
            ];

            $repository = Mockery::mock(AdminFirestoreRepository::class);
            $repository->shouldReceive('latestNonFutureScanLogDate')
                ->once()
                ->andReturn('2026-03-30');
            $repository->shouldReceive('paginateScanLogs')
                ->once()
                ->with([
                    'from' => '2026-03-30',
                    'to' => '2026-03-30',
                ], 1, 10)
                ->andReturn([
                    'items' => [$log],
                    'total' => 1,
                ]);
            $repository->shouldReceive('findTicketsByIds')
                ->once()
                ->with(['ticket-current'])
                ->andReturn([]);
            $repository->shouldReceive('findUsersByIds')
                ->once()
                ->with(['user-current'])
                ->andReturn([]);
            $repository->shouldReceive('queryScanLogs')
                ->once()
                ->with([
                    'from' => '2026-03-30',
                    'to' => '2026-03-30',
                ])
                ->andReturn([$log]);
            $repository->shouldNotReceive('allScanLogs');
            $repository->shouldNotReceive('allUsers');
            $repository->shouldNotReceive('allTickets');

            $notifications = Mockery::mock(AdminParticipantNotificationService::class);
            $notifications->shouldIgnoreMissing();

            $service = $this->makeService($repository, $notifications, ['Gate AB']);

            $attendance = $service->attendanceData([]);
            $history = $attendance['history']->items();

            $this->assertCount(1, $history);
            $this->assertSame('2026-03-30', $history[0]['scan_date']);
            $this->assertSame('2026-03-30T05:15:38Z', $history[0]['scanned_at']);
            $this->assertCount(1, $attendance['daily_attendance']);
            $this->assertSame('2026-03-30', $attendance['daily_attendance'][0]['scan_date']);
            $this->assertCount(1, $attendance['scanner_activity']);
            $this->assertSame('Gate AB', $attendance['scanner_activity'][0]['scanner_name']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_export_rows_for_admin_logs_only_queries_activity_logs_dataset(): void
    {
        $filters = ['from' => '2026-03-29'];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('queryAdminActivityLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'admin_email' => 'admin@example.test',
                    'action_type' => 'ticket_updated',
                    'target_type' => 'ticket',
                    'target_id' => 'ticket-1',
                    'created_at' => '2026-03-29T08:00:00Z',
                    'metadata' => [],
                ],
            ]);
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('allAdminActivityLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);

        $rows = $service->exportRows('admin-logs', $filters);

        $this->assertCount(1, $rows);
        $this->assertSame('ticket_updated', $rows[0]['action_type']);
    }

    public function test_update_user_by_admin_sends_profile_notification_with_hydrated_ticket(): void
    {
        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'pending_verification',
                'verification_status' => 'unverified',
            ]);
        $repository->shouldReceive('findTicket')
            ->once()
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['account_status' => 'active', 'verification_status' => 'verified'])
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ]);
        $repository->shouldReceive('findTicket')
            ->once()
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')
            ->once()
            ->withArgs(function (array $beforeUser, array $afterUser, ?array $ticket): bool {
                return ($beforeUser['account_status'] ?? null) === 'pending_verification'
                    && ($afterUser['account_status'] ?? null) === 'active'
                    && ($afterUser['country_label'] ?? null) === 'Indonesia'
                    && ($ticket['ticket_code'] ?? null) === 'TICKET-123';
            });

        $service = $this->makeService($repository, $notifications);

        $user = $service->updateUserByAdmin('user-123', [
            'account_status' => 'active',
            'verification_status' => 'verified',
        ]);

        $this->assertSame('Indonesia', $user['country_label']);
        $this->assertSame('TICKET-123', $user['ticket']['ticket_code']);
    }

    public function test_regenerate_qr_sends_updated_qr_notification(): void
    {
        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('regenerateQrCode')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user' => [
                    'user_id' => 'user-123',
                    'full_name' => 'Alya',
                    'email' => 'alya@example.test',
                    'country' => 'MY',
                    'ticket_id' => 'ticket-123',
                ],
                'ticket' => [
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-NEW',
                    'qr_version' => 'v2',
                ],
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendQrRegenerated')
            ->once()
            ->withArgs(function (array $user, array $ticket): bool {
                return ($user['country_label'] ?? null) === 'Malaysia'
                    && ($ticket['ticket_code'] ?? null) === 'TICKET-NEW'
                    && ($ticket['qr_version'] ?? null) === 'v2';
            });

        $service = $this->makeService($repository, $notifications);

        $result = $service->regenerateQrCode('user-123');

        $this->assertSame('Malaysia', $result['user']['country_label']);
        $this->assertSame('TICKET-NEW', $result['ticket']['ticket_code']);
    }

    public function test_delete_user_by_admin_sends_deleted_registration_notification(): void
    {
        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('deleteUserByAdmin')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user' => [
                    'user_id' => 'user-123',
                    'full_name' => 'Alya',
                    'email' => 'alya@example.test',
                    'country' => 'ID',
                ],
                'ticket' => [
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                ],
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendParticipantDeleted')
            ->once()
            ->withArgs(function (array $user, ?array $ticket): bool {
                return ($user['email'] ?? null) === 'alya@example.test'
                    && ($ticket['ticket_code'] ?? null) === 'TICKET-123';
            });

        $service = $this->makeService($repository, $notifications);

        $result = $service->deleteUserByAdmin('user-123');

        $this->assertSame('user-123', $result['user']['user_id']);
        $this->assertSame('TICKET-123', $result['ticket']['ticket_code']);
    }

    private function makeService(
        AdminFirestoreRepository $repository,
        AdminParticipantNotificationService $notifications,
        array $scannerGateNames = ['Gate A'],
    ): AdminPanelService {
        $scannerGates = Mockery::mock(ScannerGateService::class);
        $scannerGates->shouldReceive('names')
            ->andReturn($scannerGateNames);

        return new AdminPanelService(
            $repository,
            new AdminAnalyticsService,
            $notifications,
            $scannerGates,
        );
    }
}
