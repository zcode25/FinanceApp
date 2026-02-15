<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Revenue from successful transactions
        $totalRevenue = \App\Models\SubscriptionTransaction::where('status', 'success')->sum('amount');

        // Base user statistics (excluding admins)
        $totalUsers = \App\Models\User::where('is_admin', false)->count();
        $freeUsers = \App\Models\User::where('is_admin', false)->where('is_premium', false)->count();
        $premiumUsers = \App\Models\User::where('is_admin', false)->where('is_premium', true)->count();

        // Plan distribution logic (Master=yearly, Pro=monthly, Lifetime=lifetime)
        // Note: Counting based on Plan duration_type for flexibility
        $proUsers = \App\Models\User::where('is_admin', false)
            ->where('is_premium', true)
            ->whereHas('latestTransaction.plan', fn($q) => $q->where('duration_type', 'month'))
            ->count();

        $masterUsers = \App\Models\User::where('is_admin', false)
            ->where('is_premium', true)
            ->whereHas('latestTransaction.plan', fn($q) => $q->where('duration_type', 'year'))
            ->count();

        $lifetimeUsers = \App\Models\User::where('is_admin', false)
            ->where('is_premium', true)
            ->whereHas('latestTransaction.plan', fn($q) => $q->where('duration_type', 'lifetime'))
            ->count();

        $activePromos = \App\Models\Promo::where('is_active', true)
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->count();

        return [
            Stat::make('Total Revenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total revenue from successful subscriptions')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Visual trend placeholder
                ->color('success'),

            Stat::make('Client Base', $totalUsers)
                ->description("{$premiumUsers} Premium / {$freeUsers} Free")
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Professional Users', $proUsers)
                ->description('Active Monthly Subscribers')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Master Users', $masterUsers)
                ->description('Active Yearly Subscribers')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),

            Stat::make('Lifetime Users', $lifetimeUsers)
                ->description('One-time Purchase Access')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('purple'),

            Stat::make('Active Promo Codes', $activePromos)
                ->description('Currently available for use')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),
        ];
    }
}
