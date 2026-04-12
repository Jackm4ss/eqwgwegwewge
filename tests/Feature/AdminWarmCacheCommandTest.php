<?php

namespace Tests\Feature;

use App\Services\Admin\AdminPanelService;
use App\Services\Scanner\ScannerGateService;
use App\Services\Staff\StaffScannerService;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class AdminWarmCacheCommandTest extends TestCase
{
    public function test_admin_warm_cache_warms_admin_attendance_and_scanner_snapshots_by_default(): void
    {
        $adminPanel = Mockery::mock(AdminPanelService::class);
        $adminPanel->shouldReceive('warmUserManagementCache')
            ->once()
            ->andReturn([
                'meta' => [
                    'filter_options' => [
                        'countries' => [
                            ['value' => 'MY', 'label' => 'Malaysia', 'count' => 10],
                        ],
                    ],
                ],
                'directory' => [
                    ['user_id' => 'user-1'],
                    ['user_id' => 'user-2'],
                ],
            ]);
        $adminPanel->shouldReceive('warmAttendanceMonitoringCache')
            ->once()
            ->andReturn([
                ['scan_id' => 'scan-1'],
                ['scan_id' => 'scan-2'],
            ]);

        $staffScanner = Mockery::mock(StaffScannerService::class);
        $staffScanner->shouldReceive('warmDashboardCaches')
            ->once()
            ->with(['Gate A', 'Gate B'])
            ->andReturn([
                'Gate A' => [
                    'scope_date' => '2026-04-05',
                    'stats' => [
                        'total_scans' => 3,
                        'successful_scans' => 2,
                        'duplicate_scans' => 1,
                        'invalid_scans' => 0,
                    ],
                ],
                'Gate B' => [
                    'scope_date' => '2026-04-05',
                    'stats' => [
                        'total_scans' => 1,
                        'successful_scans' => 1,
                        'duplicate_scans' => 0,
                        'invalid_scans' => 0,
                    ],
                ],
            ]);

        $scannerGates = Mockery::mock(ScannerGateService::class);
        $scannerGates->shouldReceive('names')
            ->once()
            ->andReturn(['Gate A', 'Gate B']);

        $this->app->instance(AdminPanelService::class, $adminPanel);
        $this->app->instance(StaffScannerService::class, $staffScanner);
        $this->app->instance(ScannerGateService::class, $scannerGates);

        $exitCode = Artisan::call('admin:warm-cache');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('User Management cache warmed: 1 countries, 2 directory rows.', $output);
        $this->assertStringContainsString('Attendance cache warmed: 2 rows.', $output);
        $this->assertStringContainsString('Scanner cache warmed [Gate A] for 2026-04-05: 3 total, 2 success, 1 duplicate, 0 invalid.', $output);
        $this->assertStringContainsString('Scanner cache warmed [Gate B] for 2026-04-05: 1 total, 1 success, 0 duplicate, 0 invalid.', $output);
        $this->assertStringContainsString('Admin cache warm completed successfully.', $output);
    }

    public function test_admin_warm_cache_can_warm_only_selected_scanner_posts(): void
    {
        $adminPanel = Mockery::mock(AdminPanelService::class);
        $adminPanel->shouldNotReceive('warmUserManagementCache');
        $adminPanel->shouldNotReceive('warmAttendanceMonitoringCache');

        $staffScanner = Mockery::mock(StaffScannerService::class);
        $staffScanner->shouldReceive('warmDashboardCaches')
            ->once()
            ->with(['Gate C'])
            ->andReturn([
                'Gate C' => [
                    'scope_date' => '2026-04-05',
                    'stats' => [
                        'total_scans' => 5,
                        'successful_scans' => 4,
                        'duplicate_scans' => 1,
                        'invalid_scans' => 0,
                    ],
                ],
            ]);

        $scannerGates = Mockery::mock(ScannerGateService::class);
        $scannerGates->shouldNotReceive('names');

        $this->app->instance(AdminPanelService::class, $adminPanel);
        $this->app->instance(StaffScannerService::class, $staffScanner);
        $this->app->instance(ScannerGateService::class, $scannerGates);

        $exitCode = Artisan::call('admin:warm-cache', [
            '--scanner-only' => true,
            '--scanner-post' => ['Gate C'],
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Scanner cache warmed [Gate C] for 2026-04-05: 5 total, 4 success, 1 duplicate, 0 invalid.', $output);
        $this->assertStringNotContainsString('User Management cache warmed', $output);
        $this->assertStringNotContainsString('Attendance cache warmed', $output);
    }
}
