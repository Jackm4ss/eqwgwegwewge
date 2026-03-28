<?php

namespace Database\Seeders;

use App\Models\StaffUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('staff.bootstrap_password', '');

        if ($password === '') {
            throw new RuntimeException('Staff bootstrap password is not configured.');
        }

        $count = max(1, (int) config('staff.seed_count', 12));

        for ($index = 1; $index <= $count; $index++) {
            StaffUser::updateOrCreate(
                ['email' => sprintf('staff%02d@songkran.local', $index)],
                [
                    'name' => sprintf('Songkran Staff %02d', $index),
                    'password' => Hash::make($password),
                    'role' => 'operator',
                    'is_active' => true,
                ],
            );
        }
    }
}
