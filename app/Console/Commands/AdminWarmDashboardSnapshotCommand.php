<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminDashboardSnapshotService;
use Illuminate\Console\Command;

class AdminWarmDashboardSnapshotCommand extends Command
{
    protected $signature = 'admin:warm-dashboard-snapshot';

    protected $description = 'Warm the lightweight admin dashboard snapshot used for fast first-load rendering.';

    public function __construct(
        private readonly AdminDashboardSnapshotService $dashboardSnapshot,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $showAttendanceAnalytics = (bool) config('admin.dashboard.attendance_analytics_enabled', false);
        $snapshot = $this->dashboardSnapshot->warmDefaultSnapshot($showAttendanceAnalytics);

        $this->info(sprintf(
            'Admin dashboard snapshot warmed: %d unique IPs, %d campaign links, %d public reports.',
            (int) data_get($snapshot, 'trafficVisitSummary.total_unique_ips', 0),
            (int) data_get($snapshot, 'campaignLinkSummary.total', 0),
            (int) data_get($snapshot, 'publicReportSummary.total', 0),
        ));

        return self::SUCCESS;
    }
}
