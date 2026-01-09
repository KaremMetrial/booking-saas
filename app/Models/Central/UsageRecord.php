<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'metric',
        'quantity',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scopes
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('recorded_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('recorded_at', now()->year)
            ->whereMonth('recorded_at', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('recorded_at', now()->year);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Static Helpers
     */
    public static function record(string $tenantId, string $metric, int $quantity = 1, array $metadata = []): self
    {
        return static::create([
            'tenant_id' => $tenantId,
            'metric' => $metric,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
    }

    public static function getTotalUsage(string $tenantId, string $metric, $startDate = null, $endDate = null): int
    {
        $query = static::forTenant($tenantId)->forMetric($metric);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->sum('quantity');
    }

    public static function getUsageByDay(string $tenantId, string $metric, $days = 30): array
    {
        $records = static::forTenant($tenantId)
            ->forMetric($metric)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(recorded_at) as date, SUM(quantity) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $records->pluck('total', 'date')->toArray();
    }
}
