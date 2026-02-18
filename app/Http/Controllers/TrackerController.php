<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TrackerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPremium = $user?->is_premium ?? false;

        // For starter users, force 3m if they try to request more
        $requestedRange = $request->input('range', $isPremium ? '6m' : '3m');
        $range = $requestedRange;

        if (!$isPremium && !in_array($range, ['3m'])) {
            $range = '3m';
        }

        // 1. Determine the Date Points (Columns)
        $dates = $this->generateDatePoints($range, $isPremium);

        // 2. Fetch User's Wallets (Rows)
        $wallets = Wallet::where('user_id', $user?->id)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tracker/Index', [
            'periods' => $dates,
            'periods' => $dates,
            'deferred_data' => Inertia::defer(function () use ($dates, $wallets) {
                 return $this->getTrackerData($dates, $wallets);
            }),
            'filters' => [
                'range' => $requestedRange
            ],
            'is_premium' => $isPremium
        ]);
    }

    private function getTrackerData($dates, $wallets)
    {
        $walletIds = $wallets->pluck('id');
        $lastDateInMatrix = Carbon::parse(end($dates)['date'])->endOfDay();

        // Query: Get all changes (both inside matrix and future adjustments) in ONE call
        $allAggregates = Transaction::whereIn('wallet_id', $walletIds)
            ->where('is_active', true)
            ->where('date', '>', Carbon::parse($dates[0]['date'])->startOfMonth())
            ->select(
                'wallet_id',
                DB::raw("CASE WHEN date > '{$lastDateInMatrix->toDateTimeString()}' THEN 'future' ELSE DATE_FORMAT(date, '%Y-%m') END as month_key"),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as net_change')
            )
            ->groupBy('wallet_id', 'month_key')
            ->get()
            ->groupBy('wallet_id');

        $futureChanges = $allAggregates->map(function ($group) {
            return $group->where('month_key', 'future')->first()->net_change ?? 0;
        });

        $monthlyChanges = $allAggregates;

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

            $currentBalance = (float) $wallet->balance;
            $walletId = $wallet->id;
            $runningSumAfterMatrix = (float) ($futureChanges[$walletId] ?? 0);
            
            $reversedDates = array_reverse($dates);
            $tempBalances = [];

            foreach ($reversedDates as $datePoint) {
                $historicalBalance = $currentBalance - $runningSumAfterMatrix;
                $tempBalances[$datePoint['key']] = $historicalBalance;

                $monthKey = $datePoint['key'];
                $monthChange = $monthlyChanges->has($walletId) 
                    ? $monthlyChanges->get($walletId)->where('month_key', $monthKey)->first()->net_change ?? 0
                    : 0;
                
                $runningSumAfterMatrix += (float) $monthChange;

                if (!isset($totals[$datePoint['key']])) {
                    $totals[$datePoint['key']] = 0;
                }
                $totals[$datePoint['key']] += $historicalBalance;
            }

            $row['balances'] = $tempBalances;
            $matrix[] = $row;
        }

        return [
            'matrix' => $matrix,
            'totals' => $totals,
        ];
    }

    private function generateDatePoints($range, $isPremium)
    {
        $dates = [];
        $now = Carbon::now();

        $firstTransactionDate = Transaction::where('user_id', Auth::id())
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
