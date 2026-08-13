<?php

namespace App\Filament\Resources\PurchaseInvoices;

use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Filament\Resources\PurchaseInvoices\Schemas\PurchaseInvoiceForm;
use App\Filament\Resources\PurchaseInvoices\Tables\PurchaseInvoicesTable;
use App\Models\PurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\RelationManagers\ItemsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Purchase invoices';
    public static function form(Schema $schema): Schema { return PurchaseInvoiceForm::configure($schema); }
    public static function table(Table $table): Table { return PurchaseInvoicesTable::configure($table); }
    public static function getRelations(): array { return [ItemsRelationManager::class]; }
    public static function getPages(): array
    {
        return ['index' => ListPurchaseInvoices::route('/'), 'create' => CreatePurchaseInvoice::route('/create'), 'edit' => EditPurchaseInvoice::route('/{record}/edit')];
    }
}
