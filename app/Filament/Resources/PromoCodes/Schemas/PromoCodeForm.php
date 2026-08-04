<?php

namespace App\Filament\Resources\PromoCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->helperText('Case does not matter — customers can type it however they like.'),

                    Toggle::make('is_active')->label('Active')->default(true)->inline(false),
                ]),

            Section::make('Discount')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options(['percent' => 'Percentage off', 'fixed' => 'Fixed amount off'])
                        ->required()
                        ->default('percent')
                        ->native(false)
                        ->live(),

                    TextInput::make('value')
                        ->label(fn ($get) => $get('type') === 'percent' ? 'Percent off' : 'Amount off')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                        ->prefix(fn ($get) => $get('type') === 'fixed' ? '$' : null)
                        ->maxValue(fn ($get) => $get('type') === 'percent' ? 100 : null),

                    TextInput::make('min_subtotal')
                        ->label('Minimum spend')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Blank for no minimum.'),

                    TextInput::make('max_discount')
                        ->label('Cap the discount at')
                        ->numeric()
                        ->prefix('$')
                        ->visible(fn ($get) => $get('type') === 'percent')
                        // A percentage with no ceiling can wipe out the margin
                        // on a large order.
                        ->helperText('Stops a percentage getting expensive on big orders.'),
                ]),

            Section::make('Limits')
                ->columns(3)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Valid from')
                        ->helperText('Blank means immediately.'),

                    DateTimePicker::make('expires_at')
                        ->label('Expires')
                        ->helperText('Blank means never.'),

                    TextInput::make('max_uses')
                        ->label('Maximum redemptions')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Blank means unlimited.'),
                ]),

            Tabs::make('Description')
                ->columnSpanFull()
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            Textarea::make("description.{$code}")
                                ->label('Internal note')
                                ->rows(2)
                                ->helperText('For your reference — not shown to customers.'),
                        ]))
                    ->values()
                    ->all()),
        ]);
    }
}
