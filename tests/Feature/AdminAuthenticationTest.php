<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\Admin\AdminPanelService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 8,
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_admin_login_returns_redirect_to_dashboard(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/admin/login', [
                'email' => 'admin01@songkran.local',
                'password' => 'LocalAdmin123!',
                'remember' => true,
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'redirect' => route('admin.dashboard'),
            ]);

        $this->assertAuthenticated('admin');
    }

    public function test_admin_login_rejects_invalid_credentials_with_generic_message(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/admin/login', [
                'email' => 'admin01@songkran.local',
                'password' => 'wrong-password',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials do not match our records.',
            ]);

        $this->assertGuest('admin');
    }

    public function test_admin_dashboard_requires_authenticated_admin(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_is_redirected_away_from_public_login_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get('/login');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_dashboard_renders_date_filters_and_selected_range_summary(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('dashboardData')
                ->once()
                ->with([
                    'from' => '2026-03-24',
                    'to' => '2026-03-25',
                ])
                ->andReturn([
                    'total_registrations' => 3,
                    'daily_scan_statistics' => [
                        'total_scans' => 4,
                        'successful_scans' => 3,
                        'unique_visitors' => 3,
                        'duplicate_scans' => 1,
                        'invalid_scans' => 0,
                    ],
                    'visitor_chart' => [
                        'labels' => ['24 Mar', '25 Mar'],
                        'series' => [1, 2],
                    ],
                    'date_range' => [
                        'from' => '2026-03-24',
                        'to' => '2026-03-25',
                        'days' => 2,
                        'is_filtered' => true,
                        'label' => '24 Mar 2026 - 25 Mar 2026',
                        'badge' => 'Selected Range',
                        'registration_badge' => 'Selected Range',
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/dashboard?from=2026-03-24&to=2026-03-25');

        $response->assertOk()
            ->assertSee('Dashboard Filters')
            ->assertSee('From date')
            ->assertSee('To date')
            ->assertSee('Showing scan data for')
            ->assertSee('24 Mar 2026 - 25 Mar 2026')
            ->assertSee('Scan Statistics')
            ->assertSee('Visitor Trend');
    }

    public function test_admin_users_page_renders_for_authenticated_admin(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/users');

        $response->assertOk()
            ->assertSee('User Management')
            ->assertSee('Filters');
    }

    public function test_admin_can_delete_user_from_listing(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('deleteUserByAdmin')
                ->once()
                ->with('user-123')
                ->andReturn([
                    'user' => [
                        'user_id' => 'user-123',
                        'full_name' => 'Alya Putri',
                        'email' => 'alya@example.test',
                    ],
                    'ticket' => [
                        'ticket_id' => 'ticket-123',
                        'ticket_code' => 'TICKET-123',
                    ],
                ]);
        });

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete('/admin/users/user-123', [
                '_token' => 'csrf-token',
            ]);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Participant was deleted successfully.');
    }

    public function test_admin_users_page_shows_compact_rows_and_lightweight_detail_modal(): void
    {
        $admin = Admin::query()->firstOrFail();

        config([
            'app.timezone' => 'Asia/Jakarta',
            'admin.event.timezone' => 'Asia/Jakarta',
        ]);

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('userManagementPage')
                ->once()
                ->andReturn([
                    'users' => new LengthAwarePaginator(
                        [
                            [
                                'user_id' => 'user-123',
                                'full_name' => 'Joki',
                                'email' => 'jokiaja@gmail.com',
                                'phone_number' => '+8558521467422',
                                'country' => 'NZ',
                                'country_label' => 'New Zealand',
                                'identity_type' => 'passport',
                                'identity_number' => '320240002222',
                                'ticket_code' => '01KMHDQB6728DXYQWWCA9T7JKP',
                                'ticket_created_at' => '2026-03-24T20:26:00Z',
                                'ticket_regenerated_at' => null,
                                'attendance_status' => 'not_checked_in',
                                'account_status' => 'active',
                                'verification_status' => 'verified',
                                'traffic_source_label' => 'Instagram',
                                'traffic_source_caption' => 'Promo Link: songkran-launch',
                                'traffic_campaign' => 'songkran-launch',
                                'traffic_referrer_host' => 'l.instagram.com',
                                'traffic_medium_label' => 'Social',
                                'traffic_landing_path' => '/register?utm_source=instagram',
                                'traffic_captured_at' => '2026-03-24T20:00:00Z',
                            ],
                            [
                                'user_id' => 'user-456',
                                'full_name' => 'Alya',
                                'email' => 'alya@example.test',
                                'country' => 'JP',
                                'country_label' => 'Japan',
                                'ticket_code' => '01KMHDQB6728DXYQWWCA9T7JKQ',
                                'ticket_created_at' => '2026-03-24T20:20:00Z',
                                'ticket_regenerated_at' => '2026-03-24T20:26:00Z',
                                'attendance_status' => 'not_checked_in',
                                'account_status' => 'active',
                                'verification_status' => 'verified',
                            ],
                        ],
                        2,
                        10,
                        1,
                        [
                            'path' => route('admin.users.index'),
                            'pageName' => 'page',
                        ],
                    ),
                    'overview' => [
                        'total_users' => 2,
                        'verified_users' => 2,
                        'checked_in_users' => 0,
                        'follow_up_users' => 0,
                        'countries_count' => 2,
                    ],
                    'filter_options' => [
                        'countries' => [
                            [
                                'value' => 'JP',
                                'label' => 'Japan',
                                'count' => 1,
                            ],
                            [
                                'value' => 'NZ',
                                'label' => 'New Zealand',
                                'count' => 1,
                            ],
                        ],
                        'verification_statuses' => [],
                        'attendance_statuses' => [],
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/users');

        $response->assertOk()
            ->assertSee('fi fis fi-nz user-country-flag', false)
            ->assertSee('data-flag="jp"', false)
            ->assertSee('QR Created, 25 Mar 2026, 03:26 AM')
            ->assertSee('QR Regenerated, 25 Mar 2026, 03:26 AM')
            ->assertSee('id="userOverviewModal"', false)
            ->assertSee('data-user-detail-source="userOverviewData-user-123"', false)
            ->assertSee('/admin/users/user-123/edit', false)
            ->assertSee('/admin/users/user-456/edit', false)
            ->assertSee('Participant Overview')
            ->assertSee('Participant Details')
            ->assertSee('Summary of key participant information')
            ->assertSee('Registrant Source')
            ->assertSee('Promo Link: songkran-launch')
            ->assertDontSee('js-user-editor-modal', false)
            ->assertDontSee('Participant Information')
            ->assertDontSee('Save Changes')
            ->assertDontSee('Monitoring Scan')
            ->assertDontSee('ðŸ‡³ðŸ‡¿')
            ->assertDontSee('ðŸ‡¯ðŸ‡µ');
    }

    public function test_admin_edit_user_page_renders_full_form(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('findUser')
                ->once()
                ->with('user-123')
                ->andReturn([
                    'user_id' => 'user-123',
                    'full_name' => 'Joki',
                    'email' => 'jokiaja@gmail.com',
                    'phone_number' => '+8558521467422',
                    'country' => 'MY',
                    'country_label' => 'Malaysia',
                    'identity_type' => 'national_id',
                    'identity_number' => '320240002222',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'ticket' => [
                        'ticket_id' => 'ticket-123',
                        'ticket_code' => '01KMHDQB6728DXYQWWCA9T7JKP',
                        'qr_version' => 'v1',
                        'attendance_status' => 'not_checked_in',
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/users/user-123/edit');

        $response->assertOk()
            ->assertSee('Edit Participant')
            ->assertSee('Phone Number / WhatsApp')
            ->assertSee('Document Type')
            ->assertSee('Malaysia IC (MyKad)')
            ->assertSee('Document Number')
            ->assertSee('Save Changes')
            ->assertSee('Ticket Snapshot')
            ->assertSee('Ticket Code')
            ->assertSee('QR Actions')
            ->assertSee('Reset Attendance')
            ->assertSee('Generate QR')
            ->assertDontSee('Reset QR Code')
            ->assertDontSee('Regenerate QR Code');
    }

    public function test_admin_attendance_page_uses_friendly_operational_labels(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('attendanceData')
                ->once()
                ->andReturn([
                    'history' => new LengthAwarePaginator(
                        [
                            [
                                'scanned_at' => '2026-03-25T09:00:00Z',
                                'scan_date' => '2026-03-25',
                                'ticket_code' => 'TICKET-123',
                                'user_id' => 'user-123',
                                'scanner_name' => 'Gate A',
                                'scanner_role' => 'staff',
                                'scanner_id' => 'scanner-1',
                                'result' => 'success',
                            ],
                            [
                                'scanned_at' => '2026-03-25T09:05:00Z',
                                'scan_date' => '2026-03-25',
                                'ticket_code' => 'TICKET-123',
                                'user_id' => 'user-123',
                                'scanner_name' => 'Gate A',
                                'scanner_role' => 'staff',
                                'scanner_id' => 'scanner-1',
                                'result' => 'duplicate',
                            ],
                        ],
                        2,
                        10,
                        1,
                        [
                            'path' => route('admin.attendance.index'),
                            'pageName' => 'page',
                        ],
                    ),
                    'daily_attendance' => [
                        [
                            'scan_date' => '2026-03-25',
                            'total_scans' => 2,
                            'successful_attendance' => 1,
                            'duplicate_scans' => 1,
                            'invalid_scans' => 0,
                        ],
                    ],
                    'scanner_activity' => [
                        [
                            'scanner_name' => 'Gate A',
                            'scanner_role' => 'staff',
                            'scanner_id' => 'scanner-1',
                            'total_scans' => 2,
                            'successful_scans' => 1,
                            'duplicate_scans' => 1,
                            'invalid_scans' => 0,
                            'last_scanned_at' => '2026-03-25T09:05:00Z',
                        ],
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance');

        $response->assertOk()
            ->assertSee('Attendance Monitoring')
            ->assertSee('How to read the statuses')
            ->assertSee('Checked In')
            ->assertSee('Already Scanned')
            ->assertSee('Needs Review')
            ->assertSee('Latest Scan Activity')
            ->assertSee('Daily Summary')
            ->assertSee('Scan Posts / Staff')
            ->assertSee('Download CSV');
    }
}
