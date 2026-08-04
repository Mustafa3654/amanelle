<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Revenue counts delivered orders only. Counting pending ones would
        // flatter the number with orders that may still be cancelled.
        $delivered = Order::where('status', 'delivered');

        $thisMonth = (clone $delivered)->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->sum('total');

        $lastMonth = (clone $delivered)->whereMonth('delivered_at', now()->subMonth()->month)
            ->whereYear('delivered_at', now()->subMonth()->year)
            ->sum('total');

        $change = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100)
            : null;

        $awaiting = Order::open()->count();

        return [
            Stat::make('Revenue this month', '$'.number_format((float) $thisMonth, 2))
                ->description($change === null
                    ? 'No delivered orders last month to compare'
                    : ($change >= 0 ? "{$change}% up on last month" : abs($change).'% down on last month'))
                ->descriptionIcon($change !== null && $change >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($change === null ? 'gray' : ($change >= 0 ? 'success' : 'danger')),

            Stat::make('Orders to fulfil', $awaiting)
                ->description('Pending, processing or shipped')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaiting > 0 ? 'warning' : 'success'),

            Stat::make('Delivered all time', Order::where('status', 'delivered')->count())
                ->description('$'.number_format((float) Order::where('status', 'delivered')->sum('total'), 2).' total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
