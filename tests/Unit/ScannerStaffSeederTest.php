<?php

namespace Tests\Unit;

use App\Models\Admin;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScannerStaffSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_staff_seeder_creates_scanner_accounts_only(): void
    {
        config([
            'scanner.bootstrap_password' => 'ScannerPass123!',
            'scanner.seed_count' => 3,
        ]);

        $this->seed(ScannerStaffSeeder::class);

        $this->assertSame(3, Admin::query()->where('role', 'scanner')->count());
        $this->assertSame(0, Admin::query()->where('role', 'admin')->count());
        $this->assertTrue(Hash::check('ScannerPass123!', Admin::query()->where('role', 'scanner')->firstOrFail()->password));
    }
}
