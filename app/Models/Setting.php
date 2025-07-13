<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];

    /**
     * Boot the model and add observers
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('app_settings');
        });

        static::deleted(function () {
            Cache::forget('app_settings');
        });
    }

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $settings = self::getAllSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );
    }

    /**
     * Get all settings cached
     */
    public static function getAllSettings()
    {
        return Cache::remember('app_settings', 3600, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get delivery charges
     */
    public static function getDeliveryCharges()
    {
        return (float) self::get('delivery_charges', 100);
    }

    /**
     * Get minimum order for free delivery
     */
    public static function getFreeDeliveryMinimum()
    {
        return (float) self::get('free_delivery_minimum', 500);
    }

    /**
     * Calculate delivery charges based on order total
     */
    public static function calculateDeliveryCharges($orderTotal, $deliveryMethod = 'delivery')
    {
        if ($deliveryMethod !== 'delivery') {
            return 0;
        }

        $deliveryCharges = self::getDeliveryCharges();
        $freeDeliveryMinimum = self::getFreeDeliveryMinimum();

        // Free delivery if order total meets minimum
        if ($orderTotal >= $freeDeliveryMinimum) {
            return 0;
        }

        return $deliveryCharges;
    }
}