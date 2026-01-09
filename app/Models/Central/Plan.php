<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'trial_days',
        'stripe_price_monthly_id',
        'stripe_price_yearly_id',
        'features',
        'is_active',
        'is_popular',
        'display_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class)
            ->where('status', 'active');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedPriceMonthlyAttribute()
    {
        return number_format($this->price_monthly, 2) . ' ' . $this->currency;
    }

    public function getFormattedPriceYearlyAttribute()
    {
        return number_format($this->price_yearly, 2) . ' ' . $this->currency;
    }

    public function getYearlySavingsAttribute()
    {
        $monthlyTotal = $this->price_monthly * 12;
        return $monthlyTotal - $this->price_yearly;
    }

    public function getYearlySavingsPercentageAttribute()
    {
        $monthlyTotal = $this->price_monthly * 12;
        if ($monthlyTotal == 0) return 0;
        return round((($monthlyTotal - $this->price_yearly) / $monthlyTotal) * 100);
    }

    /**
     * Helper Methods
     */
    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]) && $this->features[$feature];
    }

    public function getFeatureLimit(string $feature)
    {
        return $this->features[$feature] ?? null;
    }

    public function isUnlimited(string $feature): bool
    {
        return $this->getFeatureLimit($feature) === 'unlimited';
    }
}
