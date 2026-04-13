<?php

namespace Tests\Unit;

use App\Services\Admin\AdminDashboardSnapshotService;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\CampaignLinkService;
use App\Services\PublicReportService;
use App\Services\TrafficVisitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminDashboardSnapshotServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'admin.dashboard.snapshot_enabled' => true,
            'admin.dashboard.snapshot_fresh_seconds' => 30,
            'admin.dashboard.snapshot_ttl_seconds' => 900,
        ]);

        Cache::flush();
        Storage::fake('local');
    }

    public function test_default_dashboard_page_reads_cached_snapshot_without_rebuilding_services(): void
    {
        $service = new AdminDashboardSnapshotService(
            $this->mockAdminPanel(),
            $this->mockCampaignLinkService(),
            $this->mockPublicReportService(),
            $this->mockTrafficVisitService(),
        );

        $expected = [
            'dashboard' => [],
            'firestoreAvailable' => true,
            'campaignLinkSummary' => ['storage_ready' => true, 'total' => 11],
            'publicReportSummary' => ['total' => 6],
            'trafficVisitSummary' => ['storage_ready' => true, 'total_unique_ips' => 1200],
        ];

        $service->warmDefaultSnapshot(false);

        $this->assertSame($expected, $service->pageData([], false));
    }

    public function test_default_dashboard_page_restores_snapshot_from_disk_when_cache_is_empty(): void
    {
        $expected = [
            'dashboard' => [],
            'firestoreAvailable' => true,
            'campaignLinkSummary' => ['storage_ready' => true, 'total' => 11],
            'publicReportSummary' => ['total' => 6],
            'trafficVisitSummary' => ['storage_ready' => true, 'total_unique_ips' => 1200],
        ];

        $warmingService = new AdminDashboardSnapshotService(
            $this->mockAdminPanel(),
            $this->mockCampaignLinkService(),
            $this->mockPublicReportService(),
            $this->mockTrafficVisitService(),
        );

        $warmingService->warmDefaultSnapshot(false);
        Cache::flush();

        $service = new AdminDashboardSnapshotService(
            $this->mockAdminPanel(shouldReceiveDashboardData: false),
            $this->mockCampaignLinkService(shouldReceiveSummary: false),
            $this->mockPublicReportService(shouldReceiveSummary: false),
            $this->mockTrafficVisitService(shouldReceiveSummary: false),
        );

        $this->assertSame($expected, $service->pageData([], false));
        $this->assertNotEmpty(Storage::disk('local')->allFiles('admin-cache'));
    }

    public function test_filtered_dashboard_request_bypasses_default_snapshot_path(): void
    {
        $service = new AdminDashboardSnapshotService(
            $this->mockAdminPanel(dashboard: ['daily_scan_statistics' => ['total_scans' => 22]]),
            $this->mockCampaignLinkService(),
            $this->mockPublicReportService(),
            $this->mockTrafficVisitService(),
        );

        $payload = $service->pageData([
            'from' => '2026-04-12',
            'to' => '2026-04-13',
        ], true);

        $this->assertSame(['total_scans' => 22], data_get($payload, 'dashboard.daily_scan_statistics'));
    }

    public function test_snapshot_can_be_disabled_for_quick_rollback(): void
    {
        config(['admin.dashboard.snapshot_enabled' => false]);

        $service = new AdminDashboardSnapshotService(
            $this->mockAdminPanel(),
            $this->mockCampaignLinkService(),
            $this->mockPublicReportService(),
            $this->mockTrafficVisitService(),
        );

        $payload = $service->pageData([], false);

        $this->assertSame(11, data_get($payload, 'campaignLinkSummary.total'));
        $this->assertSame([], Storage::disk('local')->allFiles('admin-cache'));
    }

    private function mockAdminPanel(
        bool $shouldReceiveDashboardData = true,
        array $dashboard = [],
    ): AdminPanelService {
        $mock = Mockery::mock(AdminPanelService::class);

        if ($shouldReceiveDashboardData) {
            $mock->shouldReceive('dashboardData')
                ->andReturn($dashboard);
        } else {
            $mock->shouldReceive('dashboardData')->never();
        }

        $mock->shouldReceive('firestoreAvailable')
            ->andReturnTrue();

        return $mock;
    }

    private function mockCampaignLinkService(bool $shouldReceiveSummary = true): CampaignLinkService
    {
        $mock = Mockery::mock(CampaignLinkService::class);

        if ($shouldReceiveSummary) {
            $mock->shouldReceive('dashboardSummary')
                ->andReturn([
                    'storage_ready' => true,
                    'total' => 11,
                ]);
        } else {
            $mock->shouldReceive('dashboardSummary')->never();
        }

        return $mock;
    }

    private function mockPublicReportService(bool $shouldReceiveSummary = true): PublicReportService
    {
        $mock = Mockery::mock(PublicReportService::class);

        if ($shouldReceiveSummary) {
            $mock->shouldReceive('summary')
                ->andReturn([
                    'total' => 6,
                ]);
        } else {
            $mock->shouldReceive('summary')->never();
        }

        return $mock;
    }

    private function mockTrafficVisitService(bool $shouldReceiveSummary = true): TrafficVisitService
    {
        $mock = Mockery::mock(TrafficVisitService::class);

        if ($shouldReceiveSummary) {
            $mock->shouldReceive('dashboardSummary')
                ->andReturn([
                    'storage_ready' => true,
                    'total_unique_ips' => 1200,
                ]);
        } else {
            $mock->shouldReceive('dashboardSummary')->never();
        }

        return $mock;
    }
}
