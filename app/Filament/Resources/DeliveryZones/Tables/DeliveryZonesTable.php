<?php

namespace App\Filament\Resources\DeliveryZones\Tables;

use App\Models\DeliveryZone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Area'))
                    ->description(fn (DeliveryZone $record) => $record->description)
                    ->searchable(),

                TextColumn::make('fee')->label(__('Fee'))
                    ->money('USD')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => (float) $state === 0.0 ? 'Free' : '$'.number_format((float) $state, 2)),

                TextColumn::make('free_above')
                    ->label(__('Free over'))
                    ->money('USD')
                    ->placeholder(__('—'))
                    ->alignEnd(),

                IconColumn::make('is_default')->label(__('Default'))->boolean(),
                IconColumn::make('is_active')->label(__('Offered'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No delivery areas yet'))
            // Without at least one zone the checkout shows no options and
            // every order ships free, so say so rather than looking fine.
            ->emptyStateDescription(__('Until you add one, checkout charges no delivery fee.'));
    }
}
