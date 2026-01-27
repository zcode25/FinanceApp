<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $budgetService = app(BudgetService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        return Inertia::render('Budget/Index', [
            'budgets' => $budgetService->getMonthlyBudgets($month), // Service needs update
            'summary' => $budgetService->getSummary($month), // Service needs update
            'recommendations' => $budgetService->getRecommendations(), // Service needs update
            'categories' => Category::forUser(auth()->id())->get(),
            'filters' => [
                'month' => $month
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id', // Validate foreign key
            'limit' => 'required|numeric|min:0',
            'month' => 'required|string', // YYYY-MM
        ]);

        Budget::updateOrCreate(
            [
                'category_id' => $validated['category_id'],
                'month' => $validated['month'],
                'user_id' => auth()->id()
            ],
            ['limit' => $validated['limit']]
        );

        return redirect()->back();
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'limit' => 'required|numeric|min:0',
            'month' => 'required|string',
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
        if ($budget->user_id !== auth()->id()) {
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
        ]);

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
                    'user_id' => auth()->id()
                ],
                [
                    'limit' => $item['limit'],
                    'reason' => $item['reason']
                ]
            );
        }

        return redirect()->back();
    }
}
