<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'business_name', 'value' => 'Demo Restaurant', 'type' => 'string', 'description' => 'Business name'],
            ['key' => 'business_email', 'value' => 'info@restaurant.com', 'type' => 'string', 'description' => 'Contact email'],
            ['key' => 'business_phone', 'value' => '+201234567890', 'type' => 'string', 'description' => 'Contact phone'],
            ['key' => 'business_address', 'value' => '123 Main Street, Cairo, Egypt', 'type' => 'string', 'description' => 'Business address'],

            // Booking Settings
            ['key' => 'booking_interval', 'value' => '30', 'type' => 'integer', 'description' => 'Booking time slots interval in minutes'],
            ['key' => 'min_booking_notice', 'value' => '60', 'type' => 'integer', 'description' => 'Minimum booking notice in minutes'],
            ['key' => 'max_booking_advance', 'value' => '90', 'type' => 'integer', 'description' => 'Maximum days in advance for booking'],
            ['key' => 'min_cancellation_notice', 'value' => '24', 'type' => 'integer', 'description' => 'Minimum cancellation notice in hours'],
            ['key' => 'auto_confirm_bookings', 'value' => '1', 'type' => 'boolean', 'description' => 'Auto-confirm online bookings'],

            // Notification Settings
            ['key' => 'send_booking_confirmation', 'value' => '1', 'type' => 'boolean', 'description' => 'Send booking confirmation'],
            ['key' => 'send_booking_reminder', 'value' => '1', 'type' => 'boolean', 'description' => 'Send booking reminder'],
            ['key' => 'reminder_hours', 'value' => '24', 'type' => 'integer', 'description' => 'Send reminder X hours before booking'],
            ['key' => 'sms_notifications', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable SMS notifications'],
            ['key' => 'email_notifications', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable email notifications'],

            // Payment Settings
            ['key' => 'accept_online_payments', 'value' => '1', 'type' => 'boolean', 'description' => 'Accept online payments'],
            ['key' => 'require_deposit', 'value' => '0', 'type' => 'boolean', 'description' => 'Require deposit for bookings'],
            ['key' => 'default_deposit_percentage', 'value' => '20', 'type' => 'integer', 'description' => 'Default deposit percentage'],
            ['key' => 'currency', 'value' => 'EGP', 'type' => 'string', 'description' => 'Currency code'],
            ['key' => 'tax_rate', 'value' => '14', 'type' => 'integer', 'description' => 'Tax rate percentage'],

            // Display Settings
            ['key' => 'timezone', 'value' => 'Africa/Cairo', 'type' => 'string', 'description' => 'Business timezone'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'description' => 'Date format'],
            ['key' => 'time_format', 'value' => 'H:i', 'type' => 'string', 'description' => 'Time format'],
            ['key' => 'language', 'value' => 'en', 'type' => 'string', 'description' => 'Default language'],

            // Branding
            ['key' => 'primary_color', 'value' => '#3B82F6', 'type' => 'string', 'description' => 'Primary brand color'],
            ['key' => 'secondary_color', 'value' => '#10B981', 'type' => 'string', 'description' => 'Secondary brand color'],

            // Social Media
            ['key' => 'facebook_url', 'value' => '', 'type' => 'string', 'description' => 'Facebook page URL'],
            ['key' => 'instagram_url', 'value' => '', 'type' => 'string', 'description' => 'Instagram profile URL'],
            ['key' => 'twitter_url', 'value' => '', 'type' => 'string', 'description' => 'Twitter profile URL'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        $this->command->info('Settings seeded successfully!');
    }
}
