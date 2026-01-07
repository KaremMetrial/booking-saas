<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // your custom columns may go here
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('type', [
                'restaurant',
                'clinic',
                'hospital',
                'salon',
                'spa',
                'gym',
                'wedding_hall',
                'consultation',
                'education',
                'other'
            ])->default('other');

            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone')->nullable();

            $table->string('database_name')->nullable();

            $table->enum('status', [
                'trial',
                'active',
                'suspended',
                'cancelled',
                'expired'
            ])->default('trial');
            // Trial
            $table->timestamp('trial_ends_at')->nullable();
            // Branding
            $table->string('logo')->nullable();
            $table->json('settings')->nullable();
            // Settings example:
            // {
            //   "primary_color": "#3B82F6",
            //   "secondary_color": "#10B981",
            //   "language": "ar",
            //   "timezone": "Africa/Cairo",
            //   "currency": "EGP",
            //   "date_format": "Y-m-d",
            //   "time_format": "H:i",
            //   "booking_interval": 30,
            //   "min_booking_notice": 60,
            //   "max_booking_advance": 90
            // }
            // Stats (cache)
            $table->json('stats')->nullable();
            // {
            //   "total_bookings": 0,
            //   "total_customers": 0,
            //   "total_revenue": 0
            // }


            $table->timestamps();
            $table->softDeletes();

            $table->json('data')->nullable();

            // Indexes
            $table->index('slug');
            $table->index('email');
            $table->index('status');
            $table->index('trial_ends_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
