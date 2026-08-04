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
        ] as $path) {
            $this->get($path)->assertOk();
        }
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
