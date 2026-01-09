<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Booking;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Service;
use App\Models\Tenant\Staff;
use Carbon\Carbon;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $services = Service::all();
        $staff = Staff::where('is_bookable', true)->get();

        if ($customers->isEmpty() || $services->isEmpty() || $staff->isEmpty()) {
            $this->command->warn('No customers, services, or staff found. Skipping bookings seeder.');
            return;
        }

        // Create past bookings
        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $service = $services->random();
            $staffMember = $staff->random();

            $date = Carbon::today()->subDays(rand(1, 30));
            $startTime = Carbon::parse('12:00')->addMinutes(rand(0, 8) * 30);
            $endTime = $startTime->copy()->addMinutes($service->duration);

            Booking::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'staff_id' => $staffMember->id,
                'booking_date' => $date,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'duration' => $service->duration,
                'status' => 'completed',
                'service_price' => $service->price,
                'total_price' => $service->price,
                'payment_status' => 'paid',
                'paid_amount' => $service->price,
                'payment_method' => 'cash',
                'source' => 'website',
                'confirmation_sent_at' => now(),
            ]);
        }

        // Create upcoming bookings
        for ($i = 0; $i < 5; $i++) {
            $customer = $customers->random();
            $service = $services->random();
            $staffMember = $staff->random();

            $date = Carbon::today()->addDays(rand(1, 14));
            $startTime = Carbon::parse('18:00')->addMinutes(rand(0, 6) * 30);
            $endTime = $startTime->copy()->addMinutes($service->duration);

            Booking::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'staff_id' => $staffMember->id,
                'booking_date' => $date,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'duration' => $service->duration,
                'status' => 'confirmed',
                'service_price' => $service->price,
                'total_price' => $service->price,
                'payment_status' => $service->requires_deposit ? 'deposit_paid' : 'unpaid',
                'paid_amount' => $service->requires_deposit ? $service->deposit_amount : 0,
                'deposit_amount' => $service->deposit_amount ?? 0,
                'payment_method' => $service->requires_deposit ? 'card' : null,
                'source' => 'website',
                'confirmation_sent_at' => now(),
            ]);
        }

        $this->command->info('Bookings seeded successfully!');
    }
}
