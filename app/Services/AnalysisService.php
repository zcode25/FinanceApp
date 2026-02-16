<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalysisService
{
    /**
     * Cache for request-level memoization.
     */
    protected array $memo = [];
    public function getSpendingByCategory(Carbon $startDate, Carbon $endDate)
    {
        $key = 'spending_by_category_' . $startDate->toDateString() . '_' . $endDate->toDateString();
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        return $this->memo[$key] = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category') // Eager load category
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                // Return a simple object or array to avoid Model relationship confusion
                return (object) [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category ? $item->category->name : 'Unknown', // Explicit name
                    'total' => (float) $item->total
                ];
            });
    }

    public function getCashFlowTrend(Carbon $startDate, Carbon $endDate)
    {
        $daysInMonth = $startDate->daysInMonth;
        $monthStr = $startDate->format('Y-m');

        // 1. Fetch all aggregates in ONE query
        $dailyData = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$monthStr])
            ->select(
                DB::raw("DAY(date) as day"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount_in_base_currency ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount_in_base_currency ELSE 0 END) as expense")
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        // 2. Map and fill gaps
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[] = str_pad($d, 2, '0', STR_PAD_LEFT);
            
            $dayData = $dailyData->get($d);
            $incomeData[] = (float) ($dayData->income ?? 0);
            $expenseData[] = (float) ($dayData->expense ?? 0);
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData
        ];
    }

    public function getWalletAllocation()
    {
        if (isset($this->memo['wallet_allocation'])) {
            return $this->memo['wallet_allocation'];
        }

        $wallets = Wallet::where('user_id', auth()->id())->where('is_active', true)->get();
        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getCurrentRate('USD', 'IDR') ?? 16000;

        $allocation = [];
        foreach ($wallets as $wallet) {
            $amount = (float) $wallet->balance;
            if ($wallet->currency === 'USD') {
                $amount *= $rate;
            }

            if (!isset($allocation[$wallet->type])) {
                $allocation[$wallet->type] = 0;
            }
            $allocation[$wallet->type] += $amount;
        }

        return $this->memo['wallet_allocation'] = collect($allocation)->map(function ($total, $type) {
            return ['type' => $type, 'total' => $total];
        })->values();
    }

    public function getSummary(Carbon $startDate, Carbon $endDate)
    {
        $key = 'summary_' . $startDate->toDateString() . '_' . $endDate->toDateString();
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $summary = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(CASE WHEN type = "income" THEN amount_in_base_currency ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount_in_base_currency ELSE 0 END) as total_expense'),
                DB::raw('SUM(CASE WHEN type = "expense" AND DAYOFWEEK(date) IN (1, 7) THEN amount_in_base_currency ELSE 0 END) as weekend_expense'),
                DB::raw('COUNT(CASE WHEN type = "expense" AND amount_in_base_currency < 50000 THEN 1 END) as small_transaction_count')
            )
            ->first();

        $income = (float) ($summary->total_income ?? 0);
        $expense = (float) ($summary->total_expense ?? 0);

        return $this->memo[$key] = [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_savings' => $income - $expense,
            'savings_rate' => $income > 0 ? round((($income - $expense) / $income) * 100, 1) : 0,
            'weekend_expense' => (float) ($summary->weekend_expense ?? 0),
            'small_transaction_count' => (int) ($summary->small_transaction_count ?? 0),
        ];
    }

    public function getSmartInsights(Carbon $startDate, Carbon $endDate)
    {
        $insights = [];

        // 1. Inflation Detector (Category Anomalies)
        $currentCategories = $this->getSpendingByCategory($startDate, $endDate);

        // Get average of last 3 months for comparison
        $prevStart = $startDate->copy()->subMonths(3);
        $prevEnd = $startDate->copy()->subDay();

        $historicalData = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->keyBy('category_id'); // Key by ID for reliable lookup

        foreach ($currentCategories as $cat) {
            // $cat is now our custom object from getSpendingByCategory
            $hist = $historicalData->get($cat->category_id);
            // Approx monthly avg for last 3 months (90 days)
            $avgMonthly = $hist ? ($hist->total / 3) : 0;

            if ($avgMonthly > 0 && $cat->total > ($avgMonthly * 1.5)) {
                $insights[] = [
                    'type' => 'critical',
                    'title' => __('insight_inflation_title', ['category' => $cat->category_name]),
                    'message' => __('insight_inflation_message', [
                        'amount' => 'Rp ' . number_format($cat->total, 0, ',', '.'),
                        'category' => $cat->category_name,
                        'percentage' => round(($cat->total / $avgMonthly) * 100 - 100)
                    ]),
                    'icon' => 'TrendingUp'
                ];
                break; // Only show top anomaly
            }
        }

        // 2. Burn Rate (Predictive Analysis)
        $daysPassed = $startDate->diffInDays(Carbon::now()->endOfDay()) + 1;
        $daysInMonth = $startDate->daysInMonth;

        // Only run prediction if we are in the current month and at least 3 days in
        if ($startDate->format('Y-m') === Carbon::now()->format('Y-m') && $daysPassed >= 3 && $daysPassed < $daysInMonth) {
            $summary = $this->getSummary($startDate, $endDate);
            $currentSpent = $summary['total_expense'];
            $projectedSpend = ($currentSpent / $daysPassed) * $daysInMonth;
            $income = $summary['total_income'];

            if ($income > 0 && $projectedSpend > $income) {
                $insights[] = [
                    'type' => 'critical',
                    'title' => __('insight_burn_rate_title'),
                    'message' => __('insight_burn_rate_message', ['amount' => 'Rp ' . number_format($projectedSpend, 0, ',', '.')]),
                    'icon' => 'Flame'
                ];
            } elseif ($income > 0 && $projectedSpend > ($income * 0.9)) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => __('insight_pacing_title'),
                    'message' => __('insight_pacing_message', ['amount' => 'Rp ' . number_format($projectedSpend, 0, ',', '.')]),
                    'icon' => 'AlertTriangle'
                ];
            }
        }

        // 3. Weekend Warrior
        $summary = $this->getSummary($startDate, $endDate);
        $weekendSpend = $summary['weekend_expense'];
        $totalSpend = $summary['total_expense'];

        if ($totalSpend > 0 && ($weekendSpend / $totalSpend) > 0.45) {
            $insights[] = [
                'type' => 'info',
                'title' => __('insight_weekend_title'),
                'message' => __('insight_weekend_message', ['percentage' => round(($weekendSpend / $totalSpend) * 100)]),
                'icon' => 'Calendar'
            ];
        }

        // 4. The Latte Factor (Small frequent transactions)
        $smallTxCount = $summary['small_transaction_count'];

        if ($smallTxCount > 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => __('insight_latte_title'),
                'message' => __('insight_latte_message', ['count' => $smallTxCount, 'amount' => '50k']),
                'icon' => 'Coffee'
            ];
        }

        // 5. Savings Health
        $summary = $this->getSummary($startDate, $endDate);
        if ($summary['savings_rate'] >= 20) {
            $insights[] = [
                'type' => 'success',
                'title' => __('insight_savings_title'),
                'message' => __('insight_savings_message', ['rate' => $summary['savings_rate']]),
                'icon' => 'Award'
            ];
        }

        return $insights;
    }

    public function getFinancialTips(Carbon $startDate, Carbon $endDate)
    {
        $tips = [];
        $summary = $this->getSummary($startDate, $endDate);

        // 1. Emergency Fund "Runway" Calculator
        $allocations = $this->getWalletAllocation();
        $totalLiquidAssets = $allocations->sum('total');

        $monthlyExpense = $summary['total_expense'];

        // Only calculate runway if there are actual expenses
        if ($monthlyExpense > 0) {
            $runwayMonths = $totalLiquidAssets / $monthlyExpense;

            if ($runwayMonths < 1) {
                $tips[] = [
                    'type' => 'critical',
                    'title' => __('tip_emergency_critical_title'),
                    'message' => __('tip_emergency_critical_message', ['months' => number_format($runwayMonths, 1)]),
                    'icon' => 'ShieldAlert'
                ];
            } elseif ($runwayMonths < 3) {
                $tips[] = [
                    'type' => 'warning',
                    'title' => __('tip_emergency_warning_title'),
                    'message' => __('tip_emergency_warning_message', ['months' => number_format($runwayMonths, 1)]),
                    'icon' => 'Shield'
                ];
            } elseif ($runwayMonths > 6) {
                $tips[] = [
                    'type' => 'success',
                    'title' => __('tip_emergency_success_title'),
                    'message' => __('tip_emergency_success_message', ['months' => number_format($runwayMonths, 1)]),
                    'icon' => 'TrendingUp'
                ];
            }
        }

        // 2. The "Category Squeeze"
        $spending = $this->getSpendingByCategory($startDate, $endDate);
        $topCategory = $spending->first();

        if ($topCategory) {
            $potentialSavings = $topCategory->total * 0.10; // 10% reduction
            $catName = $topCategory->category_name;
            $tips[] = [
                'type' => 'info',
                'title' => __('tip_squeeze_title', ['category' => $catName]),
                'message' => __('tip_squeeze_message', [
                    'category' => $catName,
                    'amount' => 'Rp ' . number_format($potentialSavings, 0, ',', '.')
                ]),
                'icon' => 'Scissors'
            ];
        }

        // 3. Savings Efficiency Matrix
        $savingsRate = $summary['savings_rate'];
        if ($savingsRate < 10 && $summary['total_income'] > 0) {
            $tips[] = [
                'type' => 'warning',
                'title' => __('tip_savings_title'),
                'message' => __('tip_savings_message', ['rate' => $savingsRate]),
                'icon' => 'PiggyBank'
            ];
        }

        return $tips;
    }
}


