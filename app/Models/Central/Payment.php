<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'subscription_id',
        'amount',
        'currency',
        'gateway',
        'transaction_id',
        'reference_number',
        'status',
        'gateway_response',
        'failure_reason',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
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

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
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

        // Mark invoice as paid if exists
        if ($this->invoice) {
            $this->invoice->markAsPaid($this->gateway);
        }
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

        // Update invoice if exists
        if ($this->invoice) {
            $this->invoice->update(['status' => 'refunded']);
        }
    }

    /**
     * Helpers
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getGatewayNameAttribute(): string
    {
        $gateways = [
            'stripe' => 'Stripe',
            'fawry' => 'Fawry',
            'paymob' => 'PayMob',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
        ];

        return $gateways[$this->gateway] ?? $this->gateway;
    }

    public function getReceiptUrl(): ?string
    {
        // TODO: Implement receipt generation
        return null;
    }
}
