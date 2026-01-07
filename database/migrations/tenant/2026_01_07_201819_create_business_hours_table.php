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
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            // Day (0 = Sunday, 6 = Saturday)
            $table->tinyInteger('day_of_week');

            // Hours
            $table->time('opening_time');
            $table->time('closing_time');

            // Status
            $table->boolean('is_open')->default(true);

            // Breaks
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            $table->timestamps();

            // Index
            $table->index('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
