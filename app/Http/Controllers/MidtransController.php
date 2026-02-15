<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\Promo;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class MidtransController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function getSnapToken(Request $request)
    {
        $user = Auth::user();
        $plan = $request->plan;

        $planModel = Plan::find($plan);

        if (!$planModel) {
            return response()->json(['error' => 'Invalid plan'], 400);
        }

        $amount = $planModel->price;
        $originalAmount = $amount;
        $discountAmount = 0;
        $promoId = null;

        if ($request->promo_code) {
            $promo = Promo::where('code', $request->promo_code)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>', now());
                })
                ->first();

            if ($promo) {
                if ($promo->usage_limit === null || $promo->used_count < $promo->usage_limit) {
                    if ($promo->discount_percentage) {
                        $discountAmount = ($amount * $promo->discount_percentage) / 100;
                    } elseif ($promo->discount_amount) {
                        $discountAmount = $promo->discount_amount;
                    }

                    if ($discountAmount > $amount) {
                        $discountAmount = $amount;
                    }

                    $amount -= $discountAmount;
                    $promoId = $promo->id;
                } else {
                    return response()->json(['message' => 'Promo code usage limit reached.'], 400);
                }
            } else {
                return response()->json(['message' => 'Invalid or expired promo code.'], 400);
            }
        }

        // Generate Unique Order ID (VIBE- prefix to avoid collision with other apps)
        $orderId = 'VIBE-SUB-' . time() . '-' . $user->id;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            // Override Notification URL (Critical for Multi-App support)
            // This ensures Midtrans calls THIS app's callback for THIS transaction
            'override_notification_url' => route('payment-callback'),
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => $plan,
                    'price' => $originalAmount,
                    'quantity' => 1,
                    'name' => 'VibeFinance Premium - ' . ucfirst($plan)
                ]
            ],
            'callbacks' => [
                'finish' => route('dashboard', ['upgrade_success' => 1])
            ]
        ];

        if ($discountAmount > 0) {
            $params['item_details'][] = [
                'id' => 'DISCOUNT',
                'price' => -$discountAmount,
                'quantity' => 1,
                'name' => 'Discount applied'
            ];
        }

        // Handle specific payment method selection
        if ($request->payment_method) {
            $methodMap = [
                'bni' => ['bni_va'],
                'bri' => ['bri_va'],
                'mandiri' => ['echannel'],
                'permata' => ['permata_va'],
                'cimb' => ['cimb_va'],
                'gopay' => ['gopay'],
            ];

            if (isset($methodMap[$request->payment_method])) {
                $params['enabled_payments'] = $methodMap[$request->payment_method];
            }
        }

        try {
            // Handle FREE transaction (Amount 0)
            if ($amount <= 0) {
                $orderId = 'VIBE-FREE-' . time() . '-' . $user->id;

                $transaction = SubscriptionTransaction::create([
                    'user_id' => $user->id,
                    'external_id' => $orderId,
                    'plan_id' => (int) $plan,
                    'amount' => 0,
                    'gross_amount' => $originalAmount,
                    'discount_amount' => $originalAmount,
                    'promo_code_id' => $promoId,
                    'status' => 'success', // Auto-success
                    'payment_type' => 'free_promo',
                    'snap_token' => 'free_upgrade'
                ]);

                // Activate Premium Immediately
                $user->is_premium = true;
                $user->subscription_until = $this->calculateSubscriptionExpiry($plan, $orderId);
                $user->save();

                // Increment Promo Usage
                if ($promoId) {
                    $promo = Promo::find($promoId);
                    if ($promo) {
                        $promo->increment('used_count');
                    }
                }

                return response()->json(['is_free' => true]);
            }

            // Normal Midtrans Flow (Amount > 0)
            $snapToken = Snap::getSnapToken($params);

            SubscriptionTransaction::create([
                'user_id' => $user->id,
                'external_id' => $orderId,
                'plan_id' => (int) $plan,
                'amount' => $amount,
                'gross_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'promo_code_id' => $promoId,
                'status' => 'pending',
                'payment_type' => $request->payment_method ?: 'midtrans',
                'snap_token' => $snapToken
            ]);

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculateSubscriptionExpiry($planId, $externalId = '')
    {
        // Determination of subscription duration based on plan_id (Integer)
        if ((int) $planId === 2) { // monthly
            return now()->addMonth();
        } elseif ((int) $planId === 3) { // yearly
            return now()->addYear();
        } elseif ((int) $planId === 4) { // lifetime
            return now()->addYears(100);
        } else {
            // Fallback for legacy transactions
            if (str_contains((string) $planId, 'monthly') || str_contains($externalId, 'monthly')) {
                return now()->addMonth();
            } elseif (str_contains((string) $planId, 'yearly') || str_contains($externalId, 'yearly')) {
                return now()->addYear();
            } else {
                return now()->addYears(100);
            }
        }
    }

    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();
            $transactionStatus = $notification->transaction_status;
            $orderId = $notification->order_id;
            $paymentType = $notification->payment_type;

            $transaction = SubscriptionTransaction::where('external_id', $orderId)->first();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                // Check if already success to avoid double processing
                if ($transaction->status !== 'success') {
                    $transaction->update(['status' => 'success', 'payment_type' => $paymentType]);

                    $user = $transaction->user;
                    $user->is_premium = true;

                    // Increment Promo Usage if applicable
                    if ($transaction->promo_code_id) {
                        $promo = Promo::find($transaction->promo_code_id);
                        if ($promo) {
                            $promo->increment('used_count');
                        }
                    }

                    $user->subscription_until = $this->calculateSubscriptionExpiry($transaction->plan_id, $transaction->external_id);
                    $user->save();
                }

            } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'failure') {
                $transaction->update(['status' => 'failed']);
            } elseif ($transactionStatus == 'pending') {
                $transaction->update(['status' => 'pending']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
