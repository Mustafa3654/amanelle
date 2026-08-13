<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): ?string
    {
        return __('Orders over time');
    }

    public function getDescription(): ?string
    {
        return __('Orders placed, and revenue from the ones delivered.');
    }

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => __('Last 7 days'),
            '30' => __('Last 30 days'),
            '90' => __('Last 90 days'),
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $start = now()->subDays($days - 1)->startOfDay();

        /*
         * Built from a date range rather than grouping the rows, so days with
         * no orders still appear as zero. Grouping alone silently closes the
         * gaps and makes a quiet week look like a busy one.
         */
        $placed = Order::query()
            ->where('placed_at', '>=', $start)
            ->get(['placed_at', 'total', 'status'])
            ->groupBy(fn (Order $o) => $o->placed_at?->format('Y-m-d'));

        $labels = [];
        $counts = [];
        $revenue = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $rows = $placed->get($key, collect());

            $labels[] = $day->format($days > 30 ? 'j M' : 'D j');
            $counts[] = $rows->count();
            $revenue[] = round((float) $rows->where('status', 'delivered')->sum('total'), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => __('Orders'),
                    'data' => $counts,
                    'borderColor' => '#c9a96e',
                    'backgroundColor' => 'rgba(201, 169, 110, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => __('Delivered revenue (USD)'),
                    'data' => $revenue,
                    'borderColor' => '#8c6a3a',
                    'backgroundColor' => 'rgba(140, 106, 58, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        // Counts and currency share an x-axis but not a scale — one axis would
        // flatten the order line into the floor next to dollar amounts.
        return [
            'scales' => [
                'y' => [
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => __('Orders')],
                ],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'USD'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
