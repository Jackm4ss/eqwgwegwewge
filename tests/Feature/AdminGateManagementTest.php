<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ScannerGate;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminGateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
            'scanner.posts' => ['Gate A', 'Gate B'],
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_admin_can_view_gate_management_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get('/admin/gates')
            ->assertOk()
            ->assertSee('Gate Management')
            ->assertSee('Create Gate')
            ->assertSee('Scanner Order Preview')
            ->assertSee('Manage Active Gates')
            ->assertSee('Gate A')
            ->assertSee('Gate B');
    }

    public function test_admin_can_create_update_and_delete_gate(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/gates', [
                '_token' => 'csrf-token',
                'name' => 'Gate VIP',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('admin.gates.index'))
            ->assertSessionHas('status', 'New gate added successfully.');

        $gate = ScannerGate::query()->where('name', 'Gate VIP')->firstOrFail();
        $this->assertSame(3, $gate->sort_order);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->put("/admin/gates/{$gate->id}", [
                '_token' => 'csrf-token',
                'name' => 'Gate VIP East',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.gates.index'))
            ->assertSessionHas('status', 'Gate updated successfully.');

        $this->assertDatabaseHas('scanner_gates', [
            'id' => $gate->id,
            'name' => 'Gate VIP East',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete("/admin/gates/{$gate->id}", [
                '_token' => 'csrf-token',
            ])
            ->assertRedirect(route('admin.gates.index'))
            ->assertSessionHas('status', 'Gate deleted successfully.');

        $this->assertDatabaseMissing('scanner_gates', [
            'id' => $gate->id,
        ]);
    }

    public function test_admin_cannot_delete_the_last_remaining_gate(): void
    {
        $admin = Admin::query()->firstOrFail();

        ScannerGate::query()->where('name', 'Gate B')->delete();
        $lastGate = ScannerGate::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete("/admin/gates/{$lastGate->id}", [
                '_token' => 'csrf-token',
            ])
            ->assertSessionHasErrors([
                'error' => 'At least one gate must remain available for scanner staff.',
            ]);

        $this->assertDatabaseHas('scanner_gates', [
            'id' => $lastGate->id,
        ]);
    }

    public function test_gate_management_handles_missing_gate_table_gracefully(): void
    {
        $admin = Admin::query()->firstOrFail();

        Schema::drop('scanner_gates');

        $this->actingAs($admin, 'admin')
            ->get('/admin/gates')
            ->assertOk()
            ->assertSee('scanner_gates')
            ->assertSee('php artisan migrate');

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/gates', [
                '_token' => 'csrf-token',
                'name' => 'Gate 11',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.gates.index'))
            ->assertSessionHasErrors([
                'error' => 'Gate Management is not ready because the scanner_gates table has not been created yet. Run php artisan migrate first.',
            ]);
    }
}
