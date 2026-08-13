<?php
namespace App\Filament\Resources\PurchaseInvoices\Pages;
use App\Models\Inventory;
use App\Models\StockMovement;
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
        $data['supplier_name'] = \App\Models\Supplier::find($data['supplier_id'])?->name ?? '';
        $data['debit_account'] = \App\Models\Account::find($data['debit_account_id'])?->name ?? 'Inventory / Purchases';
        $data['credit_account'] = \App\Models\Account::find($data['credit_account_id'])?->name ?? 'Accounts Payable';
        $data['subtotal'] = collect($items)->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
        $data['tax'] = 0;
        $data['total'] = $data['subtotal'];
        $data['debit'] = $data['total'];
        $data['credit'] = $data['total'];
        return $data;
    }

    protected function afterCreate(): void
    {
        $amount = (float) $this->record->total;

        \App\Models\Account::whereKey($this->record->debit_account_id)->increment('balance', $amount);
        \App\Models\Account::whereKey($this->record->credit_account_id)->increment('balance', $amount);

        foreach ($this->invoiceItems as $item) {
            \App\Models\ProductVariant::whereKey($item['product_variant_id'])->update([
                'cost_price' => (float) $item['unit_cost'],
            ]);

            $line = $this->record->items()->create($item);
            $market = config('amanelle.default_market');
            $inventory = Inventory::firstOrNew(['product_variant_id' => $line->product_variant_id, 'market' => $market]);
            $inventory->quantity = ($inventory->quantity ?? 0) + $line->quantity;
            $inventory->reserved ??= 0;
            $inventory->low_stock_threshold ??= 5;
            $inventory->save();
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
    }
}
