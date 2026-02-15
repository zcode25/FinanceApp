<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrackerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isPremium = $user->is_premium;

        // For starter users, force 3m if they try to request more
        $requestedRange = $request->input('range', $isPremium ? '6m' : '3m');
        $range = $requestedRange;

        if (!$isPremium && !in_array($range, ['3m'])) {
            $range = '3m';
        }

        // 1. Determine the Date Points (Columns)
        $dates = $this->generateDatePoints($range, $isPremium);

        // 2. Fetch User's Wallets (Rows)
        $wallets = Wallet::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get();

        // 3. Calculate Balances for each Wallet at each Date Point
        $matrix = [];
        $totals = [];

        foreach ($wallets as $wallet) {
            $row = [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'currency' => $wallet->currency,
                'balances' => []
            ];

            // Get current balance from DB
            $currentBalance = $wallet->balance;

            foreach ($dates as $datePoint) {
                $date = Carbon::parse($datePoint['date'])->endOfDay();

                $balanceChangeAfter = Transaction::where('wallet_id', $wallet->id)
                    ->where('date', '>', $date)
                    ->where('is_active', true)
                    ->select(DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as net_change'))
                    ->value('net_change') ?? 0;

                // Historical Balance = Current - NetChangeAfter
                $historicalBalance = $currentBalance - $balanceChangeAfter;

                $row['balances'][$datePoint['key']] = $historicalBalance;

                // Add to totals
                if (!isset($totals[$datePoint['key']])) {
                    $totals[$datePoint['key']] = 0;
                }
                $totals[$datePoint['key']] += $historicalBalance;
            }

            $matrix[] = $row;
        }

        return Inertia::render('Tracker/Index', [
            'periods' => $dates,
            'matrix' => $matrix,
            'totals' => $totals,
            'filters' => [
                'range' => $requestedRange // Keep requested range for UI logic
            ],
            'is_premium' => $isPremium
        ]);
    }

    private function generateDatePoints($range, $isPremium)
    {
        $dates = [];
        $now = Carbon::now();

        $firstTransactionDate = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->min('date');

        $monthsSinceStart = $firstTransactionDate
            ? Carbon::parse($firstTransactionDate)->startOfMonth()->diffInMonths($now->copy()->startOfMonth()) + 1
            : 1;

        $maxMonths = match ($range) {
            '1y' => 12,
            'ytd' => $now->month,
            'all' => $monthsSinceStart,
            '3m' => 3,
            default => 6
        };

        // Strict Enforcement: Starter users can never go back more than 3 months
        if (!$isPremium) {
            $maxMonths = min($maxMonths, 3);
        }

        // Don't show more months than available data
        $monthsToGoBack = min($maxMonths, $monthsSinceStart);

        for ($i = $monthsToGoBack - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i)->endOfMonth();
            $dates[] = [
                'key' => $date->format('Y-m'),
                'label' => $date->translatedFormat('M Y'),
                'date' => $date->format('Y-m-d')
            ];
        }

        return $dates;
    }
}
