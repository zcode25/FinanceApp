<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Generate detailed report grouped by wallet with running balances.
     */
    public function getDetailedWalletReports(Carbon $startDate, Carbon $endDate)
    {
        $wallets = Wallet::where('user_id', Auth::id())->where('is_active', true)->get();
        $reports = [];
        $walletIds = $wallets->pluck('id');

        // Check for non-IDR wallets to decide if we need the exchange rate
        $hasNonIdr = $wallets->contains(fn($w) => $w->currency !== 'IDR');
        $rate = 1.0;
        if ($hasNonIdr) {
            $rate = $this->exchangeRateService->getCurrentRate('USD', 'IDR') ?? 16000;
        }

        // 1. Batch Fetch Opening Balance Adjustments (Queries AFTER start date)
// ... (rest of logic remains same, just using $rate in the return)

        // 1. Batch Fetch Opening Balance Adjustments (Queries AFTER start date)
        $adjustments = Transaction::whereIn('wallet_id', $walletIds)
            ->where('is_active', true)
            ->where('date', '>=', $startDate)
            ->select('wallet_id',
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            )
            ->groupBy('wallet_id')
            ->get()
            ->keyBy('wallet_id');

        // 2. Batch Fetch All Transactions for the matrix period
        $allTransactions = Transaction::with('category')
            ->whereIn('wallet_id', $walletIds)
            ->where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('wallet_id');

        foreach ($wallets as $wallet) {
            $walletId = $wallet->id;
            
            // 1. Calculate Opening Balance using batch data
            $adj = $adjustments->get($walletId);
            $openingBalance = $wallet->balance
                - ($adj->income ?? 0)
                + ($adj->expense ?? 0);

            // 2. Process Transactions (from grouped collection)
            $transactions = $allTransactions->get($walletId, collect());
            
            $runningBalance = $openingBalance;
            $processedTransactions = $transactions->map(function ($tx) use (&$runningBalance) {
                if ($tx->type === 'income') {
                    $runningBalance += $tx->amount;
                } else {
                    $runningBalance -= $tx->amount;
                }

                $data = $tx->toArray();
                $data['category'] = [
                    'name' => $tx->category ? $tx->category->name : 'Uncategorized',
                    'color' => $tx->category ? $tx->category->color : 'bg-slate-500'
                ];
                $data['running_balance'] = $runningBalance;

                return $data;
            });

            // 4. Summaries
            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
            $closingBalance = $openingBalance + $totalIncome - $totalExpense;

            $reports[] = [
                'wallet' => $wallet,
                'summary' => [
                    'opening_balance' => $openingBalance,
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'closing_balance' => $closingBalance,
                    'net_flow' => $totalIncome - $totalExpense,
                    // Converted values for global totals
                    'base_income' => $wallet->currency === 'IDR' ? $totalIncome : $totalIncome * $rate,
                    'base_expense' => $wallet->currency === 'IDR' ? $totalExpense : $totalExpense * $rate,
                    'base_net_flow' => $wallet->currency === 'IDR' ? ($totalIncome - $totalExpense) : ($totalIncome - $totalExpense) * $rate,
                ],
                'transactions' => $processedTransactions->values()
            ];
        }

        return $reports;
    }
}
