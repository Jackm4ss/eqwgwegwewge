<?php

namespace App\Services\Auth;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LocalAuthBootstrapService
{
    private const DEFAULT_LOCAL_ADMIN_PASSWORD = '00000000';

    private const DEFAULT_LOCAL_SCANNER_PASSWORD = '00000000';

    public function ensureAdminAccounts(): void
    {
        $this->ensureRoleSeeded(
            role: 'admin',
            passwordConfigKey: 'admin.bootstrap_password',
            seederClass: AdminSeeder::class,
            fallbackPassword: self::DEFAULT_LOCAL_ADMIN_PASSWORD,
        );
    }

    public function ensureScannerAccounts(): void
    {
        $this->ensureRoleSeeded(
            role: 'scanner',
            passwordConfigKey: 'scanner.bootstrap_password',
            seederClass: ScannerStaffSeeder::class,
            fallbackPassword: self::DEFAULT_LOCAL_SCANNER_PASSWORD,
        );
    }

    private function ensureRoleSeeded(
        string $role,
        string $passwordConfigKey,
        string $seederClass,
        string $fallbackPassword,
    ): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->ensureLocalSqliteStorageReady();
        $this->ensureAdminsTableExists();

        $password = trim((string) config($passwordConfigKey, ''));

        if ($password === '') {
            $password = $fallbackPassword;
            config([$passwordConfigKey => $password]);
        }

        if (! Schema::hasTable('admins')) {
            return;
        }

        if (Admin::query()->where('role', $role)->exists()) {
            return;
        }

        try {
            app($seederClass)->run();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function ensureLocalSqliteStorageReady(): void
    {
        if ((string) config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = (string) config('database.connections.sqlite.database', database_path('database.sqlite'));

        if ($databasePath === '' || $databasePath === ':memory:') {
            return;
        }

        $directory = dirname($databasePath);

        if (! is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! is_file($databasePath)) {
            File::put($databasePath, '');
        }
    }

    private function ensureAdminsTableExists(): void
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
