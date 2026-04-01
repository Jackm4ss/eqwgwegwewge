<?php

namespace Tests\Feature;

use App\Mail\PublicReportReceiptMail;
use App\Mail\PublicReportTeamMail;
use App\Models\Admin;
use App\Models\PublicReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
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

    public function test_public_report_submission_sends_notification_email(): void
    {
        Mail::fake();
        config()->set('report.notification_email', 'rs@rsgr.net');

        $this->postJson('/api/report', [
            'report_type' => 'lost_item',
            'name' => 'Bang Raymond',
            'phone' => '+60123456789',
            'identity_number' => 'A12345678',
            'email' => 'raymond@example.test',
            'incident_date' => '2026-04-01',
            'incident_time' => '18:30',
            'chronology' => 'Lost a black sling bag near the main stage around 18:30.',
            'staff_name' => 'Mina',
        ])->assertCreated()
            ->assertJson([
            'message' => 'Your report has been submitted successfully.',
            'recipient' => 'rs@rsgr.net',
        ])
            ->assertJsonPath('reference', fn ($reference) => is_string($reference) && preg_match('/^[A-Z]{1,2}\d{3}$/', $reference) === 1);

        Mail::assertSent(PublicReportTeamMail::class, function (PublicReportTeamMail $mail) {
            return $mail->hasTo('rs@rsgr.net')
                && $mail->report->report_type === 'lost_item'
                && $mail->report->name === 'Bang Raymond';
        });

        Mail::assertSent(PublicReportReceiptMail::class, function (PublicReportReceiptMail $mail) {
            return $mail->hasTo('raymond@example.test')
                && $mail->report->case_id !== '';
        });
    }

    public function test_public_report_submission_requires_essential_fields(): void
    {
        $this->postJson('/api/report', [
            'report_type' => '',
            'name' => '',
            'phone' => '',
            'incident_date' => '',
            'incident_time' => '',
            'chronology' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'report_type',
                'name',
                'phone',
                'incident_date',
                'incident_time',
                'chronology',
            ]);
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
            'name' => 'Bang Raymond',
            'email' => 'raymond@example.test',
            'phone' => '+60123456789',
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
            ->assertSee('Locker card missing near the music stage.')
            ->assertSee('id="reportDetailModal"', false)
            ->assertSee('data-report-detail-source="publicReportData-', false)
            ->assertSee('View full details');
    }
}
