<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Goal extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_YEARLY = 'yearly';

    public const TRACKING_AUTOMATIC = 'automatic';

    public const TRACKING_MANUAL = 'manual';

    public const DIRECTION_INCREASE = 'increase';

    public const DIRECTION_DECREASE = 'decrease';

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const UNITS = ['orders', 'iqd', 'products', 'customers', 'percentage'];

    protected $fillable = [
        'name', 'description', 'period_type', 'period_start', 'start_date', 'deadline',
        'tracking_mode', 'metric_key', 'metric_config', 'direction', 'baseline_value',
        'target_value', 'manual_value', 'unit', 'priority', 'reward_points',
        'completed_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'start_date' => 'date',
        'deadline' => 'date',
        'metric_config' => 'array',
        'baseline_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'manual_value' => 'decimal:2',
        'reward_points' => 'integer',
        'completed_at' => 'datetime',
    ];

    public static function periodTypes(): array
    {
        return [self::PERIOD_WEEKLY, self::PERIOD_MONTHLY, self::PERIOD_YEARLY];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(GoalProgressUpdate::class)->latest('recorded_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
