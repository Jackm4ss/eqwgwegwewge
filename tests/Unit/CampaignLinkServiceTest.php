<?php

namespace Tests\Unit;

use App\Models\CampaignLink;
use App\Services\Admin\CampaignLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_present_builds_labels_and_generated_url(): void
    {
        config([
            'app.frontend_homepage_url' => 'https://songkran.test',
            'admin.future_urls.register' => 'https://register.songkran.test',
        ]);

        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        $presented = $service->present([
            'name' => 'TikTok Bio April',
            'slug' => 'fb',
            'destination' => 'register',
            'source' => 'tiktok',
            'medium' => 'bio',
            'campaign' => 'april2026',
            'utm_content' => 'video-a',
            'notes' => 'Primary bio link',
            'is_active' => true,
        ]);

        $this->assertSame('Register Page', $presented['destination_label']);
        $this->assertSame('TikTok', $presented['source_label']);
        $this->assertSame('Bio', $presented['medium_label']);
        $this->assertSame('April2026', $presented['campaign_label']);
        $this->assertSame('Video A', $presented['utm_content_label']);
        $this->assertSame('https://songkran.test/fb', $presented['short_url']);
        $this->assertSame(
            'https://register.songkran.test?utm_source=tiktok&utm_medium=bio&utm_campaign=april2026&utm_content=video-a',
            $presented['final_url']
        );
    }

    public function test_dashboard_summary_counts_campaign_links(): void
    {
        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        CampaignLink::query()->create([
            'name' => 'Homepage Link',
            'slug' => 'home-link',
            'destination' => 'homepage',
            'source' => 'instagram',
            'medium' => 'bio',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 3,
        ]);

        CampaignLink::query()->create([
            'name' => 'Register Link',
            'slug' => 'register-link',
            'destination' => 'register',
            'source' => 'tiktok',
            'medium' => 'ads',
            'campaign' => 'may2026',
            'utm_content' => 'creative-a',
            'notes' => null,
            'is_active' => false,
            'visit_count' => 4,
        ]);

        $this->assertSame([
            'storage_ready' => true,
            'total' => 2,
            'active' => 1,
            'homepage' => 1,
            'register' => 1,
            'visits' => 7,
        ], $service->dashboardSummary());
    }

    public function test_source_visit_breakdown_groups_clicks_by_source(): void
    {
        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        CampaignLink::query()->create([
            'name' => 'Instagram Bio',
            'slug' => 'ig-bio',
            'destination' => 'homepage',
            'source' => 'instagram',
            'medium' => 'bio',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 8,
        ]);

        CampaignLink::query()->create([
            'name' => 'Instagram Story',
            'slug' => 'ig-story',
            'destination' => 'register',
            'source' => 'instagram',
            'medium' => 'story',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 4,
        ]);

        CampaignLink::query()->create([
            'name' => 'WhatsApp Blast',
            'slug' => 'wa-blast',
            'destination' => 'homepage',
            'source' => 'whatsapp',
            'medium' => 'broadcast',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 3,
        ]);

        CampaignLink::query()->create([
            'name' => 'Threads Zero',
            'slug' => 'threads-zero',
            'destination' => 'homepage',
            'source' => 'threads',
            'medium' => 'post',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
            'visit_count' => 0,
        ]);

        $breakdown = $service->sourceVisitBreakdown();

        $this->assertTrue($breakdown['has_data']);
        $this->assertSame(15, $breakdown['total_visits']);
        $this->assertSame(2, $breakdown['source_count']);
        $this->assertSame('Instagram', $breakdown['top_source_label']);
        $this->assertSame(12, $breakdown['top_source_visits']);
        $this->assertSame(['Instagram', 'WhatsApp'], $breakdown['labels']);
        $this->assertSame([12, 3], $breakdown['series']);
        $this->assertSame(80.0, data_get($breakdown, 'rows.0.percentage'));
        $this->assertSame(20.0, data_get($breakdown, 'rows.1.percentage'));
    }

    public function test_source_suggestions_and_labels_include_custom_partner_sources(): void
    {
        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        $this->assertContains('Wob', $service->sourceSuggestions());
        $this->assertContains('Noodou', $service->sourceSuggestions());
        $this->assertContains('Ilovemalaysiafood', $service->sourceSuggestions());
        $this->assertContains('Fooddiver', $service->sourceSuggestions());
        $this->assertContains('Edmhub', $service->sourceSuggestions());
        $this->assertContains('Ig', $service->sourceSuggestions());
        $this->assertContains('Fb', $service->sourceSuggestions());
        $this->assertContains('Tya', $service->sourceSuggestions());

        $this->assertSame('Wob', $service->sourceLabel('wob'));
        $this->assertSame('Noodou', $service->sourceLabel('noodou'));
        $this->assertSame('Ilovemalaysiafood', $service->sourceLabel('ilovemalaysiafood'));
        $this->assertSame('Fooddiver', $service->sourceLabel('fooddiver'));
        $this->assertSame('Edmhub', $service->sourceLabel('edmhub'));
        $this->assertSame('Ig', $service->sourceLabel('ig'));
        $this->assertSame('Fb', $service->sourceLabel('fb'));
        $this->assertSame('Tya', $service->sourceLabel('tya'));
    }
}
