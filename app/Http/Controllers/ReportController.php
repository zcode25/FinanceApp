<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StatementExport; // Will create this later

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPremium = $user?->is_premium ?? false;
        $reportService = app(ReportService::class);

        // Find min/max transaction dates once
        $stats = Transaction::where('user_id', $user?->id)
            ->where('is_active', true)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();

        // Determine available months first
        if (!$stats || !$stats->min_date) {
            $now = Carbon::now();
            $availableMonths = collect([$now->format('Y-m')]);
            $defaultMonth = $now->format('Y-m');
        } else {
            $startDateRange = Carbon::parse($stats->min_date)->startOfMonth();
            $endDateRange = Carbon::parse($stats->max_date)->startOfMonth();
            $availableMonths = collect();
            $defaultMonth = $endDateRange->format('Y-m');

            while ($startDateRange->lte($endDateRange)) {
                $availableMonths->push($endDateRange->format('Y-m'));
                $endDateRange->subMonth();
            }
        }

        // Get selected month from request or use default
        $month = $request->input('month', $defaultMonth);
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

        return Inertia::render('Reports/Index', [
            'reports_data' => Inertia::defer(function () use ($reportService, $startDate, $endDate) {
                $reports = $reportService->getDetailedWalletReports($startDate, $endDate);
                return [
                    'reports' => $reports,
                    'totals' => [
                        'total_income' => collect($reports)->sum(fn($r) => $r['summary']['real_base_income']),
                        'total_expense' => collect($reports)->sum(fn($r) => $r['summary']['real_base_expense']),
                        'total_net' => collect($reports)->sum(fn($r) => $r['summary']['real_base_income'] - $r['summary']['real_base_expense']),
                    ],
                ];
            }),
            'filters' => ['month' => $month],
            'availableMonths' => $availableMonths,
            'is_premium' => $isPremium
        ]);
    }

    public function exportPdf(Request $request)
    {
        if (!Auth::user()->is_premium) {
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
        if (!Auth::user()->is_premium) {
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
