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
        Schema::create('service_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');

            // Custom price for this staff member (optional)
            $table->decimal('custom_price', 10, 2)->nullable();

            // Custom duration (optional)
            $table->integer('custom_duration')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['service_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_staff');
    }
};
