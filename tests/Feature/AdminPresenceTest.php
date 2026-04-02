<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\Admin\AdminPresenceService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPresenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 2,
            'cache.default' => 'array',
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_authenticated_admin_can_send_heartbeat_and_fetch_presence_statuses(): void
    {
        $admin = Admin::query()->where('email', 'admin01@songkran.local')->firstOrFail();
        $otherAdmin = Admin::query()->where('email', 'admin02@songkran.local')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.presence.heartbeat'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'admin_id' => (string) $admin->getKey(),
            ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.presence.statuses', [
                'ids' => [(string) $admin->getKey(), (string) $otherAdmin->getKey()],
            ]));

        $response->assertOk()
            ->assertJsonPath('statuses.'.$admin->getKey(), true)
            ->assertJsonPath('statuses.'.$otherAdmin->getKey(), false);
    }

    public function test_admin_logout_marks_presence_offline_immediately(): void
    {
        $admin = Admin::query()->where('email', 'admin01@songkran.local')->firstOrFail();
        $presence = app(AdminPresenceService::class);

        $presence->markOnline($admin);
        $this->assertTrue($presence->isOnline($admin));

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post(route('admin.logout'), [
                '_token' => 'csrf-token',
            ])
            ->assertRedirect(route('login'));

        $this->assertFalse($presence->isOnline($admin));
    }
}
