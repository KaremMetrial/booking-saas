<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Scopes
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get typed value
     */
    public function getTypedValue()
    {
        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Static Helpers
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    public static function set(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        // Convert value to string for storage
        $stringValue = match($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
    }

    public static function all(): array
    {
        return static::get()
            ->mapWithKeys(fn($setting) => [$setting->key => $setting->getTypedValue()])
            ->toArray();
    }
}
