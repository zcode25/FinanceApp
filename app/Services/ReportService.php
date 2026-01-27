<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate detailed report grouped by wallet with running balances.
     */
    public function getDetailedWalletReports(Carbon $startDate, Carbon $endDate)
    {
        $wallets = Wallet::where('user_id', auth()->id())->where('is_active', true)->get();
        $reports = [];

        foreach ($wallets as $wallet) {
            // 1. Calculate Opening Balance (Backward from Current Balance)
            // Opening = Current - (Income >= Start) + (Expense >= Start)
            // This handles the absence of 'initial_balance' column and ensures accuracy.
            $postStartTx = Transaction::where('wallet_id', $wallet->id)
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->where('date', '>=', $startDate)
                ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
                ->first();

            $openingBalance = $wallet->balance
                - ($postStartTx->income ?? 0)
                + ($postStartTx->expense ?? 0);

            // 2. Fetch Transactions Ascending for Running Balance
            $transactions = Transaction::with('category')
                ->where('wallet_id', $wallet->id)
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc') // Chronological order
                ->orderBy('created_at', 'asc')
                ->get();

            // 3. Process Transactions & Running Balance
            $runningBalance = $openingBalance;
            $processedTransactions = $transactions->map(function ($tx) use (&$runningBalance) {
                // Adjust running balance
                if ($tx->type === 'income') {
                    $runningBalance += $tx->amount;
                } else {
                    $runningBalance -= $tx->amount;
                }

                $data = $tx->toArray();
                // Flatten category to string name for display compatibility
                $data['category'] = $tx->category ? $tx->category->name : 'Uncategorized';
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
                    'net_flow' => $totalIncome - $totalExpense
                ],
                // For display, user requested listing like a statement. 
                // Usually newest first is "Activity Stream", but "Statement" implies chronological.
                // The user Example showed 2 Jan, then 3 Jan... so Ascending (Oldest First) is correct.
                'transactions' => $processedTransactions->values()
            ];
        }

        return $reports;
    }
}
