<?php
namespace App\Filament\Resources\PurchaseInvoices\Pages;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Resources\Pages\EditRecord;
class EditPurchaseInvoice extends EditRecord {
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
