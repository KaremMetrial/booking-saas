<?php

namespace App\Models\Central;

use App\Models\Models\Central\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;


class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'email',
        'phone',
        'type',
        'owner_name',
        'owner_email',
        'owner_phone',
        'database_name',
        'status',
        'trial_ends_at',
        'logo',
        'settings',
        'stats',
    ];

    protected $casts = [
        'settings' => 'array',
        'stats' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Create a new tenant with individual column support
     */
    public static function create(array $attributes = [])
    {
        // Separate individual columns from other attributes
        $individualColumns = [
            'id', 'name', 'slug', 'email', 'phone', 'type', 'owner_name', 'owner_email',
            'owner_phone', 'database_name', 'status', 'trial_ends_at', 'logo', 'settings', 'stats'
        ];
        
        $individualAttributes = [];
        $otherAttributes = [];
        
        foreach ($attributes as $key => $value) {
            if (in_array($key, $individualColumns)) {
                $individualAttributes[$key] = $value;
            } else {
                $otherAttributes[$key] = $value;
            }
        }
        
        // If there are other attributes, put them in the data field
        if (!empty($otherAttributes)) {
            $individualAttributes['data'] = $otherAttributes;
        }
        
        // Call parent create with properly formatted attributes
        return parent::create($individualAttributes);
    }

    /**
     * Relationships
     */
    public function owner()
    {
        return $this->hasOne(User::class)
            ->where('role', 'owner')
            ->oldest();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function usageRecords()
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOnTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Status Checks
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            $this->subscription &&
            $this->subscription->isActive();
    }

    public function onTrial(): bool
    {
        return $this->status === 'trial' &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    public function trialExpired(): bool
    {
        return $this->trial_ends_at &&
            $this->trial_ends_at->isPast();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Feature Checks
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->subscription) {
            return false;
        }

        $plan = $this->subscription->plan;
        return $plan && $plan->hasFeature($feature);
    }

    public function getFeatureLimit(string $feature)
    {
        if (!$this->subscription) {
            return 0;
        }

        return $this->subscription->plan->getFeatureLimit($feature);
    }

    public function canCreateBooking(): bool
    {
        $limit = $this->getFeatureLimit('bookings_limit');

        if ($limit === 'unlimited') {
            return true;
        }

        $used = $this->usageThisMonth('bookings');
        return $used < $limit;
    }

    public function canAddStaff(): bool
    {
        $limit = $this->getFeatureLimit('staff_limit');

        if ($limit === 'unlimited') {
            return true;
        }

        // Count staff in tenant database
        $currentCount = $this->run(function () {
            return \App\Models\Tenant\Staff::count();
        });

        return $currentCount < $limit;
    }

    /**
     * Usage Tracking
     */
    public function recordUsage(string $metric, int $quantity = 1): void
    {
        UsageRecord::create([
            'tenant_id' => $this->id,
            'metric' => $metric,
            'quantity' => $quantity,
            'recorded_at' => now(),
        ]);

        // Update cached stats
        $this->updateStats($metric, $quantity);
    }

    public function usageThisMonth(string $metric): int
    {
        return UsageRecord::where('tenant_id', $this->id)
            ->where('metric', $metric)
            ->whereYear('recorded_at', now()->year)
            ->whereMonth('recorded_at', now()->month)
            ->sum('quantity');
    }

    public function usageToday(string $metric): int
    {
        return UsageRecord::where('tenant_id', $this->id)
            ->where('metric', $metric)
            ->whereDate('recorded_at', today())
            ->sum('quantity');
    }

    public function resetMonthlyUsage(): void
    {
        // Called by scheduler at start of month
        // No need to delete records, just for reporting
    }

    /**
     * Stats Management
     */
    protected function updateStats(string $metric, int $quantity): void
    {
        $stats = $this->stats ?? [];
        $stats[$metric] = ($stats[$metric] ?? 0) + $quantity;

        $this->update(['stats' => $stats]);
    }

    public function refreshStats(): void
    {
        $this->run(function () {
            $this->update([
                'stats' => [
                    'total_bookings' => \App\Models\Tenant\Booking::count(),
                    'total_customers' => \App\Models\Tenant\Customer::count(),
                    'total_revenue' => \App\Models\Tenant\Payment::where('status', 'completed')->sum('amount'),
                ]
            ]);
        });
    }

    /**
     * Settings Management
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Domain Helpers
     */
    public function getPrimaryDomain()
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    public function getUrl(): string
    {
        $domain = $this->getPrimaryDomain();
        return $domain ? 'https://' . $domain->domain : '#';
    }

    /**
     * Suspend/Activate
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'settings' => array_merge($this->settings ?? [], [
                'suspension_reason' => $reason,
                'suspended_at' => now()->toDateTimeString(),
            ])
        ]);

        // Notify owner
        // TODO: Send notification
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);

        // Remove suspension info
        $settings = $this->settings ?? [];
        unset($settings['suspension_reason'], $settings['suspended_at']);
        $this->update(['settings' => $settings]);
    }
}