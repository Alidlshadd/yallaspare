<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchAnalytic extends Model
{
    use HasFactory;

    protected $table = 'search_analytics';

    protected $fillable = ['keyword', 'search_count', 'last_searched_at'];

    protected $casts = [
        'last_searched_at' => 'datetime',
    ];
}
