<?php
namespace App\Filament\Resources\PurchaseInvoices\Pages;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePurchaseInvoice extends CreateRecord {
    protected static string $resource = PurchaseInvoiceResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected array $invoiceItems = [];
    protected function mutateFormDataBeforeCreate(array $data): array {
        $items = collect($data['items'] ?? [])->map(function (array $item): array {
            $variant = \App\Models\ProductVariant::findOrFail($item['product_variant_id']);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitCost = (float) ($item['unit_cost'] ?? $variant->cost_price ?? 0);
            $item['quantity'] = $quantity;
            $item['unit_cost'] = $unitCost;
            $item['line_total'] = round($quantity * $unitCost, 2);

            return $item;
        })->values()->all();
        $this->invoiceItems = $items;
        unset($data['items']);
        $data['invoice_number'] = 'PI-'.now()->format('Ymd-His').'-'.str()->upper(str()->random(4));
        $data['created_by'] = auth()->id();
        $data['subtotal'] = collect($items)->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
        $data['tax'] = 0;
        $data['total'] = $data['subtotal'];
        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function (): void {
        foreach ($this->record->items()->get() as $line) {
            \App\Models\ProductVariant::whereKey($line->product_variant_id)->update([
                'cost_price' => (float) $line->unit_cost,
            ]);
            $market = config('amanelle.default_market');
            $inventory = Inventory::firstOrCreate(
                ['product_variant_id' => $line->product_variant_id, 'market' => $market],
                ['quantity' => 0, 'reserved' => 0, 'low_stock_threshold' => 5],
            );
            $inventory->increment('quantity', (int) $line->quantity);
            StockMovement::create([
                'product_variant_id' => $line->product_variant_id,
                'market' => $market,
                'type' => 'purchase',
                'quantity_delta' => $line->quantity,
                'reserved_delta' => 0,
                'user_id' => auth()->id(),
                'note' => "Purchase invoice {$this->record->invoice_number}",
            ]);
        }

        // Recalculate from the persisted invoice lines so the accounting post
        // cannot depend on a stale or empty header total.
        $amount = (float) $this->record->items()->sum('line_total');
        $this->record->update([
            'subtotal' => $amount,
            'tax' => 0,
            'total' => $amount,
        ]);
        });
    }
}
