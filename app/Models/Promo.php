<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'code',
        'discount_percentage',
        'discount_amount',
        'valid_until',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
    ];
}
