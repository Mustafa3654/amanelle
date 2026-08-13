<?php

namespace App\Filament\Resources\DeliveryZones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class DeliveryZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Area')
                ->columnSpanFull()
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            TextInput::make("name.{$code}")
                                ->label(__('Area name'))
                                ->required($code === 'ar')
                                ->placeholder($code === 'ar' ? 'بيروت' : 'Beirut'),

                            Textarea::make("description.{$code}")
                                ->label(__('Note for customers'))
                                ->rows(2)
                                ->placeholder($code === 'ar' ? 'توصيل خلال ٢٤ ساعة' : 'Delivered within 24 hours'),
                        ]))
                    ->values()
                    ->all()),

            Section::make(__('Fee'))
                ->description(__('Entered in USD. The storefront converts it to the customer\'s currency like every other price.'))
                ->columns(2)
                ->schema([
                    TextInput::make('fee')
                        ->label(__('Delivery fee'))
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('$')
                        ->default(0)
                        ->helperText(__('Set 0 for free delivery to this area.')),

                    TextInput::make('free_above')
                        ->label(__('Free when the order is over'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        // Charged on the discounted subtotal, so a promo can
                        // carry an order over the threshold.
                        ->helperText(__('Blank to always charge. Checked against the subtotal after any discount.')),
                ]),

            Section::make()
                ->columns(3)
                ->schema([
                    Toggle::make('is_default')
                        ->label(__('Preselected at checkout'))
                        ->helperText(__('Only one area can be the default.')),

                    Toggle::make('is_active')->label(__('Offered at checkout'))->default(true),

                    TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),
                ]),
        ]);
    }
}
