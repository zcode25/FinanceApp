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

        return Inertia::render('Analysis/Index', [
            'summary' => $analysisService->getSummary($startDate, $endDate),
            'categorySpending' => $analysisService->getSpendingByCategory($startDate, $endDate),
            'cashFlowTrend' => $analysisService->getCashFlowTrend($startDate, $endDate),
            'walletAllocation' => $analysisService->getWalletAllocation(),
            'smartInsights' => $this->getSmartInsights($analysisService, $startDate, $endDate),
            'financialTips' => $this->getFinancialTips($analysisService, $startDate, $endDate),
            'filters' => [
                'month' => $month,
            ],
            'availableMonths' => $this->getAvailableMonths(),
            'is_premium' => auth()->user()->is_premium
        ]);
    }

    private function getAvailableMonths()
    {
        $availableMonths = Transaction::where('user_id', auth()->id())
            ->where('is_active', true)
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month_value')
            ->distinct()
            ->orderBy('month_value', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->month_value,
                    'label' => Carbon::createFromFormat('Y-m', $item->month_value)->translatedFormat('F Y'),
                ];
            });

        if ($availableMonths->isEmpty()) {
            $now = Carbon::now();
            return collect([
                [
                    'value' => $now->format('Y-m'),
                    'label' => $now->translatedFormat('F Y'),
                ]
            ]);
        }

        return $availableMonths;
    }

    private function getSmartInsights($service, $startDate, $endDate)
    {
        if (!auth()->user()->is_premium) {
            return [];
        }

        try {
            return $service->getSmartInsights($startDate, $endDate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Smart Insights Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getFinancialTips($service, $startDate, $endDate)
    {
        if (!auth()->user()->is_premium) {
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
