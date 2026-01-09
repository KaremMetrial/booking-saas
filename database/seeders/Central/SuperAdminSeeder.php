<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'tenant_id' => null, // Super admin doesn't belong to any tenant
            'name' => 'Super Admin',
            'email' => 'admin@bookingsaas.com',
            'phone' => '+201234567890',
            'password' => Hash::make('password'), // Change in production!
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->warn('Email: admin@bookingsaas.com');
        $this->command->warn('Password: password');
    }
}
