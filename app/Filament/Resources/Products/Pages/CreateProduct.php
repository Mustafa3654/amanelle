<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Inventory;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Seed stock for the variant the product created on save.
     *
     * The Product model generates a first variant from the default prices, so
     * by the time this runs there is exactly one to stock. Writing a movement
     * as well keeps the audit trail complete: opening stock is a real change,
     * and a quantity that appears with no matching row is exactly the kind of
     * gap the log exists to explain.
     */
    protected function afterCreate(): void
    {
        $quantity = (int) ($this->data['default_quantity'] ?? 0);

        if ($quantity <= 0) {
            return;
        }

        $variant = $this->record->variants()->first();

        if (! $variant) {
            return;
        }

        $market = config('amanelle.default_market');

        Inventory::updateOrCreate(
            ['product_variant_id' => $variant->id, 'market' => $market],
            ['quantity' => $quantity, 'reserved' => 0, 'low_stock_threshold' => 5]
        );

        StockMovement::create([
            'product_variant_id' => $variant->id,
            'market' => $market,
            'type' => 'adjust',
            'quantity_delta' => $quantity,
            'reserved_delta' => 0,
            'user_id' => auth()->id(),
            'note' => __('Starting stock when the product was added'),
        ]);
    }
}
