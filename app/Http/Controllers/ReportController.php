<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StatementExport; // Will create this later

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isPremium = $user->is_premium;
        $reportService = app(ReportService::class);

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Enforce 3-month lookback limit for Starter users
        if (!$isPremium) {
            $threeMonthsAgo = Carbon::now()->subMonths(3)->startOfMonth();
            if ($startDate->lt($threeMonthsAgo)) {
                $month = Carbon::now()->format('Y-m');
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            }
        }

        // Pass available months for dropdown (filtered by current user)
        $availableMonths = Transaction::where('user_id', $user->id)
            ->where('is_active', true)
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month_value')
            ->distinct()
            ->orderBy('month_value', 'desc')
            ->pluck('month_value');

        // Fallback to current month if no transactions
        if ($availableMonths->isEmpty()) {
            $availableMonths = collect([Carbon::now()->format('Y-m')]);
        }

        $reports = $reportService->getDetailedWalletReports($startDate, $endDate);

        $totals = [
            'total_income' => collect($reports)->sum(fn($r) => $r['summary']['income']),
            'total_expense' => collect($reports)->sum(fn($r) => $r['summary']['expense']),
            'total_net' => collect($reports)->sum(fn($r) => $r['summary']['net_flow']),
        ];

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'totals' => $totals,
            'filters' => ['month' => $month],
            'availableMonths' => $availableMonths,
            'is_premium' => $isPremium
        ]);
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->is_premium) {
            return response()->json(['error' => 'Upgrade to Professional to export reports.'], 403);
        }

        $reportService = app(ReportService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        $locale = $request->user()->locale ?? 'en';
        app()->setLocale($locale);

        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $reports = $reportService->getDetailedWalletReports($startDate, $endDate);

        $data = [
            'period' => $startDate->locale($locale)->isoFormat('MMMM Y'),
            'reports' => $reports,
            'generated_at' => Carbon::now()->locale($locale)->isoFormat('D MMM Y HH:mm'),
        ];

        $pdf = Pdf::loadView('exports.statement_pdf', $data);
        return $pdf->stream('Statement_' . $month . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->is_premium) {
            return response()->json(['error' => 'Upgrade to Professional to export reports.'], 403);
        }

        $reportService = app(ReportService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $reports = $reportService->getDetailedWalletReports($startDate, $endDate);

        return Excel::download(new StatementExport($reports), 'Statement_' . $month . '.xlsx');
    }
}
