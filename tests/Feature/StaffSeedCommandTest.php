<?php

namespace Tests\Feature;

use App\Models\ScannerStation;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_seed_command_creates_staff_accounts_and_default_scanner_stations(): void
    {
        Artisan::call('staff:seed', [
            '--password' => 'SharedStaffPass123!',
            '--count' => 4,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Seeded 4 staff accounts.', $output);
        $this->assertSame(4, StaffUser::count());
        $this->assertSame(6, ScannerStation::count());
        $this->assertTrue(Hash::check('SharedStaffPass123!', StaffUser::query()->firstOrFail()->password));
        $this->assertDatabaseHas('staff_users', ['email' => 'staff01@songkran.local']);
        $this->assertDatabaseHas('staff_users', ['email' => 'staff04@songkran.local']);
        $this->assertDatabaseHas('scanner_stations', ['scanner_id' => 'scanner-north-a']);
        $this->assertDatabaseHas('scanner_stations', ['scanner_id' => 'scanner-south-b']);
    }

    public function test_staff_seed_command_requires_password_when_not_configured(): void
    {
        config(['staff.bootstrap_password' => null]);

        $exitCode = Artisan::call('staff:seed');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Staff seed password is required.', $output);
        $this->assertSame(0, StaffUser::count());
        $this->assertSame(0, ScannerStation::count());
    }
}
