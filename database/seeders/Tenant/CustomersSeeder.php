<?php


namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Customer;
use Faker\Factory as Faker;

class CustomersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ar_EG'); // Arabic Egypt

        $customers = [
            [
                'name' => 'محمد أحمد',
                'email' => 'mohamed@example.com',
                'phone' => '+201012345678',
                'type' => 'vip',
                'source' => 'website',
                'loyalty_points' => 150,
                'total_bookings' => 15,
                'total_spent' => 2500.00,
            ],
            [
                'name' => 'فاطمة حسن',
                'email' => 'fatma@example.com',
                'phone' => '+201098765432',
                'type' => 'regular',
                'source' => 'phone',
                'loyalty_points' => 80,
                'total_bookings' => 8,
                'total_spent' => 1200.00,
            ],
            [
                'name' => 'Ahmed Ali',
                'email' => 'ahmed@example.com',
                'phone' => '+201123456789',
                'type' => 'regular',
                'source' => 'website',
                'loyalty_points' => 50,
                'total_bookings' => 5,
                'total_spent' => 750.00,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Create 20 more random customers
        for ($i = 0; $i < 20; $i++) {
            Customer::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'date_of_birth' => $faker->dateTimeBetween('-60 years', '-18 years'),
                'gender' => $faker->randomElement(['male', 'female']),
                'address' => $faker->address,
                'city' => $faker->city,
                'type' => $faker->randomElement(['new', 'regular', 'vip']),
                'source' => $faker->randomElement(['website', 'phone', 'walk-in', 'referral']),
                'loyalty_points' => $faker->numberBetween(0, 100),
                'total_bookings' => $faker->numberBetween(0, 10),
                'total_spent' => $faker->randomFloat(2, 0, 2000),
                'marketing_emails' => $faker->boolean(80),
                'marketing_sms' => $faker->boolean(70),
            ]);
        }

        $this->command->info('Customers seeded successfully!');
    }
}
