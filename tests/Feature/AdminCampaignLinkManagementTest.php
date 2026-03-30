<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CampaignLink;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCampaignLinkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
            'app.frontend_homepage_url' => 'https://songkran.test',
            'admin.future_urls.register' => 'https://register.songkran.test',
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_admin_can_view_campaign_link_management_page(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get('/admin/campaign-links')
            ->assertOk()
            ->assertSee('Campaign Links')
            ->assertSee('Create Campaign Link')
            ->assertSee('Campaign Link Library')
            ->assertSee('Simple Link Builder');
    }

    public function test_admin_can_create_update_and_delete_campaign_link(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/campaign-links', [
                '_token' => 'csrf-token',
                'name' => 'TikTok Bio April',
                'slug' => 'fb',
                'destination' => 'register',
                'source' => 'TikTok',
                'medium' => 'Bio',
                'campaign' => 'April 2026',
                'utm_content' => 'Video A',
                'notes' => 'Primary bio link',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.campaign-links.index'))
            ->assertSessionHas('status', 'Campaign link created successfully.');

        $campaignLink = CampaignLink::query()->where('name', 'TikTok Bio April')->firstOrFail();

        $this->assertSame('register', $campaignLink->destination);
        $this->assertSame('fb', $campaignLink->slug);
        $this->assertSame('tiktok', $campaignLink->source);
        $this->assertSame('bio', $campaignLink->medium);
        $this->assertSame('april-2026', $campaignLink->campaign);
        $this->assertSame('video-a', $campaignLink->utm_content);
        $this->assertTrue($campaignLink->is_active);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->put("/admin/campaign-links/{$campaignLink->id}", [
                '_token' => 'csrf-token',
                'campaign_link_id' => (string) $campaignLink->id,
                'name' => 'Instagram Story May',
                'slug' => 'wa',
                'destination' => 'homepage',
                'source' => 'Instagram',
                'medium' => 'Story',
                'campaign' => 'May 2026',
                'utm_content' => 'Creative B',
                'notes' => 'Paused after first burst',
            ])
            ->assertRedirect(route('admin.campaign-links.index'))
            ->assertSessionHas('status', 'Campaign link updated successfully.');

        $this->assertDatabaseHas('campaign_links', [
            'id' => $campaignLink->id,
            'name' => 'Instagram Story May',
            'slug' => 'wa',
            'destination' => 'homepage',
            'source' => 'instagram',
            'medium' => 'story',
            'campaign' => 'may-2026',
            'utm_content' => 'creative-b',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete("/admin/campaign-links/{$campaignLink->id}", [
                '_token' => 'csrf-token',
            ])
            ->assertRedirect(route('admin.campaign-links.index'))
            ->assertSessionHas('status', 'Campaign link deleted successfully.');

        $this->assertDatabaseMissing('campaign_links', [
            'id' => $campaignLink->id,
        ]);
    }

    public function test_campaign_link_management_handles_missing_table_gracefully(): void
    {
        $admin = Admin::query()->firstOrFail();

        Schema::drop('campaign_links');

        $this->actingAs($admin, 'admin')
            ->get('/admin/campaign-links')
            ->assertOk()
            ->assertSee('campaign_links')
            ->assertSee('php artisan migrate');

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/campaign-links', [
                '_token' => 'csrf-token',
                'name' => 'Broken Link',
                'slug' => 'broken-link',
                'destination' => 'homepage',
                'source' => 'instagram',
                'medium' => 'bio',
                'campaign' => 'april2026',
            ])
            ->assertRedirect(route('admin.campaign-links.index'))
            ->assertSessionHasErrors([
                'error' => 'Campaign Links is not ready because the campaign_links table has not been created yet. Run php artisan migrate first.',
            ]);
    }
}
