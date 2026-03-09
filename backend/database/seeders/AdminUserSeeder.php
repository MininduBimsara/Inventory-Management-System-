<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('FIRST_ADMIN_EMAIL', 'admin@inventory.local');
        $adminPassword = env('FIRST_ADMIN_PASSWORD', 'ChangeMe123!');

        $admin = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('FIRST_ADMIN_NAME', 'System Admin'),
                'password' => $adminPassword,
            ]
        );

        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }
    }
}
