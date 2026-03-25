<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AdminAnalyticsServiceTest extends TestCase
{
    public function test_dashboard_counts_unique_visitors_and_duplicate_scans_for_default_range(): void
    {
        $service = new AdminAnalyticsService;

        $dashboard = $service->buildDashboard(
            users: [
                ['user_id' => 'user-1'],
                ['user_id' => 'user-2'],
            ],
            scanLogs: [
                [
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scan_date' => '2026-03-25',
                    'scanned_at' => '2026-03-25T09:00:00Z',
                    'result' => 'success',
                ],
                [
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scan_date' => '2026-03-25',
                    'scanned_at' => '2026-03-25T09:05:00Z',
                    'result' => 'duplicate',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-2',
                    'scan_date' => '2026-03-24',
                    'scanned_at' => '2026-03-24T10:00:00Z',
                    'result' => 'success',
                ],
            ],
            days: 2,
            referenceDate: CarbonImmutable::parse('2026-03-25T12:00:00Z'),
        );

        $this->assertSame(2, $dashboard['total_registrations']);
        $this->assertSame(3, $dashboard['daily_scan_statistics']['total_scans']);
        $this->assertSame(2, $dashboard['daily_scan_statistics']['successful_scans']);
        $this->assertSame(2, $dashboard['daily_scan_statistics']['unique_visitors']);
        $this->assertSame(1, $dashboard['daily_scan_statistics']['duplicate_scans']);
        $this->assertSame([1, 1], $dashboard['visitor_chart']['series']);
        $this->assertSame('2026-03-24', $dashboard['date_range']['from']);
        $this->assertSame('2026-03-25', $dashboard['date_range']['to']);
        $this->assertFalse($dashboard['date_range']['is_filtered']);
    }

    public function test_dashboard_can_limit_metrics_to_custom_date_range(): void
    {
        $service = new AdminAnalyticsService;

        $dashboard = $service->buildDashboard(
            users: [
                [
                    'user_id' => 'user-1',
                    'created_at' => '2026-03-24T08:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'created_at' => '2026-03-25T08:00:00Z',
                ],
            ],
            scanLogs: [
                [
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-1',
                    'scan_date' => '2026-03-24',
                    'scanned_at' => '2026-03-24T09:00:00Z',
                    'result' => 'success',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-2',
                    'scan_date' => '2026-03-25',
                    'scanned_at' => '2026-03-25T09:00:00Z',
                    'result' => 'success',
                ],
                [
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-2',
                    'scan_date' => '2026-03-25',
                    'scanned_at' => '2026-03-25T09:05:00Z',
                    'result' => 'duplicate',
                ],
            ],
            days: 7,
            referenceDate: CarbonImmutable::parse('2026-03-25T12:00:00Z'),
            filters: [
                'from' => '2026-03-25',
                'to' => '2026-03-25',
            ],
        );

        $this->assertSame(1, $dashboard['total_registrations']);
        $this->assertSame(2, $dashboard['daily_scan_statistics']['total_scans']);
        $this->assertSame(1, $dashboard['daily_scan_statistics']['successful_scans']);
        $this->assertSame(1, $dashboard['daily_scan_statistics']['unique_visitors']);
        $this->assertSame(1, $dashboard['daily_scan_statistics']['duplicate_scans']);
        $this->assertSame(['25 Mar'], $dashboard['visitor_chart']['labels']);
        $this->assertSame([1], $dashboard['visitor_chart']['series']);
        $this->assertSame('25 Mar 2026', $dashboard['date_range']['label']);
        $this->assertTrue($dashboard['date_range']['is_filtered']);
    }

    public function test_attendance_overview_groups_scanner_activity(): void
    {
        $service = new AdminAnalyticsService;

        $overview = $service->buildAttendanceOverview([
            [
                'scan_id' => 'scan-1',
                'user_id' => 'user-1',
                'ticket_code' => 'ABC',
                'scan_date' => '2026-03-25',
                'scanned_at' => '2026-03-25T09:00:00Z',
                'scanner_id' => 'scanner-1',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'result' => 'success',
            ],
            [
                'scan_id' => 'scan-2',
                'user_id' => 'user-2',
                'ticket_code' => 'DEF',
                'scan_date' => '2026-03-25',
                'scanned_at' => '2026-03-25T09:10:00Z',
                'scanner_id' => 'scanner-1',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'result' => 'duplicate',
            ],
        ]);

        $this->assertCount(2, $overview['history']);
        $this->assertSame(1, $overview['daily_attendance'][0]['successful_attendance']);
        $this->assertSame(2, $overview['scanner_activity'][0]['total_scans']);
        $this->assertSame(1, $overview['scanner_activity'][0]['successful_scans']);
        $this->assertSame('Gate A', $overview['scanner_activity'][0]['scanner_name']);
    }

    public function test_user_rows_support_country_verification_and_attendance_filters(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'ID',
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'full_name' => 'Brian Tan',
                    'email' => 'brian@example.test',
                    'country' => 'MY',
                    'verification_status' => 'unverified',
                    'account_status' => 'pending_verification',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-A',
                    'attendance_status' => 'checked_in',
                    'checked_in_at' => '2026-03-25T09:00:00Z',
                ],
                [
                    'ticket_id' => 'ticket-2',
                    'user_id' => 'user-2',
                    'ticket_code' => 'TICKET-B',
                    'attendance_status' => 'not_checked_in',
                ],
            ],
        );

        $filtered = $service->filterUserRows($rows, [
            'country' => 'ID',
            'verification_status' => 'verified',
            'attendance_status' => 'checked_in',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-1', $filtered[0]['user_id']);
        $this->assertSame('checked_in', $filtered[0]['attendance_status']);
        $this->assertSame('Indonesia', collect($rows)->firstWhere('user_id', 'user-1')['country_label']);
    }

    public function test_attendance_progress_counts_unique_success_days_per_user_within_event_window(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $service = new AdminAnalyticsService;

        $rows = $service->attachAttendanceProgress([
            ['user_id' => 'user-1'],
            ['user_id' => 'user-2'],
        ], [
            [
                'user_id' => 'user-1',
                'scan_date' => '2026-04-09',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-1',
                'scan_date' => '2026-04-09',
                'result' => 'duplicate',
            ],
            [
                'user_id' => 'user-1',
                'scan_date' => '2026-04-10',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-1',
                'scan_date' => '2026-04-20',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-2',
                'scan_date' => '2026-04-11',
                'result' => 'success',
            ],
        ]);

        $userOne = collect($rows)->firstWhere('user_id', 'user-1');
        $userTwo = collect($rows)->firstWhere('user_id', 'user-2');

        $this->assertSame(2, $userOne['attendance_days_count']);
        $this->assertSame(['2026-04-09', '2026-04-10'], $userOne['attendance_days']);
        $this->assertSame(11, $userOne['attendance_total_days']);
        $this->assertSame(18, $userOne['attendance_progress_percent']);
        $this->assertSame(1, $userTwo['attendance_days_count']);
        $this->assertSame(9, $userTwo['attendance_progress_percent']);
    }

    public function test_user_management_overview_counts_verified_checked_in_and_follow_up_users(): void
    {
        $service = new AdminAnalyticsService;

        $overview = $service->buildUserManagementOverview([
            [
                'user_id' => 'user-1',
                'country' => 'ID',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'checked_in',
            ],
            [
                'user_id' => 'user-2',
                'country' => 'MY',
                'verification_status' => 'unverified',
                'account_status' => 'pending_verification',
                'attendance_status' => 'not_checked_in',
            ],
            [
                'user_id' => 'user-3',
                'country' => 'ID',
                'verification_status' => 'verified',
                'account_status' => 'blocked',
                'attendance_status' => 'not_checked_in',
            ],
        ]);

        $this->assertSame(3, $overview['total_users']);
        $this->assertSame(2, $overview['verified_users']);
        $this->assertSame(1, $overview['checked_in_users']);
        $this->assertSame(2, $overview['follow_up_users']);
        $this->assertSame(2, $overview['countries_count']);
        $this->assertSame(67, $overview['verified_rate']);
        $this->assertSame(33, $overview['checked_in_rate']);
        $this->assertSame(67, $overview['follow_up_rate']);
    }

    public function test_user_management_counts_treat_unknown_attendance_status_as_not_checked_in(): void
    {
        $service = new AdminAnalyticsService;

        $rows = [
            [
                'user_id' => 'user-1',
                'country' => 'ID',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'checked_in',
            ],
            [
                'user_id' => 'user-2',
                'country' => 'MY',
                'verification_status' => 'verified',
                'account_status' => 'active',
                'attendance_status' => 'pending_scan_sync',
            ],
            [
                'user_id' => 'user-3',
                'country' => 'SG',
                'verification_status' => 'unverified',
                'account_status' => 'pending_verification',
                'attendance_status' => null,
            ],
        ];

        $overview = $service->buildUserManagementOverview($rows);
        $filterOptions = $service->buildUserFilterOptions($rows);

        $this->assertSame(1, $overview['checked_in_users']);
        $this->assertSame(1, $filterOptions['attendance_statuses'][0]['count']);
        $this->assertSame(2, $filterOptions['attendance_statuses'][1]['count']);
    }

    public function test_user_filter_options_keep_country_codes_but_show_full_labels(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'country' => 'JP',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'country' => 'MY',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [],
        );

        $options = $service->buildUserFilterOptions($rows);

        $this->assertSame([
            [
                'value' => 'JP',
                'label' => 'Japan',
                'count' => 1,
            ],
            [
                'value' => 'MY',
                'label' => 'Malaysia',
                'count' => 1,
            ],
        ], $options['countries']);
    }

    public function test_user_search_can_match_full_country_label(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Kenji Aoki',
                    'country' => 'JP',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
            ],
            tickets: [],
        );

        $filtered = $service->filterUserRows($rows, ['q' => 'japan']);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-1', $filtered[0]['user_id']);
        $this->assertSame('Japan', $filtered[0]['country_label']);
    }
}
