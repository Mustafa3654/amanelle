<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Stock moves in two steps, deliberately.
 *
 *   Order placed     reserved += qty     quantity unchanged
 *   Order delivered  reserved -= qty     quantity -= qty
 *   Order cancelled  reserved -= qty     quantity unchanged
 *
 * `quantity` is what is physically on the shelf, so it only changes when
 * goods physically leave. `available` (quantity - reserved) is what the shop
 * is allowed to sell, so a unit promised to one customer is invisible to the
 * next — one unit in stock and one open order means the next person sees it
 * as out of stock rather than being allowed to buy it too.
 */
class StockService
{
    /**
     * Reserve stock for an order.
     *
     * The whole order is one transaction: if the third line is short, the
     * first two are rolled back rather than leaving a half-reserved order.
     */
    public function reserveFor(Order $order): void
    {
        if ($order->stock_reserved_at) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                $this->reserve(
                    $item->product_variant_id,
                    $order->market,
                    $item->quantity,
                    $order,
                    $item->sku
                );
            }

            $order->forceFill([
                'stock_reserved_at' => now(),
                'reservation_expires_at' => now()->addHours(
                    (int) config('amanelle.reservation_hours', 48)
                ),
            ])->save();
        });
    }

    /**
     * Take one variant's units out of circulation.
     *
     * The guard is the WHERE clause, not a prior SELECT. Two checkouts landing
     * in the same millisecond both read "1 available"; only one of them can
     * satisfy `quantity - reserved >= qty` at write time, and the other gets
     * zero affected rows and an exception. Checking first and writing second
     * would let both through.
     */
    public function reserve(int $variantId, string $market, int $quantity, ?Order $order = null, string $label = ''): void
    {
        $affected = Inventory::query()
            ->where('product_variant_id', $variantId)
            ->where('market', $market)
            ->whereRaw('quantity - reserved >= ?', [$quantity])
            ->update(['reserved' => DB::raw("reserved + {$quantity}")]);

        if ($affected === 0) {
            throw new InsufficientStockException(
                $label !== ''
                    ? __('":item" is no longer available in that quantity.', ['item' => $label])
                    : __('That item is no longer available in that quantity.')
            );
        }

        $this->log($variantId, $market, 'reserve', 0, $quantity, $order);
    }

    /**
     * Order delivered: the units have physically left, so the shelf count
     * finally drops and the reservation is consumed.
     */
    public function fulfilFor(Order $order): void
    {
        if ($order->stock_fulfilled_at) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                Inventory::query()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('market', $order->market)
                    ->update([
                        'quantity' => $this->clampedDecrement('quantity', $item->quantity),
                        'reserved' => $this->clampedDecrement('reserved', $item->quantity),
                    ]);

                $this->log(
                    $item->product_variant_id,
                    $order->market,
                    'fulfil',
                    -$item->quantity,
                    -$item->quantity,
                    $order
                );
            }

            $order->forceFill([
                'stock_fulfilled_at' => now(),
                'reservation_expires_at' => null,
            ])->save();
        });
    }

    /**
     * Cancelled or expired: hand the units back.
     *
     * Guarded on stock_fulfilled_at as well — releasing an order whose goods
     * already shipped would invent stock that does not exist.
     */
    public function releaseFor(Order $order): void
    {
        if (! $order->stock_reserved_at || $order->stock_released_at || $order->stock_fulfilled_at) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                Inventory::query()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('market', $order->market)
                    ->update(['reserved' => $this->clampedDecrement('reserved', $item->quantity)]);

                $this->log(
                    $item->product_variant_id,
                    $order->market,
                    'release',
                    0,
                    -$item->quantity,
                    $order
                );
            }

            $order->forceFill([
                'stock_released_at' => now(),
                'reservation_expires_at' => null,
            ])->save();
        });
    }

    /**
     * Decrement without ever going negative.
     *
     * CASE rather than GREATEST: GREATEST is MySQL-only, and the test suite
     * runs on SQLite. The floor is a belt-and-braces guard — the transitions
     * are already idempotent — but a negative stock count is the kind of bug
     * that quietly corrupts every report downstream.
     */
    private function clampedDecrement(string $column, int $amount): \Illuminate\Contracts\Database\Query\Expression
    {
        return DB::raw("CASE WHEN {$column} - {$amount} < 0 THEN 0 ELSE {$column} - {$amount} END");
    }

    private function log(int $variantId, string $market, string $type, int $qtyDelta, int $resDelta, ?Order $order): void
    {
        StockMovement::create([
            'product_variant_id' => $variantId,
            'market' => $market,
            'type' => $type,
            'quantity_delta' => $qtyDelta,
            'reserved_delta' => $resDelta,
            'order_id' => $order?->id,
            'user_id' => auth()->id(),
        ]);
    }
}
