<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('code')->label(__('Code'))
                    ->badge()
                    ->searchable(),

                TextColumn::make('name')->label(__('Name'))
                    ->searchable(),

                TextColumn::make('symbol')->label(__('Symbol')),

                TextColumn::make('rate')
                    ->label(__('Units per 1 base'))
                    ->numeric(decimalPlaces: 2)
                    ->description(fn ($record) => $record->is_base ? 'Base currency' : null)
                    ->sortable(),

                TextColumn::make('rate_updated_at')
                    ->label(__('Rate updated'))
                    // The LBP rate moves; a stale one silently misprices the
                    // whole catalogue, so surface how old it is.
                    ->since()
                    ->placeholder(__('Never'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('In switcher'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
