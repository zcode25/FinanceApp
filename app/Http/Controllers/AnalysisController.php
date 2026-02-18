<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Services\AnalysisService;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month');
        $availableMonths = $this->getAvailableMonths($user, $month);

        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $analysisService = app(AnalysisService::class);

        return Inertia::render('Analysis/Index', [
            'summary' => $analysisService->getSummary($startDate, $endDate),
            'walletAllocation' => Inertia::defer(fn () => $analysisService->getWalletAllocation()),
            'categorySpending' => Inertia::defer(fn () => $analysisService->getSpendingByCategory($startDate, $endDate)),
            'cashFlowTrend' => Inertia::defer(fn () => $analysisService->getCashFlowTrend($startDate, $endDate)),
            'smartInsights' => Inertia::defer(fn () => $this->getSmartInsights($analysisService, $startDate, $endDate, $user)),
            'financialTips' => Inertia::defer(fn () => $this->getFinancialTips($analysisService, $startDate, $endDate, $user)),
            'filters' => [
                'month' => $month,
            ],
            'availableMonths' => $availableMonths,
            'selectedMonth' => $month,
            'is_premium' => $user?->is_premium ?? false
        ]);
    }

    private function getAvailableMonths($user, &$selectedMonth = null)
    {
        $stats = Transaction::where('user_id', $user?->id)
            ->where('is_active', true)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();

        if (!$stats || !$stats->min_date) {
            $now = Carbon::now();
            $selectedMonth = $selectedMonth ?: $now->format('Y-m');
            return collect([
                [
                    'value' => $now->format('Y-m'),
                    'label' => $now->translatedFormat('F Y'),
                ]
            ]);
        }

        // Set default selected month to latest transaction if not provided
        if (!$selectedMonth) {
            $selectedMonth = Carbon::parse($stats->max_date)->format('Y-m');
        }

        $startDate = Carbon::parse($stats->min_date)->startOfMonth();
        $endDate = Carbon::parse($stats->max_date)->startOfMonth();
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

    private function getSmartInsights($service, $startDate, $endDate, $user)
    {
        if (!$user || !$user->is_premium) {
            return [];
        }

        try {
            return $service->getSmartInsights($startDate, $endDate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Smart Insights Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getFinancialTips($service, $startDate, $endDate, $user)
    {
        if (!$user || !$user->is_premium) {
            return [];
        }

        try {
            return $service->getFinancialTips($startDate, $endDate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Financial Tips Error: ' . $e->getMessage());
            return [];
        }
    }
}
