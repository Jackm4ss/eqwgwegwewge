<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ScannerGate;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
            'scanner.bootstrap_password' => 'ScannerPass123!',
            'scanner.seed_count' => 1,
            'scanner.posts' => ['Gate A', 'Gate B'],
        ]);

        $this->seed(AdminSeeder::class);
        $this->seed(ScannerStaffSeeder::class);

        ScannerGate::query()->delete();

        foreach (config('scanner.posts', ['Gate A']) as $index => $gateName) {
            ScannerGate::query()->create([
                'name' => (string) $gateName,
                'sort_order' => $index,
            ]);
        }
    }

    public function test_staff_login_page_loads_the_spa_shell(): void
    {
        $this->get('/staff/login')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_guest_is_redirected_from_staff_home_to_staff_login(): void
    {
        $this->get('/staff')
            ->assertRedirect('/staff/login');
    }

    public function test_scanner_login_returns_redirect_to_staff_home(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/staff/login', [
                'email' => 'scanner01@songkran.local',
                'password' => 'ScannerPass123!',
                'scanner_post' => 'Gate B',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'redirect' => url('/staff'),
                'scanner_post' => 'Gate B',
            ])
            ->assertSessionHas('staff.scanner_post', 'Gate B');

        $this->assertAuthenticated('admin');
    }

    public function test_scanner_login_requires_a_valid_gate_selection(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/staff/login', [
                'email' => 'scanner01@songkran.local',
                'password' => 'ScannerPass123!',
                'scanner_post' => 'Gate Z',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scanner_post']);
    }

    public function test_staff_login_rejects_admin_credentials(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/staff/login', [
                'email' => 'admin01@songkran.local',
                'password' => 'LocalAdmin123!',
                'scanner_post' => 'Gate A',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials do not match our records.',
            ]);
    }

    public function test_admin_login_rejects_scanner_credentials(): void
    {
        $response = $this->withSession(['_token' => 'csrf-token'])
            ->postJson('/admin/login', [
                'email' => 'scanner01@songkran.local',
                'password' => 'ScannerPass123!',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials do not match our records.',
            ]);
    }

    public function test_admin_cannot_access_staff_session_endpoint(): void
    {
        $admin = Admin::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->getJson('/staff/session')
            ->assertForbidden();
    }

    public function test_scanner_is_redirected_away_from_admin_dashboard(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->actingAs($scanner, 'admin')
            ->get('/admin/dashboard')
            ->assertRedirect('/staff');
    }
}
