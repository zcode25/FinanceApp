<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTransaction;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function receive(Request $request)
    {
        // 1. Get Server Key
        $serverKey = env('MIDTRANS_SERVER_KEY');
        if (!$serverKey) {
            return response()->json(['message' => 'Server Key configuration missing'], 500);
        }

        // 2. Validate Signature
        // content: order_id + status_code + gross_amount + ServerKey
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return response()->json(['message' => 'Invalid data payload'], 400);
        }

        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($mySignature !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 3. Find Transaction
        // external_id sent to Midtrans is our 'order_id' reference
        $transaction = SubscriptionTransaction::where('external_id', $orderId)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 4. Update Status based on Midtrans Status
        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');
        $paymentType = $request->input('payment_type');

        // Log callback for debugging
        Log::info("Midtrans Callback for {$orderId}: {$transactionStatus} ({$fraudStatus})");

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $transaction->update([
                    'status' => 'pending',
                    'payment_type' => $paymentType
                ]);
            } else if ($fraudStatus == 'accept') {
                $this->handleSuccess($transaction, $paymentType);
            }
        } else if ($transactionStatus == 'settlement') {
            $this->handleSuccess($transaction, $paymentType);
        } else if ($transactionStatus == 'pending') {
            $transaction->update([
                'status' => 'pending',
                'payment_type' => $paymentType
            ]);
        } else if ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel' || $transactionStatus == 'failure') {
            $transaction->update([
                'status' => 'failed',
                'payment_type' => $paymentType
            ]);
        }

        return response()->json(['message' => 'Payment status updated']);
    }

    protected function handleSuccess(SubscriptionTransaction $transaction, $paymentType)
    {
        $transaction->markAsSuccess($paymentType);
    }
}
