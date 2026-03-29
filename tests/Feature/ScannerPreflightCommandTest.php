<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScannerPreflightCommandTest extends TestCase
{
    public function test_scanner_preflight_fails_when_required_production_config_is_missing(): void
    {
        config([
            'scanner.redis_mode' => 'required',
            'scanner.bootstrap_password' => '',
            'scanner.posts' => [],
            'firebase.project_id' => null,
        ]);

        $exitCode = Artisan::call('scanner:preflight', [
            '--production' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Scanner preflight failed.', $output);
    }

    public function test_scanner_preflight_passes_with_local_safe_defaults(): void
    {
        config([
            'scanner.redis_mode' => 'disabled',
            'scanner.bootstrap_password' => 'ScannerPass123!',
            'scanner.posts' => ['Gate A'],
            'firebase.project_id' => 'demo-project',
        ]);

        $exitCode = Artisan::call('scanner:preflight');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Scanner preflight passed.', $output);
    }
}
