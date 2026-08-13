<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }
    public static function getNavigationLabel(): string
    {
        return __('Profit & Loss');
    }
    public function getTitle(): string
    {
        return __('Profit & Loss Report');
    }
    protected string $view = 'filament.pages.profit-loss-report';

    public ?string $from = null;
    public ?string $until = null;
    public string $currency = 'all';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->until = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('Export CSV'))
                ->action(fn () => $this->export()),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form->components([
            DatePicker::make('from')->label(__('From'))->live(),
            DatePicker::make('until')->label(__('Until'))->live(),
            Select::make('currency')->label(__('Currency'))->options(['all' => __('All currencies'), 'USD' => 'USD', 'LBP' => 'LBP'])->live(),
        ])->statePath('data');
    }

    public function getReport(): array
    {
        $items = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered')
            ->whereBetween('orders.delivered_at', [$this->from.' 00:00:00', $this->until.' 23:59:59'])
            ->when($this->currency !== 'all', fn ($query) => $query->where('orders.display_currency', $this->currency))
            ->select('order_items.product_name', 'order_items.variant_label', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.line_total) as revenue'), DB::raw('SUM(order_items.unit_cost * order_items.quantity) as cost'))
            ->groupBy('order_items.product_name', 'order_items.variant_label')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->map(function (array $row): array {
                $row['profit'] = (float) $row['revenue'] - (float) $row['cost'];
                return $row;
            })
            ->all();

        $revenue = collect($items)->sum('revenue');
        $cost = collect($items)->sum('cost');

        return ['items' => $items, 'revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost, 'margin' => $revenue > 0 ? (($revenue - $cost) / $revenue) * 100 : 0];
    }

    public function export(): StreamedResponse
    {
        $report = $this->getReport();
        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Product', 'Variant', 'Quantity', 'Revenue', 'Cost', 'Profit']);
            foreach ($report['items'] as $item) fputcsv($handle, [$item['product_name'], $item['variant_label'], $item['quantity'], $item['revenue'], $item['cost'], $item['profit']]);
            fputcsv($handle, []);
            fputcsv($handle, ['TOTAL', '', '', $report['revenue'], $report['cost'], $report['profit']]);
            fclose($handle);
        }, 'profit-loss-'.$this->from.'-to-'.$this->until.'.csv');
    }
}
