<?php
namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServicesSeeder::class,
            StaffSeeder::class,
            BusinessHoursSeeder::class,
            SettingsSeeder::class,
            CustomersSeeder::class,
            BookingsSeeder::class,
        ]);

        $this->command->info('Tenant database seeded successfully!');
    }
}
