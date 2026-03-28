<?php

namespace App\Console\Commands;

use Database\Seeders\ScannerStationSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Console\Command;

class StaffSeedCommand extends Command
{
    protected $signature = 'staff:seed
        {--password= : Shared password for the seeded staff accounts}
        {--count= : Number of staff accounts to create}';

    protected $description = 'Seed local staff accounts for the scanner panel.';

    public function handle(): int
    {
        $password = trim((string) ($this->option('password') ?? ''));

        if ($password === '') {
            $password = trim((string) config('staff.bootstrap_password', ''));
        }

        if ($password === '') {
            $this->error('Staff seed password is required. Use --password=YOUR_PASSWORD or set STAFF_BOOTSTRAP_PASSWORD in .env.');

            return self::FAILURE;
        }

        $countOption = $this->option('count');
        $count = $countOption !== null && $countOption !== ''
            ? max(1, (int) $countOption)
            : max(1, (int) config('staff.seed_count', 12));

        config([
            'staff.bootstrap_password' => $password,
            'staff.seed_count' => $count,
        ]);

        $this->call('db:seed', [
            '--class' => StaffSeeder::class,
            '--force' => true,
        ]);
        $this->call('db:seed', [
            '--class' => ScannerStationSeeder::class,
            '--force' => true,
        ]);

        $this->info("Seeded {$count} staff accounts.");
        $this->line('Email range: staff01@songkran.local -> '.sprintf('staff%02d@songkran.local', $count));
        $this->line('Staff login path: /'.trim((string) config('staff.path', 'staff'), '/').'/login');
        $this->line('Scanner stations seeded: 6 default gates/stations.');

        return self::SUCCESS;
    }
}
