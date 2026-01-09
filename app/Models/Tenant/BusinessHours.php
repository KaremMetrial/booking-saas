<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Model;

class BusinessHours extends Model
{
    protected $fillable = [
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_open',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
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
        if (!$this->is_open) {
            return 'Closed';
        }

        $opening = \Carbon\Carbon::parse($this->opening_time)->format('h:i A');
        $closing = \Carbon\Carbon::parse($this->closing_time)->format('h:i A');

        return "{$opening} - {$closing}";
    }

    public static function isOpenNow(): bool
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        $hours = static::where('day_of_week', $dayOfWeek)
            ->where('is_open', true)
            ->first();

        if (!$hours) {
            return false;
        }

        return $currentTime >= $hours->opening_time &&
            $currentTime <= $hours->closing_time;
    }

    public static function getTodayHours()
    {
        return static::where('day_of_week', now()->dayOfWeek)->first();
    }
}
