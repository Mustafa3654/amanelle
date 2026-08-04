<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BestSellersChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Best sellers';

    protected ?string $description = 'Units sold on delivered orders. Cancelled and pending orders are excluded.';

    public ?string $filter = 'units';

    protected function getFilters(): ?array
    {
        return [
            'units' => 'By units sold',
            'revenue' => 'By revenue',
        ];
    }

    protected function getData(): array
    {
        $byRevenue = $this->filter === 'revenue';

        /*
         * Grouped on the snapshotted product_name rather than joining back to
         * products: a discontinued or renamed product should still show what
         * it actually sold under the name it sold as.
         *
         * Only delivered orders count — pending ones may still be cancelled,
         * and counting them would overstate every line.
         */
        $rows = OrderItem::query()
            ->select('product_name')
            ->selectRaw('SUM(quantity) as units')
            ->selectRaw('SUM(line_total) as revenue')
            ->whereHas('order', fn ($q) => $q->where('status', 'delivered'))
            ->groupBy('product_name')
            ->orderByDesc(DB::raw($byRevenue ? 'SUM(line_total)' : 'SUM(quantity)'))
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => $byRevenue ? 'Revenue (USD)' : 'Units sold',
                    'data' => $rows->map(fn ($r) => $byRevenue ? round((float) $r->revenue, 2) : (int) $r->units)->all(),
                    'backgroundColor' => [
                        '#c9a96e', '#dcae96', '#e8d5a3', '#b98b6d',
                        '#8c6a3a', '#f2c6ce', '#c99873', '#96613f',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $rows->pluck('product_name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Horizontal: product names are long, and rotated labels under a
            // vertical bar chart are unreadable at this width.
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
