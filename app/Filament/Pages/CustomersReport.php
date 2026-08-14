<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomersReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected string $view = 'filament.pages.customers-report';

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('Customers');
    }

    public function getTitle(): string
    {
        return __('Customers Report');
    }

    public $from = null;
    public $until = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->until = now()->toDateString();
    }

    public function form(Schema $form): Schema
    {
        return $form->components([
            DatePicker::make('from')->label(__('From'))->live(),
            DatePicker::make('until')->label(__('Until'))->live(),
        ])->statePath('data');
    }

    public function getCustomers()
    {
        return Order::query()
            ->select('customer_name', 'customer_email', 'customer_phone', 'city', 'shipping_address', 'number', 'total', 'placed_at')
            ->whereNotNull('customer_phone')
            ->whereBetween('placed_at', [$this->from.' 00:00:00', $this->until.' 23:59:59'])
            ->latest('placed_at')
            ->get()
            ->groupBy(fn ($order) => strtolower($order->customer_email ?: $order->customer_phone))
            ->map(fn ($orders) => [
                'name' => $orders->first()->customer_name,
                'email' => $orders->first()->customer_email,
                'phone' => $orders->first()->customer_phone,
                'city' => $orders->first()->city,
                'address' => $orders->first()->shipping_address,
                'orders' => $orders->count(),
                'total' => $orders->sum('total'),
                'last_order' => $orders->first()->placed_at,
            ])->values();
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Email', 'Phone', 'City', 'Address', 'Orders', 'Total spent', 'Last order']);
            foreach ($this->getCustomers() as $customer) {
                fputcsv($out, [$customer['name'], $customer['email'], $customer['phone'], $customer['city'], $customer['address'], $customer['orders'], $customer['total'], $customer['last_order']?->format('Y-m-d H:i')]);
            }
            fclose($out);
        }, 'amanelle-customers-'.now()->format('Y-m-d').'.csv');
    }
}
