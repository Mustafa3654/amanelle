<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test for the public pages. Cheap insurance: most of the storefront
 * breakages during this build were a view referencing a route or component
 * that did not exist yet, which only ever showed up as a 500 in the browser.
 */
class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_main_pages_render(): void
    {
        foreach (['/', '/shop', '/about', '/contact', '/cart', '/checkout'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_a_category_page_renders(): void
    {
        Category::create([
            'name' => ['en' => 'Perfumes', 'ar' => 'عطور'],
            'slug' => 'perfumes',
            'is_active' => true,
        ]);

        $this->get('/c/perfumes')->assertOk();
    }

    public function test_an_unknown_category_is_a_404(): void
    {
        $this->get('/c/nope')->assertNotFound();
    }

    public function test_the_locale_switch_stores_the_choice(): void
    {
        $this->post('/locale', ['locale' => 'en'])->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_an_unknown_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'fr'])->assertStatus(400);
    }
}
