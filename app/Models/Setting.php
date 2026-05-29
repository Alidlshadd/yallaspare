<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'key',
        'value',
    ];

    private const CACHE_KEY = 'system_settings_all';

    public static function defaults(): array
    {
        return [
            'site_name' => config('app.name', 'Laravel'),
            'currency_code' => 'IQD',
            'currency_symbol' => 'IQD',
            'low_stock_threshold' => (string) config('inventory.low_stock_threshold', 5),
            'shipping_fee' => '5000',
            'site_logo' => '',
            'site_logo_version' => '',
            'storefront_hero_title' => 'Find the right spare parts faster',
            'storefront_hero_subtitle' => 'Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.',
            'storefront_hero_button_label' => 'Shop now',
            'storefront_hero_button_url' => '',
            'storefront_hero_image' => '',
            'storefront_hero_video' => '',
        ];
    }

    public static function allWithDefaults(): array
    {
        return array_merge(self::defaults(), self::allKeyValue());
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = self::allWithDefaults();

        return $all[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    public static function setMany(array $items): void
    {
        foreach ($items as $key => $value) {
            self::query()->updateOrCreate(
                ['key' => (string) $key],
                ['value' => (string) $value]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function allKeyValue(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            try {
                if (!Schema::hasTable('settings')) {
                    return [];
                }

                return self::query()->pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
