<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('invoice_number')->label(__('Invoice'))->searchable(),
            TextColumn::make('invoice_date')->label(__('Invoice date'))->date()->sortable(),
            TextColumn::make('total')->label(__('Total'))->money('USD')->alignEnd(),
            TextColumn::make('items_count')->counts('items')->label(__('Items')),
        ])->defaultSort('invoice_date', 'desc')->recordActions([
            EditAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('Delete purchase invoice?'))
                ->modalDescription(__('This reverses the invoice stock and account postings before deletion.'))
                ->action(function ($record): void {
                    DB::transaction(function () use ($record): void {
                        $record->load('items');
                        $market = config('amanelle.default_market');

                        foreach ($record->items as $item) {
                            $inventory = \App\Models\Inventory::where('product_variant_id', $item->product_variant_id)
                                ->where('market', $market)
                                ->lockForUpdate()
                                ->first();

                            if ($inventory) {
                                $inventory->decrement('quantity', $item->quantity);
                            }

                            \App\Models\StockMovement::create([
                                'product_variant_id' => $item->product_variant_id,
                                'market' => $market,
                                'type' => 'adjust',
                                'quantity_delta' => -$item->quantity,
                                'reserved_delta' => 0,
                                'user_id' => auth()->id(),
                                'note' => "Reversed deleted purchase invoice {$record->invoice_number}",
                            ]);
                        }

                        $record->delete();
                    });
                }),
        ]);
    }
}
