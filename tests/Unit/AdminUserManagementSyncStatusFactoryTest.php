<?php

namespace Tests\Unit;

use App\Services\Admin\AdminUserManagementSyncStatusFactory;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AdminUserManagementSyncStatusFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_make_builds_malaysia_time_labels_and_relative_sync_text(): void
    {
        CarbonImmutable::setTestNow('2026-04-07 06:23:15 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.user_management.read_model.fresh_within_seconds' => 15,
            'admin.user_management.read_model.degraded_after_seconds' => 60,
            'admin.user_management.read_model.fallback_after_seconds' => 300,
        ]);

        $factory = new AdminUserManagementSyncStatusFactory;
        $payload = $factory->make('2026-04-07T06:23:12Z');

        $this->assertSame('fresh', $payload['state']);
        $this->assertSame('read_model', $payload['source']);
        $this->assertSame('Data synced 3s ago', $payload['relative_label']);
        $this->assertSame('Last synced at 14:23:12 Malaysia Time', $payload['last_synced_label']);
        $this->assertSame('2026-04-07T14:23:12+08:00', $payload['last_synced_at_myt']);
    }

    public function test_make_keeps_fresh_state_until_degraded_threshold_then_falls_back(): void
    {
        CarbonImmutable::setTestNow('2026-04-07 06:25:00 UTC');
        config([
            'admin.event.timezone' => 'Asia/Kuala_Lumpur',
            'admin.user_management.read_model.fresh_within_seconds' => 15,
            'admin.user_management.read_model.degraded_after_seconds' => 60,
            'admin.user_management.read_model.fallback_after_seconds' => 300,
        ]);

        $factory = new AdminUserManagementSyncStatusFactory;

        $stillFresh = $factory->make('2026-04-07T06:24:10Z');
        $degraded = $factory->make('2026-04-07T06:23:40Z');
        $fallback = $factory->make('2026-04-07T06:19:30Z');

        $this->assertSame('fresh', $stillFresh['state']);
        $this->assertSame('Data synced 50s ago', $stillFresh['relative_label']);

        $this->assertSame('degraded', $degraded['state']);
        $this->assertSame('Data delayed', $degraded['relative_label']);

        $this->assertSame('fallback', $fallback['state']);
        $this->assertSame('Showing last available data', $fallback['relative_label']);
    }
}
