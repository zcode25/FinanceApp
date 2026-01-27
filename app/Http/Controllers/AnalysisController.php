<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Services\AnalysisService;
use App\Models\Transaction;
use Carbon\Carbon;

class AnalysisController extends Controller
{
    public function index(Request $request)
    {
        $analysisService = app(AnalysisService::class);

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Pass available months for dropdown (same logic as Dashboard)
        $availableMonths = Transaction::selectRaw('DATE_FORMAT(date, "%Y-%m") as month_value, DATE_FORMAT(date, "%M %Y") as month_label')
            ->distinct()
            ->orderBy('month_value', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->month_value,
                    'label' => $item->month_label,
                ];
            });

        $summary = $analysisService->getSummary($startDate, $endDate);
        $categorySpending = $analysisService->getSpendingByCategory($startDate, $endDate);
        $cashFlowTrend = $analysisService->getCashFlowTrend($startDate, $endDate);
        $walletAllocation = $analysisService->getWalletAllocation();
        try {
            $smartInsights = $analysisService->getSmartInsights($startDate, $endDate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Smart Insights Error: ' . $e->getMessage());
            $smartInsights = [];
        }

        try {
            $financialTips = $analysisService->getFinancialTips($startDate, $endDate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Financial Tips Error: ' . $e->getMessage());
            $financialTips = [];
        }

        return Inertia::render('Analysis/Index', [
            'summary' => $summary,
            'categorySpending' => $categorySpending,
            'cashFlowTrend' => $cashFlowTrend,
            'walletAllocation' => $walletAllocation,
            'smartInsights' => $smartInsights,
            'financialTips' => $financialTips,
            'filters' => [
                'month' => $month,
            ],
            'availableMonths' => $availableMonths
        ]);
    }
}
