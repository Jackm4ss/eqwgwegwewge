<?php

namespace App\Console\Commands;

use Database\Seeders\AdminSeeder;
use Illuminate\Console\Command;

class AdminSeedCommand extends Command
{
    protected $signature = 'admin:seed
        {--password= : Shared password for the seeded admin accounts}
        {--count= : Number of admin accounts to create}';

    protected $description = 'Seed local admin accounts for the admin panel.';

    public function handle(): int
    {
        $password = trim((string) ($this->option('password') ?? ''));

        if ($password === '') {
            $password = trim((string) config('admin.bootstrap_password', ''));
        }

        if ($password === '') {
            $this->error('Admin seed password is required. Use --password=YOUR_PASSWORD or set ADMIN_BOOTSTRAP_PASSWORD in .env.');

            return self::FAILURE;
        }

        $countOption = $this->option('count');
        $count = $countOption !== null && $countOption !== ''
            ? max(1, (int) $countOption)
            : max(1, (int) config('admin.seed_count', 8));

        config([
            'admin.bootstrap_password' => $password,
            'admin.seed_count' => $count,
        ]);

        $this->call('db:seed', [
            '--class' => AdminSeeder::class,
            '--force' => true,
        ]);

        $this->info("Seeded {$count} admin accounts.");
        $this->line('Email range: admin01@songkran.local -> '.sprintf('admin%02d@songkran.local', $count));
        $this->line('Admin login path: /'.trim((string) config('admin.path', 'admin'), '/').'/login');

        return self::SUCCESS;
    }
}
