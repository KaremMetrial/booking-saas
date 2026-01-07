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
        Schema::create('closures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // عيد الفطر، إجازة صيفية
            $table->text('description')->nullable();

            // Dates
            $table->date('start_date');
            $table->date('end_date');

            // Type
            $table->enum('type', [
                'holiday',
                'vacation',
                'maintenance',
                'other'
            ])->default('holiday');

            // Affects
            $table->boolean('affects_all_staff')->default(true);
            $table->json('affected_staff_ids')->nullable(); // [1, 2, 3]

            $table->timestamps();

            // Indexes
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('closures');
    }
};
