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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // Type
            $table->enum('type', [
                'booking_created',
                'booking_confirmed',
                'booking_reminder',
                'booking_cancelled',
                'booking_completed',
                'payment_received',
                'review_received'
            ]);

            // Recipient
            $table->string('notifiable_type'); // Customer, Staff, User
            $table->unsignedBigInteger('notifiable_id');

            // Channel
            $table->enum('channel', ['email', 'sms', 'push', 'database'])->default('database');

            // Content
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // بيانات إضافية

            // Status
            $table->enum('status', [
                'pending',
                'sent',
                'failed',
                'read'
            ])->default('pending');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('type');
            $table->index('channel');
            $table->index('status');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
