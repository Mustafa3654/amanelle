<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('product_variant_id')
                    ->required()
                    ->numeric(),
                TextInput::make('market')
                    ->required()
                    ->default('LB'),
                Select::make('type')
                    ->options(['reserve' => 'Reserve', 'release' => 'Release', 'fulfil' => 'Fulfil', 'adjust' => 'Adjust'])
                    ->required(),
                TextInput::make('quantity_delta')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reserved_delta')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->default(null),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(null),
                TextInput::make('note')
                    ->default(null),
            ]);
    }
}
