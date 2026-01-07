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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Basic Info
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');

            // Additional Info
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Profile
            $table->string('avatar')->nullable();
            $table->text('notes')->nullable(); // ملاحظات داخلية
            $table->text('preferences')->nullable(); // تفضيلات العميل

            // Classification
            $table->enum('type', ['new', 'regular', 'vip'])->default('new');
            $table->string('source')->nullable(); // website, phone, walk-in, referral

            // Loyalty
            $table->integer('loyalty_points')->default(0);

            // Stats (cached)
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('last_visit')->nullable();

            // Marketing
            $table->boolean('marketing_emails')->default(true);
            $table->boolean('marketing_sms')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index('type');
            $table->index('last_visit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
