<?php

namespace App\Models;

use App\Support\LocalizedText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model {
    use HasFactory, LogsActivity;

    protected $fillable = ['name_en','name_ar','name_ku','slug','description','image'];
    public function products() {
        return $this->hasMany(Product::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $field = match (true) {
            str_starts_with($locale, 'ar') => 'name_ar',
            str_starts_with($locale, 'ku') => 'name_ku',
            default => 'name_en',
        };

        return LocalizedText::first($this->{$field}, $this->name_en, $this->name_ar, $this->name_ku, __('Category'));
    }

    public function localizedDescription(): string
    {
        $description = trim((string) $this->description);

        return $description !== '' ? __($description) : '';
    }

    public function getNameAttribute(): string
    {
        return $this->localizedName();
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedName();
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        return $this->localizedDescription();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
