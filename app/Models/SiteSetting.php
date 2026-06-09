<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget('site_setting_' . $setting->key);
        });

        static::deleted(function ($setting) {
            Cache::forget('site_setting_' . $setting->key);
        });
    }

    public static function get(string $key, mixed $default = null)
    {
        return Cache::rememberForever('site_setting_' . $key, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            // Try to decode JSON if possible
            $decoded = json_decode($setting->value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        $valueToSave = is_array($value) || is_object($value) ? json_encode($value) : $value;
        
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $valueToSave]
        );
    }
}
