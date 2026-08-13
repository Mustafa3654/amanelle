<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('invoice_number')->label('Invoice')->searchable(),
            TextColumn::make('supplier_name')->label('Supplier')->searchable(),
            TextColumn::make('invoice_date')->date()->sortable(),
            TextColumn::make('total')->money('USD')->alignEnd(),
            TextColumn::make('items_count')->counts('items')->label('Items'),
        ])->defaultSort('invoice_date', 'desc')->recordActions([EditAction::make()]);
    }
}
