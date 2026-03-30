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
            'admin.future_urls.landing' => 'https://songkran.test',
            'admin.future_urls.register' => 'https://register.songkran.test',
        ]);

        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        $presented = $service->present([
            'name' => 'TikTok Bio April',
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
        $this->assertSame(
            'https://register.songkran.test?utm_source=tiktok&utm_medium=bio&utm_campaign=april2026&utm_content=video-a',
            $presented['generated_url']
        );
    }

    public function test_dashboard_summary_counts_campaign_links(): void
    {
        /** @var CampaignLinkService $service */
        $service = app(CampaignLinkService::class);

        CampaignLink::query()->create([
            'name' => 'Homepage Link',
            'destination' => 'homepage',
            'source' => 'instagram',
            'medium' => 'bio',
            'campaign' => 'april2026',
            'utm_content' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        CampaignLink::query()->create([
            'name' => 'Register Link',
            'destination' => 'register',
            'source' => 'tiktok',
            'medium' => 'ads',
            'campaign' => 'may2026',
            'utm_content' => 'creative-a',
            'notes' => null,
            'is_active' => false,
        ]);

        $this->assertSame([
            'storage_ready' => true,
            'total' => 2,
            'active' => 1,
            'homepage' => 1,
            'register' => 1,
        ], $service->dashboardSummary());
    }
}
