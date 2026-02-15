<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EngagementChart extends ChartWidget
{
    protected static ?string $heading = 'User Engagement (Financial Records)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Transaction::where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');

            $match = $data->firstWhere('date', $date);
            $values[] = $match ? (int) $match->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Records Created',
                    'data' => $values,
                    'borderColor' => 'rgb(245, 158, 11)', // Amber/Orange
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
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
