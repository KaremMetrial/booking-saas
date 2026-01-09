<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount_price',
        'duration',
        'buffer_time',
        'category',
        'color',
        'image',
        'display_order',
        'max_capacity',
        'allow_group_booking',
        'is_bookable_online',
        'requires_deposit',
        'deposit_amount',
        'is_active',
        'total_bookings',
        'total_revenue',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'allow_group_booking' => 'boolean',
        'is_bookable_online' => 'boolean',
        'requires_deposit' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'service_staff')
            ->withPivot('custom_price', 'custom_duration')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBookableOnline($query)
    {
        return $query->where('is_bookable_online', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Accessors
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null &&
            $this->discount_price < $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->has_discount) {
            return null;
        }

        return round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function getFormattedPriceAttribute(): string
    {
        $currency = tenancy()->tenant->getSetting('currency', 'USD');
        return number_format($this->current_price, 2) . ' ' . $currency;
    }

    public function getTotalDurationAttribute(): int
    {
        return $this->duration + $this->buffer_time;
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Staff Assignment
     */
    public function assignStaff(Staff $staff, ?float $customPrice = null, ?int $customDuration = null): void
    {
        $this->staff()->attach($staff->id, [
            'custom_price' => $customPrice,
            'custom_duration' => $customDuration,
        ]);
    }

    public function removeStaff(Staff $staff): void
    {
        $this->staff()->detach($staff->id);
    }

    public function syncStaff(array $staffIds): void
    {
        $this->staff()->sync($staffIds);
    }

    /**
     * Price for specific staff
     */
    public function getPriceForStaff(Staff $staff): float
    {
        $pivot = $this->staff()->where('staff_id', $staff->id)->first()?->pivot;

        return $pivot?->custom_price ?? $this->current_price;
    }

    public function getDurationForStaff(Staff $staff): int
    {
        $pivot = $this->staff()->where('staff_id', $staff->id)->first()?->pivot;

        return $pivot?->custom_duration ?? $this->duration;
    }

    /**
     * Availability
     */
    public function isAvailableForBooking(): bool
    {
        return $this->is_active && $this->is_bookable_online;
    }

    /**
     * Stats Update
     */
    public function updateStats(): void
    {
        $this->update([
            'total_bookings' => $this->bookings()->count(),
            'total_revenue' => $this->bookings()
                ->whereIn('status', ['completed'])
                ->sum('service_price'),
        ]);
    }

    /**
     * Rating
     */
    public function getAverageRating(): float
    {
        return $this->reviews()->avg('service_rating') ?? 0;
    }

    public function getTotalReviews(): int
    {
        return $this->reviews()->count();
    }
}
