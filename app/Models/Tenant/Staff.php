<?php

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'avatar',
        'bio',
        'specialization',
        'role',
        'title',
        'color',
        'display_order',
        'commission_rate',
        'commission_type',
        'is_active',
        'is_bookable',
        'total_bookings',
        'total_revenue',
        'avg_rating',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_bookable' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_staff')
            ->withPivot('custom_price', 'custom_duration')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function workingHours()
    {
        return $this->hasMany(StaffWorkingHours::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBookable($query)
    {
        return $query->where('is_bookable', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('specialization', 'like', "%{$search}%");
    }

    /**
     * Working Hours Management
     */
    public function setWorkingHours(int $dayOfWeek, string $startTime, string $endTime, ?string $breakStart = null, ?string $breakEnd = null): void
    {
        $this->workingHours()->updateOrCreate(
            ['day_of_week' => $dayOfWeek],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
                'is_available' => true,
            ]
        );
    }

    public function setDayOff(int $dayOfWeek): void
    {
        $this->workingHours()->updateOrCreate(
            ['day_of_week' => $dayOfWeek],
            ['is_available' => false]
        );
    }

    public function getWorkingHoursForDay(int $dayOfWeek)
    {
        return $this->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();
    }

    public function isWorkingOn(string $date): bool
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;

        return $this->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->exists();
    }

    /**
     * Availability Check
     */
    public function isAvailableAt(string $date, string $startTime, int $duration): bool
    {
        // Check if working that day
        if (!$this->isWorkingOn($date)) {
            return false;
        }

        // Check working hours
        $workingHours = $this->getWorkingHoursForDay(\Carbon\Carbon::parse($date)->dayOfWeek);

        if (!$workingHours) {
            return false;
        }

        $endTime = \Carbon\Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');

        if ($startTime < $workingHours->start_time || $endTime > $workingHours->end_time) {
            return false;
        }

        // Check if during break
        if ($workingHours->break_start && $workingHours->break_end) {
            if ($startTime < $workingHours->break_end && $endTime > $workingHours->break_start) {
                return false;
            }
        }

        // Check for conflicting bookings
        $hasConflict = $this->bookings()
            ->where('booking_date', $date)
            ->whereIn('status', ['confirmed', 'in_progress', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return !$hasConflict;
    }

    public function getAvailableSlots(string $date, Service $service): array
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
        $workingHours = $this->getWorkingHoursForDay($dayOfWeek);

        if (!$workingHours) {
            return [];
        }

        $slots = [];
        $duration = $service->getDurationForStaff($this);
        $interval = tenancy()->tenant->getSetting('booking_interval', 30); // minutes

        $currentTime = \Carbon\Carbon::parse($workingHours->start_time);
        $endTime = \Carbon\Carbon::parse($workingHours->end_time);

        while ($currentTime->copy()->addMinutes($duration)->lte($endTime)) {
            $slotStart = $currentTime->format('H:i:s');

            if ($this->isAvailableAt($date, $slotStart, $duration)) {
                $slots[] = [
                    'time' => $slotStart,
                    'formatted' => $currentTime->format('h:i A'),
                ];
            }

            $currentTime->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Commission Calculation
     */
    public function calculateCommission(float $amount): float
    {
        if ($this->commission_type === 'percentage') {
            return ($amount * $this->commission_rate) / 100;
        }

        return $this->commission_rate;
    }

    public function getTotalCommissions(\Carbon\Carbon $startDate = null, \Carbon\Carbon $endDate = null): float
    {
        $query = $this->bookings()->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate]);
        }

        $totalRevenue = $query->sum('service_price');

        return $this->calculateCommission($totalRevenue);
    }

    /**
     * Stats Update
     */
    public function updateStats(): void
    {
        $this->update([
            'total_bookings' => $this->bookings()->count(),
            'total_revenue' => $this->bookings()
                ->where('status', 'completed')
                ->sum('service_price'),
            'avg_rating' => $this->reviews()->avg('staff_rating') ?? 0,
        ]);
    }

    /**
     * Service Assignment
     */
    public function assignService(Service $service, ?float $customPrice = null, ?int $customDuration = null): void
    {
        $this->services()->attach($service->id, [
            'custom_price' => $customPrice,
            'custom_duration' => $customDuration,
        ]);
    }

    public function removeService(Service $service): void
    {
        $this->services()->detach($service->id);
    }

    public function canProvideService(Service $service): bool
    {
        return $this->services()->where('service_id', $service->id)->exists();
    }

    /**
     * Bookings
     */
    public function getTodayBookings()
    {
        return $this->bookings()
            ->where('booking_date', today())
            ->whereIn('status', ['confirmed', 'pending', 'in_progress'])
            ->orderBy('start_time')
            ->get();
    }

    public function getUpcomingBookings(int $days = 7)
    {
        return $this->bookings()
            ->where('booking_date', '>=', today())
            ->where('booking_date', '<=', today()->addDays($days))
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Performance Metrics
     */
    public function getBookingCompletionRate(): float
    {
        $total = $this->bookings()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->bookings()->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 2);
    }

    public function getNoShowRate(): float
    {
        $total = $this->bookings()->count();

        if ($total === 0) {
            return 0;
        }

        $noShows = $this->bookings()->where('status', 'no_show')->count();

        return round(($noShows / $total) * 100, 2);
    }

    public function getAverageBookingValue(): float
    {
        return $this->bookings()
            ->where('status', 'completed')
            ->avg('service_price') ?? 0;
    }
}
