<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\Components\WebpUpload;
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
                        Select::make('type')
                            ->options([
                                'fragrance' => 'Fragrance',
                                'skincare' => 'Skincare',
                                'makeup' => 'Makeup',
                            ])
                            ->required()
                            ->default('fragrance')
                            // Drives which detail fields and variant axes
                            // apply, so the rest of the form reacts to it.
                            ->live()
                            ->helperText('Chooses which fields and variant options apply.'),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the product URL.'),

                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()
                            ->preload(),

                        Select::make('category_id')
                            ->label('Category')
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
                                    ->label('Name')
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
                                    ->label('Short description')
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->helperText('Shown on cards and at the top of the product page.'),

                                Textarea::make("description.{$code}")
                                    ->label('Full description')
                                    ->rows(5),
                            ]))
                        ->values()
                        ->all()),

                Section::make('Fragrance')
                    ->description('Longevity and projection are what this audience actually compares, so they are rated and filterable rather than buried in prose.')
                    ->visible(fn ($get) => $get('type') === 'fragrance')
                    ->columns(2)
                    ->schema([
                        Select::make('longevity')
                            ->label('Longevity — الثبات')
                            ->options([1 => '1 — weak', 2 => '2', 3 => '3 — moderate', 4 => '4', 5 => '5 — very long'])
                            ->native(false),

                        Select::make('projection')
                            ->label('Projection — الفوحان')
                            ->options([1 => '1 — close to skin', 2 => '2', 3 => '3 — moderate', 4 => '4', 5 => '5 — fills a room'])
                            ->native(false),

                        Select::make('gender')
                            ->options(['women' => 'Women', 'men' => 'Men', 'unisex' => 'Unisex'])
                            ->native(false),

                        TagsInput::make('notes_top')->label('Top notes')->placeholder('Bergamot'),
                        TagsInput::make('notes_heart')->label('Heart notes')->placeholder('Rose'),
                        TagsInput::make('notes_base')->label('Base notes')->placeholder('Oud'),
                    ]),

                Section::make('Skincare')
                    ->visible(fn ($get) => $get('type') === 'skincare')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('skin_types')->placeholder('oily, dry, combination'),
                        TagsInput::make('concerns')->placeholder('dark-circles, fine-lines'),
                    ]),

                Section::make('Photography')
                    ->description('Uploads are converted to WebP and resized automatically. A variant with its own photo overrides the main one.')
                    ->schema([
                        WebpUpload::make('image_path')
                            ->label('Main image')
                            ->directory('products'),

                        WebpUpload::make('gallery')
                            ->label('More images')
                            ->directory('products')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->helperText('Other angles, packaging, the authenticity seal. Drag to reorder.'),
                    ]),

                Section::make('Visibility')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Published')->default(true),
                        Toggle::make('is_featured')->label('Featured on the homepage'),
                        DateTimePicker::make('published_at')->default(now()),
                    ]),
            ]);
    }
}
