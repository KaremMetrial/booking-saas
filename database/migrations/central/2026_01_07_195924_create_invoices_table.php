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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->string('tenant_id');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');

            // Invoice Details
            $table->string('number')->unique(); // INV-2024-00001
            $table->text('description')->nullable();

            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Status
            $table->enum('status', [
                'draft',
                'pending',
                'paid',
                'failed',
                'refunded',
                'void'
            ])->default('pending');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            // Payment
            $table->string('payment_method')->nullable(); // card, cash, transfer
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Indexes
            $table->index('tenant_id');
            $table->index('number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
