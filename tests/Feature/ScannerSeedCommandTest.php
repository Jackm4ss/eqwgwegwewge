<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScannerSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_seed_command_creates_scanner_accounts_with_explicit_password(): void
    {
        Artisan::call('scanner:seed', [
            '--password' => 'ScannerPass123!',
            '--count' => 2,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Seeded 2 scanner staff accounts.', $output);
        $this->assertSame(2, Admin::query()->where('role', 'scanner')->count());
        $this->assertTrue(Hash::check('ScannerPass123!', Admin::query()->where('email', 'scanner01@songkran.local')->firstOrFail()->password));
        $this->assertDatabaseHas('admins', ['email' => 'scanner01@songkran.local', 'role' => 'scanner']);
        $this->assertDatabaseHas('admins', ['email' => 'scanner02@songkran.local', 'role' => 'scanner']);
    }

    public function test_scanner_seed_command_requires_password_when_not_configured(): void
    {
        config(['scanner.bootstrap_password' => null]);

        $exitCode = Artisan::call('scanner:seed');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Scanner seed password is required.', $output);
        $this->assertSame(0, Admin::query()->where('role', 'scanner')->count());
    }
}
