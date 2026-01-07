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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->string('tenant_id');
            $table->foreignId('plan_id')->constrained()->onDelete('restrict');

            // Billing Period
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

            // Status
            $table->enum('status', [
                'trial',
                'active',
                'past_due',
                'cancelled',
                'expired',
                'paused'
            ])->default('trial');

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('ends_at')->nullable(); // للاشتراكات الملغية
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            // Stripe
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_status')->nullable();

            // Pricing (snapshot at subscription time)
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');

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
            $table->index('status');
            $table->index(['current_period_start', 'current_period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
