<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Models\StockMovement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y H:i')->sortable(),

                TextColumn::make('variant.product.name')
                    ->label('Product')
                    ->description(fn (StockMovement $r) => $r->variant?->sku)
                    ->wrap(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'reserve' => 'warning',
                        'release' => 'info',
                        'fulfil' => 'success',
                        'adjust' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'reserve' => 'Reserved for an order',
                        'release' => 'Released back on sale',
                        'fulfil' => 'Left the shelf',
                        'adjust' => 'Manual correction',
                        default => $state,
                    }),

                // Signed, so replaying the log from zero reproduces the row.
                TextColumn::make('quantity_delta')
                    ->label('Shelf')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),

                TextColumn::make('reserved_delta')
                    ->label('Reserved')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color('gray'),

                TextColumn::make('order.number')
                    ->label('Order')
                    ->placeholder('—')
                    ->url(fn (StockMovement $r) => $r->order
                        ? \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $r->order])
                        : null),

                TextColumn::make('user.name')->label('By')->placeholder('System'),

                TextColumn::make('note')->limit(40)->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'reserve' => 'Reserved',
                    'release' => 'Released',
                    'fulfil' => 'Left the shelf',
                    'adjust' => 'Manual correction',
                ]),
            ])
            // Append-only by design: this is the answer to "the system says 3
            // and the shelf says 2", and an editable audit trail answers
            // nothing.
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('No stock movements yet');
    }
}
