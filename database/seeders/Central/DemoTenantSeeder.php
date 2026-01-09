<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use App\Models\Central\Tenant;
use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Get Professional plan
        $plan = Plan::where('slug', 'professional')->first();

        // Create Demo Restaurant Tenant
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'Demo Restaurant',
            'slug' => 'demo-restaurant',
            'email' => 'demo@restaurant.com',
            'phone' => '+201234567890',
            'type' => 'restaurant',
            'owner_name' => 'Demo Owner',
            'owner_email' => 'owner@restaurant.com',
            'owner_phone' => '+201234567890',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'settings' => [
                'primary_color' => '#3B82F6',
                'secondary_color' => '#10B981',
                'language' => 'en',
                'timezone' => 'Africa/Cairo',
                'currency' => 'EGP',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'booking_interval' => 30,
                'min_booking_notice' => 60,
                'max_booking_advance' => 90,
            ],
        ]);

        // Create database for tenant
        $tenant->createDatabase();

        // Create domain
        $tenant->domains()->create([
            'domain' => 'demo-restaurant.localhost',
            'is_primary' => true,
        ]);

        // Create owner user
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Owner',
            'email' => 'owner@restaurant.com',
            'phone' => '+201234567890',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'owner',
            'language' => 'en',
            'timezone' => 'Africa/Cairo',
        ]);

        // Create subscription
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
            'price' => $plan->price_monthly,
            'currency' => 'USD',
        ]);

        $this->command->info('Demo Tenant created successfully!');
        $this->command->warn('Domain: demo-restaurant.localhost');
        $this->command->warn('Email: owner@restaurant.com');
        $this->command->warn('Password: password');

        // Now seed tenant database
        $tenant->run(function () {
            $this->call(\Database\Seeders\Tenant\TenantDatabaseSeeder::class);
        });
    }
}