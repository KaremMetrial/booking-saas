<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'payment_number',
        'amount',
        'currency',
        'type',
        'method',
        'status',
        'transaction_id',
        'reference_number',
        'gateway_response',
        'failure_reason',
        'paid_at',
        'refunded_at',
        'receipt_number',
        'receipt_url',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });

        static::updated(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === 'completed') {
                // Update booking payment status
                if ($payment->booking) {
                    $payment->booking->increment('paid_amount', $payment->amount);

                    if ($payment->booking->paid_amount >= $payment->booking->total_price) {
                        $payment->booking->update(['payment_status' => 'paid']);
                    } elseif ($payment->booking->paid_amount > 0) {
                        $payment->booking->update(['payment_status' => 'deposit_paid']);
                    }
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('paid_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month);
    }

    /**
     * Status Checks
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->paid_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded' && $this->refunded_at !== null;
    }

    /**
     * Actions
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Generate receipt
        $this->generateReceipt();
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function refund(): void
    {
        // TODO: Implement actual refund logic with payment gateway

        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        // Update booking
        if ($this->booking) {
            $this->booking->decrement('paid_amount', $this->amount);

            if ($this->booking->paid_amount <= 0) {
                $this->booking->update(['payment_status' => 'refunded']);
            }
        }
    }

    /**
     * Receipt Generation
     */
    public function generateReceipt(): void
    {
        $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);

        // TODO: Generate actual receipt PDF or link

        $this->update([
            'receipt_number' => $receiptNumber,
            'receipt_url' => null, // Set actual URL
        ]);
    }

    /**
     * Helpers
     */
    public static function generatePaymentNumber(): string
    {
        $date = date('Ymd');
        $lastPayment = static::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPayment ? ((int) substr($lastPayment->payment_number, -4)) + 1 : 1;

        return 'PAY-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->currency ?? tenancy()->tenant->getSetting('currency', 'USD');
        return number_format($this->amount, 2) . ' ' . $currency;
    }

    public function getMethodNameAttribute(): string
    {
        $methods = [
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'fawry' => 'Fawry',
            'paymob' => 'PayMob',
            'stripe' => 'Stripe',
            'bank_transfer' => 'Bank Transfer',
        ];

        return $methods[$this->method] ?? $this->method;
    }

    public function getTypeNameAttribute(): string
    {
        $types = [
            'full_payment' => 'Full Payment',
            'deposit' => 'Deposit',
            'remaining' => 'Remaining Balance',
            'refund' => 'Refund',
        ];

        return $types[$this->type] ?? $this->type;
    }
}
