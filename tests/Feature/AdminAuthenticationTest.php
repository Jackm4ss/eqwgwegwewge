<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminPresenceService;
use App\Services\Admin\AdminQrManagementLookupService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
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

    public function test_admin_login_reseeds_local_bootstrap_accounts_when_admins_are_missing(): void
    {
        $this->app['env'] = 'local';
        Admin::query()->delete();

        $this->assertSame(0, Admin::query()->where('role', 'admin')->count());

        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/admin/login', [
                'email' => 'admin01@songkran.local',
                'password' => 'LocalAdmin123!',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'redirect' => route('admin.dashboard'),
            ]);

        $this->assertAuthenticated('admin');
        $this->assertGreaterThan(0, Admin::query()->where('role', 'admin')->count());
        $this->assertDatabaseHas('admins', [
            'email' => 'admin01@songkran.local',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_login_bootstraps_local_sqlite_auth_when_schema_and_password_are_missing(): void
    {
        $this->app['env'] = 'local';
        config(['admin.bootstrap_password' => '']);

        Schema::dropIfExists('admins');

        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/admin/login', [
                'email' => 'admin01@songkran.local',
                'password' => '00000000',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'redirect' => route('admin.dashboard'),
            ]);

        $this->assertAuthenticated('admin');
        $this->assertDatabaseHas('admins', [
            'email' => 'admin01@songkran.local',
            'role' => 'admin',
            'is_active' => true,
        ]);
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
            ->assertSee('Analytics Dashboard')
            ->assertSee('Unique IP visitors')
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

    public function test_admin_qr_management_page_renders_for_authenticated_admin(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminQrManagementLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookup')->never();
        });

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.qr-management.index'));

        $response->assertOk()
            ->assertSee('QR Management')
            ->assertSee('Participant Lookup')
            ->assertSee('Entry Code')
            ->assertSee('Search Participant')
            ->assertSee('Scanner Management')
            ->assertSee(route('admin.qr-management.index'), false);
    }

    public function test_admin_qr_management_page_shows_event_day_lookup_result(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminQrManagementLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with([
                    'search_type' => 'email',
                    'email' => 'jokiaja@gmail.com',
                    'entry_code' => null,
                    'phone_country_code' => null,
                    'phone_national_number' => null,
                    'phone_number' => null,
                    'country' => null,
                    'identity_type' => null,
                    'identity_number' => null,
                ])
                ->andReturn([
                    'found' => true,
                    'participant' => [
                        'user_id' => 'user-123',
                        'full_name' => 'Joki',
                        'email' => 'jokiaja@gmail.com',
                        'phone_country_code' => '+64',
                        'phone_national_number' => '8123456789',
                        'phone_number' => '+648123456789',
                        'country' => 'NZ',
                        'country_label' => 'New Zealand',
                        'identity_type' => 'passport',
                        'identity_number' => 'A1234567',
                        'account_status' => 'active',
                        'verification_status' => 'verified',
                        'attendance_days_count' => 3,
                        'attendance_total_days' => 4,
                        'attendance_progress_percent' => 75,
                    ],
                    'ticket' => [
                        'ticket_id' => 'ticket-123',
                        'ticket_code' => '01KMHDQB6728DXYQWWCA9T7JKP',
                        'entry_code' => 'ABCD1234',
                        'entry_code_display' => '',
                        'attendance_status' => 'not_checked_in',
                        'qr_version' => 'v3',
                        'created_at' => '2026-03-24T20:26:00Z',
                        'updated_at' => '2026-03-24T20:26:00Z',
                        'regenerated_at' => '2026-03-25T03:00:00Z',
                    ],
                    'ticket_url' => 'https://example.test/ticket/signed',
                ]);
        });

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.qr-management.index', [
                'search_type' => 'email',
                'email' => 'jokiaja@gmail.com',
            ]));

        $response->assertOk()
            ->assertSee('Support Result')
            ->assertSee('Joki')
            ->assertSee('New Zealand')
            ->assertSee('ABCD-1234')
            ->assertSee('75%')
            ->assertSee('Priority Countries')
            ->assertSee('All Other Countries')
            ->assertSee('Search country or dial code...', false)
            ->assertSee('Reset Attendance')
            ->assertSee('Regenerate QR')
            ->assertSee('Open Ticket')
            ->assertSee('fi-nz', false)
            ->assertDontSee('QR Version')
            ->assertDontSee('Ticket Code')
            ->assertSee(route('admin.users.qr.reset', 'user-123'), false)
            ->assertSee(route('admin.users.qr.regenerate', 'user-123'), false);
    }

    public function test_admin_qr_management_page_normalizes_entry_code_lookup_request(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminQrManagementLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with([
                    'search_type' => 'entry_code',
                    'email' => null,
                    'entry_code' => 'ABCD1234',
                    'phone_country_code' => null,
                    'phone_national_number' => null,
                    'phone_number' => null,
                    'country' => null,
                    'identity_type' => null,
                    'identity_number' => null,
                ])
                ->andReturn([
                    'found' => false,
                    'message' => 'Participant data was not found.',
                ]);
        });

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.qr-management.index', [
                'search_type' => 'entry_code',
                'entry_code' => 'abcd-1234',
            ]));

        $response->assertOk()
            ->assertSee('Participant data was not found.')
            ->assertSee('Entry Code');
    }

    public function test_admin_management_menu_and_admin_account_list_render_for_authenticated_admin(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/admin-users');

        $response->assertOk()
            ->assertSee('Admin Management')
            ->assertSee('Admin Activity Log')
            ->assertSee('List User Admin')
            ->assertSee('Admin Account Directory')
            ->assertSee('All presence')
            ->assertSee('admin01@songkran.local')
            ->assertSee('Action')
            ->assertSee('Create Admin')
            ->assertSee('sweetalert2.css')
            ->assertSee('sweetalert2.js')
            ->assertSee('Delete this admin account?')
            ->assertSee(route('admin.admin-users.create'), false)
            ->assertSee(route('admin.admin-users.edit', $admin), false);
    }

    public function test_admin_can_filter_admin_directory_by_online_presence(): void
    {
        config(['cache.default' => 'array']);

        $admin = Admin::query()->where('email', 'admin01@songkran.local')->firstOrFail();
        $offlineAdmin = Admin::query()->where('email', 'admin02@songkran.local')->firstOrFail();

        app(AdminPresenceService::class)->markOnline($admin);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.index', [
                'presence' => 'online',
            ]));

        $response->assertOk()
            ->assertSee($admin->email)
            ->assertDontSee($offlineAdmin->email);
    }

    public function test_admin_can_filter_admin_directory_by_offline_presence(): void
    {
        config(['cache.default' => 'array']);

        $admin = Admin::query()->where('email', 'admin01@songkran.local')->firstOrFail();
        $onlineAdmin = Admin::query()->where('email', 'admin02@songkran.local')->firstOrFail();
        $offlineAdmin = Admin::query()->where('email', 'admin03@songkran.local')->firstOrFail();

        app(AdminPresenceService::class)->markOnline($onlineAdmin);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.index', [
                'presence' => 'offline',
            ]));

        $response->assertOk()
            ->assertSee($offlineAdmin->email)
            ->assertDontSee($onlineAdmin->email);
    }

    public function test_admin_can_view_create_admin_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.create'));

        $response->assertOk()
            ->assertSee('Create Admin')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Initial Password')
            ->assertSee('Create Admin');
    }

    public function test_admin_can_create_admin_from_create_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.admin-users.store'), [
                'name' => 'New Admin Account',
                'email' => 'new-admin-account@songkran.local',
                'is_active' => '1',
                'password' => 'NewAdminPass123!',
                'password_confirmation' => 'NewAdminPass123!',
            ]);

        $response->assertRedirect(route('admin.admin-users.index'))
            ->assertSessionHas('status', 'Admin account was created successfully.');

        $this->assertDatabaseHas('admins', [
            'name' => 'New Admin Account',
            'email' => 'new-admin-account@songkran.local',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_edit_admin_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.edit', $admin));

        $response->assertOk()
            ->assertSee('Edit Admin')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Account Status')
            ->assertSee('New Password')
            ->assertSee('Save Changes');
    }

    public function test_admin_can_update_admin_from_edit_page(): void
    {
        $admin = Admin::query()->firstOrFail();
        $targetAdmin = Admin::query()
            ->whereKeyNot($admin->getKey())
            ->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.admin-users.edit', $targetAdmin))
            ->put(route('admin.admin-users.update', $targetAdmin), [
                'name' => 'Festival Admin Updated',
                'email' => 'festival-admin-updated@songkran.local',
                'is_active' => '1',
                'password' => 'NewAdminPass123!',
                'password_confirmation' => 'NewAdminPass123!',
            ]);

        $response->assertRedirect(route('admin.admin-users.index'))
            ->assertSessionHas('status', 'Admin account was updated successfully.');

        $this->assertDatabaseHas('admins', [
            'id' => $targetAdmin->getKey(),
            'name' => 'Festival Admin Updated',
            'email' => 'festival-admin-updated@songkran.local',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_another_admin_from_directory(): void
    {
        $admin = Admin::query()->firstOrFail();
        $targetAdmin = Admin::query()
            ->whereKeyNot($admin->getKey())
            ->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.admin-users.destroy', $targetAdmin));

        $response->assertRedirect(route('admin.admin-users.index'))
            ->assertSessionHas('status', 'Admin account was deleted successfully.');

        $this->assertDatabaseMissing('admins', [
            'id' => $targetAdmin->getKey(),
        ]);
    }

    public function test_admin_cannot_delete_own_account_from_directory(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.admin-users.index'))
            ->delete(route('admin.admin-users.destroy', $admin));

        $response->assertRedirect(route('admin.admin-users.index'))
            ->assertSessionHasErrors([
                'error' => 'You cannot delete the account currently signed in.',
            ]);

        $this->assertDatabaseHas('admins', [
            'id' => $admin->getKey(),
        ]);
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
                                'entry_code_display' => 'ABCD-1234',
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
                                'entry_code_display' => 'WXYZ-6789',
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
                        'identity_types' => [
                            [
                                'value' => 'national_id',
                                'label' => 'Malaysia IC (MyKad)',
                                'count' => 1,
                            ],
                            [
                                'value' => 'passport',
                                'label' => 'Passport',
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
            ->assertSee('Refresh in')
            ->assertSee('After the timer ends, refresh browser to see the latest data.')
            ->assertSee('Document Type')
            ->assertSee('Malaysia IC (MyKad) (1)')
            ->assertSee('Passport (1)')
            ->assertSee('Filter participants by country, document type, verification, check-in status, or suspected email typos.')
            ->assertSee('If you just scanned or edited data, wait for the timer, then refresh this page.')
            ->assertSee('Search includes name, email, document type, identity number, ticket code, country, and traffic source.')
            ->assertSee('QR Created, 25 Mar 2026, 03:26 AM')
            ->assertSee('QR Regenerated, 25 Mar 2026, 03:26 AM')
            ->assertSee('id="userOverviewModal"', false)
            ->assertSee('data-user-detail-source="userOverviewData-user-123"', false)
            ->assertSee('/admin/users/user-123/edit', false)
            ->assertSee('/admin/users/user-456/edit', false)
            ->assertSee('Participant Overview')
            ->assertSee('Participant Details')
            ->assertSee('Summary of key participant information')
            ->assertSee('Entry Code')
            ->assertSee('ABCD-1234')
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
                        'entry_code_display' => 'ABCD-1234',
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
            ->assertSee('Refresh in')
            ->assertSee('After the timer ends, refresh browser to see the latest data.')
            ->assertSee('Phone Number / WhatsApp')
            ->assertSee('Document Type')
            ->assertSee('Malaysia IC (MyKad)')
            ->assertSee('Document Number')
            ->assertSee('Priority Countries')
            ->assertSee('All Other Countries')
            ->assertSee('Search country or dial code...', false)
            ->assertSee('Search nationality...', false)
            ->assertSee('Choose the country code first, then enter the number without the leading zero.')
            ->assertSee('name="_simple_phone_mode"', false)
            ->assertSee('name="_skip_phone_index_sync"', false)
            ->assertSee('name="phone_number"', false)
            ->assertSee('Save Changes')
            ->assertSee('Ticket Snapshot')
            ->assertSee('Entry Code')
            ->assertSee('ABCD-1234')
            ->assertDontSee('QR Actions')
            ->assertDontSee('Reset Attendance')
            ->assertDontSee('Generate QR')
            ->assertDontSee('Reset QR Code')
            ->assertDontSee('Regenerate QR Code');
    }

    public function test_admin_update_user_from_edit_page_stays_on_edit_screen(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('updateUserByAdmin')
                ->once()
                ->with('user-123', [
                    'full_name' => 'Joki Updated',
                    'email' => 'joki.updated@example.test',
                    '_skip_phone_index_sync' => true,
                    'phone_country_code' => '+60',
                    'phone_national_number' => '123456789',
                    'phone_number' => '+60123456789',
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '320240002222',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                ])
                ->andReturn([
                    'user_id' => 'user-123',
                    'email' => 'joki.updated@example.test',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                ]);
        });

        $this->mock(AdminAuditLogger::class, function ($mock): void {
            $mock->shouldReceive('log')
                ->once();
        });

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.users.edit', 'user-123'))
            ->put(route('admin.users.update', 'user-123'), [
                'full_name' => 'Joki Updated',
                'email' => 'Joki.Updated@Example.Test',
                '_skip_phone_index_sync' => '1',
                'phone_country_code' => '+60',
                'phone_national_number' => '123456789',
                'phone_number' => '+60123456789',
                'country' => 'my',
                'identity_type' => 'national_id',
                'identity_number' => '320240002222',
                'account_status' => 'active',
                'verification_status' => 'verified',
            ]);

        $response->assertRedirect(route('admin.users.edit', 'user-123'))
            ->assertSessionHas('status', 'Participant data was updated successfully.');
    }

    public function test_admin_attendance_page_renders_near_realtime_summary_filters_and_participant_table(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('attendanceManagementPage')
                ->once()
                ->with([])
                ->andReturn([
                    'rows' => new LengthAwarePaginator(
                        [
                            [
                                'participant_key' => 'user:user-123',
                                'user_id' => 'user-123',
                                'full_name' => 'Alya Putri',
                                'email' => 'alya@example.test',
                                'initials' => 'AP',
                                'country' => 'MY',
                                'country_label' => 'Malaysia',
                                'entry_code_display' => 'ABCD-1234',
                                'attendance_days_count' => 1,
                                'attendance_total_days' => 2,
                                'attendance_progress_percent' => 50,
                                'attendance_status' => 'checked_in',
                                'checked_in_at' => '2026-03-25T09:00:00Z',
                                'scanner_name' => 'Gate A',
                                'scanner_role' => 'staff',
                                'scanner_id' => 'scanner-1',
                                'latest_scan_at' => '2026-03-25T09:05:00Z',
                                'latest_scan_result' => 'duplicate',
                            ],
                            [
                                'participant_key' => 'user:user-456',
                                'user_id' => 'user-456',
                                'full_name' => 'Rafi Hakim',
                                'email' => 'rafi@example.test',
                                'initials' => 'RH',
                                'country' => 'SG',
                                'country_label' => 'Singapore',
                                'entry_code_display' => 'WXYZ-6789',
                                'attendance_days_count' => 0,
                                'attendance_total_days' => 2,
                                'attendance_progress_percent' => 0,
                                'attendance_status' => 'not_checked_in',
                                'checked_in_at' => null,
                                'scanner_name' => 'Gate B',
                                'scanner_role' => 'staff',
                                'scanner_id' => 'scanner-2',
                                'latest_scan_at' => '2026-03-25T09:10:00Z',
                                'latest_scan_result' => 'invalid',
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
                    'overview' => [
                        'total_attendance' => 2,
                        'checked_in' => 1,
                        'repeat_scans' => 1,
                        'needs_review' => 1,
                        'gate_counts' => [
                            [
                                'label' => 'Gate A',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Gate B',
                                'count' => 1,
                            ],
                        ],
                    ],
                    'filter_options' => [
                        'countries' => [
                            [
                                'value' => 'MY',
                                'label' => 'Malaysia',
                                'count' => 1,
                            ],
                            [
                                'value' => 'SG',
                                'label' => 'Singapore',
                                'count' => 1,
                            ],
                        ],
                        'identity_types' => [
                            [
                                'value' => 'national_id',
                                'label' => 'Malaysia IC (MyKad)',
                                'count' => 1,
                            ],
                            [
                                'value' => 'passport',
                                'label' => 'Passport',
                                'count' => 1,
                            ],
                        ],
                        'attendance_statuses' => [
                            [
                                'value' => 'checked_in',
                                'label' => 'Checked In',
                                'count' => 1,
                            ],
                            [
                                'value' => 'not_checked_in',
                                'label' => 'Not Checked In',
                                'count' => 1,
                            ],
                        ],
                        'scan_results' => [
                            [
                                'value' => 'duplicate',
                                'label' => 'Repeat Scans',
                                'count' => 1,
                            ],
                            [
                                'value' => 'needs_review',
                                'label' => 'Needs Review',
                                'count' => 1,
                            ],
                            [
                                'value' => 'success',
                                'label' => 'Checked In',
                                'count' => 1,
                            ],
                        ],
                        'scan_posts' => [
                            [
                                'value' => 'Gate A',
                                'label' => 'Gate A',
                                'count' => 2,
                            ],
                            [
                                'value' => 'Gate B',
                                'label' => 'Gate B',
                                'count' => 1,
                            ],
                        ],
                    ],
                    'sync_status' => [
                        'state' => 'fresh',
                        'source' => 'read_model',
                        'last_synced_at_utc' => '2026-03-25T09:10:00Z',
                        'fresh_within_seconds' => 15,
                        'degraded_after_seconds' => 60,
                        'fallback_after_seconds' => 300,
                        'relative_label' => 'Refresh in 15s',
                        'helper_label' => 'After the timer ends, refresh browser to see the latest data.',
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance');

        $response->assertOk()
            ->assertSee('Data Attendance')
            ->assertSee('Near realtime attendance directory for participant scans, gate activity, and issue follow-up.')
            ->assertSee('Refresh in')
            ->assertSee('After the timer ends, refresh browser to see the latest data.')
            ->assertSee('Total Attendance')
            ->assertSee('Checked In')
            ->assertSee('Repeat Scans')
            ->assertSee('Needs Review')
            ->assertSee('Gate A')
            ->assertSee('Gate B')
            ->assertSee('Filters')
            ->assertSee('Country')
            ->assertSee('Document Type')
            ->assertSee('Check-In Status')
            ->assertSee('Scan Result')
            ->assertSee('Scan Post')
            ->assertSee('From Date')
            ->assertSee('To Date')
            ->assertSee('Search participant, entry code, passport/MyKad, phone')
            ->assertSee('Malaysia IC (MyKad) (1)')
            ->assertSee('Passport (1)')
            ->assertSee('Repeat Scans (1)')
            ->assertSee('Needs Review (1)')
            ->assertSee('Participant')
            ->assertSee('Entry Code')
            ->assertSee('Attendance')
            ->assertSee('Alya Putri')
            ->assertSee('Rafi Hakim')
            ->assertSee('ABCD-1234')
            ->assertSee('WXYZ-6789')
            ->assertSee('Malaysia')
            ->assertSee('Singapore')
            ->assertSee('Repeat Scan')
            ->assertSee('Not Checked In')
            ->assertSee('2 participants found');
    }

    public function test_admin_attendance_page_passes_filters_to_management_page_and_renders_requested_result_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->mock(AdminPanelService::class, function ($mock): void {
            $mock->shouldReceive('attendanceManagementPage')
                ->once()
                ->withArgs(function (array $filters): bool {
                    return ($filters['q'] ?? null) === 'MYKAD-7788'
                        && ($filters['country'] ?? null) === 'MY'
                        && ($filters['identity_type'] ?? null) === 'national_id'
                        && ($filters['attendance_status'] ?? null) === 'checked_in'
                        && ($filters['scan_result'] ?? null) === 'needs_review'
                        && ($filters['scanner_post'] ?? null) === 'Gate C'
                        && ($filters['from'] ?? null) === '2026-03-24'
                        && ($filters['to'] ?? null) === '2026-03-25'
                        && ($filters['page'] ?? null) === '2'
                        && ($filters['per_page'] ?? null) === '10';
                })
                ->andReturn([
                    'rows' => new LengthAwarePaginator(
                        [
                            [
                                'participant_key' => 'user:user-999',
                                'user_id' => 'user-999',
                                'full_name' => 'Nur Aisyah',
                                'email' => 'nur.aisyah@example.test',
                                'initials' => 'NA',
                                'country' => 'MY',
                                'country_label' => 'Malaysia',
                                'entry_code_display' => 'MNOP-3456',
                                'attendance_days_count' => 2,
                                'attendance_total_days' => 2,
                                'attendance_progress_percent' => 100,
                                'attendance_status' => 'checked_in',
                                'checked_in_at' => '2026-03-25T10:00:00Z',
                                'scanner_name' => 'Gate C',
                                'scanner_role' => 'staff',
                                'scanner_id' => 'scanner-3',
                                'latest_scan_at' => '2026-03-25T10:00:00Z',
                                'latest_scan_result' => 'success',
                            ],
                        ],
                        12,
                        10,
                        2,
                        [
                            'path' => route('admin.attendance.index'),
                            'pageName' => 'page',
                        ],
                    ),
                    'overview' => [
                        'total_attendance' => 12,
                        'checked_in' => 10,
                        'repeat_scans' => 1,
                        'needs_review' => 1,
                        'gate_counts' => [
                            [
                                'label' => 'Gate C',
                                'count' => 12,
                            ],
                        ],
                    ],
                    'filter_options' => [
                        'countries' => [
                            [
                                'value' => 'MY',
                                'label' => 'Malaysia',
                                'count' => 12,
                            ],
                        ],
                        'identity_types' => [
                            [
                                'value' => 'national_id',
                                'label' => 'Malaysia IC (MyKad)',
                                'count' => 12,
                            ],
                        ],
                        'attendance_statuses' => [
                            [
                                'value' => 'checked_in',
                                'label' => 'Checked In',
                                'count' => 10,
                            ],
                        ],
                        'scan_results' => [
                            [
                                'value' => 'needs_review',
                                'label' => 'Needs Review',
                                'count' => 1,
                            ],
                        ],
                        'scan_posts' => [
                            [
                                'value' => 'Gate C',
                                'label' => 'Gate C',
                                'count' => 12,
                            ],
                        ],
                    ],
                    'sync_status' => [
                        'state' => 'fresh',
                        'source' => 'read_model',
                        'last_synced_at_utc' => '2026-03-25T10:00:00Z',
                        'fresh_within_seconds' => 15,
                        'degraded_after_seconds' => 60,
                        'fallback_after_seconds' => 300,
                        'relative_label' => 'Refresh in 15s',
                        'helper_label' => 'After the timer ends, refresh browser to see the latest data.',
                    ],
                ]);

            $mock->shouldReceive('firestoreAvailable')
                ->once()
                ->andReturnTrue();
        });

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance?q=MYKAD-7788&country=MY&identity_type=national_id&attendance_status=checked_in&scan_result=needs_review&scanner_post=Gate%20C&from=2026-03-24&to=2026-03-25&page=2&per_page=10');

        $response->assertOk()
            ->assertSee('12 participants found')
            ->assertSee('Nur Aisyah')
            ->assertSee('MNOP-3456')
            ->assertSee('Gate C')
            ->assertSee('value="MYKAD-7788"', false)
            ->assertSee('value="2026-03-24"', false)
            ->assertSee('value="2026-03-25"', false)
            ->assertSee('Needs Review (1)')
            ->assertSee('Malaysia IC (MyKad)')
            ->assertDontSee('No attendance data matches the current filters.');
    }
}
