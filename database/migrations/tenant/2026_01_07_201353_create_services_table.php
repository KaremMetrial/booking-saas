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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable(); // سعر مخفض

            // Duration
            $table->integer('duration'); // بالدقائق
            $table->integer('buffer_time')->default(0); // وقت راحة بعد الخدمة

            // Category
            $table->string('category')->nullable();

            // Display
            $table->string('color', 7)->default('#3B82F6'); // للتقويم
            $table->string('image')->nullable();
            $table->integer('display_order')->default(0);

            // Capacity
            $table->integer('max_capacity')->default(1); // للخدمات الجماعية
            $table->boolean('allow_group_booking')->default(false);

            // Online Booking
            $table->boolean('is_bookable_online')->default(true);
            $table->boolean('requires_deposit')->default(false);
            $table->decimal('deposit_amount', 10, 2)->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Stats
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
