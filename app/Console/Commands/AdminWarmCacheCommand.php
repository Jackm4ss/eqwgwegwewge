<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminPanelService;
use App\Services\Scanner\ScannerGateService;
use App\Services\Staff\StaffScannerService;
use Illuminate\Console\Command;

class AdminWarmCacheCommand extends Command
{
    protected $signature = 'admin:warm-cache
        {--user-management-only : Warm only the User Management cache snapshots}
        {--attendance-only : Warm only the Attendance Monitoring cache snapshot}
        {--scanner-only : Warm only the scanner dashboard cache snapshots}
        {--scanner-post=* : Warm only selected scanner posts (repeatable)}';

    protected $description = 'Warm the critical admin and scanner cache snapshots after deploys or cache clears.';

    public function __construct(
        private readonly AdminPanelService $adminPanel,
        private readonly StaffScannerService $staffScanner,
        private readonly ScannerGateService $scannerGates,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $failures = [];
        $sections = $this->selectedSections();

        if (in_array('user-management', $sections, true)) {
            try {
                $userManagement = $this->adminPanel->warmUserManagementCache();

                $this->line(sprintf(
                    'User Management cache warmed: %d countries, %d directory rows.',
                    count((array) data_get($userManagement, 'meta.filter_options.countries', [])),
                    count((array) data_get($userManagement, 'directory', [])),
                ));

                unset($userManagement);
                $this->collectCycles();
            } catch (\Throwable $throwable) {
                $failures[] = 'User Management cache failed: '.$throwable->getMessage();
            }
        }

        if (in_array('attendance', $sections, true)) {
            try {
                $attendance = $this->adminPanel->warmAttendanceMonitoringCache();

                $this->line(sprintf(
                    'Attendance cache warmed: %d rows.',
                    count($attendance),
                ));

                unset($attendance);
                $this->collectCycles();
            } catch (\Throwable $throwable) {
                $failures[] = 'Attendance cache failed: '.$throwable->getMessage();
            }
        }

        if (in_array('scanner', $sections, true)) {
            $scannerPosts = $this->selectedScannerPosts();

            if ($scannerPosts === []) {
                $this->warn('Scanner cache warm skipped: no scanner posts are configured.');
            }

            foreach ($scannerPosts as $scannerPost) {
                try {
                    $snapshot = $this->staffScanner->warmDashboardCache($scannerPost);
                    $stats = (array) data_get($snapshot, 'stats', []);

                    $this->line(sprintf(
                        'Scanner cache warmed [%s] for %s: %d total, %d success, %d duplicate, %d invalid.',
                        $scannerPost,
                        (string) data_get($snapshot, 'scope_date', 'unknown-date'),
                        (int) ($stats['total_scans'] ?? 0),
                        (int) ($stats['successful_scans'] ?? 0),
                        (int) ($stats['duplicate_scans'] ?? 0),
                        (int) ($stats['invalid_scans'] ?? 0),
                    ));

                    unset($snapshot, $stats);
                    $this->collectCycles();
                } catch (\Throwable $throwable) {
                    $failures[] = sprintf(
                        'Scanner cache failed for [%s]: %s',
                        $scannerPost,
                        $throwable->getMessage(),
                    );
                }
            }
        }

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error($failure);
            }

            $this->error('Admin cache warm failed.');

            return self::FAILURE;
        }

        $this->info('Admin cache warm completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function selectedSections(): array
    {
        $selected = [];

        if ((bool) $this->option('user-management-only')) {
            $selected[] = 'user-management';
        }

        if ((bool) $this->option('attendance-only')) {
            $selected[] = 'attendance';
        }

        if ((bool) $this->option('scanner-only')) {
            $selected[] = 'scanner';
        }

        if ($selected === []) {
            return ['user-management', 'attendance', 'scanner'];
        }

        return array_values(array_unique($selected));
    }

    /**
     * @return array<int, string>
     */
    private function selectedScannerPosts(): array
    {
        $selected = array_values(array_filter(array_map(
            static fn (mixed $scannerPost): string => trim((string) $scannerPost),
            (array) $this->option('scanner-post'),
        )));

        if ($selected === []) {
            $selected = $this->scannerGates->names();
        }

        return array_values(array_unique($selected));
    }

    private function collectCycles(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
