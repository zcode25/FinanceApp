<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

class BudgetService
{
    /**
     * Cache for request-level memoization.
     */
    protected array $memo = [];
    /**
     * Get budgets for a specific month with spent progress.
     */
    public function getMonthlyBudgets(string $month)
    {
        if (isset($this->memo['budgets_' . $month])) {
            return $this->memo['budgets_' . $month];
        }

        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $budgets = Budget::with('category')->where('user_id', Auth::id())->where('month', $month)->get();

        $spentByCategory = Transaction::where('user_id', Auth::id())
            ->where('is_active', true)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $result = $budgets->map(function ($budget) use ($spentByCategory) {
            $categoryId = $budget->category_id;
            $spent = (float) ($spentByCategory[$categoryId] ?? 0);
            $percentage = $budget->limit > 0 ? round(($spent / $budget->limit) * 100) : 0;

            return [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category' => $budget->category,
                'limit' => (float) $budget->limit,
                'spent' => $spent,
                'percentage' => $percentage,
                'reason' => $budget->reason,
                'status' => $this->getBudgetStatus($percentage),
            ];
        });

        // Store intermediate data for reuse in other methods
        $this->memo['raw_spent_' . $month] = $spentByCategory;
        
        return $this->memo['budgets_' . $month] = $result;
    }

    /**
     * Get summary metrics for the budget dashboard.
     */
    public function getSummary(string $month)
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        // Use the memoized budgets to avoid redundant queries
        $budgets = $this->getMonthlyBudgets($month);
        $spentByCategory = $this->memo['raw_spent_' . $month] ?? collect();

        $totalBudget = $budgets->sum('limit');
        $totalSpent = $spentByCategory->sum();

        $savingsBudget = $budgets->filter(function ($b) {
            return $b['category'] && in_array($b['category']->name, ['Saving', 'Investment', 'Tabungan', 'Investasi']);
        })->sum('limit');

        $remaining = max(0, $totalBudget - $totalSpent);
        $percentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100) : 0;

        // Calculate days remaining
        $now = Carbon::now();
        $daysInMonth = $endDate->day;
        $currentDay = $now->month == $startDate->month ? $now->day : 1;
        $daysRemaining = max(1, $daysInMonth - $currentDay + 1);

        return [
            'total_budget' => (float) $totalBudget,
            'total_spent' => (float) $totalSpent,
            'remaining' => (float) $remaining,
            'percentage' => $percentage,
            'savings_budget' => (float) $savingsBudget,
            'daily_allowance' => $remaining / $daysRemaining,
            'days_remaining' => $daysRemaining,
        ];
    }

    /**
     * Generate budget recommendations based on last 3 months of spending.
     */
    public function getRecommendations(?string $targetMonth = null)
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3)->startOfMonth();
        $targetMonth = $targetMonth ?? Carbon::now()->format('Y-m');

        // Get average spending per category in the last 3 months
        $avgSpending = Transaction::where('user_id', Auth::id())
            ->where('is_active', true)
            ->where('type', 'expense')
            ->where('date', '>=', $threeMonthsAgo)
            ->where('date', '<', Carbon::now()->startOfMonth())
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount_in_base_currency) / 3 as avg_total'))
            ->groupBy('category_id')
            ->having('avg_total', '>', 0)
            ->get();

        // Get target month's budgets to avoid suggesting categories that already have a budget
        $currentBudgets = $this->getMonthlyBudgets($targetMonth)->pluck('category_id')->toArray();

        return $avgSpending
            ->filter(fn($item) => !in_array($item->category_id, $currentBudgets) && $item->category)
            ->map(function ($item) {
                // Recommend a budget 10% lower than average to encourage saving
                $recommendedLimit = round($item->avg_total * 0.9, -3); // Round to nearest 1000
    
                return [
                    'category_id' => $item->category_id,
                    'category' => $item->category->name, // Return name for display
                    'avg_spending' => (float) $item->avg_total,
                    'recommended_limit' => (float) $recommendedLimit,
                    'reason' => __('based_on_avg_spending', ['amount' => number_format($item->avg_total)])
                ];
            })
            ->values();
    }

    /**
     * Get reasoning and allocations for auto-setup based on user profile.
     */
    /**
     * Get reasoning and allocations for auto-setup based on user profile and DB categories.
     */
    public function getAutoSetupPlan(float $income, string $goal, string $lifestyle)
    {
        // 1. Calculate Ideal Allocations
        $allocations = $this->calculateAllocations($goal, $lifestyle);

        // 2. Fetch User Categories (Active & Expense Only)
        $userCategories = \App\Models\Category::forUser(Auth::id())
            ->where('type', 'expense')
            ->get();

        // 3. Fallback if no categories exist (only if really empty)
        if ($userCategories->isEmpty()) {
            return $this->generateStandardPlan($allocations, $income, $goal, $lifestyle);
        }

        // 4. Map DB Categories to Allocation Types
        $mapping = $this->mapCategoriesToTypes($userCategories);

        // Define colors for auto-creation
        $colors = [
            'Saving' => 'bg-emerald-500',
            'Investment' => 'bg-indigo-500',
            'Food & Drink' => 'bg-orange-500',
            'Transport' => 'bg-blue-500',
            'Utilities' => 'bg-yellow-500',
            'Rent' => 'bg-rose-500',
            'Shopping' => 'bg-pink-500',
            'Entertainment' => 'bg-purple-500',
            'Groceries' => 'bg-teal-500'
        ];

        // 5. Generate Plan
        $plan = [];
        $totalAllocated = 0;

        foreach ($allocations as $type => $percentage) {
            // If mapping is missing, create the category
            if (!isset($mapping[$type])) {
                $newCat = \App\Models\Category::create([
                    'user_id' => Auth::id(),
                    'name' => $type,
                    'type' => 'expense',
                    'color' => $colors[$type] ?? 'bg-gray-500',
                    'is_active' => true,
                    'is_system' => false // Created by user flow
                ]);
                $mapping[$type] = [$newCat];
            }

            // Distribute evenly among matched categories (Auto-created one is single, so count=1)
            $count = count($mapping[$type]);
            $perCatPerc = $percentage / $count;

            foreach ($mapping[$type] as $cat) {
                $amount = round($income * $perCatPerc, -3); // Round to nearest 1000
                $totalAllocated += $amount;

                $plan[] = [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'limit' => $amount,
                    'percentage' => round($perCatPerc * 100, 1),
                    'reason' => $this->getReasoning($type, $goal, $lifestyle),
                    'type' => $type // For identifying Saving bucket later (internal use)
                ];
            }
        }

        // 6. Rounding Rebalancer
        $diff = $income - $totalAllocated;

        if ($diff != 0 && !empty($plan)) {
            // Find 'Saving' bucket or default to first bucket
            $targetIndex = 0;
            foreach ($plan as $index => $p) {
                if ($p['type'] === 'Saving') {
                    $targetIndex = $index;
                    break;
                }
            }

            // Adjust the target bucket to absorb the diff
            $plan[$targetIndex]['limit'] += $diff;
        }

        // Remove temp 'type' key from output
        foreach ($plan as &$p) {
            unset($p['type']);
        }

        return $plan;
    }

    private function calculateAllocations(string $goal, string $lifestyle): array
    {
        $base = [
            'Saving' => 0.10,
            'Investment' => 0.10,
            'Food & Drink' => 0.20,
            'Transport' => 0.1,
            'Utilities' => 0.1,
            'Rent' => 0.25,
            'Shopping' => 0.05,
            'Entertainment' => 0.05,
            'Groceries' => 0.05,
        ];

        // Adjust based on Goal
        if ($goal === 'aggressive_saving') {
            $base['Saving'] += 0.05;
            $base['Investment'] += 0.10;
            $base['Shopping'] -= 0.05;
            $base['Entertainment'] -= 0.05;
            $base['Food & Drink'] -= 0.05;
        }

        // Adjust based on Lifestyle
        if ($lifestyle === 'commuter') {
            $base['Transport'] += 0.05;
            $base['Rent'] -= 0.05;
        } elseif ($lifestyle === 'homebody') {
            $base['Transport'] -= 0.05;
            $base['Rent'] += 0.05;
        }

        return $base;
    }

    private function mapCategoriesToTypes($categories): array
    {
        $mapping = [];
        // Define buckets and their keywords
        $keywords = [
            'Food & Drink' => ['food', 'makan', 'minum', 'snack', 'cafe', 'restoran', 'warung'],
            'Groceries' => ['groceries', 'belanja', 'sayur', 'dapur', 'supermarket', 'mart'],
            'Transport' => ['transport', 'bensin', 'fuel', 'parkir', 'gojek', 'grab', 'uber', 'kendaraan', 'mobil', 'motor', 'servis'],
            'Utilities' => ['utility', 'utilities', 'tagihan', 'listrik', 'air', 'internet', 'wifi', 'pulsa', 'pln', 'pam'],
            'Rent' => ['rent', 'sewa', 'kos', 'kontrakan', 'cicilan rumah', 'kpr', 'housing'],
            'Shopping' => ['shop', 'belanja', 'mall', 'fashion', 'baju', 'sepatu', 'aksesoris'],
            'Entertainment' => ['entertain', 'hiburan', 'nonton', 'game', 'spotify', 'netflix', 'wisata', 'hobi'],
            'Saving' => ['saving', 'save', 'simpan', 'tabung', 'dana darurat', 'emergency'], // Added 'saving' explicitly
            'Investment' => ['invest', 'saham', 'reksa', 'crypto', 'gold', 'emas', 'deposito'],
        ];

        foreach ($categories as $cat) {
            $nameClean = trim($cat->name);
            $nameLower = strtolower($nameClean);
            $found = false;

            // 1. Exact Match Priority (Case Insensitive)
            foreach (array_keys($keywords) as $type) {
                if ($nameLower === strtolower($type)) {
                    $mapping[$type][] = $cat;
                    $found = true;
                    // echo "Exact Match: '$nameClean' -> '$type'\n";
                    break;
                }
            }

            if ($found)
                continue;

            // 2. Keyword Match
            foreach ($keywords as $type => $words) {
                foreach ($words as $word) {
                    if (str_contains($nameLower, strtolower($word))) {
                        $mapping[$type][] = $cat;
                        $found = true;
                        // echo "Keyword Match: '$nameClean' -> '$type' (keyword: '$word')\n";
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    private function generateStandardPlan($allocations, $income, $goal, $lifestyle)
    {
        $plan = [];
        $colors = [
            'Saving' => 'bg-emerald-500',
            'Investment' => 'bg-indigo-500',
            'Food & Drink' => 'bg-orange-500',
            'Transport' => 'bg-blue-500',
            'Utilities' => 'bg-yellow-500',
            'Rent' => 'bg-rose-500',
            'Shopping' => 'bg-pink-500',
            'Entertainment' => 'bg-purple-500',
            'Groceries' => 'bg-teal-500'
        ];

        foreach ($allocations as $categoryName => $perc) {
            // Check if category exists
            $category = \App\Models\Category::where('user_id', Auth::id())
                ->where('name', $categoryName)
                ->first();

            // If exists but inactive, skip it (respecting "active only")
            if ($category && !$category->is_active) {
                continue;
            }

            // If doesn't exist, create it
            if (!$category) {
                $category = \App\Models\Category::create([
                    'user_id' => Auth::id(),
                    'name' => $categoryName,
                    'type' => 'expense',
                    'color' => $colors[$categoryName] ?? 'bg-gray-500',
                    'is_active' => true
                ]);
            }

            $amount = round($income * $perc, -3);
            $plan[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'limit' => $amount,
                'percentage' => $perc * 100,
                'reason' => $this->getReasoning($categoryName, $goal, $lifestyle)
            ];
        }
        return $plan;
    }

    private function getReasoning(string $category, string $goal, string $lifestyle): string
    {
        $reasons = [
            'Saving' => $goal === 'aggressive_saving' ? 'reason_saving_aggressive' : 'reason_saving_standard',
            'Investment' => $goal === 'aggressive_saving' ? 'reason_investment_aggressive' : 'reason_investment_standard',
            'Food & Drink' => 'reason_food',
            'Transport' => $lifestyle === 'commuter' ? 'reason_transport_commuter' : 'reason_transport_standard',
            'Utilities' => 'reason_utilities',
            'Rent' => $lifestyle === 'homebody' ? 'reason_rent_homebody' : 'reason_rent_standard',
            'Shopping' => $goal === 'aggressive_saving' ? 'reason_shopping_aggressive' : 'reason_shopping_standard',
            'Entertainment' => 'reason_entertainment',
            'Groceries' => 'reason_groceries',
        ];

        return $reasons[$category] ?? 'reason_general';
    }

    private function getBudgetStatus(float $percentage): string
    {
        if ($percentage >= 100)
            return 'danger';
        if ($percentage >= 80)
            return 'warning';
        return 'safe';
    }
}
