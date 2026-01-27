<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['name', 'type', 'balance', 'currency', 'account_number', 'bank_name', 'color', 'is_active', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    protected $casts = [
        'is_active' => 'boolean',
    ];
}
