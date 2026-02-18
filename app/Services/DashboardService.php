<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected $exchangeRateService;
    protected $walletsCache = null;
    protected $categoriesCache = null;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function getBasicData($month = null)
    {
        $userId = Auth::id();
        $now = Carbon::now();

        // Calculate resolved month if none provided. 
        // Force range calculation here because we need it for available_months dropdown.
        $resolvedMonth = $this->resolveEffectiveMonth($userId, $month, true);
        $month = $resolvedMonth['month'];
        $statsRange = $resolvedMonth['range'];

        $selectedDate = Carbon::parse($month . '-01');
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();
        $monthStr = $selectedDate->format('Y-m');
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $stats = $this->getAggregatedStats($userId, $startOfMonth, $endOfMonth);
        $totalBudget = Budget::where('user_id', $userId)->where('month', $monthStr)->sum('limit');
        
        $wallets = $this->getCachedWallets($userId);
        $totalNetWorth = $this->getTotalNetWorth($wallets);

        $savingsRate = $stats->lifetime_income > 0
            ? round((($stats->lifetime_income - $stats->lifetime_expense) / $stats->lifetime_income) * 100)
            : 0;

        return [
            'summary' => [
                'net_worth' => (float) $totalNetWorth,
                'lifetime_savings_rate' => $savingsRate,
                'emergency_fund_months' => $stats->last_3_months_expense > 0 ? round($totalNetWorth / ($stats->last_3_months_expense / 3), 1) : 0,
                'monthly_income' => (float) $stats->monthly_income,
                'monthly_expense' => (float) $stats->monthly_expense,
                'monthly_net_savings' => (float) ($stats->monthly_income - $stats->monthly_expense),
                'budget_limit' => (float) $totalBudget,
                'burn_rate' => $totalBudget > 0 ? round(($stats->monthly_expense / $totalBudget) * 100) : 0,
                'selected_month' => $monthStr,
                'selected_month_label' => $selectedDate->translatedFormat('F Y')
            ],
            'available_months' => $this->generateAvailableMonthsFromRange($statsRange, $now),
            'subscription' => [
                'is_premium' => (bool) ($user?->is_premium),
                'plan_id' => $user?->current_plan_id ?? 1,
                'plan_name' => $user?->current_plan_name ?? 'Starter',
                'subscription_until' => $user?->subscription_until ? $user->subscription_until->format('d M Y') : ($user?->is_premium ? 'Lifetime' : null),
                'days_remaining' => $user?->days_remaining ?? 0,
            ],
        ];
    }

    public function getChartsData($month = null)
    {
        $userId = Auth::id();
        $resolvedMonth = $this->resolveEffectiveMonth($userId, $month);
        $month = $resolvedMonth['month'];
        $selectedDate = Carbon::parse($month . '-01');
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user?->id;

        $trends = $this->getDailyTrends($userId, $selectedDate);

        return [
            'labels' => $trends['labels'],
            'income' => $trends['income'],
            'expense' => $trends['expense'],
        ];
    }

    public function getBreakdownData($month = null)
    {
        $userId = Auth::id();
        $resolvedMonth = $this->resolveEffectiveMonth($userId, $month);
        $month = $resolvedMonth['month'];

        $selectedDate = Carbon::parse($month . '-01');
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user?->id;

        $wallets = $this->getCachedWallets($userId);
        
        $categoryBreakdownRaw = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        $categoryIds = $categoryBreakdownRaw->pluck('category_id')->filter()->unique();
        $categoriesById = $this->getCachedCategories($categoryIds);

        $categories = $categoryBreakdownRaw->map(function ($item) use ($categoriesById) {
            $cat = $categoriesById->get($item->category_id);
            return [
                'category_id' => $item->category_id,
                'total' => $item->total,
                'category' => $cat ? $cat->name : 'Unknown',
                'color' => $cat ? $cat->color : 'bg-gray-500'
            ];
        });

        return [
            'wallets' => $this->getWalletDistribution($wallets),
            'categories' => $categories,
        ];
    }

    public function getRecentTransactions($month = null)
    {
        $userId = Auth::id();
        $resolvedMonth = $this->resolveEffectiveMonth($userId, $month);
        $month = $resolvedMonth['month'];

        $selectedDate = Carbon::parse($month . '-01');
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user?->id;

        $recentTransactions = Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        $walletsById = $this->getCachedWallets($userId)->keyBy('id');
        
        $categoryIds = $recentTransactions->pluck('category_id')->filter()->unique();
        $categoriesById = $this->getCachedCategories($categoryIds);

        $recentTransactions->each(function ($tx) use ($walletsById, $categoriesById) {
            $tx->setRelation('wallet', $walletsById->get($tx->wallet_id));
            $tx->setRelation('category', $categoriesById->get($tx->category_id));
        });

        return $recentTransactions;
    }

    protected function getCachedWallets($userId)
    {
        if ($this->walletsCache === null) {
            $this->walletsCache = Wallet::where('user_id', $userId)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');
        }
        return $this->walletsCache;
    }

    protected function getCachedCategories($ids)
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        if ($this->categoriesCache === null) {
            $this->categoriesCache = collect();
        }

        // Find IDs not in cache
        $missingIds = $ids->reject(fn($id) => $this->categoriesCache->has($id));

        if ($missingIds->isNotEmpty()) {
            $newCategories = Category::whereIn('id', $missingIds)->get()->keyBy('id');
            // Use union() to preserve numeric keys
            $this->categoriesCache = $this->categoriesCache->union($newCategories);
        }

        return $this->categoriesCache->only($ids);
    }


    private function getTotalNetWorth($wallets)
    {
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

    private function getAggregatedStats($userId, $startOfMonth, $endOfMonth)
    {
        $last3Months = Carbon::now()->subMonths(3);

        return Transaction::where('user_id', $userId)
            ->where('is_active', true)
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount_in_base_currency ELSE 0 END) as lifetime_income,
                SUM(CASE WHEN type = 'expense' THEN amount_in_base_currency ELSE 0 END) as lifetime_expense,
                SUM(CASE WHEN type = 'income' AND date BETWEEN ? AND ? THEN amount_in_base_currency ELSE 0 END) as monthly_income,
                SUM(CASE WHEN type = 'expense' AND date BETWEEN ? AND ? THEN amount_in_base_currency ELSE 0 END) as monthly_expense,
                SUM(CASE WHEN type = 'expense' AND date >= ? THEN amount_in_base_currency ELSE 0 END) as last_3_months_expense
            ", [$startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth, $last3Months])
            ->first();
    }

    private function getDailyTrends($userId, $selectedDate)
    {
        $daysInMonth = $selectedDate->daysInMonth;
        $monthStr = $selectedDate->format('Y-m');
        
        $dailyData = Transaction::where('user_id', $userId)
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

        $trendLabels = [];
        $trendIncome = [];
        $trendExpense = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $trendLabels[] = str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayData = $dailyData->get($d);
            $trendIncome[] = (float) ($dayData->income ?? 0);
            $trendExpense[] = (float) ($dayData->expense ?? 0);
        }

        return ['labels' => $trendLabels, 'income' => $trendIncome, 'expense' => $trendExpense];
    }

    private function getWalletDistribution($wallets)
    {
        return $wallets->map(function ($w) {
            $balanceInIdr = $w->balance;
            
            if ($w->currency !== 'IDR') {
                $rate = $this->exchangeRateService->getCurrentRate($w->currency, 'IDR') ?? 1.0;
                $balanceInIdr = $w->balance * $rate;
            }

            return [
                'name' => $w->name,
                'type' => $w->type,
                'balance' => $balanceInIdr,
                'currency' => $w->currency,
                'color' => $w->color ?? 'bg-indigo-500'
            ];
        });
    }

    private function generateAvailableMonthsFromRange($range, $now)
    {
        if (!$range || !$range->min_date) {
            return collect([
                [
                    'value' => $now->format('Y-m'),
                    'label' => $now->translatedFormat('F Y')
                ]
            ]);
        }

        $startDate = Carbon::parse($range->min_date)->startOfMonth();
        $endDate = Carbon::parse($range->max_date)->startOfMonth();
        
        $months = collect();

        while ($startDate->lte($endDate)) {
            $months->push([
                'value' => $endDate->format('Y-m'),
                'label' => $endDate->translatedFormat('F Y')
            ]);
            $endDate->subMonth();
        }

        return $months;
    }

    private function resolveEffectiveMonth($userId, $month = null, $forceRange = false)
    {
        // If month is already specified and we don't strictly need the range object 
        // (e.g. for charts/transactions segments), we can skip the SQL query.
        if ($month && !$forceRange) {
            return [
                'month' => $month,
                'range' => null
            ];
        }

        // Cache the range query for 1 minute to sync across the 4 requests 
        // triggered by Inertia's deferred props on the same page load.
        $statsRange = Cache::remember("user_{$userId}_tx_range_v2", 60, function () use ($userId) {
            return Transaction::where('user_id', $userId)
                ->where('is_active', true)
                ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
                ->first();
        });

        if (!$month) {
            $month = ($statsRange && $statsRange->max_date)
                ? Carbon::parse($statsRange->max_date)->format('Y-m') 
                : Carbon::now()->format('Y-m');
        }

        return [
            'month' => $month,
            'range' => $statsRange
        ];
    }
}
