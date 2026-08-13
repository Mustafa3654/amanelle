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
        $items = $data['items'] ?? [];
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
