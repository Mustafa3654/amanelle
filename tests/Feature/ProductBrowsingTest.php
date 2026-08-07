<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FragranceReference;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => ['en' => 'Perfumes', 'ar' => 'عطور'],
            'slug' => 'perfumes',
            'is_active' => true,
        ]);
    }

    private function product(array $attributes, float $price, ?array $reference = null): Product
    {
        $brand = Brand::firstOrCreate(
            ['slug' => $attributes['brand'] ?? 'assaf'],
            ['name' => ['en' => 'ASSAF'], 'is_active' => true]
        );

        $product = Product::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $this->category->id,
            'type' => 'fragrance',
            'is_active' => true,
            'published_at' => now(),
        ], collect($attributes)->except('brand')->all()));

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.uniqid(),
            'price' => $price,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        if ($reference) {
            FragranceReference::create([
                'product_id' => $product->id,
                'designer_house' => $reference[0],
                'original_name' => $reference[1],
            ]);
        }

        return $product;
    }

    private function seedThree(): void
    {
        $this->product(['name' => ['en' => 'Pink Lady'], 'slug' => 'pink-lady', 'gender' => 'women', 'longevity' => 5], 50);
        $this->product(['name' => ['en' => 'Noble Intense'], 'slug' => 'noble', 'gender' => 'men', 'longevity' => 3], 20);
        $this->product(['name' => ['en' => 'Pink Marshmallow'], 'slug' => 'marshmallow', 'gender' => 'women', 'longevity' => 4], 35, ['Kayali', 'Yum']);
    }

    /**
     * The regression that prompted this test: ProductQuery type-hinted
     * Builder, but a category page passes a HasMany relation, so every
     * category listing 500'd.
     */
    public function test_every_category_page_renders_with_each_sort(): void
    {
        $this->seedThree();

        foreach (array_keys(ProductQuery::SORTS) as $sort) {
            $this->get("/c/perfumes?sort={$sort}")->assertOk();
            $this->get("/shop?sort={$sort}")->assertOk();
        }
    }

    public function test_search_matches_a_product_name(): void
    {
        $this->seedThree();

        $results = ProductQuery::apply(Product::query(), ['q' => 'marshmallow'])->get();

        $this->assertCount(1, $results);
        $this->assertSame('marshmallow', $results->first()->slug);
    }

    public function test_search_finds_a_product_by_the_scent_it_replaces(): void
    {
        $this->seedThree();

        // Someone searching "Kayali" wants the alternative to it — that
        // journey is the account's whole pitch.
        $results = ProductQuery::apply(Product::query(), ['q' => 'kayali'])->get();

        $this->assertCount(1, $results);
        $this->assertSame('marshmallow', $results->first()->slug);
    }

    public function test_price_sorting_runs_low_to_high(): void
    {
        $this->seedThree();

        $slugs = ProductQuery::apply(Product::query(), ['sort' => 'price_asc'])->get()->pluck('slug')->all();

        $this->assertSame(['noble', 'marshmallow', 'pink-lady'], $slugs);
    }

    public function test_longevity_filters_at_least_not_exactly(): void
    {
        $this->seedThree();

        $results = ProductQuery::apply(Product::query(), ['longevity' => 4])->get();

        // 4 and 5, not only 4.
        $this->assertEqualsCanonicalizing(['marshmallow', 'pink-lady'], $results->pluck('slug')->all());
    }

    public function test_filters_combine(): void
    {
        $this->seedThree();

        $results = ProductQuery::apply(Product::query(), ['gender' => 'women', 'max_price' => 40])->get();

        $this->assertCount(1, $results);
        $this->assertSame('marshmallow', $results->first()->slug);
    }

    public function test_the_inspired_by_filter_narrows_to_alternatives(): void
    {
        $this->seedThree();

        $results = ProductQuery::apply(Product::query(), ['inspired' => '1'])->get();

        $this->assertCount(1, $results);
        $this->assertSame('marshmallow', $results->first()->slug);
    }

    public function test_filters_survive_pagination_links(): void
    {
        // Enough to spill onto a second page, or there are no pagination
        // links to inspect and the test would pass without proving anything.
        for ($i = 0; $i < 15; $i++) {
            $this->product([
                'name' => ['en' => "Scent {$i}"],
                'slug' => "scent-{$i}",
                'gender' => 'women',
            ], 10 + $i);
        }

        $this->get('/shop?gender=women&sort=price_asc')
            ->assertOk()
            // withQueryString keeps the filter on page 2 rather than silently
            // resetting the listing.
            ->assertSee('gender=women', escape: false);
    }
}
