<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\Components\WebpUpload;
use App\Support\ProductTypes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('type')->label(__('Type'))
                            // Read from config so adding a type is a config
                            // change, not a migration and three edits here.
                            ->options(ProductTypes::options())
                            ->required()
                            ->default('fragrance')
                            ->native(false)
                            // Drives which detail fields and variant axes
                            // apply, so the rest of the form reacts to it.
                            ->live()
                            ->helperText(fn ($state) => $state
                                ? __('Variants will vary by: :axes', [
                                    'axes' => collect(ProductTypes::axesFor($state))
                                        ->map(fn (string $axis) => __(ucfirst($axis)))
                                        ->join(__(' and ')) ?: __('nothing — a single variant'),
                                ])
                                : __('Chooses which fields and variant options apply.')),

                        TextInput::make('slug')->label(__('Slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText(__('Used in the product URL.')),

                        Select::make('brand_id')
                            ->label(__('Brand'))
                            ->relationship('brand', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()
                            ->preload(),

                        Select::make('category_id')
                            ->label(__('Category'))
                            ->relationship('category', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()
                            ->preload(),
                    ]),

                // One tab per locale, writing into the translatable JSON
                // columns. Arabic leads because it is the default locale.
                Tabs::make('Translations')
                    ->columnSpanFull()
                    ->tabs(collect(config('amanelle.locales'))
                        ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                            ->schema([
                                TextInput::make("name.{$code}")
                                    ->label(__('Name'))
                                    ->required($code === 'ar')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) use ($code) {
                                        // Seed the slug from English only, and
                                        // only while it is still blank: Arabic
                                        // transliterates to nothing useful, and
                                        // changing a live slug breaks links.
                                        if ($code === 'en' && blank($get('slug')) && filled($state)) {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),

                                Textarea::make("short_description.{$code}")
                                    ->label(__('Short description'))
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->helperText(__('Shown on cards and at the top of the product page.')),

                                Textarea::make("description.{$code}")
                                    ->label(__('Full description'))
                                    ->rows(5),
                            ]))
                        ->values()
                        ->all()),

                Section::make(__('Fragrance'))
                    ->description(__('Longevity and projection are what this audience actually compares, so they are rated and filterable rather than buried in prose.'))
                    // Any type carrying a concentration is a fragrance for
                    // these purposes, so a new scent type inherits the fields.
                    ->visible(fn ($get) => ProductTypes::hasAxis($get('type'), 'concentration'))
                    ->columns(2)
                    ->schema([
                        Select::make('longevity')
                            ->label(__('Longevity — الثبات'))
                            ->options([1 => '1 — weak', 2 => '2', 3 => '3 — moderate', 4 => '4', 5 => '5 — very long'])
                            ->native(false),

                        Select::make('projection')
                            ->label(__('Projection — الفوحان'))
                            ->options([1 => '1 — close to skin', 2 => '2', 3 => '3 — moderate', 4 => '4', 5 => '5 — fills a room'])
                            ->native(false),

                        Select::make('gender')->label(__('Gender'))
                            ->options(['women' => __('Women'), 'men' => __('Men'), 'unisex' => __('Unisex')])
                            ->native(false),

                        TagsInput::make('notes_top')->label(__('Top notes'))->placeholder(__('Bergamot')),
                        TagsInput::make('notes_heart')->label(__('Heart notes'))->placeholder(__('Rose')),
                        TagsInput::make('notes_base')->label(__('Base notes'))->placeholder(__('Oud')),
                    ]),

                Section::make(__('Default pricing'))
                    ->description(__('Saving creates the first variant from these values. You can add more sizes or shades afterwards, each with its own price and stock.'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('default_cost_price')
                            ->label(__('Cost price'))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$'),
                        TextInput::make('default_sale_price')
                            ->label(__('Sale price'))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$'),

                        /*
                         * Stock for the variant this product will create on
                         * save. Relation managers only appear once a record
                         * exists, so without this there is nowhere to enter a
                         * quantity while adding a product — you had to save,
                         * then go back in and open the variant.
                         *
                         * Not a column on products: it seeds an inventory row
                         * once and is meaningless afterwards.
                         */
                        TextInput::make('default_quantity')
                            ->label(__('Starting stock'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->helperText(__('How many you have right now. Change it later with the Stock button on the variant.')),
                    ]),

                Section::make(__('Skincare'))
                    // Skin fields suit anything applied to skin or hair.
                    ->visible(fn ($get) => in_array($get('type'), ['skincare', 'haircare', 'bodycare'], true))
                    ->columns(2)
                    ->schema([
                        TagsInput::make('skin_types')->label(__('Skin types'))->placeholder(__('oily, dry, combination')),
                        TagsInput::make('concerns')->label(__('Concerns'))->placeholder(__('dark-circles, fine-lines')),
                    ]),

                Section::make(__('Photography'))
                    ->description(__('Uploads are converted to WebP and resized automatically. A variant with its own photo overrides the main one.'))
                    ->schema([
                        WebpUpload::make('image_path')
                            ->label(__('Main image'))
                            ->directory('products'),

                        WebpUpload::make('gallery')
                            ->label(__('More images'))
                            ->directory('products')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->helperText(__('Other angles, packaging, the authenticity seal. Drag to reorder.')),
                    ]),

                Section::make(__('Visibility'))
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label(__('Published'))->default(true),
                        Toggle::make('is_featured')->label(__('Featured on the homepage')),
                        DateTimePicker::make('published_at')->label(__('Published at'))->default(now()),
                    ]),
            ]);
    }
}
