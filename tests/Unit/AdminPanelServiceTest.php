<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminParticipantNotificationService;
use App\Services\Admin\AdminUserManagementReadModelDispatcher;
use App\Services\Admin\AdminUserManagementReadModel;
use App\Support\EmailTypoInspector;
use App\Services\Scanner\ScannerGateService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminPanelServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['admin.user_management.read_model.enabled' => false]);
        EmailTypoInspector::clearFakes();
        $this->clearUserManagementMetaCache();
        $this->clearAttendanceCache();
    }

    protected function tearDown(): void
    {
        EmailTypoInspector::clearFakes();
        $this->clearUserManagementMetaCache();
        $this->clearAttendanceCache();

        parent::tearDown();
    }

    public function test_dashboard_data_uses_targeted_queries_and_caches_default_range_snapshot(): void
    {
        Cache::forget(AdminPanelService::DASHBOARD_CACHE_VERSION_KEY);
        CarbonImmutable::setTestNow('2026-04-03 12:00:00');
        config([
            'admin.dashboard_days' => 7,
            'admin.event.timezone' => 'UTC',
            'app.timezone' => 'UTC',
        ]);

        try {
            $repository = Mockery::mock(AdminFirestoreRepository::class);
            $repository->shouldReceive('countUsers')
                ->once()
                ->withNoArgs()
                ->andReturn(3582);
            $repository->shouldReceive('queryScanLogs')
                ->once()
                ->with([
                    'from' => '2026-03-28',
                    'to' => '2026-04-03',
                ])
                ->andReturn([
                    [
                        'user_id' => 'user-1',
                        'ticket_code' => 'TICKET-1',
                        'scan_date' => '2026-04-03',
                        'scanned_at' => '2026-04-03T09:00:00Z',
                        'result' => 'success',
                    ],
                ]);
            $repository->shouldNotReceive('countUsersByRegistrationDate');
            $repository->shouldNotReceive('allUsers');
            $repository->shouldNotReceive('allScanLogs');

            $notifications = Mockery::mock(AdminParticipantNotificationService::class);
            $notifications->shouldIgnoreMissing();

            $service = $this->makeService($repository, $notifications);

            $first = $service->dashboardData([]);
            $second = $service->dashboardData([]);

            $this->assertSame(3582, $first['total_registrations']);
            $this->assertSame(1, $first['daily_scan_statistics']['total_scans']);
            $this->assertSame($first, $second);
        } finally {
            CarbonImmutable::setTestNow();
            Cache::forget(AdminPanelService::DASHBOARD_CACHE_VERSION_KEY);
        }
    }

    public function test_dashboard_data_counts_registrations_by_selected_range_without_loading_all_users(): void
    {
        Cache::forget(AdminPanelService::DASHBOARD_CACHE_VERSION_KEY);
        config([
            'admin.dashboard_days' => 7,
            'admin.event.timezone' => 'UTC',
            'app.timezone' => 'UTC',
        ]);

        $filters = [
            'from' => '2026-03-25',
            'to' => '2026-03-25',
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('countUsersByRegistrationDate')
            ->once()
            ->with($filters)
            ->andReturn(12);
        $repository->shouldReceive('queryScanLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-2',
                    'scan_date' => '2026-03-25',
                    'scanned_at' => '2026-03-25T09:00:00Z',
                    'result' => 'success',
                ],
            ]);
        $repository->shouldNotReceive('countUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $dashboard = $service->dashboardData($filters);

        $this->assertSame(12, $dashboard['total_registrations']);
        $this->assertSame(1, $dashboard['daily_scan_statistics']['successful_scans']);
        $this->assertTrue($dashboard['date_range']['is_filtered']);
    }

    public function test_optimized_user_management_meta_counts_only_explicit_checked_in_tickets(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);

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
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                ['user_id' => 'user-1', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
                ['user_id' => 'user-2', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
                ['user_id' => 'user-3', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
                ['user_id' => 'user-4', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
                ['user_id' => 'user-5', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(0);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(0);
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
        $this->assertSame(0, $page['filter_options']['identity_types'][0]['count']);
        $this->assertSame(5, $page['filter_options']['identity_types'][1]['count']);
    }

    public function test_user_management_page_uses_read_model_for_attendance_status_filters(): void
    {
        config([
            'admin.user_management.read_model.enabled' => true,
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $scannerGates = Mockery::mock(ScannerGateService::class);
        $scannerGates->shouldReceive('names')->andReturn(['Gate A']);

        $readModel = Mockery::mock(AdminUserManagementReadModel::class);
        $readModel->shouldReceive('supportsFilters')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(true);
        $readModel->shouldReceive('page')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn([
                'users' => new LengthAwarePaginator([], 1, 10, 1),
                'overview' => ['checked_in_users' => 1],
                'filter_options' => ['attendance_statuses' => []],
                'sync_status' => ['state' => 'fresh'],
            ]);

        $service = new AdminPanelService(
            $repository,
            new AdminAnalyticsService,
            $notifications,
            $scannerGates,
            $readModel,
        );

        $page = $service->userManagementPage([
            'attendance_status' => 'checked_in',
        ]);

        $this->assertSame(1, $page['overview']['checked_in_users']);
        $this->assertSame('fresh', $page['sync_status']['state']);
    }

    public function test_optimized_user_management_meta_builds_country_filters_from_actual_users(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);

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
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                ['user_id' => 'user-al', 'country' => 'AL', 'identity_type' => 'passport'],
                ['user_id' => 'user-mm-1', 'country' => 'MM', 'identity_type' => 'passport'],
                ['user_id' => 'user-mm-2', 'country' => 'MM', 'identity_type' => 'passport'],
                ['user_id' => 'user-my', 'country' => 'MY', 'identity_type' => 'national_id'],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(0);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(0);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(0);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $page = $service->userManagementPage([]);
        $countries = collect($page['filter_options']['countries'])->keyBy('value');

        $this->assertSame('Albania', $countries['AL']['label'] ?? null);
        $this->assertSame(1, $countries['AL']['count'] ?? null);
        $this->assertSame('Myanmar', $countries['MM']['label'] ?? null);
        $this->assertSame(2, $countries['MM']['count'] ?? null);
        $this->assertSame('Malaysia', $countries['MY']['label'] ?? null);
        $this->assertSame(3, $page['overview']['countries_count']);
        $this->assertSame('Malaysia IC (MyKad)', $page['filter_options']['identity_types'][0]['label']);
        $this->assertSame(1, $page['filter_options']['identity_types'][0]['count']);
        $this->assertSame('Passport', $page['filter_options']['identity_types'][1]['label']);
        $this->assertSame(3, $page['filter_options']['identity_types'][1]['count']);
    }

    public function test_optimized_user_management_page_uses_cached_directory_filter_counts_when_meta_snapshot_is_missing(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-1',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'MY',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'identity_type' => 'national_id',
                'attendance_status' => 'checked_in',
                'created_at' => '2026-04-05T08:00:00Z',
            ],
            [
                'user_id' => 'user-2',
                'full_name' => 'Joki',
                'email' => 'joki@example.test',
                'country' => 'ID',
                'verification_status' => 'unverified',
                'account_status' => 'pending_verification',
                'identity_type' => 'passport',
                'attendance_status' => 'not_checked_in',
                'created_at' => '2026-04-04T08:00:00Z',
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with([], 1, 10)
            ->andReturn([
                'items' => [
                    [
                        'user_id' => 'user-1',
                        'full_name' => 'Alya Putri',
                        'email' => 'alya@example.test',
                        'country' => 'MY',
                        'verification_status' => 'verified',
                        'account_status' => 'active',
                        'identity_type' => 'national_id',
                        'ticket_id' => 'ticket-1',
                        'created_at' => '2026-04-05T08:00:00Z',
                    ],
                ],
                'total' => 2,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with(['ticket-1'])
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'ticket_code' => 'TICKET-1',
                    'user_id' => 'user-1',
                    'attendance_status' => 'checked_in',
                ],
            ]);
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(1);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(1);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(1);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage([]);

        $countries = collect($page['filter_options']['countries'])->keyBy('value');
        $identityTypes = collect($page['filter_options']['identity_types'])->keyBy('value');
        $verificationStatuses = collect($page['filter_options']['verification_statuses'])->keyBy('value');
        $attendanceStatuses = collect($page['filter_options']['attendance_statuses'])->keyBy('value');

        $this->assertSame(1, $countries['MY']['count'] ?? null);
        $this->assertSame(1, $countries['ID']['count'] ?? null);
        $this->assertSame(1, $identityTypes['national_id']['count'] ?? null);
        $this->assertSame(1, $identityTypes['passport']['count'] ?? null);
        $this->assertSame(1, $verificationStatuses['verified']['count'] ?? null);
        $this->assertSame(1, $verificationStatuses['pending_verification']['count'] ?? null);
        $this->assertSame(1, $attendanceStatuses['checked_in']['count'] ?? null);
        $this->assertSame(1, $attendanceStatuses['not_checked_in']['count'] ?? null);
    }

    public function test_optimized_user_management_meta_includes_email_typo_counts(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);

        EmailTypoInspector::fake([
            'bad@gmial.com' => [
                'suspected' => true,
                'suggested_email' => 'bad@gmail.com',
            ],
            'good@example.test' => [
                'suspected' => false,
            ],
        ]);

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
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                ['user_id' => 'user-1', 'country' => 'MY', 'email' => 'bad@gmial.com', 'identity_type' => 'passport'],
                ['user_id' => 'user-2', 'country' => 'ID', 'email' => 'good@example.test', 'identity_type' => 'passport'],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(0);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(0);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(0);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage([]);
        $emailTypoOptions = collect($page['filter_options']['email_typo_statuses'])
            ->keyBy('value');

        $this->assertSame(1, $emailTypoOptions['suspected']['count']);
        $this->assertSame(1, $emailTypoOptions['clean']['count']);
    }

    public function test_optimized_user_management_meta_includes_pending_verification_counts(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);

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
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                ['user_id' => 'user-1', 'country' => 'MY', 'identity_type' => 'passport', 'verification_status' => 'verified', 'account_status' => 'active'],
                ['user_id' => 'user-2', 'country' => 'ID', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'pending_verification'],
                ['user_id' => 'user-3', 'country' => 'TH', 'identity_type' => 'passport', 'verification_status' => 'unverified', 'account_status' => 'blocked'],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(0);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(0);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(0);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage([]);
        $verificationOptions = collect($page['filter_options']['verification_statuses'])
            ->keyBy('value');

        $this->assertSame(1, $verificationOptions['verified']['count']);
        $this->assertSame(1, $verificationOptions['pending_verification']['count']);
        $this->assertSame(2, $verificationOptions['unverified']['count']);
    }

    public function test_warm_user_management_cache_builds_meta_from_same_directory_snapshot(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allUsers')
            ->once()
            ->andReturn([
                [
                    'user_id' => 'user-1',
                    'country' => 'MY',
                    'email' => 'first@example.test',
                    'identity_type' => 'passport',
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                    'ticket_id' => 'ticket-1',
                ],
                [
                    'user_id' => 'user-2',
                    'country' => 'ID',
                    'email' => 'second@example.test',
                    'identity_type' => 'passport',
                    'verification_status' => 'unverified',
                    'account_status' => 'pending_verification',
                    'ticket_id' => 'ticket-2',
                ],
            ]);
        $repository->shouldReceive('allTickets')
            ->once()
            ->andReturn([
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'attendance_status' => 'checked_in',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-2',
                    'attendance_status' => 'not_checked_in',
                ],
            ]);
        $repository->shouldReceive('allAttendanceDaily')
            ->once()
            ->andReturn([]);
        $repository->shouldNotReceive('countUsers');
        $repository->shouldNotReceive('countTickets');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $result = $service->warmUserManagementCache();

        $this->assertCount(2, $result['directory']);
        $this->assertSame(2, data_get($result, 'meta.overview.total_users'));
        $this->assertSame(1, data_get($result, 'meta.overview.checked_in_users'));
        $this->assertSame(1, data_get($result, 'meta.filter_options.verification_statuses.1.count'));
    }

    public function test_optimized_user_management_page_uses_live_overview_counts_while_meta_snapshot_is_stale(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => [
                'total_users' => 9350,
                'verified_users' => 9100,
                'checked_in_users' => 1200,
                'follow_up_users' => 250,
                'countries_count' => 12,
                'verified_rate' => 97.33,
                'checked_in_rate' => 12.83,
                'follow_up_rate' => 2.67,
            ],
            'filter_options' => [
                'countries' => [
                    [
                        'value' => 'MY',
                        'label' => 'Malaysia',
                        'count' => 1200,
                    ],
                ],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY, true);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with([], 1, 10)
            ->andReturn([
                'items' => [],
                'total' => 9350,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with([])
            ->andReturn([]);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(9125);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(9000);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(1405);
        $repository->shouldNotReceive('allUsers');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage([]);

        $this->assertSame(9350, $page['overview']['total_users']);
        $this->assertSame(9125, $page['overview']['verified_users']);
        $this->assertSame(1405, $page['overview']['checked_in_users']);
        $this->assertSame(350, $page['overview']['follow_up_users']);
        $this->assertSame('Malaysia', $page['filter_options']['countries'][0]['label']);
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
    }

    public function test_optimized_user_management_page_keeps_cached_checked_in_count_when_user_filters_need_joined_data(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => [
                'total_users' => 9350,
                'verified_users' => 9100,
                'checked_in_users' => 1200,
                'follow_up_users' => 250,
            ],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with(['country' => 'MY'], 1, 10)
            ->andReturn([
                'items' => [],
                'total' => 300,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with([])
            ->andReturn([]);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'country' => 'MY',
                'verification_status' => 'verified',
            ])
            ->andReturn(280);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'country' => 'MY',
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(260);
        $repository->shouldNotReceive('countTickets');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $page = $service->userManagementPage(['country' => 'MY']);

        $this->assertSame(300, $page['overview']['total_users']);
        $this->assertSame(280, $page['overview']['verified_users']);
        $this->assertSame(1200, $page['overview']['checked_in_users']);
        $this->assertSame(40, $page['overview']['follow_up_users']);
    }

    public function test_user_management_search_uses_cached_directory_snapshot_when_query_is_present(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'not_checked_in',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-mm',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'checked_in',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'identity_number' => 'B1234567',
                'created_at' => '2026-04-02T09:00:00Z',
            ],
        ]);

        $filters = [
            'q' => 'cherry',
            'page' => 1,
            'per_page' => 10,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $page = $service->userManagementPage($filters);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-my', $page['users']->items()[0]['user_id']);
    }

    public function test_user_management_check_in_filter_uses_cached_directory_snapshot(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'not_checked_in',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-mm',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'checked_in',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'identity_number' => 'B1234567',
                'created_at' => '2026-04-02T09:00:00Z',
            ],
        ]);

        $filters = [
            'attendance_status' => 'checked_in',
            'page' => 1,
            'per_page' => 10,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage($filters);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-mm', $page['users']->items()[0]['user_id']);
    }

    public function test_user_management_email_typo_filter_uses_cached_directory_snapshot(): void
    {
        EmailTypoInspector::fake([
            'cherry@gmial.com' => [
                'suspected' => true,
                'suggested_email' => 'cherry@gmail.com',
            ],
            'hein@example.test' => [
                'suspected' => false,
            ],
        ]);

        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
                'email_typo_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@gmial.com',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'not_checked_in',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-mm',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'checked_in',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'identity_number' => 'B1234567',
                'created_at' => '2026-04-02T09:00:00Z',
            ],
        ]);

        $filters = [
            'email_typo' => 'suspected',
            'page' => 1,
            'per_page' => 10,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage($filters);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-my', $page['users']->items()[0]['user_id']);
        $this->assertTrue($page['users']->items()[0]['email_typo_suspected']);
    }

    public function test_user_management_pending_verification_filter_uses_cached_directory_snapshot(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
                'email_typo_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-pending',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'unverified',
                'account_status' => 'pending_verification',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'not_checked_in',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-active',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'unverified',
                'account_status' => 'blocked',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'attendance_status' => 'checked_in',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'identity_number' => 'B1234567',
                'created_at' => '2026-04-02T09:00:00Z',
            ],
        ]);

        $filters = [
            'verification_status' => 'pending_verification',
            'page' => 1,
            'per_page' => 10,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage($filters);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-pending', $page['users']->items()[0]['user_id']);
        $this->assertSame('pending_verification', $page['users']->items()[0]['account_status']);
    }

    public function test_user_management_page_falls_back_to_cached_directory_when_firestore_query_needs_index(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'not_checked_in',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-mm',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'not_checked_in',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'created_at' => '2026-04-02T11:00:00Z',
            ],
        ]);

        $filters = [
            'country' => 'MY',
            'page' => 1,
            'per_page' => 10,
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with($filters, 1, 10)
            ->andThrow(new \RuntimeException('Firestore FAILED_PRECONDITION: query requires an index.'));
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('findScanLogsByUserIds');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);

        $page = $service->userManagementPage($filters);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-my', $page['users']->items()[0]['user_id']);
        $this->assertSame('Malaysia', $page['users']->items()[0]['country_label']);
        $this->assertSame(1, $page['overview']['total_users']);
    }

    public function test_user_management_page_restores_cached_snapshot_from_local_storage_without_rebuilding_firestore_data(): void
    {
        Storage::disk('local')->put('admin-cache/user-management-meta-v1.json', json_encode([
            'overview' => ['total_users' => 2],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
                'email_typo_statuses' => [],
            ],
        ]));
        Storage::disk('local')->put('admin-cache/user-management-directory-v1.json', json_encode([
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'not_checked_in',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'ticket_id' => 'ticket-my',
                'ticket_code' => 'TICKET-MY',
                'identity_type' => 'passport',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
            [
                'user_id' => 'user-mm',
                'full_name' => 'Hein Lin',
                'email' => 'hein@example.test',
                'country' => 'MM',
                'country_label' => 'Myanmar',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'checked_in',
                'traffic_source_label' => 'Not Captured',
                'traffic_source_caption' => 'Registrant source has not been captured yet',
                'ticket_id' => 'ticket-mm',
                'ticket_code' => 'TICKET-MM',
                'identity_type' => 'passport',
                'created_at' => '2026-04-02T09:00:00Z',
            ],
        ]));

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('paginateUsers');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('findScanLogsByUserIds');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $page = $service->userManagementPage([
            'q' => 'cherry',
            'page' => 1,
            'per_page' => 10,
        ]);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('user-my', $page['users']->items()[0]['user_id']);
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY));
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

        Cache::forever(AdminPanelService::ATTENDANCE_DIRECTORY_CACHE_KEY, [[
            'scan_id' => 'scan-1',
            'ticket_id' => 'ticket-123',
            'ticket_code' => '01KMYH3W10ECD5BV9F8APYPHTZ',
            'user_id' => 'user-123',
            'scanner_id' => 'scanner-post:gate-ab',
            'scanner_name' => 'Gate AB',
            'scanner_role' => 'staff',
            'scanned_at' => '2026-03-30T05:15:38Z',
            'scan_date' => '2026-03-30',
            'result' => 'duplicate',
            'entry_code_display' => '2RCA-GYXF',
            'participant' => [
                'name' => 'wegwegwegweg',
                'full_name' => 'wegwegwegweg',
                'email' => 'wegwegwegweg@gmail.com',
                'phone_number' => '+603298592389',
                'country' => 'MY',
                'country_label' => 'Malaysia',
                'ticket_code' => '01KMYH3W10ECD5BV9F8APYPHTZ',
                'entry_code_display' => '2RCA-GYXF',
            ],
            'search_blob' => '01kmyh3w10ecd5bv9f8apyphtz wegwegwegweg wegwegwegweg@gmail.com gate ab',
        ]]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('latestNonFutureScanLogDate')
            ->once()
            ->andReturn('2026-03-30');
        $repository->shouldNotReceive('paginateScanLogs');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('queryScanLogs');
        $repository->shouldNotReceive('findTicketsByIds');
        $repository->shouldNotReceive('findUsersByIds');

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

    public function test_attendance_search_uses_cached_directory_without_loading_full_collections(): void
    {
        config(['scanner.posts' => ['Gate AB', 'Gate C']]);

        Cache::forever(AdminPanelService::ATTENDANCE_DIRECTORY_CACHE_KEY, [
            [
                'scan_id' => 'scan-1',
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
                'user_id' => 'user-123',
                'scanner_id' => 'scanner-post:gate-ab',
                'scanner_name' => 'Gate AB',
                'scanner_role' => 'staff',
                'scanned_at' => '2026-03-30T05:15:38Z',
                'scan_date' => '2026-03-30',
                'result' => 'success',
                'entry_code_display' => '2RCA-GYXF',
                'participant' => [
                    'name' => 'Alice Tan',
                    'full_name' => 'Alice Tan',
                    'email' => 'alice@example.com',
                    'phone_number' => '+62811111111',
                    'country' => 'ID',
                    'country_label' => 'Indonesia',
                    'ticket_code' => 'TICKET-123',
                    'entry_code_display' => '2RCA-GYXF',
                ],
                'search_blob' => 'ticket-123 user-123 gate ab alice tan alice@example.com 2rca-gyxf',
            ],
            [
                'scan_id' => 'scan-2',
                'ticket_id' => 'ticket-456',
                'ticket_code' => 'TICKET-456',
                'user_id' => 'user-456',
                'scanner_id' => 'scanner-post:gate-c',
                'scanner_name' => 'Gate C',
                'scanner_role' => 'staff',
                'scanned_at' => '2026-03-30T04:10:00Z',
                'scan_date' => '2026-03-30',
                'result' => 'duplicate',
                'entry_code_display' => 'WXYZ-6789',
                'participant' => [
                    'name' => 'Bob Lim',
                    'full_name' => 'Bob Lim',
                    'email' => 'bob@example.com',
                    'phone_number' => '+62822222222',
                    'country' => 'MY',
                    'country_label' => 'Malaysia',
                    'ticket_code' => 'TICKET-456',
                    'entry_code_display' => 'WXYZ-6789',
                ],
                'search_blob' => 'ticket-456 user-456 gate c bob lim bob@example.com wxyz-6789',
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('paginateScanLogs');
        $repository->shouldNotReceive('queryScanLogs');
        $repository->shouldNotReceive('allScanLogs');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('findTicketsByIds');
        $repository->shouldNotReceive('findUsersByIds');
        $repository->shouldNotReceive('latestNonFutureScanLogDate');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB', 'Gate C']);

        $attendance = $service->attendanceData([
            'q' => 'alice@example.com',
            'from' => '2026-03-30',
            'to' => '2026-03-30',
        ]);
        $history = $attendance['history']->items();

        $this->assertCount(1, $history);
        $this->assertSame('Alice Tan', $history[0]['participant']['full_name']);
        $this->assertSame('Gate AB', $history[0]['scanner_name']);
        $this->assertSame(1, $attendance['history']->total());
        $this->assertCount(1, $attendance['daily_attendance']);
        $this->assertSame('Gate AB', $attendance['scan_post_options'][0]['value']);
    }

    public function test_warm_attendance_monitoring_cache_uses_collection_scan_reads_instead_of_query_scan_logs(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, []);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('allScanLogs')
            ->once()
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                    'user_id' => 'user-123',
                    'scanner_id' => 'scanner-post:gate-ab',
                    'scanner_name' => 'Gate AB',
                    'scanner_role' => 'staff',
                    'scanned_at' => '2026-03-30T05:15:38Z',
                    'scan_date' => '2026-03-30',
                    'result' => 'success',
                ],
            ]);
        $repository->shouldNotReceive('queryScanLogs');
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications, ['Gate AB']);
        $rows = $service->warmAttendanceMonitoringCache();

        $this->assertCount(1, $rows);
        $this->assertSame('scan-1', $rows[0]['scan_id']);
        $this->assertSame('Gate AB', $rows[0]['scanner_name']);
        $this->assertTrue(Cache::has(AdminPanelService::ATTENDANCE_DIRECTORY_CACHE_KEY));
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

            Cache::forever(AdminPanelService::ATTENDANCE_DIRECTORY_CACHE_KEY, [[
                'scan_id' => 'scan-current',
                'ticket_id' => 'ticket-current',
                'ticket_code' => 'CURRENT-1',
                'user_id' => 'user-current',
                'scanner_id' => 'scanner-post:gate-ab',
                'scanner_name' => 'Gate AB',
                'scanner_role' => 'staff',
                'scanned_at' => '2026-03-30T05:15:38Z',
                'scan_date' => '2026-03-30',
                'result' => 'duplicate',
                'entry_code_display' => '',
                'participant' => null,
                'search_blob' => 'current-1 user-current gate ab',
            ]]);

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
            $repository->shouldNotReceive('allScanLogs');
            $repository->shouldNotReceive('allUsers');
            $repository->shouldNotReceive('allTickets');
            $repository->shouldNotReceive('queryScanLogs');

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

    public function test_reports_use_filtered_scan_log_query_without_loading_users_or_tickets(): void
    {
        $filters = [
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('queryScanLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scanner_name' => 'Gate A',
                    'result' => 'success',
                    'scan_date' => '2026-03-29',
                    'scanned_at' => '2026-03-29T10:00:00Z',
                ],
                [
                    'scan_id' => 'scan-2',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scanner_name' => 'Gate A',
                    'result' => 'duplicate',
                    'scan_date' => '2026-03-29',
                    'scanned_at' => '2026-03-29T10:05:00Z',
                ],
            ]);
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $reports = $service->reports($filters);

        $this->assertSame(2, data_get($reports, 'daily.total_scans'));
        $this->assertSame(1, data_get($reports, 'overall.visitor_statistics.0.unique_visitors'));
    }

    public function test_export_users_uses_cached_directory_snapshot(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-my',
                'full_name' => 'Cherry Thin',
                'email' => 'cherry@example.test',
                'country' => 'MY',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'identity_type' => 'passport',
                'attendance_status' => 'not_checked_in',
                'traffic_source_label' => 'Instagram',
                'traffic_source_caption' => 'Instagram / reel',
                'created_at' => '2026-04-01T10:00:00Z',
            ],
            [
                'user_id' => 'user-id',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'verification_status' => 'unverified',
                'account_status' => 'pending_verification',
                'identity_type' => 'passport',
                'attendance_status' => 'not_checked_in',
                'traffic_source_label' => 'Direct',
                'traffic_source_caption' => 'Direct visit',
                'created_at' => '2026-04-02T10:00:00Z',
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $rows = $service->exportRows('users', ['q' => 'cherry']);

        $this->assertCount(1, $rows);
        $this->assertSame('Cherry Thin', $rows[0]['full_name']);
    }

    public function test_export_attendance_uses_filtered_scan_log_query(): void
    {
        $filters = [
            'scanner_post' => 'Gate A',
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('queryScanLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scanner_name' => 'Gate A',
                    'result' => 'success',
                    'scan_date' => '2026-03-29',
                    'scanned_at' => '2026-03-29T10:00:00Z',
                ],
            ]);
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $rows = $service->exportRows('attendance', $filters);

        $this->assertCount(1, $rows);
        $this->assertSame('Gate A', $rows[0]['scanner_name']);
    }

    public function test_export_daily_report_uses_filtered_scan_log_query_without_loading_users_or_tickets(): void
    {
        $filters = [
            'from' => '2026-03-29',
            'to' => '2026-03-29',
        ];

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('queryScanLogs')
            ->once()
            ->with($filters)
            ->andReturn([
                [
                    'scan_id' => 'scan-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scanner_name' => 'Gate A',
                    'result' => 'success',
                    'scan_date' => '2026-03-29',
                    'scanned_at' => '2026-03-29T10:00:00Z',
                ],
            ]);
        $repository->shouldNotReceive('allUsers');
        $repository->shouldNotReceive('allTickets');
        $repository->shouldNotReceive('allScanLogs');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldIgnoreMissing();

        $service = $this->makeService($repository, $notifications);
        $rows = $service->exportRows('daily-report', $filters);

        $this->assertCount(1, $rows);
        $this->assertSame('2026-03-29', $rows[0]['scan_date']);
        $this->assertSame(1, $rows[0]['successful_attendance']);
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

    public function test_update_user_by_admin_marks_user_management_cache_stale_without_dropping_snapshot(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 9349],
            'filter_options' => [],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            ['user_id' => 'user-123', 'full_name' => 'Alya'],
        ]);

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
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['account_status' => 'active'])
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'active',
                'verification_status' => 'unverified',
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')->once();

        $service = $this->makeService($repository, $notifications);

        $service->updateUserByAdmin('user-123', [
            'account_status' => 'active',
        ]);

        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY));
    }

    public function test_update_user_by_admin_dispatches_read_model_sync_and_meta_refresh_without_inline_rebuild(): void
    {
        config([
            'admin.user_management.read_model.enabled' => true,
        ]);

        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 16016],
            'filter_options' => [],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, []);

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
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
                'user_id' => 'user-123',
                'attendance_status' => 'not_checked_in',
                'status' => 'active',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['account_status' => 'active'])
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')->once();

        $scannerGates = Mockery::mock(ScannerGateService::class);
        $scannerGates->shouldReceive('names')->andReturn(['Gate A']);

        $dispatcher = Mockery::mock(AdminUserManagementReadModelDispatcher::class);
        $dispatcher->shouldReceive('syncUser')
            ->once()
            ->with('user-123', 'admin_update');
        $dispatcher->shouldReceive('requestMetaRefresh')
            ->once()
            ->with('admin_update');

        $service = new AdminPanelService(
            $repository,
            new AdminAnalyticsService,
            $notifications,
            $scannerGates,
            null,
            $dispatcher,
        );

        $service->updateUserByAdmin('user-123', [
            'account_status' => 'active',
        ]);

        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY));
    }

    public function test_update_user_by_admin_keeps_cached_meta_available_for_following_index_request(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 9349],
            'filter_options' => [
                'countries' => [],
                'verification_statuses' => [],
                'identity_types' => [],
                'attendance_statuses' => [],
            ],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, []);

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
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['account_status' => 'active'])
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'active',
                'verification_status' => 'unverified',
            ]);
        $repository->shouldReceive('paginateUsers')
            ->once()
            ->with([], 1, 10)
            ->andReturn([
                'items' => [
                    [
                        'user_id' => 'user-123',
                        'full_name' => 'Alya',
                        'email' => 'alya@example.test',
                        'country' => 'ID',
                        'ticket_id' => 'ticket-123',
                        'account_status' => 'active',
                        'verification_status' => 'unverified',
                        'created_at' => '2026-04-05T07:00:00Z',
                    ],
                ],
                'total' => 1,
            ]);
        $repository->shouldReceive('findTicketsByIds')
            ->once()
            ->with(['ticket-123'])
            ->andReturn([
                [
                    'ticket_id' => 'ticket-123',
                    'ticket_code' => 'TICKET-123',
                    'user_id' => 'user-123',
                ],
            ]);
        $repository->shouldNotReceive('findScanLogsByUserIds');
        $repository->shouldReceive('countUsers')
            ->once()
            ->with(['verification_status' => 'verified'])
            ->andReturn(1);
        $repository->shouldReceive('countUsers')
            ->once()
            ->with([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ])
            ->andReturn(1);
        $repository->shouldReceive('countTickets')
            ->once()
            ->with(['attendance_status' => 'checked_in'])
            ->andReturn(0);
        $repository->shouldNotReceive('allUsers');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')->once();

        $service = $this->makeService($repository, $notifications);

        $service->updateUserByAdmin('user-123', [
            'account_status' => 'active',
        ]);
        $page = $service->userManagementPage([]);

        $this->assertSame(1, $page['overview']['total_users']);
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
    }

    public function test_update_user_by_admin_updates_cached_directory_search_results_immediately(): void
    {
        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 1],
            'filter_options' => [],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'country_label' => 'Indonesia',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-05T07:00:00Z',
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ]);
        $repository->shouldReceive('findTicket')
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'user_id' => 'user-123',
                'ticket_code' => 'TICKET-123',
                'status' => 'active',
                'attendance_status' => 'not_checked_in',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['full_name' => 'Alya Putri'])
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-05T07:00:00Z',
            ]);
        $repository->shouldNotReceive('paginateUsers');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')->once();

        $service = $this->makeService($repository, $notifications);

        $service->updateUserByAdmin('user-123', [
            'full_name' => 'Alya Putri',
        ]);
        $page = $service->userManagementPage([
            'q' => 'putri',
        ]);

        $this->assertSame(1, $page['users']->total());
        $this->assertSame('Alya Putri', $page['users']->items()[0]['full_name']);
    }

    public function test_update_user_by_admin_skips_inline_snapshot_sync_for_large_cached_directory(): void
    {
        config([
            'admin.user_management.inline_snapshot_sync_max_rows' => 2000,
        ]);

        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 16016],
            'filter_options' => [],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'country_label' => 'Indonesia',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-05T07:00:00Z',
            ],
        ]);

        $repository = Mockery::mock(AdminFirestoreRepository::class);
        $repository->shouldReceive('findUser')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ]);
        $repository->shouldReceive('findTicket')
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'user_id' => 'user-123',
                'ticket_code' => 'TICKET-123',
                'status' => 'active',
                'attendance_status' => 'not_checked_in',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['full_name' => 'Alya Putri'])
            ->andReturn([
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'account_status' => 'active',
                'verification_status' => 'verified',
                'created_at' => '2026-04-05T07:00:00Z',
            ]);
        $repository->shouldNotReceive('paginateUsers');

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')->once();

        $service = $this->makeService($repository, $notifications);

        $service->updateUserByAdmin('user-123', [
            'full_name' => 'Alya Putri',
        ]);

        $cachedDirectory = Cache::get(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, []);

        $this->assertSame('Alya', $cachedDirectory[0]['full_name'] ?? null);
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY));
    }

    public function test_update_user_by_admin_ignores_profile_notification_failures(): void
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
            ->times(2)
            ->with('ticket-123')
            ->andReturn([
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ]);
        $repository->shouldReceive('updateUserByAdmin')
            ->once()
            ->with('user-123', ['account_status' => 'active'])
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'ticket_id' => 'ticket-123',
                'account_status' => 'active',
                'verification_status' => 'unverified',
            ]);

        $notifications = Mockery::mock(AdminParticipantNotificationService::class);
        $notifications->shouldReceive('sendProfileUpdated')
            ->once()
            ->andThrow(new \RuntimeException('SMTP timeout'));

        $service = $this->makeService($repository, $notifications);

        $user = $service->updateUserByAdmin('user-123', [
            'account_status' => 'active',
        ]);

        $this->assertSame('active', $user['account_status']);
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

    public function test_delete_user_by_admin_does_not_send_deleted_registration_notification(): void
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
        $notifications->shouldNotReceive('sendParticipantDeleted');

        $service = $this->makeService($repository, $notifications);

        $result = $service->deleteUserByAdmin('user-123');

        $this->assertSame('user-123', $result['user']['user_id']);
        $this->assertSame('TICKET-123', $result['ticket']['ticket_code']);
    }

    public function test_delete_user_by_admin_skips_inline_snapshot_removal_for_large_cached_directory(): void
    {
        config([
            'admin.user_management.inline_snapshot_sync_max_rows' => 2000,
        ]);

        Cache::forever(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY, [
            'overview' => ['total_users' => 16016],
            'filter_options' => [],
        ]);
        Cache::forever(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, [
            [
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'created_at' => '2026-04-05T07:00:00Z',
            ],
        ]);

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
        $notifications->shouldNotReceive('sendParticipantDeleted');

        $service = $this->makeService($repository, $notifications);

        $service->deleteUserByAdmin('user-123');

        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY));
        $this->assertTrue(Cache::has(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY));
        $this->assertSame([
            [
                'user_id' => 'user-123',
                'ticket_id' => 'ticket-123',
                'full_name' => 'Alya',
                'email' => 'alya@example.test',
                'country' => 'ID',
                'created_at' => '2026-04-05T07:00:00Z',
            ],
        ], Cache::get(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY));
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

    private function clearUserManagementMetaCache(): void
    {
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_META_STALE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_DIRECTORY_CACHE_KEY);
        Cache::forget(AdminPanelService::USER_MANAGEMENT_DIRECTORY_STALE_KEY);
    }

    private function clearAttendanceCache(): void
    {
        Cache::forget(AdminPanelService::ATTENDANCE_DIRECTORY_CACHE_KEY);
        Cache::forget(AdminPanelService::ATTENDANCE_DIRECTORY_STALE_KEY);
    }
}
