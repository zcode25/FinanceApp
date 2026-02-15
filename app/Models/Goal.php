<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'type',
        'target_date',
        'start_date',
        'notes',
        'currency'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_date' => 'date',
        'start_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallets()
    {
        return $this->belongsToMany(Wallet::class, 'goal_wallet')->withPivot('allocation_percentage')->withTimestamps();
    }
}
