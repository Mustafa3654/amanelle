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
                TextInput::make('product_variant_id')->label(__('Product variant id'))
                    ->required()
                    ->numeric(),
                TextInput::make('market')->label(__('Market'))
                    ->required()
                    ->default('LB'),
                Select::make('type')->label(__('Type'))
                    ->options(['reserve' => __('Reserve'), 'release' => __('Release'), 'fulfil' => __('Fulfil'), 'adjust' => __('Adjust')])
                    ->required(),
                TextInput::make('quantity_delta')->label(__('Quantity delta'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reserved_delta')->label(__('Reserved delta'))
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('order_id')->label(__('Order id'))
                    ->relationship('order', 'id')
                    ->default(null),
                Select::make('user_id')->label(__('User id'))
                    ->relationship('user', 'name')
                    ->default(null),
                TextInput::make('note')->label(__('Note'))
                    ->default(null),
            ]);
    }
}
