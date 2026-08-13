<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        $market = config('amanelle.default_market');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['product.brand', 'inventories']))
            ->defaultSort('id', 'desc')
            ->columns([
                ColorColumn::make('shade_hex')->label(''),

                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->description(fn (ProductVariant $r) => $r->product?->brand?->name)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHas('product', fn ($p) => $p->where('search_text', 'like', "%{$search}%")))
                    ->wrap(),

                TextColumn::make('label')
                    ->label(__('Variant'))
                    ->state(fn (ProductVariant $r) => $r->label())
                    ->placeholder(__('—')),

                TextColumn::make('sku')->label(__('SKU'))->searchable()->copyable(),

                TextColumn::make('item_code')->label(__('Item code'))->searchable()->toggleable(),

                TextColumn::make('cost_price')->label(__('Cost price'))->money('USD')->alignEnd()->toggleable(),

                TextColumn::make('price')->label(__('Sale price'))->money('USD')->alignEnd()->sortable(),

                TextColumn::make('on_shelf')
                    ->label(__('On shelf'))
                    ->alignEnd()
                    ->state(fn (ProductVariant $r) => $r->inventories->firstWhere('market', $market)?->quantity ?? 0),

                TextColumn::make('reserved')
                    ->label(__('Reserved'))
                    ->alignEnd()
                    ->toggleable()
                    ->state(fn (ProductVariant $r) => $r->inventories->firstWhere('market', $market)?->reserved ?? 0),

                TextColumn::make('sellable')
                    ->label(__('Sellable'))
                    ->alignEnd()
                    ->badge()
                    ->state(fn (ProductVariant $r) => $r->availableIn($market))
                    ->color(fn ($state) => $state === 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                IconColumn::make('is_active')->label(__('Available to buy'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->label(__('Product'))
                    ->relationship('product', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')->label(__('Available to buy')),

                // The two questions this screen exists to answer quickly.
                Filter::make('out_of_stock')
                    ->label(__('Out of stock'))
                    ->query(fn ($query) => $query->whereHas(
                        'inventories',
                        fn ($i) => $i->whereRaw('quantity - reserved <= 0')
                    )),

                Filter::make('needs_price')
                    ->label(__('Needs a price'))
                    ->query(fn ($query) => $query->where('price', '<=', 0)),
            ])
            ->recordActions([
                /*
                 * The same stock action as the relation manager. Editing stock
                 * anywhere has to write a movement, or the log stops
                 * reconciling and the whole audit trail is worthless.
                 */
                Action::make('stock')
                    ->label(__('Stock'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->fillForm(function (ProductVariant $record) use ($market) {
                        $inventory = $record->inventories->firstWhere('market', $market);

                        return [
                            'quantity' => $inventory?->quantity ?? 0,
                            'low_stock_threshold' => $inventory?->low_stock_threshold ?? 5,
                        ];
                    })
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('Units on the shelf'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('low_stock_threshold')
                            ->label(__('Warn me below'))
                            ->numeric()
                            ->minValue(0)
                            ->default(5),

                        Textarea::make('note')
                            ->label(__('Reason (optional)'))
                            ->rows(2)
                            ->placeholder(__('Stocktake, new delivery, damaged…')),
                    ])
                    ->action(function (array $data, ProductVariant $record) use ($market) {
                        $inventory = Inventory::firstOrNew([
                            'product_variant_id' => $record->id,
                            'market' => $market,
                        ]);

                        $before = $inventory->quantity ?? 0;

                        $inventory->fill([
                            'quantity' => (int) $data['quantity'],
                            'low_stock_threshold' => (int) $data['low_stock_threshold'],
                            'reserved' => $inventory->reserved ?? 0,
                        ])->save();

                        $delta = (int) $data['quantity'] - $before;

                        if ($delta !== 0) {
                            StockMovement::create([
                                'product_variant_id' => $record->id,
                                'market' => $market,
                                'type' => 'adjust',
                                'quantity_delta' => $delta,
                                'reserved_delta' => 0,
                                'user_id' => auth()->id(),
                                'note' => $data['note'] ?? null,
                            ]);
                        }

                        Notification::make()
                            ->title(__('Stock updated'))
                            ->body("{$record->sku} — {$data['quantity']}")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->emptyStateHeading(__('No variants yet'))
            ->emptyStateDescription(__('Variants are created from a product. Open a product to add sizes or shades.'));
    }
}
