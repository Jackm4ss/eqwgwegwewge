<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Support\BootstrapAccountEmail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('admin.bootstrap_password', '');

        if ($password === '') {
            throw new RuntimeException('Admin bootstrap password is not configured.');
        }

        $count = max(1, (int) config('admin.seed_count', 8));

        for ($index = 1; $index <= $count; $index++) {
            Admin::updateOrCreate(
                ['email' => BootstrapAccountEmail::admin($index)],
                [
                    'name' => sprintf('Songkran Admin %02d', $index),
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'is_active' => true,
                ],
            );
        }
    }
}
