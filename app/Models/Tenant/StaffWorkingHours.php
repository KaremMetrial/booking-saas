<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class StaffWorkingHours extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Helpers
     */
    public function getDayName(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$this->day_of_week] ?? '';
    }

    public function getFormattedHours(): string
    {
        if (!$this->is_available) {
            return 'Day Off';
        }

        $start = \Carbon\Carbon::parse($this->start_time)->format('h:i A');
        $end = \Carbon\Carbon::parse($this->end_time)->format('h:i A');

        $hours = "{$start} - {$end}";

        if ($this->break_start && $this->break_end) {
            $breakStart = \Carbon\Carbon::parse($this->break_start)->format('h:i A');
            $breakEnd = \Carbon\Carbon::parse($this->break_end)->format('h:i A');
            $hours .= " (Break: {$breakStart} - {$breakEnd})";
        }

        return $hours;
    }

    public function getTotalHours(): float
    {
        if (!$this->is_available) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        $totalMinutes = $end->diffInMinutes($start);

        // Subtract break time
        if ($this->break_start && $this->break_end) {
            $breakStart = \Carbon\Carbon::parse($this->break_start);
            $breakEnd = \Carbon\Carbon::parse($this->break_end);
            $totalMinutes -= $breakEnd->diffInMinutes($breakStart);
        }

        return round($totalMinutes / 60, 2);
    }
}
