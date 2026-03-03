<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

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
            'site_logo' => '',
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
}
