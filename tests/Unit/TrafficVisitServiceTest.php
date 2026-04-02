<?php

namespace Tests\Unit;

use App\Services\TrafficVisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrafficVisitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_counts_unique_ips_per_source_group(): void
    {
        /** @var TrafficVisitService $service */
        $service = app(TrafficVisitService::class);

        $service->recordVisit([
            'traffic_source' => 'google',
            'traffic_source_detail' => 'google',
            'traffic_medium' => 'search',
            'traffic_campaign' => 'songkran-search',
            'traffic_referrer_host' => 'google.com',
            'traffic_landing_path' => '/?utm_source=google',
            'traffic_captured_at' => '2026-04-02T10:00:00+07:00',
        ], '10.0.0.1');

        $service->recordVisit([
            'traffic_source' => 'google',
            'traffic_source_detail' => 'google',
            'traffic_medium' => 'search',
            'traffic_campaign' => 'songkran-search',
            'traffic_referrer_host' => 'google.com',
            'traffic_landing_path' => '/register?utm_source=google',
            'traffic_captured_at' => '2026-04-02T10:05:00+07:00',
        ], '10.0.0.1');

        $service->recordVisit([
            'traffic_source' => 'direct',
            'traffic_source_detail' => 'direct',
            'traffic_medium' => 'direct',
            'traffic_landing_path' => '/',
            'traffic_captured_at' => '2026-04-02T11:00:00+07:00',
        ], '10.0.0.2');

        $service->recordVisit([
            'traffic_source' => 'instagram',
            'traffic_source_detail' => 'instagram',
            'traffic_medium' => 'social',
            'traffic_campaign' => 'songkran-social',
            'traffic_referrer_host' => 'l.instagram.com',
            'traffic_landing_path' => '/?utm_source=instagram',
            'traffic_captured_at' => '2026-04-02T12:00:00+07:00',
        ], '10.0.0.3');

        $service->recordVisit([
            'traffic_source' => 'facebook',
            'traffic_source_detail' => 'facebook',
            'traffic_medium' => 'social',
            'traffic_campaign' => 'songkran-social',
            'traffic_referrer_host' => 'facebook.com',
            'traffic_landing_path' => '/?utm_source=facebook',
            'traffic_captured_at' => '2026-04-02T13:00:00+07:00',
        ], '10.0.0.4');

        $this->assertSame([
            'storage_ready' => true,
            'total_unique_ips' => 4,
            'google_search_unique_ips' => 1,
            'direct_unique_ips' => 1,
            'social_media_unique_ips' => 2,
        ], $service->dashboardSummary());
    }

    public function test_dashboard_summary_returns_empty_values_when_storage_is_missing(): void
    {
        /** @var TrafficVisitService $service */
        $service = app(TrafficVisitService::class);

        Schema::dropIfExists('traffic_visits');

        $this->assertSame([
            'storage_ready' => false,
            'total_unique_ips' => 0,
            'google_search_unique_ips' => 0,
            'direct_unique_ips' => 0,
            'social_media_unique_ips' => 0,
        ], $service->dashboardSummary());
    }
}
