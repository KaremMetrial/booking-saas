<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'avatar',
        'notes',
        'preferences',
        'type',
        'source',
        'loyalty_points',
        'total_bookings',
        'total_spent',
        'last_visit',
        'marketing_emails',
        'marketing_sms',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'total_spent' => 'decimal:2',
        'last_visit' => 'datetime',
        'marketing_emails' => 'boolean',
        'marketing_sms' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Scopes
     */
    public function scopeVip($query)
    {
        return $query->where('type', 'vip');
    }

    public function scopeRegular($query)
    {
        return $query->where('type', 'regular');
    }

    public function scopeNew($query)
    {
        return $query->where('type', 'new');
    }

    public function scopeActive($query, $days = 90)
    {
        return $query->where('last_visit', '>=', now()->subDays($days));
    }

    public function scopeInactive($query, $days = 90)
    {
        return $query->where('last_visit', '<', now()->subDays($days))
            ->orWhereNull('last_visit');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->address, $this->city]);
        return implode(', ', $parts);
    }

    /**
     * Customer Type Management
     */
    public function promoteToVip(): void
    {
        $this->update(['type' => 'vip']);
    }

    public function promoteToRegular(): void
    {
        $this->update(['type' => 'regular']);
    }

    /**
     * Loyalty Points
     */
    public function addPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }

    public function deductPoints(int $points): void
    {
        $this->decrement('loyalty_points', $points);
    }

    public function resetPoints(): void
    {
        $this->update(['loyalty_points' => 0]);
    }

    /**
     * Stats Update
     */
    public function updateStats(): void
    {
        $this->update([
            'total_bookings' => $this->bookings()->count(),
            'total_spent' => $this->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'last_visit' => $this->bookings()
                ->where('status', 'completed')
                ->latest('booking_date')
                ->value('booking_date'),
        ]);

        // Auto-promote based on stats
        if ($this->total_bookings >= 10 && $this->type === 'regular') {
            $this->promoteToVip();
        } elseif ($this->total_bookings >= 3 && $this->type === 'new') {
            $this->promoteToRegular();
        }
    }

    /**
     * Booking Methods
     */
    public function hasUpcomingBooking(): bool
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', today())
            ->exists();
    }

    public function getUpcomingBooking()
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', today())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->first();
    }

    public function getPastBookings()
    {
        return $this->bookings()
            ->where('status', 'completed')
            ->orderBy('booking_date', 'desc')
            ->get();
    }

    /**
     * Average Rating
     */
    public function getAverageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Lifecycle Helpers
     */
    public function getDaysSinceLastVisit(): ?int
    {
        if (!$this->last_visit) {
            return null;
        }

        return now()->diffInDays($this->last_visit);
    }

    public function isActive(): bool
    {
        return $this->last_visit &&
            $this->last_visit->isAfter(now()->subDays(90));
    }
}
