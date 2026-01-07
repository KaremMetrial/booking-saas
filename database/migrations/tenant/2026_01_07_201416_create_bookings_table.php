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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Unique booking number
            $table->string('booking_number')->unique(); // BK-2024-00001

            // Relations
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('restrict');
            $table->foreignId('staff_id')->nullable()->constrained()->onDelete('set null');

            // Date & Time
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); // بالدقائق (cached)

            // Status
            $table->enum('status', [
                'pending',      // في انتظار التأكيد
                'confirmed',    // مؤكد
                'in_progress',  // جاري التنفيذ
                'completed',    // مكتمل
                'cancelled',    // ملغي
                'no_show'       // لم يحضر
            ])->default('pending');

            // Pricing
            $table->decimal('service_price', 10, 2); // snapshot of price
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);

            // Payment
            $table->enum('payment_status', [
                'unpaid',
                'deposit_paid',
                'paid',
                'refunded'
            ])->default('unpaid');

            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->enum('payment_method', [
                'cash',
                'card',
                'online',
                'bank_transfer'
            ])->nullable();

            // Notes
            $table->text('customer_notes')->nullable(); // ملاحظات العميل
            $table->text('internal_notes')->nullable(); // ملاحظات داخلية

            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable(); // customer, staff, system
            $table->text('cancellation_reason')->nullable();

            // Notifications
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();

            // Source
            $table->enum('source', [
                'online',       // من الموقع
                'phone',        // تليفون
                'walk_in',      // Walk-in
                'admin',        // من لوحة التحكم
                'api'           // من API
            ])->default('online');

            // Recurring (للحجوزات المتكررة)
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('recurring_parent_id')->nullable()->constrained('bookings')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_number');
            $table->index('customer_id');
            $table->index('service_id');
            $table->index('staff_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['booking_date', 'start_time']);
            $table->index(['staff_id', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
