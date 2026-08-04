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
        ] as $path) {
            $this->get($path)->assertOk();
        }
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
