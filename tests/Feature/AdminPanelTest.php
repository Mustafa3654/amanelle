<?php

namespace Tests\Feature;

use App\Filament\Widgets\BestSellersChart;
use App\Filament\Widgets\LowStockAlert;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\SalesOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards the admin against the failure mode that dogged this build: a
 * resource or widget that only blows up when the page is actually rendered.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_dashboard_and_resource_pages_render(): void
    {
        foreach ([
            '/admin',
            '/admin/products',
            '/admin/orders',
            '/admin/brands',
            '/admin/categories',
            '/admin/currencies',
            '/admin/promo-codes',
            '/admin/notification-settings',
            '/admin/profile',
            '/admin/delivery-zones',
            '/admin/enquiries',
            '/admin/instagram-posts',
            '/admin/stock-movements',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_duplicating_a_product_copies_variants_but_not_stock(): void
    {
        $product = \App\Models\Product::create([
            'type' => 'fragrance',
            'name' => ['en' => 'Pink Lady'],
            'slug' => 'pink-lady',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $variant = \App\Models\ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ASF-PL-100',
            'price' => 49,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        \App\Models\Inventory::create([
            'product_variant_id' => $variant->id,
            'market' => 'LB',
            'quantity' => 12,
            'reserved' => 0,
        ]);

        Livewire::test(\App\Filament\Resources\Products\Pages\ListProducts::class)
            ->callTableAction('duplicate', $product);

        $copy = \App\Models\Product::where('id', '!=', $product->id)->sole();

        // A half-filled duplicate must never appear in the shop, and
        // inheriting stock would invent units that do not exist.
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_featured);
        $this->assertCount(1, $copy->variants);
        $this->assertNotSame($variant->sku, $copy->variants->first()->sku);
        $this->assertSame(0, $copy->variants->first()->availableIn('LB'));
    }

    public function test_bulk_publishing_works(): void
    {
        $products = collect(range(1, 3))->map(fn ($i) => \App\Models\Product::create([
            'type' => 'fragrance',
            'name' => ['en' => "P{$i}"],
            'slug' => "p{$i}",
            'is_active' => false,
        ]));

        Livewire::test(\App\Filament\Resources\Products\Pages\ListProducts::class)
            ->callTableBulkAction('publish', $products);

        $this->assertSame(3, \App\Models\Product::where('is_active', true)->count());
    }

    public function test_telegram_credentials_round_trip_and_the_token_is_encrypted(): void
    {
        \App\Models\Setting::putEncrypted('telegram_token', '123456:ABC-secret');
        \App\Models\Setting::put('telegram_chat_id', '99887766');

        $this->assertSame('123456:ABC-secret', \App\Support\Telegram::token());
        $this->assertSame('99887766', \App\Support\Telegram::chatId());
        $this->assertTrue(\App\Support\Telegram::isConfigured());

        // The stored value must not be the token in plaintext.
        $stored = \App\Models\Setting::where('key', 'telegram_token')->value('value');
        $this->assertNotSame('123456:ABC-secret', $stored['v']);
    }

    public function test_a_staff_member_can_change_their_password(): void
    {
        $user = \App\Models\User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user);

        Livewire::test(\Filament\Auth\Pages\EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                // Filament requires the current password before it will change
                // one — worth keeping, since a hijacked session should not be
                // able to lock the owner out of their own shop.
                'currentPassword' => 'old-password',
                'password' => 'a-much-better-password',
                'passwordConfirmation' => 'a-much-better-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('a-much-better-password', $user->fresh()->password)
        );
    }

    public function test_the_widgets_render(): void
    {
        foreach ([SalesOverview::class, LowStockAlert::class, OrdersChart::class, BestSellersChart::class] as $widget) {
            Livewire::test($widget)->assertOk();
        }
    }

    public function test_the_product_create_form_renders(): void
    {
        $this->get('/admin/products/create')->assertOk();
    }

    public function test_the_admin_language_toggle_flips_the_locale(): void
    {
        $this->from('/admin')->get('/admin/locale')->assertRedirect();

        // App default is Arabic, so the first toggle lands on English.
        $this->assertSame('en', session('locale'));
    }
}
