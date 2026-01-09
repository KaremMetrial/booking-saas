<?php

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    protected $fillable = [
        'domain',
        'tenant_id',
        'is_primary',
        'verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
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
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Status Checks
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Actions
     */
    public function verify(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function makePrimary(): void
    {
        // Remove primary from other domains
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Make this primary
        $this->update(['is_primary' => true]);
    }

    /**
     * DNS Helpers
     */
    public function checkDNS(): bool
    {
        $records = dns_get_record($this->domain, DNS_CNAME + DNS_A);

        $expectedTarget = config('tenancy.central_domain');

        foreach ($records as $record) {
            if (isset($record['target']) && str_contains($record['target'], $expectedTarget)) {
                return true;
            }
            if (isset($record['ip']) && $record['ip'] === config('tenancy.central_ip')) {
                return true;
            }
        }

        return false;
    }

    public function getDNSInstructions(): array
    {
        return [
            'type' => 'CNAME',
            'name' => '@',
            'value' => config('tenancy.central_domain'),
            'ttl' => 3600,
        ];
    }
}
