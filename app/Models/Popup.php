<?php

namespace App\Models;

use App\Support\LocalizedText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Popup extends Model
{
    use HasFactory;
    use LogsActivity;

    public const PAGE_KEYS = ['all', 'home', 'shop', 'product', 'cart', 'checkout'];

    public const FREQUENCIES = ['every_visit', 'once_per_session', 'once_per_days'];

    private const CACHE_KEY = 'storefront_active_popups';

    protected $fillable = [
        'title_en',
        'title_ar',
        'title_ku',
        'description_en',
        'description_ar',
        'description_ku',
        'button_label_en',
        'button_label_ar',
        'button_label_ku',
        'button_url',
        'image_path',
        'is_active',
        'starts_at',
        'ends_at',
        'pages',
        'frequency',
        'frequency_days',
        'delay_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'pages' => 'array',
            'frequency_days' => 'integer',
            'delay_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function localizedTitle(): string
    {
        $field = 'title_' . app()->getLocale();

        return LocalizedText::first($this->{$field} ?? '', $this->title_en, $this->title_ar, $this->title_ku, '');
    }

    public function localizedDescription(): ?string
    {
        $field = 'description_' . app()->getLocale();

        return LocalizedText::nullable($this->{$field} ?? '', $this->description_en);
    }

    public function localizedButtonLabel(): ?string
    {
        $field = 'button_label_' . app()->getLocale();

        return LocalizedText::nullable($this->{$field} ?? '', $this->button_label_en);
    }

    public function hasButton(): bool
    {
        return $this->localizedButtonLabel() !== null && trim((string) $this->button_url) !== '';
    }

    public function targetsPage(string $pageKey): bool
    {
        $pages = is_array($this->pages) ? $this->pages : [];

        return in_array('all', $pages, true) || in_array($pageKey, $pages, true);
    }

    public function isCurrentlyRunning(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function scheduleState(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return 'expired';
        }

        return 'running';
    }

    /**
     * Newest popup eligible for the given storefront page, or null.
     * The active list is cached; date-window checks run per request so a
     * cached popup never leaks outside its schedule.
     */
    public static function activeForPage(string $pageKey): ?self
    {
        /** @var array<int, self> $candidates */
        $candidates = Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            try {
                return self::query()
                    ->where('is_active', true)
                    ->latest('id')
                    ->get()
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        foreach ($candidates as $popup) {
            if ($popup->isCurrentlyRunning() && $popup->targetsPage($pageKey)) {
                return $popup;
            }
        }

        return null;
    }

    /**
     * Map a storefront route name to a popup page key.
     */
    public static function pageKeyForRoute(?string $routeName): string
    {
        if ($routeName === null) {
            return 'other';
        }

        // Storefront routes exist both bare and under the "user." prefix.
        $routeName = str_starts_with($routeName, 'user.')
            ? substr($routeName, strlen('user.'))
            : $routeName;

        return match (true) {
            $routeName === 'home', $routeName === 'shop.home' => 'home',
            $routeName === 'shop.index' => 'shop',
            $routeName === 'shop.show' => 'product',
            str_starts_with($routeName, 'cart.') => 'cart',
            str_starts_with($routeName, 'checkout.') => 'checkout',
            default => 'other',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
