<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalAchievement extends Model
{
    protected $fillable = ['key', 'unlocked_at', 'context'];

    protected $casts = ['unlocked_at' => 'datetime', 'context' => 'array'];
}
