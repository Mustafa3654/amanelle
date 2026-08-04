<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 5, float $price = 50): ProductVariant
    {
        Currency::firstOrCreate(['code' => 'USD'], [
            'name' => ['en' => 'US Dollar'],
            'symbol' => '$',
            'rate' => 1,
            'decimals' => 2,
            'is_base' => true,
            'is_active' => true,
        ]);

        $product = Product::create([
            'type' => 'fragrance',
            'name' => ['en' => 'Test Scent'],
            'slug' => 'scent-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.uniqid(),
            'volume_ml' => 100,
            'price' => $price,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'market' => 'LB',
            'quantity' => $stock,
            'reserved' => 0,
        ]);

        return $variant;
    }

    private function details(): array
    {
        return [
            'customer_name' => 'Layla',
            'customer_phone' => '+961 70 000 000',
            'customer_email' => null,
            'shipping_address' => 'Hamra Street, Building 4',
            'city' => 'Beirut',
            'notes' => null,
        ];
    }

    public function test_cart_refuses_more_than_is_available(): void
    {
        $variant = $this->variant(stock: 2);
        $cart = app(CartService::class);

        $this->assertTrue($cart->add($variant->id, 2)['ok']);

        $result = $cart->add($variant->id, 1);

        $this->assertFalse($result['ok']);
        $this->assertSame(2, $cart->count());
    }

    public function test_checkout_creates_an_order_and_reserves_stock(): void
    {
        Notification::fake();

        $variant = $this->variant(stock: 5, price: 40);
        app(CartService::class)->add($variant->id, 2);

        $order = app(CheckoutService::class)->place($this->details());

        $this->assertSame('pending', $order->status);
        $this->assertSame('80.00', $order->total);
        $this->assertCount(1, $order->items);

        $inventory = $variant->inventories()->first();

        // Reserved, not deducted — the shelf count is untouched until delivery.
        $this->assertSame(5, $inventory->quantity);
        $this->assertSame(2, $inventory->reserved);
        $this->assertSame(3, $inventory->available());

        $this->assertNotNull($order->reservation_expires_at);
    }

    public function test_checkout_empties_the_cart(): void
    {
        Notification::fake();

        $variant = $this->variant();
        app(CartService::class)->add($variant->id);

        app(CheckoutService::class)->place($this->details());

        $this->assertTrue(app(CartService::class)->isEmpty());
    }

    public function test_staff_are_notified_of_a_new_order(): void
    {
        Notification::fake();

        $staff = User::factory()->create();
        $variant = $this->variant();

        app(CartService::class)->add($variant->id);
        app(CheckoutService::class)->place($this->details());

        Notification::assertSentTo($staff, OrderPlaced::class);
    }

    public function test_the_order_snapshots_the_currency_and_rate_shown(): void
    {
        Notification::fake();

        Currency::create([
            'code' => 'LBP',
            'name' => ['en' => 'Lebanese Pound'],
            'symbol' => 'ل.ل',
            'rate' => 89500,
            'decimals' => 0,
            'is_base' => false,
            'is_active' => true,
        ]);

        session()->put(\App\Support\Money::SESSION_KEY, 'LBP');
        \App\Support\Money::flush();

        $variant = $this->variant(price: 40);
        app(CartService::class)->add($variant->id);

        $order = app(CheckoutService::class)->place($this->details());

        // Totals stay in the base currency; what the customer saw is recorded
        // so a later rate edit cannot re-price a placed order.
        $this->assertSame('40.00', $order->total);
        $this->assertSame('LBP', $order->display_currency);
        $this->assertSame('89500.000000', $order->display_rate);
    }

    public function test_a_sold_out_line_rolls_the_whole_order_back(): void
    {
        Notification::fake();

        $variant = $this->variant(stock: 1);
        app(CartService::class)->add($variant->id);

        // Someone else takes the last unit after it went into the cart.
        $variant->inventories()->first()->update(['reserved' => 1]);

        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        try {
            app(CheckoutService::class)->place($this->details());
        } finally {
            // No half-order left behind for staff to chase.
            $this->assertSame(0, Order::count());
        }
    }
}
