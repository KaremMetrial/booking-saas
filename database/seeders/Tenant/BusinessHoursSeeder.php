<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\BusinessHours;

class BusinessHoursSeeder extends Seeder
{
    public function run(): void
    {
        $hours = [
            // Sunday - Open
            [
                'day_of_week' => 0,
                'opening_time' => '12:00:00',
                'closing_time' => '23:00:00',
                'is_open' => true,
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
            ],
            // Monday - Open
            [
                'day_of_week' => 1,
                'opening_time' => '12:00:00',
                'closing_time' => '23:00:00',
                'is_open' => true,
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
            ],
            // Tuesday - Open
            [
                'day_of_week' => 2,
                'opening_time' => '12:00:00',
                'closing_time' => '23:00:00',
                'is_open' => true,
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
            ],
            // Wednesday - Open
            [
                'day_of_week' => 3,
                'opening_time' => '12:00:00',
                'closing_time' => '23:00:00',
                'is_open' => true,
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
            ],
            // Thursday - Open
            [
                'day_of_week' => 4,
                'opening_time' => '12:00:00',
                'closing_time' => '00:00:00', // Midnight
                'is_open' => true,
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
            ],
            // Friday - Extended hours
            [
                'day_of_week' => 5,
                'opening_time' => '12:00:00',
                'closing_time' => '01:00:00', // 1 AM
                'is_open' => true,
                'break_start' => null,
                'break_end' => null,
            ],
            // Saturday - Extended hours
            [
                'day_of_week' => 6,
                'opening_time' => '12:00:00',
                'closing_time' => '01:00:00',
                'is_open' => true,
                'break_start' => null,
                'break_end' => null,
            ],
        ];

        foreach ($hours as $hour) {
            BusinessHours::create($hour);
        }

        $this->command->info('Business hours seeded successfully!');
    }
}
