<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Forms\Components\WebpUpload;
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
use Illuminate\Support\Str;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants & stock';

    public function form(Schema $schema): Schema
    {
        // Categories are the most useful signal for staff; product type remains
        // the fallback for generic or uncategorized products.
        $product = $this->getOwnerRecord()->loadMissing('category');
        $categoryText = Str::lower(collect([
            $product->category?->getTranslation('name', 'en', false),
            $product->category?->getTranslation('name', 'ar', false),
            $product->category?->slug,
        ])->filter()->implode(' '));
        $isMakeup = Str::contains($categoryText, [
            'makeup', 'lipstick', 'blush', 'foundation', 'concealer', 'mascara',
            'eyeshadow', 'كحل', 'روج', 'أحمر الشفاه', 'مكياج', 'بلاشر',
        ]);
        $isSizeBased = ! $isMakeup && (
            $product->type !== 'makeup' || Str::contains($categoryText, [
                'perfume', 'fragrance', 'cologne', 'serum', 'cream', 'lotion',
                'عطر', 'سيروم', 'كريم', 'لوشن',
            ])
        );

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Your internal code. Appears on the order.'),

                    TextInput::make('item_code')
                        ->label('Item code')
                        ->unique(ignoreRecord: true)
                        ->helperText('Your purchase/catalogue code.'),

                    TextInput::make('sort_order')->numeric()->default(0),

                    TextInput::make('initial_quantity')
                        ->label('Initial quantity')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(),

                    TextInput::make('volume_ml')
                        ->label('Size (ml)')
                        ->numeric()
                        ->visible($isSizeBased),

                    Select::make('concentration')
                        ->options([
                            'edc' => 'EDC', 'edt' => 'EDT', 'edp' => 'EDP',
                            'parfum' => 'Parfum', 'extrait' => 'Extrait',
                            'mist' => 'Body mist', 'oil' => 'Oil',
                        ])
                        ->native(false)
                        ->visible($isSizeBased && $product->type === 'fragrance'),

                    ColorPicker::make('shade_hex')
                        ->label('Shade colour')
                        ->visible($isMakeup)
                        ->helperText('Becomes the swatch on the product page.'),
                ]),

            Tabs::make('Shade name')
                ->columnSpanFull()
                ->visible($isMakeup)
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
                    TextInput::make('cost_price')->label('Cost price')->numeric()->required()->prefix('$')->default($product->default_cost_price),

                    TextInput::make('price')->label('Sale price')->numeric()->required()->prefix('$')->default($product->default_sale_price),

                    TextInput::make('compare_at_price')
                        ->label('Was (optional)')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Shown struck through. Blank for no discount.'),
                ]),

            WebpUpload::make('image_path')
                ->label('Photo for this variant')
                ->directory('variants')
                ->helperText('Optional. Use it when the variant looks different — a shade, not another bottle size.'),

            Toggle::make('is_active')->label('Available to buy')->default(true),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialQuantity = (int) ($data['initial_quantity'] ?? 0);
        unset($data['initial_quantity']);

        return $data;
    }

    protected int $initialQuantity = 0;

    protected function afterCreate(): void
    {
        $market = config('amanelle.default_market');
        $inventory = Inventory::firstOrNew([
            'product_variant_id' => $this->getCreatedRecord()->id,
            'market' => $market,
        ]);
        $inventory->quantity = $this->initialQuantity;
        $inventory->reserved ??= 0;
        $inventory->low_stock_threshold ??= 5;
        $inventory->save();

        if ($this->initialQuantity > 0) {
            StockMovement::create([
                'product_variant_id' => $this->getCreatedRecord()->id,
                'market' => $market,
                'type' => 'adjust',
                'quantity_delta' => $this->initialQuantity,
                'reserved_delta' => 0,
                'user_id' => auth()->id(),
                'note' => 'Initial quantity when variant was created',
            ]);
        }
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
