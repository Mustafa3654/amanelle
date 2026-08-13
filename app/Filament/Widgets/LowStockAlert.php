<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlert extends TableWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('Running low');
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Inventory::query()
                    ->with('variant.product.brand')
                    // Compares sellable units against each row's own
                    // threshold, so a fast mover can warn earlier than a slow
                    // one instead of everything sharing a single number.
                    ->whereRaw('quantity - reserved <= low_stock_threshold')
                    ->whereHas('variant', fn (Builder $q) => $q->where('is_active', true))
                    ->orderByRaw('quantity - reserved ASC')
            )
            ->columns([
                TextColumn::make('variant.product.name')
                    ->label(__('Product'))
                    ->description(fn (Inventory $record) => $record->variant?->product?->brand?->name)
                    ->wrap(),

                TextColumn::make('variant.sku')->label(__('SKU')),

                TextColumn::make('variant_label')
                    ->label(__('Variant'))
                    ->state(fn (Inventory $record) => $record->variant?->label()),

                TextColumn::make('quantity')->label(__('On shelf'))->alignEnd(),
                TextColumn::make('reserved')->label(__('Reserved'))->alignEnd(),

                TextColumn::make('available')
                    ->label(__('Sellable'))
                    ->alignEnd()
                    ->badge()
                    ->state(fn (Inventory $record) => $record->available())
                    ->color(fn ($state) => $state === 0 ? 'danger' : 'warning'),
            ])
            ->emptyStateHeading(__('Everything is well stocked'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }
}
