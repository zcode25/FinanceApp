<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $budgetService = app(BudgetService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $user = $request->user();

        return Inertia::render('Budget/Index', [
            'deferred_budgets' => Inertia::defer(function () use ($budgetService, $month) {
                return $budgetService->getMonthlyBudgets($month);
            }),
            'deferred_recommendations' => Inertia::defer(function () use ($budgetService, $month, $user) {
                return ($user && $user->is_premium) ? $budgetService->getRecommendations($month) : [];
            }),
            'summary' => $budgetService->getSummary($month),
            'categories' => Category::forUser($user?->id)->get(),
            'filters' => [
                'month' => $month
            ],
            'is_premium' => $user?->is_premium ?? false,
            'auto_setup_usage' => $user?->auto_setup_usage ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'limit' => 'required|numeric|min:0',
            'month' => 'required|string',
        ], [
            'category_id.required' => __('category_required'),
            'category_id.exists' => __('category_invalid'),
            'limit.required' => __('limit_required'),
            'limit.numeric' => __('limit_numeric'),
            'limit.min' => __('limit_min'),
            'month.required' => __('month_required'),
        ]);

        Budget::updateOrCreate(
            [
                'category_id' => $validated['category_id'],
                'month' => $validated['month'],
                'user_id' => Auth::id()
            ],
            ['limit' => $validated['limit']]
        );

        return redirect()->back();
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'limit' => 'required|numeric|min:0',
            'month' => 'required|string',
        ], [
            'category_id.required' => __('category_required'),
            'category_id.exists' => __('category_invalid'),
            'limit.required' => __('limit_required'),
            'limit.numeric' => __('limit_numeric'),
            'limit.min' => __('limit_min'),
            'month.required' => __('month_required'),
        ]);

        $budget->update([
            'category_id' => $validated['category_id'],
            'limit' => $validated['limit'],
            'month' => $validated['month']
        ]);

        return redirect()->back();
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }
        $budget->delete();
        return redirect()->back();
    }

    public function autoSetup(Request $request)
    {
        $validated = $request->validate([
            'estimated_income' => 'required|numeric|min:0',
            'month' => 'required|string',
            'goal' => 'required|string',
            'lifestyle' => 'required|string',
        ], [
            'estimated_income.required' => __('estimated_income_required'),
            'estimated_income.numeric' => __('estimated_income_numeric'),
            'estimated_income.min' => __('estimated_income_min'),
            'month.required' => __('month_required'),
            'goal.required' => __('goal_required'),
            'lifestyle.required' => __('lifestyle_required'),
        ]);

        $user = Auth::user();

        // Gating logic: Must be premium OR have used 0 times (1x trial)
        if (!$user->is_premium && $user->auto_setup_usage >= 1) {
            return redirect()->back()->with('error', 'Trial limit reached. Please upgrade to Professional.');
        }

        $budgetService = app(BudgetService::class);
        $plan = $budgetService->getAutoSetupPlan(
            $validated['estimated_income'],
            $validated['goal'],
            $validated['lifestyle']
        );

        foreach ($plan as $item) {
            Budget::updateOrCreate(
                [
                    'category_id' => $item['category_id'],
                    'month' => $validated['month'],
                    'user_id' => Auth::id()
                ],
                [
                    'limit' => $item['limit'],
                    'reason' => $item['reason']
                ]
            );
        }

        // Increment usage count
        $user->increment('auto_setup_usage');

        return redirect()->back();
    }
}
