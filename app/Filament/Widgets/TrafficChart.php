<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TrafficChart extends ChartWidget
{
    protected static ?string $heading = 'User Growth (New Clients)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = User::where('is_admin', false)
            ->where('created_at', '>=', now()->subDays(30))
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
                    'label' => 'New Users',
                    'data' => $values,
                    'borderColor' => 'rgb(168, 85, 247)', // Purple
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
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
