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
        $reportService = app(ReportService::class);

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Pass available months for dropdown
        // Pass available months (raw Y-m) so frontend can localize
        $availableMonths = Transaction::selectRaw('DATE_FORMAT(date, "%Y-%m") as month_value')
            ->distinct()
            ->orderBy('month_value', 'desc')
            ->pluck('month_value');

        $reports = $reportService->getDetailedWalletReports($startDate, $endDate);

        // Calculate Global Totals for Summary if needed (optional, or computed in frontend)
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
        ]);
    }

    public function exportPdf(Request $request)
    {
        $reportService = app(ReportService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Set App Locale based on user preference
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

        // Ensure view exists later
        $pdf = Pdf::loadView('exports.statement_pdf', $data);
        return $pdf->stream('Statement_' . $month . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $reportService = app(ReportService::class);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $reports = $reportService->getDetailedWalletReports($startDate, $endDate);

        return Excel::download(new StatementExport($reports), 'Statement_' . $month . '.xlsx');
    }
}
