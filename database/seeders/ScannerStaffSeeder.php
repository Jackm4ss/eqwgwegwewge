<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ScannerStaffSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('scanner.bootstrap_password', '');

        if ($password === '') {
            throw new RuntimeException('Scanner bootstrap password is not configured.');
        }

        $count = max(1, (int) config('scanner.seed_count', 1));

        for ($index = 1; $index <= $count; $index++) {
            Admin::updateOrCreate(
                ['email' => sprintf('scanner%02d@songkran.local', $index)],
                [
                    'name' => sprintf('Scanner Staff %02d', $index),
                    'password' => Hash::make($password),
                    'role' => 'scanner',
                    'is_active' => true,
                ],
            );
        }
    }
}
