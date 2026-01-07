<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Professional, Business, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('trial_days')->default(14);

            // Stripe IDs (للدفع)
            $table->string('stripe_price_monthly_id')->nullable();
            $table->string('stripe_price_yearly_id')->nullable();

            // Features (JSON)
            $table->json('features');
            // Example:
            // {
            //   "bookings_limit": 50 | "unlimited",
            //   "staff_limit": 1 | "unlimited",
            //   "sms_limit": 100,
            //   "email_limit": "unlimited",
            //   "storage_gb": 5,
            //   "custom_domain": true|false,
            //   "api_access": true|false,
            //   "white_label": true|false,
            //   "priority_support": true|false,
            //   "advanced_reports": true|false,
            //   "multiple_locations": true|false
            // }

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
