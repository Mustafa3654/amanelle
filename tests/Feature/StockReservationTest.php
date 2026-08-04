<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    private function variantWithStock(int $quantity): ProductVariant
    {
        $product = Product::create([
            'type' => 'fragrance',
            'name' => ['en' => 'Test Scent'],
            'slug' => 'test-scent-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-'.uniqid(),
            'volume_ml' => 100,
            'price' => 50,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'market' => 'LB',
            'quantity' => $quantity,
            'reserved' => 0,
        ]);

        return $variant;
    }

    private function orderFor(ProductVariant $variant, int $quantity = 1): Order
    {
        $order = Order::create([
            'number' => Order::nextNumber().'-'.uniqid(),
            'customer_name' => 'Test',
            'customer_phone' => '000',
            'shipping_address' => 'Beirut',
            'market' => 'LB',
            'status' => 'pending',
            'subtotal' => 50,
            'total' => 50,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => 'Test Scent',
            'unit_price' => 50,
            'quantity' => $quantity,
            'line_total' => 50 * $quantity,
        ]);

        return $order->load('items');
    }

    public function test_placing_an_order_reserves_stock_without_touching_the_shelf_count(): void
    {
        $variant = $this->variantWithStock(1);

        app(StockService::class)->reserveFor($this->orderFor($variant));

        $inventory = $variant->inventories()->first();

        // The unit has not left the shelf yet.
        $this->assertSame(1, $inventory->quantity);
        $this->assertSame(1, $inventory->reserved);
        $this->assertSame(0, $inventory->available());
    }

    public function test_a_second_customer_cannot_order_the_last_reserved_unit(): void
    {
        $variant = $this->variantWithStock(1);
        $stock = app(StockService::class);

        $stock->reserveFor($this->orderFor($variant));

        $this->expectException(InsufficientStockException::class);

        $stock->reserveFor($this->orderFor($variant));
    }

    public function test_marking_delivered_deducts_from_the_shelf_count(): void
    {
        $variant = $this->variantWithStock(1);
        $order = $this->orderFor($variant);

        app(StockService::class)->reserveFor($order);

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        $inventory = $variant->inventories()->first();

        $this->assertSame(0, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved);
    }

    public function test_marking_delivered_twice_deducts_only_once(): void
    {
        $variant = $this->variantWithStock(5);
        $order = $this->orderFor($variant, 2);

        app(StockService::class)->reserveFor($order);

        $order->update(['status' => 'delivered']);
        $order->update(['status' => 'processing']);
        $order->update(['status' => 'delivered']);

        $inventory = $variant->inventories()->first();

        $this->assertSame(3, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved);
    }

    public function test_cancelling_returns_the_unit_to_sale(): void
    {
        $variant = $this->variantWithStock(1);
        $order = $this->orderFor($variant);

        app(StockService::class)->reserveFor($order);
        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $inventory = $variant->inventories()->first();

        $this->assertSame(1, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved);
        $this->assertSame(1, $inventory->available());
    }

    public function test_cancelling_after_delivery_cannot_invent_stock(): void
    {
        $variant = $this->variantWithStock(1);
        $order = $this->orderFor($variant);

        app(StockService::class)->reserveFor($order);
        $order->update(['status' => 'delivered']);
        $order->update(['status' => 'cancelled']);

        $inventory = $variant->inventories()->first();

        // The goods physically left; releasing must not hand them back.
        $this->assertSame(0, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved);
    }

    public function test_expired_reservations_are_released(): void
    {
        $variant = $this->variantWithStock(1);
        $order = $this->orderFor($variant);

        app(StockService::class)->reserveFor($order);
        $order->forceFill(['reservation_expires_at' => now()->subHour()])->save();

        $this->artisan('stock:release-expired')->assertSuccessful();

        $this->assertSame(1, $variant->inventories()->first()->available());
    }
}
