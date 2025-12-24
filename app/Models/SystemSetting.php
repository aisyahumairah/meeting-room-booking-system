<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $storedValue = is_array($value) ? json_encode($value) : (string) $value;

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings grouped.
     */
    public static function getAllGrouped(): array
    {
        return self::all()->groupBy('group')->toArray();
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Check if maintenance mode is enabled.
     */
    public static function isMaintenanceMode(): bool
    {
        return self::get('maintenance_mode', false);
    }

    /**
     * Check if emails are enabled.
     */
    public static function isEmailEnabled(): bool
    {
        return self::get('email_enabled', true);
    }

    /**
     * Get session timeout in minutes.
     */
    public static function getSessionTimeout(): int
    {
        return self::get('session_timeout', 30);
    }
}
