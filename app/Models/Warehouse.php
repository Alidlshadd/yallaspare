<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'phone',
        'country_code',
        'city',
        'district',
        'address_line_1',
        'address_line_2',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
