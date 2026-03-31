<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RegistrationPreflightCommand extends Command
{
    protected $signature = 'registration:preflight
        {--production : Run strict production checks before deploy}';

    protected $description = 'Validate registration Redis profile before local usage or production deploys.';

    public function handle(): int
    {
        $production = (bool) $this->option('production');
        $issues = [];
        $redisMode = (string) config('registration.redis_mode', 'disabled');
        $queueConnection = (string) config('queue.default', 'sync');
        $cacheStore = (string) config('cache.default', 'file');
        $sessionDriver = (string) config('session.driver', 'file');
        $redisConnections = [];

        if (! in_array($redisMode, ['disabled', 'required'], true)) {
            $issues[] = 'REGISTRATION_REDIS_MODE must be either disabled or required.';
        }

        if ($production && $redisMode === 'required' && $queueConnection !== 'redis') {
            $issues[] = 'QUEUE_CONNECTION must be redis when REGISTRATION_REDIS_MODE=required.';
        }

        if ($production && $redisMode === 'required' && $cacheStore !== 'redis') {
            $issues[] = 'CACHE_STORE must be redis when REGISTRATION_REDIS_MODE=required.';
        }

        if ($queueConnection === 'redis') {
            $redisConnections['queue'] = (string) config('queue.connections.redis.connection', 'default');
        }

        if ($cacheStore === 'redis') {
            $redisConnections['cache'] = (string) config('cache.stores.redis.connection', 'cache');
        }

        if ($sessionDriver === 'redis') {
            $redisConnections['session'] = (string) config('session.connection', 'default');
        }

        foreach ($redisConnections as $purpose => $connectionName) {
            try {
                Redis::connection($connectionName)->ping();
            } catch (\Throwable $throwable) {
                $issues[] = sprintf(
                    'Redis connection [%s] for %s is unavailable: %s',
                    $connectionName,
                    $purpose,
                    $throwable->getMessage(),
                );
            }
        }

        $this->line('Registration Redis mode: '.$redisMode);
        $this->line('Queue connection: '.$queueConnection);
        $this->line('Cache store: '.$cacheStore);
        $this->line('Session driver: '.$sessionDriver);
        $this->line('Email queue: '.(string) config('registration.email_queue', 'registration-emails'));

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->error($issue);
            }

            $this->error('Registration preflight failed.');

            return self::FAILURE;
        }

        $this->info('Registration preflight passed.');

        return self::SUCCESS;
    }
}
