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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->string('tenant_id');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');

            // Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Gateway
            $table->enum('gateway', [
                'stripe',
                'fawry',
                'paymob',
                'paypal',
                'bank_transfer',
                'cash'
            ]);

            // Transaction
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');

            // Gateway Response
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();

            // Dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Indexes
            $table->index('tenant_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
