<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalMetricSnapshot extends Model
{
    protected $fillable = [
        'period_type', 'period_start', 'metric_key', 'metric_config_hash', 'metric_config',
        'value', 'captured_on', 'captured_at',
    ];

    protected $casts = [
        'period_start' => 'date', 'metric_config' => 'array', 'value' => 'decimal:2',
        'captured_on' => 'date', 'captured_at' => 'datetime',
    ];
}
