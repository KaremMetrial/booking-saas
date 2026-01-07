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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained()->onDelete('set null');

            // Rating (1-5)
            $table->tinyInteger('rating'); // 1, 2, 3, 4, 5
            $table->tinyInteger('service_rating')->nullable(); // تقييم الخدمة
            $table->tinyInteger('staff_rating')->nullable(); // تقييم الموظف
            $table->tinyInteger('cleanliness_rating')->nullable(); // النظافة
            $table->tinyInteger('value_rating')->nullable(); // القيمة مقابل المال

            // Review
            $table->text('comment')->nullable();
            $table->text('reply')->nullable(); // رد المنشأة
            $table->timestamp('replied_at')->nullable();

            // Status
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);

            // Helpful
            $table->integer('helpful_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('service_id');
            $table->index('staff_id');
            $table->index('rating');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
