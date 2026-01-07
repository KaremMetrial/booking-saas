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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            // User Relationship (optional - للموظفين اللي عندهم حساب)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Basic Info
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');

            // Profile
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('specialization')->nullable(); // التخصص

            // Role
            $table->string('role')->nullable(); // doctor, stylist, waiter, trainer, etc.
            $table->string('title')->nullable(); // Job title

            // Display
            $table->string('color', 7)->default('#10B981'); // للتقويم
            $table->integer('display_order')->default(0);

            // Commission
            $table->decimal('commission_rate', 5, 2)->default(0); // نسبة العمولة %
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bookable')->default(true); // يمكن حجز معاه؟

            // Stats
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('email');
            $table->index('is_active');
            $table->index('is_bookable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
