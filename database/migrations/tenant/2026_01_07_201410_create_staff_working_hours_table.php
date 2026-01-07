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
        Schema::create('staff_working_hours', function (Blueprint $table) {
            $table->id();
            // Staff
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');

            // Day (0 = Sunday, 6 = Saturday)
            $table->tinyInteger('day_of_week'); // 0-6

            // Hours
            $table->time('start_time');
            $table->time('end_time');

            // Break Time (optional)
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            // Status
            $table->boolean('is_available')->default(true);

            // Indexes
            $table->index(['staff_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_working_hours');
    }
};
