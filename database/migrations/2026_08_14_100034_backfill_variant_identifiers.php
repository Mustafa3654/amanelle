<?php

use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        ProductVariant::with('product')->where(function ($query): void {
            $query->whereNull('sku')->orWhereNull('item_code');
        })->each(function (ProductVariant $variant): void {
            $prefix = str($variant->product?->slug ?: 'PRODUCT')->upper()->replace('-', '')->limit(12, '');
            if (blank($variant->sku)) {
                do {
                    $sku = 'SKU-'.$prefix.'-'.str()->upper(Str::random(6));
                } while (ProductVariant::where('sku', $sku)->whereKeyNot($variant->id)->exists());
                $variant->sku = $sku;
            }
            if (blank($variant->item_code)) {
                do {
                    $itemCode = 'ITEM-'.str()->upper(Str::random(8));
                } while (ProductVariant::where('item_code', $itemCode)->whereKeyNot($variant->id)->exists());
                $variant->item_code = $itemCode;
            }
            $variant->saveQuietly();
        });
    }

    public function down(): void
    {
        // Generated identifiers are valid catalogue data and are not removed.
    }
};
