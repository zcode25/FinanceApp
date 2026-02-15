<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $plans = \App\Models\Plan::all()->values();

        // Determine current plan ID
        // 1 = Starter (Free)
        // Others = Professional, Master, Lifetime based on latest successful transaction
        $currentPlanId = null;
        if ($user) {
            if (!$user->is_premium) {
                $currentPlanId = 1;
            } else {
                $latestSuccess = $user->transactions()
                    ->where('status', 'success')
                    ->latest()
                    ->first();

                $currentPlanId = $latestSuccess ? (int) $latestSuccess->plan_id : 1;
            }
        }

        return Inertia::render('Subscription/Pricing', [
            'is_premium' => $user ? $user->is_premium : false,
            'subscription_until' => $user && $user->is_premium
                ? ($user->subscription_until ? $user->subscription_until->format('d M Y') : 'Lifetime')
                : null,
            'days_remaining' => $user ? $user->days_remaining : 0,
            'plan_name' => $user ? $user->current_plan_name : 'Starter',
            'intended_plan' => $request->session()->pull('intended_plan'),
            'plans' => $plans,
            'current_plan_id' => $currentPlanId,
        ]);
    }

    public function checkout(Request $request, $plan)
    {
        $user = auth()->user();
        $planModel = \App\Models\Plan::find($plan);

        if (!$planModel) {
            return redirect()->route('subscription.index');
        }

        $allPlans = [
            1 => [
                'period' => '/forever',
                'description' => 'Basic tracking for personal use.',
            ],
            2 => [
                'period' => '/month',
                'description' => 'Perfect for dedicated personal finance management.',
            ],
            3 => [
                'period' => '/year',
                'description' => 'Our most popular plan for serious wealth builders.',
            ],
            4 => [
                'period' => 'once',
                'description' => 'Unlimited access forever. No more subscriptions.',
            ]
        ];

        $planData = array_merge(
            [
                'id' => (int) $plan,
                'name' => $planModel->name,
                'price' => 'Rp ' . number_format($planModel->price, 0, ',', '.'),
                'raw_price' => (float) $planModel->price
            ],
            $allPlans[(int) $plan]
        );

        // Check for existing pending transaction to preserve lock state
        $existingTransaction = \App\Models\SubscriptionTransaction::where('user_id', $user->id)
            ->where('plan_id', (int) $plan)
            ->where('status', 'pending')
            ->with('promoCode')
            ->latest()
            ->first();

        return Inertia::render('Subscription/Checkout', [
            'plan' => $planData,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'existing_transaction' => $existingTransaction ? [
                'snap_token' => $existingTransaction->snap_token,
                'promo_code' => $existingTransaction->promoCode ? $existingTransaction->promoCode->code : null,
                'payment_method' => $existingTransaction->payment_type,
                'amount' => (float) $existingTransaction->amount,
                'discount_amount' => (float) $existingTransaction->discount_amount,
            ] : null
        ]);
    }

    public function validatePromo(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string',
            'plan_id' => 'required|integer',
        ]);

        $promo = \App\Models\Promo::where('code', $request->promo_code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->first();

        if (!$promo) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired promo code.'
            ]);
        }

        if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
            return response()->json([
                'valid' => false,
                'message' => 'Promo code usage limit reached.'
            ]);
        }

        $plan = \App\Models\Plan::find($request->plan_id);
        if (!$plan) {
            return response()->json(['message' => 'Invalid plan selected.'], 422);
        }

        $discountAmount = 0;
        if ($promo->discount_percentage) {
            $discountAmount = ($plan->price * $promo->discount_percentage) / 100;
        } elseif ($promo->discount_amount) {
            $discountAmount = $promo->discount_amount;
        }

        if ($discountAmount > $plan->price) {
            $discountAmount = $plan->price;
        }

        return response()->json([
            'valid' => true,
            'discount_amount' => $discountAmount,
            'discount_percentage' => $promo->discount_percentage,
            'message' => 'Promo code applied successfully!'
        ]);
    }
}
