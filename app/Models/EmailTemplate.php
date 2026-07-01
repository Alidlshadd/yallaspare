<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EmailTemplate extends Model
{
    use HasFactory;

    /** @var list<string> */
    public const KEYS = [
        'verify-email',
        'reset-password',
        'two-factor-code',
        'welcome',
        'order-status',
        'dealer',
        'security-alert',
        'low-stock',
        'support',
    ];

    /** @var list<string> */
    public const LOCALES = ['en', 'ar', 'ku'];

    protected $fillable = [
        'template_key',
        'locale',
        'subject',
        'body_html',
        'updated_by',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('email_templates');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Fetch an admin-editable override for the given template + locale, if any.
     * Returns null when the table is missing or no row was saved yet.
     */
    public static function findOverride(string $key, string $locale): ?self
    {
        if (! self::tableExists()) {
            return null;
        }

        return Cache::rememberForever(self::cacheKey($key, $locale), function () use ($key, $locale) {
            return self::query()
                ->where('template_key', $key)
                ->where('locale', $locale)
                ->first();
        });
    }

    protected static function booted(): void
    {
        static::saved(function (self $template) {
            Cache::forget(self::cacheKey($template->template_key, $template->locale));
        });

        static::deleted(function (self $template) {
            Cache::forget(self::cacheKey($template->template_key, $template->locale));
        });
    }

    private static function cacheKey(string $key, string $locale): string
    {
        return "email_template:{$key}:{$locale}";
    }
}
