<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function getDashboardData($month = null)
    {
        $now = Carbon::now();
        $selectedDate = $month ? Carbon::parse($month . '-01') : $now;
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();
        $monthStr = $selectedDate->format('Y-m');
        $userId = auth()->id();

        // 1. Total Net Worth & Lifetime Stats (All Time)
        $totalNetWorth = $this->getTotalNetWorth($userId);
        $lifetimeStats = $this->getLifetimeStats($userId);

        // 2. Monthly Stats (For Selected Month)
        $monthlyStats = $this->getMonthlyStats($userId, $startOfMonth, $endOfMonth);

        $totalBudget = Budget::where('user_id', $userId)->where('month', $monthStr)->sum('limit');

        $savingsRate = $lifetimeStats['income'] > 0
            ? round((($lifetimeStats['income'] - $lifetimeStats['expense']) / $lifetimeStats['income']) * 100)
            : 0;

        // 3. Daily Trend Statistics
        $trends = $this->getDailyTrends($userId, $selectedDate);

        // 4. Wallet Distribution
        $walletDistribution = $this->getWalletDistribution($userId);

        // 5. Recent Transactions
        $recentTransactions = $this->getRecentTransactions($userId, $startOfMonth, $endOfMonth);

        // 6. Category Breakdown
        $categoryBreakdown = $this->getCategoryBreakdown($userId, $startOfMonth, $endOfMonth);

        // 7. Emergency Fund Calculation
        $emergencyFundMonths = $this->getEmergencyFundStats($userId, $totalNetWorth);

        // 8. Available Months
        $availableMonths = $this->getAvailableMonths($userId, $now);

        return [
            'summary' => [
                'net_worth' => (float) $totalNetWorth,
                'lifetime_savings_rate' => $savingsRate,
                'emergency_fund_months' => (float) $emergencyFundMonths,
                'monthly_income' => (float) $monthlyStats['income'],
                'monthly_expense' => (float) $monthlyStats['expense'],
                'monthly_net_savings' => (float) ($monthlyStats['income'] - $monthlyStats['expense']),
                'budget_limit' => (float) $totalBudget,
                'burn_rate' => $totalBudget > 0 ? round(($monthlyStats['expense'] / $totalBudget) * 100) : 0,
                'selected_month' => $monthStr,
                'selected_month_label' => $selectedDate->translatedFormat('F Y')
            ],
            'charts' => [
                'labels' => $trends['labels'],
                'income' => $trends['income'],
                'expense' => $trends['expense'],
            ],
            'wallets' => $walletDistribution,
            'categories' => $categoryBreakdown,
            'recent_transactions' => $recentTransactions,
            'available_months' => $availableMonths
        ];
    }

    private function getTotalNetWorth($userId)
    {
        $wallets = Wallet::where('user_id', $userId)->where('is_active', true)->get();
        $totalNetWorth = 0;
        foreach ($wallets as $wallet) {
            if ($wallet->currency === 'IDR') {
                $totalNetWorth += $wallet->balance;
            } else {
                $rate = $this->exchangeRateService->getCurrentRate($wallet->currency, 'IDR');
                $totalNetWorth += $wallet->balance * $rate;
            }
        }
        return $totalNetWorth;
    }

    private function getLifetimeStats($userId)
    {
        $income = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'income')
            ->sum('amount_in_base_currency');

        $expense = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->sum('amount_in_base_currency');

        return ['income' => $income, 'expense' => $expense];
    }

    private function getMonthlyStats($userId, $startOfMonth, $endOfMonth)
    {
        $income = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount_in_base_currency');

        $expense = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount_in_base_currency');

        return ['income' => $income, 'expense' => $expense];
    }

    private function getDailyTrends($userId, $selectedDate)
    {
        $daysInMonth = $selectedDate->daysInMonth;
        $trendLabels = [];
        $trendIncome = [];
        $trendExpense = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = $selectedDate->copy()->day($d);
            $dayStr = $currentDate->format('d');
            $dateFull = $currentDate->toDateString();

            $trendLabels[] = $dayStr;

            $stats = Transaction::where('user_id', $userId)
                ->where('is_active', true)
                ->whereRaw('DATE(date) = ?', [$dateFull])
                ->select(
                    DB::raw("SUM(CASE WHEN type = 'income' THEN amount_in_base_currency ELSE 0 END) as income"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN amount_in_base_currency ELSE 0 END) as expense")
                )
                ->first();

            $trendIncome[] = (float) ($stats->income ?? 0);
            $trendExpense[] = (float) ($stats->expense ?? 0);
        }

        return ['labels' => $trendLabels, 'income' => $trendIncome, 'expense' => $trendExpense];
    }

    private function getWalletDistribution($userId)
    {
        $wallets = Wallet::where('user_id', $userId)->where('is_active', true)->get();
        return $wallets->map(function ($w) {
            $balanceInIdr = $w->currency === 'IDR'
                ? $w->balance
                : $w->balance * $this->exchangeRateService->getCurrentRate($w->currency, 'IDR');

            return [
                'name' => $w->name,
                'type' => $w->type,
                'balance' => $balanceInIdr,
                'currency' => $w->currency,
                'color' => $w->color ?? 'bg-indigo-500'
            ];
        });
    }

    private function getRecentTransactions($userId, $startOfMonth, $endOfMonth)
    {
        return Transaction::with(['wallet', 'category'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($tx) {
                $data = $tx->toArray();
                $data['category_color'] = $tx->category ? $tx->category->color : 'bg-gray-500';
                $data['category'] = $tx->category ? $tx->category->name : 'Unknown';
                return $data;
            });
    }

    private function getCategoryBreakdown($userId, $startOfMonth, $endOfMonth)
    {
        return Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['category'] = $item->category ? $item->category->name : 'Unknown';
                $data['color'] = $item->category ? $item->category->color : 'bg-gray-500';
                return $data;
            });
    }

    private function getEmergencyFundStats($userId, $totalNetWorth)
    {
        $avgExpense = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [Carbon::now()->subMonths(3), Carbon::now()])
            ->sum('amount_in_base_currency') / 3;

        return $avgExpense > 0 ? round($totalNetWorth / $avgExpense, 1) : 0;
    }

    private function getAvailableMonths($userId, $now)
    {
        $availableMonths = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%m') as value"))
            ->groupBy('value')
            ->orderBy('value', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->value,
                    'label' => Carbon::parse($item->value . '-01')->translatedFormat('F Y')
                ];
            });

        if ($availableMonths->isEmpty()) {
            return collect([
                [
                    'value' => $now->format('Y-m'),
                    'label' => $now->translatedFormat('F Y')
                ]
            ]);
        }
        return $availableMonths;
    }
}
