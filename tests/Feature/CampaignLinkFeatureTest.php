<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CampaignLink;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.frontend_homepage_url' => 'https://songkranfestival.my',
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
            'admin.future_urls.register' => 'https://songkranfestival.my/register',
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_active_shortlink_redirects_to_final_url_and_records_visit_analytics(): void
    {
        $campaignLink = CampaignLink::query()->create([
            'name' => 'Facebook Campaign',
            'slug' => 'fb',
            'destination' => 'register',
            'source' => 'facebook',
            'medium' => 'bio',
            'campaign' => 'songkran-launch',
            'utm_content' => 'creative-a',
            'notes' => 'Main Facebook shortlink',
            'is_active' => true,
            'visit_count' => 0,
        ]);

        $this->get('/fb')
            ->assertRedirect('https://songkranfestival.my/register?utm_source=facebook&utm_medium=bio&utm_campaign=songkran-launch&utm_content=creative-a');

        $campaignLink->refresh();

        $this->assertSame(1, $campaignLink->visit_count);
        $this->assertNotNull($campaignLink->last_visited_at);
    }

    public function test_unknown_slug_redirects_to_public_homepage(): void
    {
        $this->get('/unknown-slug')
            ->assertRedirect('https://songkranfestival.my');
    }

    public function test_inactive_slug_redirects_to_public_homepage_without_recording_visit(): void
    {
        $campaignLink = CampaignLink::query()->create([
            'name' => 'WhatsApp Broadcast',
            'slug' => 'wa',
            'destination' => 'homepage',
            'source' => 'whatsapp',
            'medium' => 'broadcast',
            'campaign' => 'songkran-launch',
            'utm_content' => null,
            'notes' => null,
            'is_active' => false,
            'visit_count' => 2,
        ]);

        $this->get('/wa')
            ->assertRedirect('https://songkranfestival.my');

        $campaignLink->refresh();

        $this->assertSame(2, $campaignLink->visit_count);
        $this->assertNull($campaignLink->last_visited_at);
    }

    public function test_admin_page_shows_slug_prefix_and_hides_reset_traffic_and_bulk_import_features(): void
    {
        $admin = Admin::query()->firstOrFail();

        CampaignLink::query()->create([
            'name' => 'Instagram Campaign',
            'slug' => 'ig-main',
            'destination' => 'homepage',
            'source' => 'instagram',
            'medium' => 'bio',
            'campaign' => 'songkran-launch',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 9,
        ]);

        CampaignLink::query()->create([
            'name' => 'WhatsApp Campaign',
            'slug' => 'wa-main',
            'destination' => 'register',
            'source' => 'whatsapp',
            'medium' => 'broadcast',
            'campaign' => 'songkran-launch',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 4,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/campaign-links')
            ->assertOk()
            ->assertSee('Create Campaign Link')
            ->assertSee('Public slug after the slash')
            ->assertSee('name="source"', false)
            ->assertSee('<select', false)
            ->assertSee('data-campaign-source-select', false)
            ->assertDontSee('campaign-link-source-options', false)
            ->assertSee('https://songkranfestival.my/')
            ->assertSee('/fb')
            ->assertDontSee('Export link CSV')
            ->assertSee('Analytics & Filters', false)
            ->assertSee('Source Click Breakdown')
            ->assertSee('Top source')
            ->assertSee('Instagram')
            ->assertSee('WhatsApp')
            ->assertDontSee('Reset traffic')
            ->assertDontSee('Bulk import')
            ->assertDontSee('template CSV');
    }

    public function test_campaign_link_routes_only_expose_core_routes_without_bulk_import_or_template_routes(): void
    {
        $routesByName = app('router')->getRoutes()->getRoutesByName();

        $this->assertArrayHasKey('admin.campaign-links.index', $routesByName);
        $this->assertArrayHasKey('admin.campaign-links.store', $routesByName);
        $this->assertArrayHasKey('admin.campaign-links.update', $routesByName);
        $this->assertArrayHasKey('admin.campaign-links.destroy', $routesByName);
        $this->assertArrayHasKey('campaign-links.redirect', $routesByName);

        $this->assertSame('{slug}', $routesByName['campaign-links.redirect']->uri());
        $this->assertArrayNotHasKey('admin.campaign-links.bulk-import', $routesByName);
        $this->assertArrayNotHasKey('admin.campaign-links.template', $routesByName);
    }
}
