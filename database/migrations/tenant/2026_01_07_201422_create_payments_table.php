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
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Payment Details
            $table->string('payment_number')->unique(); // PAY-2024-00001
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Type
            $table->enum('type', [
                'full_payment',
                'deposit',
                'remaining',
                'refund'
            ])->default('full_payment');

            // Method
            $table->enum('method', [
                'cash',
                'card',
                'fawry',
                'paymob',
                'stripe',
                'bank_transfer'
            ]);

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');

            // Gateway Info
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();

            // Dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Receipt
            $table->string('receipt_number')->nullable();
            $table->string('receipt_url')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('payment_number');
            $table->index('transaction_id');
            $table->index('status');
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
