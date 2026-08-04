<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Inventory;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants & stock';

    public function form(Schema $schema): Schema
    {
        // Which axes apply depends on the parent product's type: a perfume has
        // volume and concentration, a lipstick has a shade.
        $type = $this->getOwnerRecord()->type;

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Your internal code. Appears on the order.'),

                    TextInput::make('sort_order')->numeric()->default(0),

                    TextInput::make('volume_ml')
                        ->label('Size (ml)')
                        ->numeric()
                        ->visible($type !== 'makeup'),

                    Select::make('concentration')
                        ->options([
                            'edc' => 'EDC', 'edt' => 'EDT', 'edp' => 'EDP',
                            'parfum' => 'Parfum', 'extrait' => 'Extrait',
                            'mist' => 'Body mist', 'oil' => 'Oil',
                        ])
                        ->native(false)
                        ->visible($type === 'fragrance'),

                    ColorPicker::make('shade_hex')
                        ->label('Shade colour')
                        ->visible($type === 'makeup')
                        ->helperText('Becomes the swatch on the product page.'),
                ]),

            Tabs::make('Shade name')
                ->columnSpanFull()
                ->visible($type === 'makeup')
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            TextInput::make("shade_name.{$code}")->label('Shade name'),
                        ]))
                    ->values()
                    ->all()),

            Section::make('Price')
                ->description('Entered in USD, the base currency. The storefront converts to LBP at the current rate.')
                ->columns(2)
                ->schema([
                    TextInput::make('price')->numeric()->required()->prefix('$'),

                    TextInput::make('compare_at_price')
                        ->label('Was (optional)')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Shown struck through. Blank for no discount.'),
                ]),

            Toggle::make('is_active')->label('Available to buy')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        $market = config('amanelle.default_market');

        return $table
            ->recordTitleAttribute('sku')
            ->defaultSort('sort_order')
            ->columns([
                ColorColumn::make('shade_hex')
                    ->label('')
                    ->visible($this->getOwnerRecord()->type === 'makeup'),

                TextColumn::make('sku')->searchable(),

                TextColumn::make('label')
                    ->label('Variant')
                    ->state(fn ($record) => $record->label()),

                TextColumn::make('price')->money('USD')->alignEnd(),

                TextColumn::make('on_shelf')
                    ->label('On shelf')
                    ->alignEnd()
                    ->state(fn ($record) => $record->inventories->firstWhere('market', $market)?->quantity ?? 0),

                TextColumn::make('reserved')
                    ->label('Reserved')
                    ->alignEnd()
                    ->tooltip('Held by open orders. Leaves the shelf when the order is delivered.')
                    ->state(fn ($record) => $record->inventories->firstWhere('market', $market)?->reserved ?? 0),

                TextColumn::make('sellable')
                    ->label('Sellable')
                    ->alignEnd()
                    ->badge()
                    ->state(fn ($record) => $record->availableIn($market))
                    ->color(fn ($state) => $state === 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                /*
                 * Stock is its own action rather than fields on the variant
                 * form, because it lives on a different table and moves for
                 * different reasons. Every change writes a stock_movement, so
                 * a manual correction is as traceable as one made by an order.
                 */
                Action::make('stock')
                    ->label('Stock')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->fillForm(function ($record) use ($market) {
                        $inventory = $record->inventories->firstWhere('market', $market);

                        return [
                            'quantity' => $inventory?->quantity ?? 0,
                            'low_stock_threshold' => $inventory?->low_stock_threshold ?? 5,
                        ];
                    })
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Units on the shelf')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('What you physically have. Reserved units are subtracted automatically when working out what is sellable.'),

                        TextInput::make('low_stock_threshold')
                            ->label('Warn me below')
                            ->numeric()
                            ->minValue(0)
                            ->default(5),

                        Textarea::make('note')
                            ->label('Reason (optional)')
                            ->rows(2)
                            ->placeholder('Stocktake, new delivery, damaged…'),
                    ])
                    ->action(function (array $data, $record) use ($market) {
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
                            ->title('Stock updated')
                            ->body("{$record->sku} is now {$data['quantity']} on the shelf.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
