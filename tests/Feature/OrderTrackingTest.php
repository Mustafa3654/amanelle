<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\OrderStatusChanged;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(?string $email = null): Order
    {
        Notification::fake();

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
            'price' => 40, 'currency' => 'USD', 'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id, 'market' => 'LB',
            'quantity' => 10, 'reserved' => 0,
        ]);

        app(CartService::class)->add($variant->id);

        return app(CheckoutService::class)->place([
            'customer_name' => 'Layla',
            'customer_phone' => '+961 70 123 456',
            'customer_email' => $email,
            'shipping_address' => 'Hamra',
            'city' => 'Beirut',
            'notes' => null,
        ]);
    }

    public function test_the_buyer_can_see_their_own_order(): void
    {
        $order = $this->placeOrder();

        // Checkout granted this session access.
        $this->get(route('order.confirmation', $order->number))->assertOk();
    }

    public function test_a_stranger_cannot_open_someone_elses_order(): void
    {
        $order = $this->placeOrder();

        // Order numbers are sequential and guessable; the page carries a name,
        // phone number and home address.
        $this->flushSession();

        $this->get(route('order.confirmation', $order->number))->assertForbidden();
    }

    public function test_lookup_with_the_right_phone_grants_access(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();

        Livewire::test('order-lookup')
            ->set('number', $order->number)
            ->set('phone', '70 123 456')
            ->call('find');

        $this->get(route('order.confirmation', $order->number))->assertOk();
    }

    public function test_lookup_with_the_wrong_phone_is_refused(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();

        // Asserting an error was shown, not its wording: the default locale is
        // Arabic, so the copy is translated.
        Livewire::test('order-lookup')
            ->set('number', $order->number)
            ->set('phone', '70 999 999')
            ->call('find')
            ->assertSet('error', fn ($error) => filled($error));

        $this->get(route('order.confirmation', $order->number))->assertForbidden();
    }

    public function test_shipping_an_order_emails_the_customer(): void
    {
        $order = $this->placeOrder(email: 'layla@example.com');

        Notification::fake();

        $order->update(['status' => 'shipped']);

        Notification::assertSentOnDemand(OrderStatusChanged::class);
    }

    public function test_internal_statuses_do_not_pester_the_customer(): void
    {
        $order = $this->placeOrder(email: 'layla@example.com');

        Notification::fake();

        // They know you are processing it — they just ordered.
        $order->update(['status' => 'processing']);

        Notification::assertNothingSent();
    }

    public function test_an_order_without_an_email_is_not_a_failure(): void
    {
        $order = $this->placeOrder(email: null);

        Notification::fake();

        $order->update(['status' => 'shipped']);

        Notification::assertNothingSent();
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_the_track_page_renders(): void
    {
        $this->get('/track')->assertOk();
    }
}
