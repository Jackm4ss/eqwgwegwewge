<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PublicReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        config([
            'services.recaptcha.enabled' => false,
            'services.recaptcha.site_key' => null,
            'services.recaptcha.secret_key' => null,
        ]);
    }

    public function test_report_page_loads_the_spa_shell(): void
    {
        $this->get('/report')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_public_report_submission_saves_report_without_sending_email_notifications(): void
    {
        Mail::fake();

        $this->postJson('/api/report', [
            'report_type' => 'lost_item',
            'name' => 'Bang Raymond',
            'phone' => '+60123456789',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
            'email' => 'raymond@example.test',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Lost a black sling bag near the main stage around 18:30.',
            'staff_name' => 'Mina',
        ])->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', 'Your report has been submitted successfully.')
                ->missing('recipient')
                ->missing('delivery_status')
                ->missing('team_notification_sent')
                ->etc()
            )
            ->assertJsonPath('reference', fn ($reference) => is_string($reference) && preg_match('/^[A-Z]{1,2}\d{3}$/', $reference) === 1);

        Mail::assertNothingSent();

        $this->assertDatabaseHas('public_reports', [
            'report_type' => 'lost_item',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
        ]);
    }

    public function test_public_report_submission_requires_essential_fields(): void
    {
        $this->postJson('/api/report', [
            'report_type' => '',
            'name' => '',
            'phone' => '',
            'identity_type' => '',
            'identity_number' => '',
            'email' => '',
            'incident_date' => '',
            'incident_time' => '',
            'chronology' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'report_type',
                'name',
                'phone',
                'identity_type',
                'identity_number',
                'email',
                'incident_date',
                'incident_time',
                'chronology',
            ]);
    }

    public function test_public_report_submission_requires_recaptcha_token_when_enabled(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake();

        $this->postJson('/api/report', [
            'report_type' => 'lost_item',
            'name' => 'Bang Raymond',
            'phone' => '+60123456789',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
            'email' => 'raymond@example.test',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Lost a black sling bag near the main stage around 18:30.',
            'staff_name' => 'Mina',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['recaptcha_token']);

        Http::assertNothingSent();
    }

    public function test_public_report_submission_rejects_invalid_recaptcha_token_when_enabled(): void
    {
        Mail::fake();

        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $this->postJson('/api/report', [
            'report_type' => 'lost_item',
            'name' => 'Bang Raymond',
            'phone' => '+60123456789',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
            'email' => 'raymond@example.test',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Lost a black sling bag near the main stage around 18:30.',
            'staff_name' => 'Mina',
            'recaptcha_token' => 'invalid-token',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['recaptcha_token']);

        $this->assertDatabaseCount('public_reports', 0);
    }

    public function test_public_report_submission_accepts_valid_recaptcha_token_when_enabled(): void
    {
        Mail::fake();

        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $this->postJson('/api/report', [
            'report_type' => 'lost_item',
            'name' => 'Bang Raymond',
            'phone' => '+60123456789',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
            'email' => 'raymond@example.test',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Lost a black sling bag near the main stage around 18:30.',
            'staff_name' => 'Mina',
            'recaptcha_token' => 'valid-token',
        ])->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', 'Your report has been submitted successfully.')
                ->missing('recipient')
                ->missing('delivery_status')
                ->missing('team_notification_sent')
                ->etc()
            )
            ->assertJsonPath('reference', fn ($reference) => is_string($reference) && $reference !== '');

        Mail::assertNothingSent();
    }

    public function test_admin_reports_page_shows_saved_public_report_rows(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        PublicReport::query()->create([
            'case_id' => 'LC001',
            'report_type' => 'lost_locker_card',
            'case_prefix' => 'LC',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Bang Raymond',
            'email' => 'raymond@example.test',
            'phone' => '+60123456789',
            'identity_type' => 'passport',
            'identity_number' => 'A12345678',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Locker card missing near the music stage.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Public Reports')
            ->assertSee('LC001')
            ->assertSee('Bang Raymond')
            ->assertSee('No Action Yet')
            ->assertSee('tabler-eye', false)
            ->assertSee('tabler-trash', false)
            ->assertSee('id="reportDetailModal"', false)
            ->assertSee('data-report-detail-source="publicReportData-', false);
    }

    public function test_admin_public_report_page_shows_sidebar_menu_and_form_reference(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        PublicReport::query()->create([
            'case_id' => 'IS001',
            'report_type' => 'incident_security',
            'case_prefix' => 'IS',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Alya Putri',
            'email' => 'alya@example.test',
            'phone' => '+60111111111',
            'identity_type' => 'national_id',
            'identity_number' => 'A12345678',
            'incident_date' => '2026-04-01',
            'incident_time' => '20:15',
            'chronology' => 'Security incident reported near the main entrance.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/public-reports')
            ->assertOk()
            ->assertSee('Report Management')
            ->assertSee('Reporting & Export', false)
            ->assertSee('Public Report')
            ->assertSee('Every submitted report is stored in the admin dashboard for review.')
            ->assertSee('Required Fields')
            ->assertSee('Type of Report')
            ->assertSee('Document Type')
            ->assertSee('Document Number')
            ->assertSee('E-mail')
            ->assertSee('IS001')
            ->assertSee('No Action Yet')
            ->assertSee('tabler-eye', false)
            ->assertSee('tabler-trash', false);
    }

    public function test_admin_public_report_page_filters_reported_date_using_day_month_year_format(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        PublicReport::query()->create([
            'case_id' => 'IS001',
            'report_type' => 'incident_security',
            'case_prefix' => 'IS',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Alya Putri',
            'email' => 'alya@example.test',
            'phone' => '+60111111111',
            'identity_type' => 'national_id',
            'identity_number' => 'A12345678',
            'incident_date' => '2026-04-01',
            'incident_time' => '20:15',
            'chronology' => 'Security incident reported near the main entrance.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => '2026-04-01 10:00:00',
        ]);

        PublicReport::query()->create([
            'case_id' => 'LI001',
            'report_type' => 'lost_item',
            'case_prefix' => 'LI',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'phone' => '+60122222222',
            'identity_type' => 'passport',
            'identity_number' => 'B1234567',
            'incident_date' => '2026-04-02',
            'incident_time' => '21:00',
            'chronology' => 'Lost a bag near the food court.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => '2026-04-02 10:00:00',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/public-reports?from=02/04/2026&to=02/04/2026')
            ->assertOk()
            ->assertSee('LI001')
            ->assertSee('Budi Santoso')
            ->assertDontSee('IS001')
            ->assertDontSee('Alya Putri')
            ->assertSee('value="02/04/2026"', false);
    }

    public function test_admin_can_update_public_report_action_status_and_note(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $report = PublicReport::query()->create([
            'case_id' => 'LI001',
            'report_type' => 'lost_item',
            'case_prefix' => 'LI',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Aisyah',
            'email' => 'aisyah@example.test',
            'phone' => '+60122222222',
            'identity_type' => 'passport',
            'identity_number' => 'B9876543',
            'incident_date' => '2026-04-01',
            'incident_time' => '19:20',
            'chronology' => 'Lost a wallet near the food court.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/public-reports')
            ->put(route('admin.public-reports.update', $report), [
                'public_report_id' => $report->getKey(),
                'action_status' => PublicReport::ACTION_STATUS_RESOLVED,
                'admin_note' => 'Case closed by admin response team.',
            ])
            ->assertRedirect('/admin/public-reports')
            ->assertSessionHas('status', 'Public report LI001 updated successfully.');

        $this->assertDatabaseHas('public_reports', [
            'id' => $report->getKey(),
            'action_status' => PublicReport::ACTION_STATUS_RESOLVED,
            'admin_note' => 'Case closed by admin response team.',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/public-reports')
            ->assertOk()
            ->assertSee('Resolved')
            ->assertSee('Case closed by admin response team.');
    }

    public function test_admin_can_delete_public_report(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $report = PublicReport::query()->create([
            'case_id' => 'LI001',
            'report_type' => 'lost_item',
            'case_prefix' => 'LI',
            'case_sequence' => 1,
            'action_status' => PublicReport::ACTION_STATUS_PENDING,
            'admin_note' => null,
            'name' => 'Aisyah',
            'email' => 'aisyah@example.test',
            'phone' => '+60122222222',
            'identity_type' => 'passport',
            'identity_number' => 'B9876543',
            'incident_date' => '2026-04-01',
            'incident_time' => '19:20',
            'chronology' => 'Lost a wallet near the food court.',
            'staff_name' => 'Mina',
            'ip_address' => '127.0.0.1',
            'reported_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/public-reports')
            ->delete(route('admin.public-reports.destroy', $report))
            ->assertRedirect('/admin/public-reports')
            ->assertSessionHas('status', 'Public report LI001 was deleted successfully.');

        $this->assertDatabaseMissing('public_reports', [
            'id' => $report->getKey(),
        ]);
    }
}
