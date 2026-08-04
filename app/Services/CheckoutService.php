<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderPlaced;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckoutService
{
    public function __construct(
        private CartService $cart,
        private StockService $stock,
    ) {}

    /**
     * Turn the cart into a reserved order.
     *
     * The order rows and the reservation share one transaction. If the last
     * unit of anything went to someone else between viewing the cart and
     * pressing the button, reserveFor throws, the whole thing rolls back, and
     * no half-order is left behind for someone to chase.
     *
     * @param  array{customer_name: string, customer_email: ?string, customer_phone: string, shipping_address: string, city: ?string, notes: ?string}  $details
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function place(array $details): Order
    {
        $lines = $this->cart->lines();

        abort_if($lines->isEmpty(), 400);

        $displayCurrency = Money::current();

        return DB::transaction(function () use ($lines, $details, $displayCurrency) {
            $subtotal = (float) $lines->sum('line_total');

            $order = Order::create([
                'number' => Order::nextNumber(),
                'customer_name' => $details['customer_name'],
                'customer_email' => $details['customer_email'] ?? null,
                'customer_phone' => $details['customer_phone'],
                'shipping_address' => $details['shipping_address'],
                'city' => $details['city'] ?? null,
                'market' => config('amanelle.default_market'),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_total' => 0,
                'total' => $subtotal,

                // Snapshot what the customer was actually looking at. The LBP
                // rate moves, and an old order must not re-price itself when
                // someone edits it in the admin.
                'display_currency' => $displayCurrency?->code ?? 'USD',
                'display_rate' => $displayCurrency?->rate ?? 1,

                'notes' => $details['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $variant = $line['variant'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'product_name' => $variant->product?->name ?? $variant->sku,
                    'variant_label' => $variant->label(),
                    'unit_price' => $variant->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            // Reserves stock atomically. Throws if anything is short, which
            // rolls the order back with it.
            $this->stock->reserveFor($order->load('items'));

            $this->cart->clear();

            $this->notifyStaff($order);

            return $order;
        });
    }

    /**
     * Alert whoever runs the shop. Wrapped because a dead SMTP host or a
     * Telegram outage must never lose a real order — the sale is committed,
     * the alert is best-effort.
     */
    private function notifyStaff(Order $order): void
    {
        try {
            $staff = \App\Models\User::all();

            if ($staff->isNotEmpty()) {
                Notification::send($staff, new OrderPlaced($order));
            }
        } catch (\Throwable $e) {
            Log::error('Order placed but staff notification failed', [
                'order' => $order->number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
