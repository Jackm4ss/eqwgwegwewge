<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAnalyticsService;
use App\Support\EmailTypoInspector;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AdminAnalyticsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        EmailTypoInspector::clearFakes();
    }

    protected function tearDown(): void
    {
        EmailTypoInspector::clearFakes();

        parent::tearDown();
    }

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

    public function test_attendance_overview_counts_unique_success_per_scanner_activity(): void
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
                'user_id' => 'user-1',
                'ticket_code' => 'ABC',
                'scan_date' => '2026-03-25',
                'scanned_at' => '2026-03-25T09:05:00Z',
                'scanner_id' => 'scanner-1',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'result' => 'success',
            ],
            [
                'scan_id' => 'scan-3',
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

        $this->assertSame(1, $overview['daily_attendance'][0]['successful_attendance']);
        $this->assertSame(1, $overview['scanner_activity'][0]['successful_scans']);
        $this->assertSame(1, $overview['scanner_activity'][0]['duplicate_scans']);
        $this->assertSame(3, $overview['scanner_activity'][0]['total_scans']);
    }

    public function test_attendance_overview_can_filter_to_a_specific_scan_post(): void
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
                'scanner_id' => 'scanner-2',
                'scanner_name' => 'Gate B',
                'scanner_role' => 'staff',
                'result' => 'duplicate',
            ],
        ], [
            'scanner_post' => 'Gate A',
        ]);

        $this->assertCount(1, $overview['history']);
        $this->assertSame('Gate A', $overview['history'][0]['scanner_name']);
        $this->assertCount(1, $overview['daily_attendance']);
        $this->assertSame(1, $overview['daily_attendance'][0]['total_scans']);
        $this->assertSame(1, $overview['daily_attendance'][0]['successful_attendance']);
        $this->assertCount(1, $overview['scanner_activity']);
        $this->assertSame('Gate A', $overview['scanner_activity'][0]['scanner_name']);
        $this->assertSame(1, $overview['scanner_activity'][0]['total_scans']);
    }

    public function test_attendance_overview_sorts_sections_by_latest_dates_by_default(): void
    {
        $service = new AdminAnalyticsService;

        $overview = $service->buildAttendanceOverview([
            [
                'scan_id' => 'scan-1',
                'user_id' => 'user-1',
                'ticket_code' => 'ABC',
                'scan_date' => '2026-03-29',
                'scanned_at' => '2026-03-29T08:00:00Z',
                'scanner_id' => 'scanner-a',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'result' => 'success',
            ],
            [
                'scan_id' => 'scan-2',
                'user_id' => 'user-2',
                'ticket_code' => 'DEF',
                'scan_date' => '2026-03-30',
                'scanned_at' => '2026-03-30T10:00:00Z',
                'scanner_id' => 'scanner-b',
                'scanner_name' => 'Gate B',
                'scanner_role' => 'staff',
                'result' => 'duplicate',
            ],
            [
                'scan_id' => 'scan-3',
                'user_id' => 'user-3',
                'ticket_code' => 'GHI',
                'scan_date' => '2026-03-30',
                'scanned_at' => '2026-03-30T09:00:00Z',
                'scanner_id' => 'scanner-a',
                'scanner_name' => 'Gate A',
                'scanner_role' => 'staff',
                'result' => 'duplicate',
            ],
        ]);

        $this->assertSame('2026-03-30T10:00:00Z', $overview['history'][0]['scanned_at']);
        $this->assertSame('2026-03-30T09:00:00Z', $overview['history'][1]['scanned_at']);
        $this->assertSame('2026-03-29T08:00:00Z', $overview['history'][2]['scanned_at']);

        $this->assertSame('2026-03-30', $overview['daily_attendance'][0]['scan_date']);
        $this->assertSame('2026-03-29', $overview['daily_attendance'][1]['scan_date']);

        $this->assertSame('Gate B', $overview['scanner_activity'][0]['scanner_name']);
        $this->assertSame('2026-03-30T10:00:00Z', $overview['scanner_activity'][0]['last_scanned_at']);
        $this->assertSame('Gate A', $overview['scanner_activity'][1]['scanner_name']);
        $this->assertSame('2026-03-30T09:00:00Z', $overview['scanner_activity'][1]['last_scanned_at']);
    }

    public function test_user_rows_support_identity_country_verification_and_attendance_filters(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'full_name' => 'Brian Tan',
                    'email' => 'brian@example.test',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
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
            'country' => 'MY',
            'identity_type' => 'national_id',
            'verification_status' => 'unverified',
            'attendance_status' => 'not_checked_in',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-2', $filtered[0]['user_id']);
        $this->assertSame('national_id', $filtered[0]['identity_type']);
        $this->assertSame('not_checked_in', $filtered[0]['attendance_status']);
        $this->assertSame('Malaysia', collect($rows)->firstWhere('user_id', 'user-2')['country_label']);
        $this->assertSame('Malaysia IC (MyKad)', $service->identityTypeLabel($filtered[0]['identity_type']));
    }

    public function test_user_rows_include_entry_code_fields_from_active_ticket(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'ticket_id' => 'ticket-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
            ],
            tickets: [
                [
                    'ticket_id' => 'ticket-1',
                    'user_id' => 'user-1',
                    'ticket_code' => 'TICKET-A',
                    'entry_code' => '2W7QG9LC',
                    'entry_code_display' => '2W7Q-G9LC',
                    'attendance_status' => 'not_checked_in',
                ],
            ],
        );

        $this->assertSame('2W7QG9LC', $rows[0]['entry_code']);
        $this->assertSame('2W7Q-G9LC', $rows[0]['entry_code_display']);
    }

    public function test_user_rows_can_filter_suspected_email_typos(): void
    {
        EmailTypoInspector::fake([
            'alya@gmial.com' => [
                'suspected' => true,
                'suggested_email' => 'alya@gmail.com',
            ],
            'brian@example.test' => [
                'suspected' => false,
            ],
        ]);

        $service = new AdminAnalyticsService;
        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@gmial.com',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'full_name' => 'Brian Tan',
                    'email' => 'brian@example.test',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [],
        );

        $filtered = $service->filterUserRows($rows, [
            'email_typo' => 'suspected',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-1', $filtered[0]['user_id']);
        $this->assertTrue($filtered[0]['email_typo_suspected']);
        $this->assertSame('alya@gmail.com', $filtered[0]['email_typo_suggestion']);
    }

    public function test_user_rows_can_filter_pending_verification(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Alya Putri',
                    'email' => 'alya@example.test',
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'full_name' => 'Brian Tan',
                    'email' => 'brian@example.test',
                    'verification_status' => 'unverified',
                    'account_status' => 'pending_verification',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [],
        );

        $filtered = $service->filterUserRows($rows, [
            'verification_status' => 'pending_verification',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-2', $filtered[0]['user_id']);
        $this->assertSame('pending_verification', $filtered[0]['account_status']);
    }

    public function test_country_labels_follow_shared_registration_catalog(): void
    {
        $service = new AdminAnalyticsService;

        $this->assertSame('Myanmar', $service->countryLabel('MM'));
        $this->assertSame('UAE', $service->countryLabel('AE'));
        $this->assertContains('AL', $service->supportedCountryCodes());
        $this->assertContains('MM', $service->supportedCountryCodes());
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

    public function test_attendance_progress_falls_back_to_current_ticket_scans_outside_event_window_when_needed(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $service = new AdminAnalyticsService;

        $rows = $service->attachAttendanceProgress([
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
            ],
        ], [
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
                'scan_date' => '2026-03-29',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-old',
                'ticket_code' => 'TICKET-OLD',
                'scan_date' => '2026-04-10',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
                'scan_date' => '2026-03-29',
                'result' => 'duplicate',
            ],
        ]);

        $user = $rows[0];

        $this->assertSame(1, $user['attendance_days_count']);
        $this->assertSame(['2026-03-29'], $user['attendance_days']);
        $this->assertSame(11, $user['attendance_total_days']);
        $this->assertSame(9, $user['attendance_progress_percent']);
    }

    public function test_attendance_progress_normalizes_success_days_using_event_timezone_from_scanned_at(): void
    {
        config()->set('admin.event.timezone', 'Asia/Kuala_Lumpur');
        config()->set('admin.event.start_date', '2026-03-01');
        config()->set('admin.event.end_date', '2026-04-19');

        $service = new AdminAnalyticsService;

        $rows = $service->attachAttendanceProgress([
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
            ],
        ], [
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
                'scan_date' => '2026-03-29',
                'scanned_at' => '2026-03-29T22:20:39.748171Z',
                'result' => 'success',
            ],
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-active',
                'ticket_code' => 'TICKET-ACTIVE',
                'scan_date' => '2026-03-30',
                'scanned_at' => '2026-03-30T00:15:18.215861Z',
                'result' => 'success',
            ],
        ]);

        $user = $rows[0];

        $this->assertSame(1, $user['attendance_days_count']);
        $this->assertSame(['2026-03-30'], $user['attendance_days']);
        $this->assertSame(50, $user['attendance_total_days']);
        $this->assertSame(2, $user['attendance_progress_percent']);
    }

    public function test_attendance_progress_marks_rows_checked_in_when_attendance_daily_exists(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $service = new AdminAnalyticsService;

        $rows = $service->attachAttendanceProgress([
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'ticket_code' => 'TICKET-001',
                'attendance_status' => 'not_checked_in',
                'checked_in_at' => null,
            ],
        ], [
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'ticket_code' => 'TICKET-001',
                'scan_date' => '2026-04-10',
                'first_scanned_at' => '2026-04-10T08:30:00Z',
            ],
        ]);

        $this->assertSame('checked_in', $rows[0]['attendance_status']);
        $this->assertSame('2026-04-10T08:30:00Z', $rows[0]['checked_in_at']);
        $this->assertSame(1, $rows[0]['attendance_days_count']);
    }

    public function test_attendance_progress_clears_stale_checked_in_status_when_daily_records_are_missing(): void
    {
        config()->set('admin.event.start_date', '2026-04-09');
        config()->set('admin.event.end_date', '2026-04-19');

        $service = new AdminAnalyticsService;

        $rows = $service->attachAttendanceProgress([
            [
                'user_id' => 'user-1',
                'ticket_id' => 'ticket-1',
                'ticket_code' => 'TICKET-001',
                'attendance_status' => 'checked_in',
                'checked_in_at' => '2026-04-10T08:30:00Z',
            ],
        ], []);

        $this->assertSame('not_checked_in', $rows[0]['attendance_status']);
        $this->assertNull($rows[0]['checked_in_at']);
        $this->assertSame(0, $rows[0]['attendance_days_count']);
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

    public function test_user_filter_options_include_identity_document_types_with_human_labels(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'identity_type' => 'passport',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'identity_type' => 'national_id',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
                [
                    'user_id' => 'user-3',
                    'identity_type' => 'passport',
                    'created_at' => '2026-03-25T12:00:00Z',
                ],
            ],
            tickets: [],
        );

        $options = $service->buildUserFilterOptions($rows);

        $this->assertSame([
            [
                'value' => 'national_id',
                'label' => 'Malaysia IC (MyKad)',
                'count' => 1,
            ],
            [
                'value' => 'passport',
                'label' => 'Passport',
                'count' => 2,
            ],
        ], $options['identity_types']);
    }

    public function test_user_filter_options_include_email_typo_counts(): void
    {
        EmailTypoInspector::fake([
            'alya@gmial.com' => [
                'suspected' => true,
                'suggested_email' => 'alya@gmail.com',
            ],
            'brian@example.test' => [
                'suspected' => false,
            ],
        ]);

        $service = new AdminAnalyticsService;
        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'email' => 'alya@gmial.com',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'email' => 'brian@example.test',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [],
        );

        $options = collect($service->buildUserFilterOptions($rows)['email_typo_statuses'])
            ->keyBy('value');

        $this->assertSame(1, $options['suspected']['count']);
        $this->assertSame(1, $options['clean']['count']);
    }

    public function test_user_filter_options_include_pending_verification_counts(): void
    {
        $service = new AdminAnalyticsService;
        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'email' => 'verified@example.test',
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                    'created_at' => '2026-03-25T09:00:00Z',
                ],
                [
                    'user_id' => 'user-2',
                    'email' => 'pending@example.test',
                    'verification_status' => 'unverified',
                    'account_status' => 'pending_verification',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
                [
                    'user_id' => 'user-3',
                    'email' => 'review@example.test',
                    'verification_status' => 'unverified',
                    'account_status' => 'blocked',
                    'created_at' => '2026-03-25T11:00:00Z',
                ],
            ],
            tickets: [],
        );

        $options = collect($service->buildUserFilterOptions($rows)['verification_statuses'])
            ->keyBy('value');

        $this->assertSame(1, $options['verified']['count']);
        $this->assertSame(1, $options['pending_verification']['count']);
        $this->assertSame(2, $options['unverified']['count']);
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

    public function test_user_rows_humanize_flexible_traffic_sources_and_support_searching_them(): void
    {
        $service = new AdminAnalyticsService;

        $rows = $service->buildUserRows(
            users: [
                [
                    'user_id' => 'user-1',
                    'full_name' => 'Dewi',
                    'email' => 'dewi@example.test',
                    'country' => 'ID',
                    'traffic_source' => 'media-partner',
                    'traffic_source_detail' => 'media-partner',
                    'traffic_medium' => 'social',
                    'traffic_campaign' => 'songkran-launch',
                    'traffic_referrer_host' => 'partner.example.com',
                    'traffic_landing_path' => '/register?utm_source=media-partner',
                    'created_at' => '2026-03-25T10:00:00Z',
                ],
            ],
            tickets: [],
        );

        $this->assertSame('Media Partner', $rows[0]['traffic_source_label']);
        $this->assertSame('Promo Link: songkran-launch', $rows[0]['traffic_source_caption']);

        $filtered = $service->filterUserRows($rows, ['q' => 'media partner']);

        $this->assertCount(1, $filtered);
        $this->assertSame('user-1', $filtered[0]['user_id']);
    }
}
