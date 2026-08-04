<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\StockService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'stock:release-expired';

    protected $description = 'Return stock held by pending orders that have passed their reservation window';

    public function handle(StockService $stock): int
    {
        /*
         * Without this, one abandoned checkout takes a unit off sale forever.
         * The order is left pending rather than cancelled — a customer may
         * still pay — but the goods go back on the shelf, because an unpaid
         * order should not outrank a real one.
         */
        $orders = Order::expiredReservations()->with('items')->get();

        foreach ($orders as $order) {
            $stock->releaseFor($order);
            $this->line("Released {$order->number}");
        }

        $this->info("{$orders->count()} reservation(s) released.");

        return self::SUCCESS;
    }
}
