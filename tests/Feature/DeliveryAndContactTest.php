<?php

namespace Tests\Feature;

use App\Mail\EnquiryReceived;
use App\Models\Currency;
use App\Models\DeliveryZone;
use App\Models\Enquiry;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeliveryAndContactTest extends TestCase
{
    use RefreshDatabase;

    private function variant(float $price = 40): ProductVariant
    {
        Currency::firstOrCreate(['code' => 'USD'], [
            'name' => ['en' => 'US Dollar'], 'symbol' => '$', 'rate' => 1,
            'decimals' => 2, 'is_base' => true, 'is_active' => true,
        ]);

        $product = Product::create([
            'type' => 'fragrance', 'name' => ['en' => 'T'],
            'slug' => 't-'.uniqid(), 'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'S-'.uniqid(),
            'price' => $price, 'currency' => 'USD', 'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id, 'market' => 'LB',
            'quantity' => 20, 'reserved' => 0,
        ]);

        return $variant;
    }

    private function zones(): array
    {
        return [
            DeliveryZone::create([
                'name' => ['en' => 'Beirut', 'ar' => 'بيروت'],
                'fee' => 2, 'is_default' => true, 'is_active' => true, 'sort_order' => 0,
            ]),
            DeliveryZone::create([
                'name' => ['en' => 'Outside Beirut', 'ar' => 'خارج بيروت'],
                'fee' => 3, 'is_active' => true, 'sort_order' => 1,
            ]),
        ];
    }

    private function details(): array
    {
        return [
            'customer_name' => 'Layla', 'customer_phone' => '+961 70 000 000',
            'customer_email' => null, 'shipping_address' => 'Hamra',
            'city' => 'Beirut', 'notes' => null,
        ];
    }

    public function test_the_default_zone_is_used_when_none_is_chosen(): void
    {
        $this->zones();
        $cart = app(CartService::class);
        $cart->add($this->variant()->id);

        $this->assertSame('Beirut', $cart->zone()->getTranslation('name', 'en'));
        $this->assertSame(2.0, $cart->shipping());
        $this->assertSame(42.0, $cart->total());
    }

    public function test_choosing_a_zone_changes_the_fee(): void
    {
        [, $outside] = $this->zones();
        $cart = app(CartService::class);
        $cart->add($this->variant()->id);

        $cart->setZone($outside->id);

        $this->assertSame(3.0, $cart->shipping());
        $this->assertSame(43.0, $cart->total());
    }

    public function test_delivery_can_be_free_above_a_threshold(): void
    {
        DeliveryZone::create([
            'name' => ['en' => 'Beirut'], 'fee' => 2, 'free_above' => 50,
            'is_default' => true, 'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($this->variant(price: 60)->id);

        $this->assertSame(0.0, $cart->shipping());
        $this->assertSame(60.0, $cart->total());
    }

    public function test_a_discount_counts_towards_the_free_delivery_threshold(): void
    {
        DeliveryZone::create([
            'name' => ['en' => 'Beirut'], 'fee' => 2, 'free_above' => 50,
            'is_default' => true, 'is_active' => true,
        ]);
        PromoCode::create(['code' => 'TEN', 'type' => 'percent', 'value' => 20, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($this->variant(price: 55)->id);
        $cart->applyPromo('TEN');

        // 55 less 20% is 44, under the 50 threshold, so the fee returns.
        $this->assertSame(2.0, $cart->shipping());
        $this->assertSame(46.0, $cart->total());
    }

    public function test_the_order_records_the_fee_and_snapshots_the_zone(): void
    {
        Notification::fake();
        [, $outside] = $this->zones();

        $cart = app(CartService::class);
        $cart->add($this->variant(price: 40)->id);
        $cart->setZone($outside->id);

        $order = app(CheckoutService::class)->place($this->details());

        $this->assertSame('3.00', $order->shipping_total);
        $this->assertSame('43.00', $order->total);
        $this->assertSame($outside->id, $order->delivery_zone_id);
        $this->assertSame('Outside Beirut', $order->delivery_zone_name);
    }

    public function test_only_one_zone_can_be_the_default(): void
    {
        [$beirut, $outside] = $this->zones();

        $outside->update(['is_default' => true]);

        $this->assertFalse($beirut->fresh()->is_default);
        $this->assertTrue($outside->fresh()->is_default);
    }

    public function test_an_enquiry_is_stored_and_emailed_to_the_configured_address(): void
    {
        Mail::fake();
        Setting::put('contact_email', 'orders@amanelle.store');

        $this->post('/contact', [
            'name' => 'Rana',
            'email' => 'rana@example.com',
            'message' => 'Do you have Pink Lady in 50ml?',
        ])->assertRedirect();

        $enquiry = Enquiry::sole();
        $this->assertSame('Rana', $enquiry->name);
        $this->assertNotNull($enquiry->emailed_at);

        Mail::assertSent(EnquiryReceived::class, fn ($mail) => $mail->hasTo('orders@amanelle.store'));
    }

    public function test_an_enquiry_survives_a_mail_failure(): void
    {
        Setting::put('contact_email', 'orders@amanelle.store');

        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP down'));

        $this->post('/contact', [
            'name' => 'Rana',
            'email' => 'rana@example.com',
            'message' => 'Still here?',
        ])->assertRedirect();

        // The row is the record; the email is only the notification.
        $this->assertSame(1, Enquiry::count());
        $this->assertNull(Enquiry::sole()->emailed_at);
    }
}
