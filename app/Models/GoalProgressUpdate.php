<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgressUpdate extends Model
{
    protected $fillable = ['goal_id', 'value', 'note', 'recorded_by', 'recorded_at'];

    protected $casts = ['value' => 'decimal:2', 'recorded_at' => 'datetime'];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
