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

        $adjustments = Transaction::where(function($q) use ($walletIds) {
                $q->whereIn('wallet_id', $walletIds)
                  ->orWhereIn('target_wallet_id', $walletIds);
            })
            ->where('is_active', true)
            ->where('date', '>=', $startDate)
            ->get();
            
        $adjByWallet = [];
        foreach ($adjustments as $tx) {
            if ($tx->type === 'income') {
                $adjByWallet[$tx->wallet_id]['income'] = ($adjByWallet[$tx->wallet_id]['income'] ?? 0) + $tx->amount;
            } elseif ($tx->type === 'expense') {
                $adjByWallet[$tx->wallet_id]['expense'] = ($adjByWallet[$tx->wallet_id]['expense'] ?? 0) + $tx->amount;
            } elseif ($tx->type === 'transfer') {
                $adjByWallet[$tx->wallet_id]['expense'] = ($adjByWallet[$tx->wallet_id]['expense'] ?? 0) + $tx->amount + $tx->fee;
                if ($tx->target_wallet_id) {
                    $adjByWallet[$tx->target_wallet_id]['income'] = ($adjByWallet[$tx->target_wallet_id]['income'] ?? 0) + $tx->amount;
                }
            }
        }

        // 2. Batch Fetch All Transactions for the matrix period
        $allTransactionsTxs = Transaction::with(['category', 'wallet', 'targetWallet'])
            ->where(function($q) use ($walletIds) {
                $q->whereIn('wallet_id', $walletIds)
                  ->orWhereIn('target_wallet_id', $walletIds);
            })
            ->where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $transactionsByWallet = [];
        foreach ($allTransactionsTxs as $tx) {
            if ($tx->type === 'transfer') {
                $transactionsByWallet[$tx->wallet_id][] = $tx;
                if ($tx->target_wallet_id) {
                    $txTarget = clone $tx;
                    $txTarget->is_target_side = true; 
                    $transactionsByWallet[$tx->target_wallet_id][] = $txTarget;
                }
            } else {
                $transactionsByWallet[$tx->wallet_id][] = $tx;
            }
        }

        foreach ($wallets as $wallet) {
            $walletId = $wallet->id;
            
            // 1. Calculate Opening Balance using batch data
            $adj = $adjByWallet[$walletId] ?? ['income' => 0, 'expense' => 0];
            $openingBalance = $wallet->balance - ($adj['income'] ?? 0) + ($adj['expense'] ?? 0);

            // 2. Process Transactions (from grouped collection)
            $transactions = collect($transactionsByWallet[$walletId] ?? []);
            
            $runningBalance = $openingBalance;
            $totalIncome = 0;
            $totalExpense = 0;
            $realIncome = 0;
            $realExpense = 0;

            $processedTransactions = $transactions->map(function ($tx) use (&$runningBalance, &$totalIncome, &$totalExpense, &$realIncome, &$realExpense, $wallet) {
                $isTargetSide = isset($tx->is_target_side) && $tx->is_target_side;

                $data = $tx->toArray();
                
                if ($tx->type === 'income') {
                    $runningBalance += $tx->amount;
                    $totalIncome += $tx->amount;
                    $realIncome += $tx->amount;
                } elseif ($tx->type === 'expense') {
                    $runningBalance -= $tx->amount;
                    $totalExpense += $tx->amount;
                    $realExpense += $tx->amount;
                } elseif ($tx->type === 'transfer') {
                    if ($isTargetSide) {
                        $runningBalance += $tx->amount;
                        $totalIncome += $tx->amount;
                        $data['type'] = 'income'; 
                        $data['category'] = ['name' => 'Transfer In', 'color' => 'bg-indigo-500'];
                        $data['description'] = $tx->description ?: 'Transfer from ' . ($tx->wallet->name ?? 'Wallet');
                    } else {
                        $deduction = $tx->amount + $tx->fee;
                        $runningBalance -= $deduction;
                        $totalExpense += $deduction;
                        $realExpense += $tx->fee;
                        
                        $data['amount'] = $deduction;
                        $data['category'] = ['name' => 'Transfer Out', 'color' => 'bg-indigo-500'];
                        $data['description'] = $tx->description ?: 'Transfer to ' . ($tx->targetWallet->name ?? 'Wallet');
                    }
                }

                if ($tx->type !== 'transfer') {
                    $data['category'] = [
                        'name' => $tx->category ? $tx->category->name : 'Uncategorized',
                        'color' => $tx->category ? $tx->category->color : 'bg-slate-500'
                    ];
                }

                $data['running_balance'] = $runningBalance;
                return $data;
            });

            // 4. Summaries
            $closingBalance = $openingBalance + $totalIncome - $totalExpense;

            $reports[] = [
                'wallet' => $wallet,
                'summary' => [
                    'opening_balance' => $openingBalance,
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'closing_balance' => $closingBalance,
                    'net_flow' => $totalIncome - $totalExpense,
                    'base_income' => $wallet->currency === 'IDR' ? $totalIncome : $totalIncome * $rate,
                    'base_expense' => $wallet->currency === 'IDR' ? $totalExpense : $totalExpense * $rate,
                    'base_net_flow' => $wallet->currency === 'IDR' ? ($totalIncome - $totalExpense) : ($totalIncome - $totalExpense) * $rate,
                    'real_base_income' => $wallet->currency === 'IDR' ? $realIncome : $realIncome * $rate,
                    'real_base_expense' => $wallet->currency === 'IDR' ? $realExpense : $realExpense * $rate,
                ],
                'transactions' => $processedTransactions->values()
            ];
        }

        return $reports;
    }
}
