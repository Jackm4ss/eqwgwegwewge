<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminParticipantNotificationService;
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

        $service = new AdminPanelService($repository, new AdminAnalyticsService, $notifications);

        $page = $service->userManagementPage([]);

        $this->assertSame(0, $page['overview']['checked_in_users']);
        $this->assertSame(0, $page['filter_options']['attendance_statuses'][0]['count']);
        $this->assertSame(5, $page['filter_options']['attendance_statuses'][1]['count']);
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

        $service = new AdminPanelService($repository, new AdminAnalyticsService, $notifications);

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

        $service = new AdminPanelService($repository, new AdminAnalyticsService, $notifications);

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

        $service = new AdminPanelService($repository, new AdminAnalyticsService, $notifications);

        $result = $service->deleteUserByAdmin('user-123');

        $this->assertSame('user-123', $result['user']['user_id']);
        $this->assertSame('TICKET-123', $result['ticket']['ticket_code']);
    }
}
