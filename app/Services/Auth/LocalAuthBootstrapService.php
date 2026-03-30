<?php

namespace App\Services\Auth;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LocalAuthBootstrapService
{
    public function ensureAdminAccounts(): void
    {
        $this->ensureRoleSeeded(
            role: 'admin',
            passwordConfigKey: 'admin.bootstrap_password',
            seederClass: AdminSeeder::class,
        );
    }

    public function ensureScannerAccounts(): void
    {
        $this->ensureRoleSeeded(
            role: 'scanner',
            passwordConfigKey: 'scanner.bootstrap_password',
            seederClass: ScannerStaffSeeder::class,
        );
    }

    private function ensureRoleSeeded(string $role, string $passwordConfigKey, string $seederClass): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('admins')) {
            return;
        }

        if (Admin::query()->where('role', $role)->exists()) {
            return;
        }

        if (trim((string) config($passwordConfigKey, '')) === '') {
            return;
        }

        try {
            app($seederClass)->run();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
