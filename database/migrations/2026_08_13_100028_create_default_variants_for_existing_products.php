<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()->doesntHave('variants')->each(function (Product $product): void {
            $product->variants()->create([
                'sku' => 'PROD-'.str($product->slug)->upper()->limit(30, '').'-'.str()->upper(Str::random(4)),
                'item_code' => 'ITEM-'.str()->upper(Str::random(8)),
                'price' => $product->default_sale_price ?? 0,
                'cost_price' => $product->default_cost_price ?? 0,
                'is_active' => true,
            ]);
        });
    }

    public function down(): void
    {
        // Default variants are retained to avoid deleting catalogue data.
    }
};
