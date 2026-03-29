<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminFirestoreRepository;
use Illuminate\Console\Command;

class ScannerPreflightCommand extends Command
{
    protected $signature = 'scanner:preflight
        {--production : Run strict production checks before deploy}';

    protected $description = 'Validate scanner staff configuration before local usage or production deploys.';

    public function handle(AdminFirestoreRepository $repository): int
    {
        $production = (bool) $this->option('production');
        $issues = [];

        if (trim((string) config('scanner.bootstrap_password', '')) === '') {
            $issues[] = 'SCANNER_BOOTSTRAP_PASSWORD is missing.';
        }

        if (count(array_filter(config('scanner.posts', []), fn (mixed $value): bool => trim((string) $value) !== '')) === 0) {
            $issues[] = 'At least one scanner post must be configured.';
        }

        if ($production && trim((string) config('firebase.project_id', '')) === '') {
            $issues[] = 'FIREBASE_PROJECT_ID is missing.';
        }

        if ($production && (string) config('scanner.redis_mode', 'disabled') === 'required' && (string) config('cache.default') !== 'redis') {
            $issues[] = 'Redis is required when SCANNER_REDIS_MODE=required.';
        }

        if ($production && ! $repository->available()) {
            $issues[] = 'Firestore storage is unavailable for production scanner mode.';
        }

        $this->line('Scanner path: /'.trim((string) config('scanner.path', 'staff'), '/'));
        $this->line('Configured posts: '.implode(', ', array_values(config('scanner.posts', []))));
        $this->line('Redis mode: '.(string) config('scanner.redis_mode', 'disabled'));

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->error($issue);
            }

            $this->error('Scanner preflight failed.');

            return self::FAILURE;
        }

        $this->info('Scanner preflight passed.');

        return self::SUCCESS;
    }
}
