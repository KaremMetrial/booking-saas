<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'type',
        'affects_all_staff',
        'affected_staff_ids',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'affects_all_staff' => 'boolean',
        'affected_staff_ids' => 'array',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', today())
            ->where('end_date', '>=', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', today());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', today());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Checks
     */
    public function isActive(): bool
    {
        return $this->start_date <= today() && $this->end_date >= today();
    }

    public function affectsDate(string $date): bool
    {
        $checkDate = \Carbon\Carbon::parse($date);

        return $checkDate->between($this->start_date, $this->end_date);
    }

    public function affectsStaff(Staff $staff): bool
    {
        if ($this->affects_all_staff) {
            return true;
        }

        return in_array($staff->id, $this->affected_staff_ids ?? []);
    }

    /**
     * Duration
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Static Helpers
     */
    public static function isDateClosed(string $date, ?Staff $staff = null): bool
    {
        $query = static::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);

        if ($staff) {
            $query->where(function ($q) use ($staff) {
                $q->where('affects_all_staff', true)
                    ->orWhereJsonContains('affected_staff_ids', $staff->id);
            });
        }

        return $query->exists();
    }

    public static function getClosuresForDateRange($startDate, $endDate)
    {
        return static::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        })->get();
    }
}

