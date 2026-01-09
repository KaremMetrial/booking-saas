<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'number',
        'description',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'status',
        'invoice_date',
        'due_date',
        'paid_at',
        'payment_method',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->number) {
                $invoice->number = static::generateInvoiceNumber();
            }
        });
    }

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Status Checks
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' &&
            $this->due_date &&
            $this->due_date->isPast();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Actions
     */
    public function markAsPaid(string $paymentMethod = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function void(): void
    {
        $this->update(['status' => 'void']);
    }

    /**
     * PDF Generation
     */
    public function generatePDF(): string
    {
        // TODO: Implement PDF generation
        // Using DomPDF or similar

        $pdf = \PDF::loadView('invoices.pdf', ['invoice' => $this]);

        $filename = 'invoices/' . $this->number . '.pdf';
        $path = storage_path('app/public/' . $filename);

        $pdf->save($path);

        $this->update(['pdf_path' => $filename]);

        return $filename;
    }

    public function getPDFUrl(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return asset('storage/' . $this->pdf_path);
    }

    /**
     * Helpers
     */
    public function getDaysUntilDue(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->number, -5)) + 1 : 1;

        return 'INV-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Calculations
     */
    public function calculateTotal(): void
    {
        $total = $this->subtotal + $this->tax - $this->discount;

        $this->update(['total' => $total]);
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2) . ' ' . $this->currency;
    }
}
