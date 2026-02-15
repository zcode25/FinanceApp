<?php

namespace App\Filament\Widgets;

use App\Models\SubscriptionTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Revenue (Successful)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = SubscriptionTransaction::where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        // Fill gaps for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');

            $match = $data->firstWhere('date', $date);
            $values[] = $match ? (int) $match->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $values,
                    'fill' => 'start',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
