<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'service_id',
        'staff_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration',
        'status',
        'service_price',
        'discount',
        'tax',
        'total_price',
        'payment_status',
        'paid_amount',
        'deposit_amount',
        'payment_method',
        'customer_notes',
        'internal_notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'reminder_sent_at',
        'confirmation_sent_at',
        'source',
        'is_recurring',
        'recurring_parent_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'service_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (!$booking->booking_number) {
                $booking->booking_number = static::generateBookingNumber();
            }

            // Track usage
            tenancy()->tenant->recordUsage('bookings');
        });

        static::created(function ($booking) {
            // Update customer stats
            $booking->customer->updateStats();

            // Update service stats
            $booking->service->updateStats();

            // Update staff stats if assigned
            if ($booking->staff) {
                $booking->staff->updateStats();
            }
        });

        static::updated(function ($booking) {
            if ($booking->isDirty('status')) {
                // Update stats when status changes
                $booking->customer->updateStats();
                $booking->service->updateStats();

                if ($booking->staff) {
                    $booking->staff->updateStats();
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function recurringChildren()
    {
        return $this->hasMany(Booking::class, 'recurring_parent_id');
    }

    public function recurringParent()
    {
        return $this->belongsTo(Booking::class, 'recurring_parent_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function scopePast($query)
    {
        return $query->where('booking_date', '<', today())
            ->orWhereIn('status', ['completed', 'cancelled', 'no_show']);
    }

    public function scopeToday($query)
    {
        return $query->where('booking_date', today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('booking_date', $date);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Status Checks
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isNoShow(): bool
    {
        return $this->status === 'no_show';
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
            $this->booking_date >= today();
    }

    public function isPast(): bool
    {
        return $this->booking_date < today() ||
            in_array($this->status, ['completed', 'cancelled', 'no_show']);
    }

    /**
     * Payment Status Checks
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' &&
            $this->paid_amount >= $this->total_price;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'deposit_paid' &&
            $this->paid_amount < $this->total_price;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid' &&
            $this->paid_amount == 0;
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->total_price - $this->paid_amount);
    }

    /**
     * Actions
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmation_sent_at' => now(),
        ]);

        // Send confirmation notification
        // TODO: Implement notification
    }

    public function cancel(string $cancelledBy = 'customer', ?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);

        // Handle refund if paid
        if ($this->isPaid() || $this->isPartiallyPaid()) {
            // TODO: Implement refund logic
        }

        // Send cancellation notification
        // TODO: Implement notification
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);

        // Update customer's last visit
        $this->customer->update(['last_visit' => $this->booking_date]);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }

    public function reschedule(string $newDate, string $newStartTime): void
    {
        $service = $this->service;
        $duration = $service->getDurationForStaff($this->staff);

        $endTime = \Carbon\Carbon::parse($newStartTime)
            ->addMinutes($duration)
            ->format('H:i:s');

        $this->update([
            'booking_date' => $newDate,
            'start_time' => $newStartTime,
            'end_time' => $endTime,
            'status' => 'confirmed',
        ]);

        // Send reschedule notification
        // TODO: Implement notification
    }

    /**
     * Helpers
     */
    public static function generateBookingNumber(): string
    {
        $date = date('Ymd');
        $lastBooking = static::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastBooking ? ((int) substr($lastBooking->booking_number, -4)) + 1 : 1;

        return 'BK-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->booking_date->format('M d, Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        $start = \Carbon\Carbon::parse($this->start_time)->format('h:i A');
        $end = \Carbon\Carbon::parse($this->end_time)->format('h:i A');

        return "{$start} - {$end}";
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['color' => 'warning', 'label' => 'Pending'],
            'confirmed' => ['color' => 'success', 'label' => 'Confirmed'],
            'in_progress' => ['color' => 'info', 'label' => 'In Progress'],
            'completed' => ['color' => 'success', 'label' => 'Completed'],
            'cancelled' => ['color' => 'danger', 'label' => 'Cancelled'],
            'no_show' => ['color' => 'dark', 'label' => 'No Show'],
        ];

        return $badges[$this->status] ?? ['color' => 'secondary', 'label' => $this->status];
    }

    public function canBeCancelled(): bool
    {
        $minNoticePeriod = tenancy()->tenant->getSetting('min_cancellation_notice', 24); // hours

        $bookingDateTime = \Carbon\Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time);

        return $bookingDateTime->isFuture() &&
            $bookingDateTime->diffInHours(now()) >= $minNoticePeriod &&
            in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
            $this->booking_date >= today();
    }

    /**
     * Notifications
     */
    public function sendReminder(): void
    {
        // TODO: Implement reminder notification

        $this->update(['reminder_sent_at' => now()]);
    }

    public function needsReminder(): bool
    {
        if ($this->reminder_sent_at) {
            return false;
        }

        $reminderHours = tenancy()->tenant->getSetting('reminder_hours', 24);
        $bookingDateTime = \Carbon\Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time);

        return $bookingDateTime->diffInHours(now()) <= $reminderHours &&
            $bookingDateTime->isFuture() &&
            in_array($this->status, ['pending', 'confirmed']);
    }
}
