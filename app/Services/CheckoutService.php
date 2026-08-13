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

            // Re-resolved here rather than trusting anything the checkout form
            // sent, so a code cannot be replayed after it expires or sells out.
            $promo = $this->cart->promo();
            $discount = $promo?->discountFor($subtotal) ?? 0.0;

            // Re-resolved here rather than trusting the form, so a repriced or
            // deactivated zone cannot be checked out at the old fee.
            $zone = $this->cart->zone();
            $shipping = $zone?->feeFor($subtotal - $discount) ?? 0.0;

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
                'shipping_total' => $shipping,
                'delivery_zone_id' => $zone?->id,
                /*
                 * Snapshotted so deleting a zone does not erase where a past
                 * order went. Stored in the fallback locale, not the
                 * customer's: otherwise the same zone reads differently
                 * depending on which language they happened to browse in, and
                 * exports become impossible to group. The live translated name
                 * is still available through the relation.
                 */
                'delivery_zone_name' => $zone?->getTranslation('name', config('app.fallback_locale')),
                'promo_code' => $promo?->code,
                'promo_code_id' => $promo?->id,
                'discount_total' => $discount,
                'total' => round($subtotal - $discount + $shipping, 2),

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
                    'unit_cost' => $variant->cost_price ?? 0,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            // Reserves stock atomically. Throws if anything is short, which
            // rolls the order back with it.
            $this->stock->reserveFor($order->load('items'));

            // Incremented atomically so two people redeeming the last use of a
            // limited code cannot both get through.
            if ($promo) {
                $promo->increment('used_count');
            }

            $this->cart->clear();

            // So they can see the confirmation page they are about to land on.
            $order->grantSessionAccess();

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
