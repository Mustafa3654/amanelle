<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->helperText('ISO 4217, e.g. USD or LBP.')
                            ->required()
                            ->maxLength(3)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state)),

                        TextInput::make('symbol')
                            ->required()
                            ->maxLength(8),

                        // One tab per locale. Filament writes to the JSON
                        // column via the translatable cast on the model.
                        Tabs::make()
                            ->columnSpanFull()
                            ->tabs(collect(config('amanelle.locales'))
                                ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                                    ->schema([
                                        TextInput::make("name.{$code}")
                                            ->label('Name')
                                            ->required(),
                                    ]))
                                ->values()
                                ->all()),
                    ]),

                Section::make('Exchange rate')
                    ->description('How many units of this currency equal one unit of the base currency. The base currency is always 1.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('rate')
                            ->label('Units per 1 base')
                            ->numeric()
                            ->required()
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->default(1)
                            // Editing the base's own rate would rescale every
                            // price on the site, so it is locked at 1.
                            ->disabled(fn ($get) => (bool) $get('is_base'))
                            ->dehydrated()
                            ->helperText('Changing this reprices the whole catalogue immediately.'),

                        TextInput::make('decimals')
                            ->label('Decimal places')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(4)
                            ->default(2)
                            ->helperText('Use 0 for LBP — decimals on six-figure amounts are noise.'),

                        Toggle::make('is_base')
                            ->label('Base currency')
                            ->helperText('Product prices are stored in this currency. Only one may be the base.')
                            ->live(),

                        Toggle::make('is_active')
                            ->label('Show in the storefront switcher')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
