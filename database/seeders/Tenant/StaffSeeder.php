<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Staff;
use App\Models\Tenant\Service;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staffMembers = [
            [
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed@restaurant.com',
                'phone' => '+201234567891',
                'role' => 'server',
                'title' => 'Head Waiter',
                'bio' => 'Experienced server with 5 years in fine dining',
                'color' => '#3B82F6',
                'is_active' => true,
                'is_bookable' => true,
            ],
            [
                'name' => 'Sara Mohamed',
                'email' => 'sara@restaurant.com',
                'phone' => '+201234567892',
                'role' => 'server',
                'title' => 'Server',
                'bio' => 'Friendly and professional server',
                'color' => '#10B981',
                'is_active' => true,
                'is_bookable' => true,
            ],
            [
                'name' => 'Omar Ali',
                'email' => 'omar@restaurant.com',
                'phone' => '+201234567893',
                'role' => 'host',
                'title' => 'Restaurant Host',
                'bio' => 'Welcoming host ensuring great first impressions',
                'color' => '#F59E0B',
                'is_active' => true,
                'is_bookable' => false,
            ],
        ];

        foreach ($staffMembers as $staffData) {
            $staff = Staff::create($staffData);

            // Set working hours (Monday to Sunday)
            for ($day = 0; $day < 7; $day++) {
                $staff->workingHours()->create([
                    'day_of_week' => $day,
                    'start_time' => '12:00:00',
                    'end_time' => '23:00:00',
                    'break_start' => '17:00:00',
                    'break_end' => '18:00:00',
                    'is_available' => true,
                ]);
            }

            // Assign all services to staff
            $services = Service::all();
            foreach ($services as $service) {
                $staff->services()->attach($service->id);
            }
        }

        $this->command->info('Staff seeded successfully!');
    }
}
