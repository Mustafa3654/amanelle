<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Currency;
use App\Models\DeliveryZone;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * One journey through the whole shop, end to end, exercising the pieces
 * together rather than in isolation: browse, add, discount, deliver, pay,
 * fulfil.
 */
class EndToEndPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_buy_something_and_the_shop_stays_consistent(): void
    {
        Notification::fake();

        Currency::create([
            'code' => 'USD', 'name' => ['en' => 'US Dollar'], 'symbol' => '$',
            'rate' => 1, 'decimals' => 2, 'is_base' => true, 'is_active' => true,
        ]);
        Currency::create([
            'code' => 'LBP', 'name' => ['en' => 'Lebanese Pound'], 'symbol' => 'ل.ل',
            'rate' => 89500, 'decimals' => 0, 'is_base' => false, 'is_active' => true,
        ]);

        DeliveryZone::create([
            'name' => ['en' => 'Beirut', 'ar' => 'بيروت'],
            'fee' => 2, 'is_default' => true, 'is_active' => true,
        ]);

        PromoCode::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $category = Category::create(['name' => ['en' => 'Perfumes'], 'slug' => 'perfumes', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'type' => 'fragrance',
            'name' => ['en' => 'Pink Lady', 'ar' => 'بينك ليدي'],
            'slug' => 'pink-lady',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ASF-PL-100',
            'price' => 50,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'market' => 'LB', 'quantity' => 3, 'reserved' => 0,
        ]);

        User::factory()->create();

        // Browse. Asserted on the link, not the name: the default locale is
        // Arabic, so the listing renders بينك ليدي.
        $this->get('/shop')->assertOk()->assertSee('/p/pink-lady', escape: false);
        $this->get('/p/pink-lady')->assertOk();

        // Add, discount, deliver.
        $cart = app(CartService::class);

        $added = $cart->add($variant->id, 2);
        $this->assertTrue($added['ok'], 'add to cart: '.$added['message']);

        $promo = $cart->applyPromo('welcome10');
        $this->assertTrue($promo['ok'], 'apply promo: '.$promo['message']);

        $this->assertSame(100.0, $cart->subtotal());
        $this->assertSame(10.0, $cart->discount());
        $this->assertSame(2.0, $cart->shipping());
        $this->assertSame(92.0, $cart->total());

        // Pay.
        $order = app(CheckoutService::class)->place([
            'customer_name' => 'Layla',
            'customer_phone' => '+961 70 123 456',
            'customer_email' => 'layla@example.com',
            'shipping_address' => 'Hamra Street',
            'city' => 'Beirut',
            'notes' => null,
        ]);

        $this->assertSame('92.00', $order->total);
        $this->assertSame('WELCOME10', $order->promo_code);
        $this->assertTrue($cart->isEmpty());

        // Reserved, not yet deducted.
        $inventory = $variant->inventories()->first();
        $this->assertSame(3, $inventory->quantity);
        $this->assertSame(2, $inventory->reserved);
        $this->assertSame(1, $inventory->available());

        // The buyer can see it; a stranger cannot.
        $this->get(route('order.confirmation', $order->number))->assertOk();
        $this->flushSession();
        $this->get(route('order.confirmation', $order->number))->assertForbidden();

        // Fulfil.
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        $inventory = $variant->inventories()->first()->fresh();
        $this->assertSame(1, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved);

        // The movement log accounts for every unit.
        $this->assertSame(-2, (int) $order->movements()->where('type', 'fulfil')->sum('quantity_delta'));
    }
}
