<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index(Request $request)
    {
        $month = $request->query('month');
        $basic = $this->dashboardService->getBasicData($month);
        
        return Inertia::render('Dashboard', [
            'summary' => $basic['summary'] ?? [],
            'available_months' => $basic['available_months'] ?? [],
            'subscription' => $basic['subscription'] ?? [],
            'deferred_charts' => Inertia::defer(fn () => $this->dashboardService->getChartsData($month)),
            'deferred_breakdown' => Inertia::defer(fn () => $this->dashboardService->getBreakdownData($month)),
            'deferred_transactions' => Inertia::defer(fn () => $this->dashboardService->getRecentTransactions($month)),
        ]);
    }
}
