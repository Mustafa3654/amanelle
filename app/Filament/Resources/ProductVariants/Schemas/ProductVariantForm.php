<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Filament\Forms\Components\WebpUpload;
use App\Models\Product;
use App\Support\ProductTypes;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        /*
         * Which axes apply depends on the parent product's type. Here the
         * product is chosen in the form rather than implied by the page, so
         * the type is read from the selected product and the fields appear as
         * soon as it is picked.
         */
        $axis = fn ($get, string $axis): bool => ProductTypes::hasAxis(
            Product::find($get('product_id'))?->type,
            $axis
        );

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->label(__('Product'))
                        ->relationship('product', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->helperText(__('The product this size or shade belongs to.')),

                    TextInput::make('sku')
                        ->label(__('SKU'))
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('item_code')
                        ->label(__('Item code'))
                        ->unique(ignoreRecord: true),

                    TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),

                    TextInput::make('volume_ml')
                        ->label(__('Size (ml)'))
                        ->numeric()
                        ->visible(fn ($get) => $axis($get, 'volume')),

                    Select::make('concentration')
                        ->label(__('Concentration'))
                        ->options([
                            'edc' => 'EDC', 'edt' => 'EDT', 'edp' => 'EDP',
                            'parfum' => __('Parfum'), 'extrait' => __('Extrait'),
                            'mist' => __('Body mist'), 'oil' => __('Oil'),
                        ])
                        ->native(false)
                        ->visible(fn ($get) => $axis($get, 'concentration')),

                    ColorPicker::make('shade_hex')
                        ->label(__('Shade colour'))
                        ->visible(fn ($get) => $axis($get, 'shade'))
                        ->helperText(__('Becomes the swatch on the product page.')),
                ]),

            Tabs::make('Shade name')
                ->columnSpanFull()
                ->visible(fn ($get) => $axis($get, 'shade'))
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            TextInput::make("shade_name.{$code}")->label(__('Shade name')),
                        ]))
                    ->values()
                    ->all()),

            Section::make(__('Price'))
                ->description(__('Entered in USD, the base currency. The storefront converts to LBP at the current rate.'))
                ->columns(3)
                ->schema([
                    TextInput::make('cost_price')->label(__('Cost price'))->numeric()->prefix('$'),
                    TextInput::make('price')->label(__('Sale price'))->numeric()->required()->prefix('$'),
                    TextInput::make('compare_at_price')
                        ->label(__('Was (optional)'))
                        ->numeric()
                        ->prefix('$')
                        ->helperText(__('Shown struck through. Blank for no discount.')),
                ]),

            WebpUpload::make('image_path')
                ->label(__('Photo for this variant'))
                ->directory('variants'),

            Toggle::make('is_active')->label(__('Available to buy'))->default(true),
        ]);
    }
}
