<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $exchangeRateService = app(\App\Services\ExchangeRateService::class);

        $goals = Goal::with('wallets')
            ->where('user_id', $user?->id)
            ->latest()
            ->get()
            ->map(function (\App\Models\Goal $goal) use ($exchangeRateService) {
                $currentAmountTotal = 0;

                foreach ($goal->wallets as $wallet) {
                    $balance = (float) $wallet->balance;
                    
                    if ($wallet->currency !== $goal->currency) {
                        $balance = $exchangeRateService->convertAmount(
                            $balance,
                            $wallet->currency,
                            $goal->currency
                        );
                    }
                    
                    $currentAmountTotal += $balance;
                }

                return array_merge($goal->toArray(), [
                    'current_amount' => $currentAmountTotal,
                ]);
            });

        $wallets = Wallet::where('user_id', $user?->id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('goals/index', [
            'goals' => $goals,
            'wallets' => $wallets,
            'currentExchangeRate' => Inertia::defer(function () use ($user) {
                // Return rate ONLY if user has a USD goal or USD wallet
                $hasUsd = \App\Models\Goal::where('user_id', $user?->id)->where('currency', 'USD')->exists() ||
                         \App\Models\Wallet::where('user_id', $user?->id)->where('currency', 'USD')->exists();
                
                return $hasUsd ? app(\App\Services\ExchangeRateService::class)->getCurrentRate('USD', 'IDR') : null;
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'type' => 'required|in:emergency,retirement,saving,custom',
            'target_date' => 'nullable|date|after_or_equal:start_date',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'wallet_ids' => 'required|array|min:1',
            'wallet_ids.*' => 'exists:wallets,id',
        ], [
            'name.required' => __('goal_name_required'),
            'target_amount.required' => __('target_amount_required'),
            'target_amount.numeric' => __('target_amount_numeric'),
            'target_amount.min' => __('target_amount_min'),
            'type.required' => __('type_required'),
            'target_date.date' => __('date_invalid'),
            'target_date.after_or_equal' => __('target_date_after_start'),
            'start_date.required' => __('start_date_required'),
            'wallet_ids.required' => __('wallet_allocation_required'),
            'wallet_ids.min' => __('wallet_allocation_required'),
        ]);

        $user = $request->user();
        $goalCount = Goal::where('user_id', $user?->id)->count();

        if (!$user->is_premium && $goalCount >= 1) {
            return redirect()->back()->withErrors([
                'premium' => 'You have reached the limit of 1 financial goal. Upgrade to Professional to add unlimited goals.'
            ]);
        }

        $validated['user_id'] = $user?->id;

        $goal = Goal::create($validated);
        $goal->wallets()->attach($request->wallet_ids);

        return redirect()->back()->with('message', 'Goal created successfully');
    }

    public function update(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()?->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'type' => 'required|in:emergency,retirement,saving,custom',
            'target_date' => 'nullable|date',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'wallet_ids' => 'required|array',
            'wallet_ids.*' => 'exists:wallets,id',
        ]);

        $goal->update($validated);
        $goal->wallets()->sync($request->wallet_ids);

        return redirect()->back()->with('message', 'Goal updated successfully');
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()?->id) {
            abort(403);
        }

        $goal->delete();

        return redirect()->back()->with('message', 'Goal deleted successfully');
    }
}
