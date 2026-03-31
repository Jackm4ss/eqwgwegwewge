<?php

namespace App\Console\Commands;

use App\Support\BootstrapAccountEmail;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Console\Command;

class ScannerSeedCommand extends Command
{
    protected $signature = 'scanner:seed
        {--password= : Shared password for the seeded scanner staff accounts}
        {--count= : Number of scanner staff accounts to create}';

    protected $description = 'Seed local scanner staff accounts for the staff scanner portal.';

    public function handle(): int
    {
        $password = trim((string) ($this->option('password') ?? ''));

        if ($password === '') {
            $password = trim((string) config('scanner.bootstrap_password', ''));
        }

        if ($password === '') {
            $this->error('Scanner seed password is required. Use --password=YOUR_PASSWORD or set SCANNER_BOOTSTRAP_PASSWORD in .env.');

            return self::FAILURE;
        }

        $countOption = $this->option('count');
        $count = $countOption !== null && $countOption !== ''
            ? max(1, (int) $countOption)
            : max(1, (int) config('scanner.seed_count', 1));

        config([
            'scanner.bootstrap_password' => $password,
            'scanner.seed_count' => $count,
        ]);

        $this->call('db:seed', [
            '--class' => ScannerStaffSeeder::class,
            '--force' => true,
        ]);

        $this->info("Seeded {$count} scanner staff accounts.");
        $this->line('Email range: '.BootstrapAccountEmail::scanner(1).' -> '.BootstrapAccountEmail::scanner($count));
        $this->line('Staff login path: /'.trim((string) config('scanner.path', 'staff'), '/').'/login');

        return self::SUCCESS;
    }
}
