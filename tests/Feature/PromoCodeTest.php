<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private function stockedVariant(float $price = 100): ProductVariant
    {
        Currency::firstOrCreate(['code' => 'USD'], [
            'name' => ['en' => 'US Dollar'], 'symbol' => '$', 'rate' => 1,
            'decimals' => 2, 'is_base' => true, 'is_active' => true,
        ]);

        $product = Product::create([
            'type' => 'fragrance',
            'name' => ['en' => 'Test'],
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.uniqid(),
            'price' => $price,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'market' => 'LB', 'quantity' => 10, 'reserved' => 0,
        ]);

        return $variant;
    }

    private function details(): array
    {
        return [
            'customer_name' => 'Layla',
            'customer_phone' => '+961 70 000 000',
            'customer_email' => null,
            'shipping_address' => 'Hamra',
            'city' => 'Beirut',
            'notes' => null,
        ];
    }

    public function test_code_is_matched_regardless_of_case(): void
    {
        PromoCode::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant()->id);

        $this->assertTrue($cart->applyPromo('welcome10')['ok']);
        $this->assertSame(10.0, $cart->discount());
    }

    public function test_percentage_discount_can_be_capped(): void
    {
        PromoCode::create([
            'code' => 'BIG', 'type' => 'percent', 'value' => 50,
            'max_discount' => 20, 'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant(price: 100)->id);
        $cart->applyPromo('BIG');

        // 50% of 100 is 50, but the cap holds it to 20.
        $this->assertSame(20.0, $cart->discount());
        $this->assertSame(80.0, $cart->total());
    }

    public function test_a_fixed_discount_cannot_exceed_the_order(): void
    {
        PromoCode::create(['code' => 'FIFTY', 'type' => 'fixed', 'value' => 50, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant(price: 20)->id);
        $cart->applyPromo('FIFTY');

        // Never negative — the shop must not end up owing the customer.
        $this->assertSame(20.0, $cart->discount());
        $this->assertSame(0.0, $cart->total());
    }

    public function test_minimum_spend_is_explained_rather_than_rejected_blankly(): void
    {
        PromoCode::create([
            'code' => 'SPEND50', 'type' => 'percent', 'value' => 10,
            'min_subtotal' => 50, 'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant(price: 20)->id);

        $result = $cart->applyPromo('SPEND50');

        $this->assertFalse($result['ok']);

        // Asserts on the amount, not an English word: the default locale is
        // Arabic, so the wording around it is translated.
        $this->assertStringContainsString('50.00', $result['message']);
    }

    public function test_an_expired_code_is_refused(): void
    {
        PromoCode::create([
            'code' => 'OLD', 'type' => 'percent', 'value' => 10,
            'expires_at' => now()->subDay(), 'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant()->id);

        $this->assertFalse($cart->applyPromo('OLD')['ok']);
    }

    public function test_a_fully_redeemed_code_is_refused(): void
    {
        PromoCode::create([
            'code' => 'GONE', 'type' => 'percent', 'value' => 10,
            'max_uses' => 1, 'used_count' => 1, 'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant()->id);

        $this->assertFalse($cart->applyPromo('GONE')['ok']);
    }

    public function test_the_order_records_the_discount_and_counts_the_redemption(): void
    {
        Notification::fake();

        $promo = PromoCode::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant(price: 100)->id);
        $cart->applyPromo('SAVE10');

        $order = app(CheckoutService::class)->place($this->details());

        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('10.00', $order->discount_total);
        $this->assertSame('90.00', $order->total);
        $this->assertSame('SAVE10', $order->promo_code);

        $this->assertSame(1, $promo->fresh()->used_count);
    }

    public function test_deactivating_a_code_stops_it_applying_to_open_carts(): void
    {
        $promo = PromoCode::create(['code' => 'TEMP', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($this->stockedVariant(price: 100)->id);
        $cart->applyPromo('TEMP');

        $this->assertSame(10.0, $cart->discount());

        $promo->update(['is_active' => false]);

        // The session only holds the code string, so it is re-checked on read.
        $this->assertSame(0.0, $cart->discount());
        $this->assertSame(100.0, $cart->total());
    }
}
