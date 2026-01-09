<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            \Database\Seeders\Central\PlansSeeder::class,
            \Database\Seeders\Central\SuperAdminSeeder::class,
            \Database\Seeders\Central\DemoTenantSeeder::class,
        ]);

        $this->command->info('✅ All seeders completed successfully!');
    }
}
