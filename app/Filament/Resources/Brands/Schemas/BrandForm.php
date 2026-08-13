<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Filament\Forms\Components\WebpUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('slug')->label(__('Slug'))->required()->unique(ignoreRecord: true),

                    Select::make('origin_country')
                        ->label(__('Made in'))
                        ->options([
                            'SA' => __('Saudi Arabia'),
                            'AE' => __('United Arab Emirates'),
                            'KR' => __('South Korea'),
                            'FR' => __('France'),
                            'LB' => __('Lebanon'),
                        ])
                        ->searchable()
                        // Where the stock is made, not a market we ship to.
                        ->helperText(__('Where the brand is from. Amanelle sells in Lebanon only.')),

                    WebpUpload::make('logo_path')
                        ->label(__('Logo'))
                        ->directory('brands')
                        ->columnSpanFull(),
                ]),

            Tabs::make('Translations')
                ->columnSpanFull()
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            TextInput::make("name.{$code}")
                                ->label(__('Name'))
                                ->required($code === 'ar'),

                            Textarea::make("description.{$code}")
                                ->label(__('Description'))
                                ->rows(3),
                        ]))
                    ->values()
                    ->all()),

            Section::make()
                ->columns(3)
                ->schema([
                    Toggle::make('is_authorised_stockist')
                        ->label(__('Authorised stockist'))
                        // Counterfeit awareness is the brand's biggest theme,
                        // so this claim is structured data rather than prose.
                        ->helperText(__('Shows the authenticity badge on product pages.'))
                        ->default(true),

                    Toggle::make('is_active')->label(__('Active'))->default(true),

                    TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),
                ]),
        ]);
    }
}
