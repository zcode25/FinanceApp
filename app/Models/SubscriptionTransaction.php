<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'external_id',
        'plan_id',
        'amount',
        'gross_amount',
        'discount_amount',
        'promo_code_id',
        'status',
        'payment_type',
        'snap_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'promo_code_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function markAsSuccess(string $paymentType): void
    {
        // Avoid double processing
        if ($this->status === 'success') {
            return;
        }

        $this->update([
            'status' => 'success',
            'payment_type' => $paymentType
        ]);

        // Activate User Subscription
        $user = $this->user;
        $user->is_premium = true;

        // Increment Promo Usage if applicable
        if ($this->promoCode) {
            $this->promoCode->increment('used_count');
        }

        // Calculate subscription extension
        $plan = $this->plan;

        // Logical Base Date: 
        // If the user's current subscription is STILL ACTIVE (in the future), 
        // we add the new plan duration to that future date (Cumulative Renewal).
        // Otherwise, we start from now.
        $baseDate = ($user->subscription_until && $user->subscription_until->isFuture())
            ? $user->subscription_until
            : now();

        if ($plan) {
            if ($plan->duration_type === 'month') {
                $user->subscription_until = $baseDate->addMonths($plan->duration_value);
            } elseif ($plan->duration_type === 'year') {
                $user->subscription_until = $baseDate->addYears($plan->duration_value);
            } elseif ($plan->duration_type === 'lifetime') {
                $user->subscription_until = null;
            }
        } else {
            // Legacy/Fallback mapping if Plan model is missing
            if (str_contains($this->external_id, 'monthly') || $this->plan_id == 2) {
                $user->subscription_until = $baseDate->addMonth();
            } elseif (str_contains($this->external_id, 'yearly') || $this->plan_id == 3) {
                $user->subscription_until = $baseDate->addYear();
            } else {
                $user->subscription_until = null;
            }
        }

        $user->save();
    }
}
