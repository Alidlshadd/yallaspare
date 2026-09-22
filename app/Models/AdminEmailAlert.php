<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminEmailAlert extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
        'sent_at' => 'datetime',
    ];
}
