<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seed_command_creates_admin_accounts_with_explicit_password(): void
    {
        Artisan::call('admin:seed', [
            '--password' => 'SharedAdminPass123!',
            '--count' => 3,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Seeded 3 admin accounts.', $output);
        $this->assertSame(3, Admin::count());
        $this->assertTrue(Hash::check('SharedAdminPass123!', Admin::query()->firstOrFail()->password));
        $this->assertDatabaseHas('admins', ['email' => 'admin01@songkran.local']);
        $this->assertDatabaseHas('admins', ['email' => 'admin03@songkran.local']);
    }

    public function test_admin_seed_command_requires_password_when_not_configured(): void
    {
        config(['admin.bootstrap_password' => null]);

        $exitCode = Artisan::call('admin:seed');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Admin seed password is required.', $output);
        $this->assertSame(0, Admin::count());
    }
}
