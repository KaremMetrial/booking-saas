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
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            // Tenant
            $table->string('tenant_id');

            // Metric
            $table->enum('metric', [
                'bookings',
                'customers',
                'staff',
                'sms',
                'emails',
                'api_calls',
                'storage_mb'
            ]);

            // Usage
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable(); // معلومات إضافية

            // Date
            $table->timestamp('recorded_at')->useCurrent();

            // Indexes
            $table->index('tenant_id');
            $table->index('metric');
            $table->index('recorded_at');
            $table->index(['tenant_id', 'metric', 'recorded_at']);

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
