<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Service;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // Restaurant Services
            [
                'name' => 'Table for 2',
                'description' => 'Reservation for 2 people',
                'price' => 0.00, // Free reservation
                'duration' => 90, // 1.5 hours
                'buffer_time' => 15,
                'category' => 'Dining',
                'color' => '#3B82F6',
                'max_capacity' => 2,
                'is_bookable_online' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Table for 4',
                'description' => 'Reservation for 4 people',
                'price' => 0.00,
                'duration' => 120, // 2 hours
                'buffer_time' => 15,
                'category' => 'Dining',
                'color' => '#10B981',
                'max_capacity' => 4,
                'is_bookable_online' => true,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Table',
                'description' => 'Premium table reservation for up to 6 people',
                'price' => 50.00,
                'duration' => 180, // 3 hours
                'buffer_time' => 30,
                'category' => 'VIP',
                'color' => '#F59E0B',
                'max_capacity' => 6,
                'requires_deposit' => true,
                'deposit_amount' => 50.00,
                'is_bookable_online' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Private Room',
                'description' => 'Private dining room for special occasions',
                'price' => 100.00,
                'duration' => 240, // 4 hours
                'buffer_time' => 30,
                'category' => 'Private',
                'color' => '#EF4444',
                'max_capacity' => 10,
                'requires_deposit' => true,
                'deposit_amount' => 100.00,
                'is_bookable_online' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Outdoor Seating',
                'description' => 'Al fresco dining experience',
                'price' => 0.00,
                'duration' => 90,
                'buffer_time' => 15,
                'category' => 'Outdoor',
                'color' => '#8B5CF6',
                'max_capacity' => 4,
                'is_bookable_online' => true,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        $this->command->info('Services seeded successfully!');
    }
}
