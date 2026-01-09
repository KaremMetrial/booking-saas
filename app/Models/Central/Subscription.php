<?php

namespace App\Models\Central;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_period',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'ends_at',
        'cancelled_at',
        'cancel_at_period_end',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_status',
        'price',
        'currency',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeEnding($query, $days = 7)
    {
        return $query->where('current_period_end', '<=', now()->addDays($days))
            ->where('current_period_end', '>=', now());
    }

    /**
     * Status Checks
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            $this->current_period_end &&
            $this->current_period_end->isFuture();
    }

    public function onTrial(): bool
    {
        return $this->status === 'trial' &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at &&
            $this->ends_at->isFuture();
    }

    public function cancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function ended(): bool
    {
        return $this->status === 'cancelled' &&
            $this->ends_at &&
            $this->ends_at->isPast();
    }

    /**
     * Actions
     */
    public function cancel($immediately = false): void
    {
        if ($immediately) {
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'ends_at' => now(),
            ]);
        } else {
            $this->update([
                'cancel_at_period_end' => true,
                'cancelled_at' => now(),
                'ends_at' => $this->current_period_end,
            ]);
        }
    }

    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'cancelled_at' => null,
            'cancel_at_period_end' => false,
            'ends_at' => null,
        ]);
    }

    public function swap(Plan $newPlan): void
    {
        $this->update([
            'plan_id' => $newPlan->id,
            'price' => $this->billing_period === 'monthly'
                ? $newPlan->price_monthly
                : $newPlan->price_yearly,
        ]);
    }

    /**
     * Billing
     */
    public function renew(): void
    {
        $this->update([
            'current_period_start' => now(),
            'current_period_end' => $this->billing_period === 'monthly'
                ? now()->addMonth()
                : now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function getDaysUntilRenewal(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return now()->diffInDays($this->current_period_end, false);
    }

    public function getDaysUntilTrialEnds(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }
}
