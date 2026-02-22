<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'category_id', // Replaced category
        'description',
        'date',
        'currency',
        'wallet_id',
        'exchange_rate',
        'exchange_rate_date',
        'amount_in_base_currency',
        'is_active',
        'user_id',
        'target_wallet_id',
        'fee',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function targetWallet()
    {
        return $this->belongsTo(Wallet::class, 'target_wallet_id');
    }
}
