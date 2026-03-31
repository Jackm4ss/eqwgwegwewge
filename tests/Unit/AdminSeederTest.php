<?php

namespace Tests\Unit;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_eight_admins_with_shared_role(): void
    {
        config([
            'admin.bootstrap_password' => 'SharedAdminPass123!',
            'admin.seed_count' => 8,
            'admin.seed_email_domain' => 'songkranfestival.my',
        ]);

        $this->seed(AdminSeeder::class);

        $this->assertSame(8, Admin::count());
        $this->assertSame(8, Admin::query()->where('role', 'admin')->count());
        $this->assertTrue(Hash::check('SharedAdminPass123!', Admin::firstOrFail()->password));
        $this->assertDatabaseHas('admins', ['email' => 'admin01@songkranfestival.my']);
        $this->assertDatabaseHas('admins', ['email' => 'admin08@songkranfestival.my']);
    }
}
