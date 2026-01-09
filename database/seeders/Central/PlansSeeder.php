<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use App\Models\Central\Plan;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses just getting started',
                'price_monthly' => 15.00,
                'price_yearly' => 150.00, // 17% discount
                'currency' => 'USD',
                'trial_days' => 14,
                'features' => [
                    'bookings_limit' => 50,
                    'staff_limit' => 1,
                    'sms_limit' => 100,
                    'email_limit' => 'unlimited',
                    'storage_gb' => 1,
                    'custom_domain' => false,
                    'api_access' => false,
                    'white_label' => false,
                    'priority_support' => false,
                    'advanced_reports' => false,
                    'multiple_locations' => false,
                    'online_booking' => true,
                    'calendar_sync' => false,
                    'customer_management' => true,
                    'payment_processing' => true,
                    'email_notifications' => true,
                    'sms_notifications' => true,
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing businesses that need more power',
                'price_monthly' => 35.00,
                'price_yearly' => 350.00,
                'currency' => 'USD',
                'trial_days' => 14,
                'features' => [
                    'bookings_limit' => 200,
                    'staff_limit' => 5,
                    'sms_limit' => 500,
                    'email_limit' => 'unlimited',
                    'storage_gb' => 5,
                    'custom_domain' => false,
                    'api_access' => false,
                    'white_label' => false,
                    'priority_support' => true,
                    'advanced_reports' => true,
                    'multiple_locations' => false,
                    'online_booking' => true,
                    'calendar_sync' => true,
                    'customer_management' => true,
                    'payment_processing' => true,
                    'email_notifications' => true,
                    'sms_notifications' => true,
                    'loyalty_program' => true,
                    'review_management' => true,
                ],
                'is_active' => true,
                'is_popular' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Advanced features for established businesses',
                'price_monthly' => 70.00,
                'price_yearly' => 700.00,
                'currency' => 'USD',
                'trial_days' => 14,
                'features' => [
                    'bookings_limit' => 'unlimited',
                    'staff_limit' => 'unlimited',
                    'sms_limit' => 2000,
                    'email_limit' => 'unlimited',
                    'storage_gb' => 20,
                    'custom_domain' => true,
                    'api_access' => true,
                    'white_label' => true,
                    'priority_support' => true,
                    'advanced_reports' => true,
                    'multiple_locations' => true,
                    'online_booking' => true,
                    'calendar_sync' => true,
                    'customer_management' => true,
                    'payment_processing' => true,
                    'email_notifications' => true,
                    'sms_notifications' => true,
                    'loyalty_program' => true,
                    'review_management' => true,
                    'automated_marketing' => true,
                    'custom_branding' => true,
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solution for large organizations',
                'price_monthly' => 0.00, // Custom pricing
                'price_yearly' => 0.00,
                'currency' => 'USD',
                'trial_days' => 30,
                'features' => [
                    'bookings_limit' => 'unlimited',
                    'staff_limit' => 'unlimited',
                    'sms_limit' => 'unlimited',
                    'email_limit' => 'unlimited',
                    'storage_gb' => 'unlimited',
                    'custom_domain' => true,
                    'api_access' => true,
                    'white_label' => true,
                    'priority_support' => true,
                    'advanced_reports' => true,
                    'multiple_locations' => true,
                    'online_booking' => true,
                    'calendar_sync' => true,
                    'customer_management' => true,
                    'payment_processing' => true,
                    'email_notifications' => true,
                    'sms_notifications' => true,
                    'loyalty_program' => true,
                    'review_management' => true,
                    'automated_marketing' => true,
                    'custom_branding' => true,
                    'dedicated_account_manager' => true,
                    'custom_development' => true,
                    'sla_guarantee' => true,
                    'private_hosting' => true,
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }

        $this->command->info('Plans seeded successfully!');
    }
}
