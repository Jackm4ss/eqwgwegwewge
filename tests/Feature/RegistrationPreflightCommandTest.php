<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class RegistrationPreflightCommandTest extends TestCase
{
    public function test_registration_preflight_fails_when_required_production_redis_profile_is_missing(): void
    {
        config([
            'registration.redis_mode' => 'required',
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'session.driver' => 'file',
        ]);

        $exitCode = Artisan::call('registration:preflight', [
            '--production' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Registration preflight failed.', $output);
    }

    public function test_registration_preflight_passes_with_local_safe_defaults(): void
    {
        config([
            'registration.redis_mode' => 'disabled',
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'session.driver' => 'file',
        ]);

        $exitCode = Artisan::call('registration:preflight');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Registration preflight passed.', $output);
    }

    public function test_registration_preflight_passes_with_required_production_profile_when_redis_is_reachable(): void
    {
        config([
            'registration.redis_mode' => 'required',
            'queue.default' => 'redis',
            'cache.default' => 'redis',
            'session.driver' => 'redis',
            'session.connection' => 'default',
            'queue.connections.redis.connection' => 'default',
            'cache.stores.redis.connection' => 'cache',
        ]);

        $defaultConnection = Mockery::mock();
        $defaultConnection->shouldReceive('ping')->twice()->andReturn('PONG');

        $cacheConnection = Mockery::mock();
        $cacheConnection->shouldReceive('ping')->once()->andReturn('PONG');

        Redis::shouldReceive('connection')->with('default')->twice()->andReturn($defaultConnection);
        Redis::shouldReceive('connection')->with('cache')->once()->andReturn($cacheConnection);

        $exitCode = Artisan::call('registration:preflight', [
            '--production' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Registration preflight passed.', $output);
    }
}
