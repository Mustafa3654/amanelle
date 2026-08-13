<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Customer'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')->label(__('Customer name'))->required(),
                        TextInput::make('customer_phone')->label(__('Customer phone'))->tel()->required(),
                        TextInput::make('customer_email')->label(__('Customer email'))->email(),
                        TextInput::make('city')->label(__('City')),
                        Textarea::make('shipping_address')->label(__('Shipping address'))->rows(2)->columnSpanFull(),
                        Textarea::make('notes')->label(__('Notes'))->rows(2)->columnSpanFull(),
                    ]),

                Section::make(__('Fulfilment'))
                    ->columns(2)
                    ->schema([
                        Select::make('status')->label(__('Status'))
                            ->options(array_combine(Order::STATUSES, array_map('ucfirst', Order::STATUSES)))
                            ->required()
                            ->native(false)
                            // Stock follows this field through the model hook,
                            // so it is worth saying so where it is changed.
                            ->helperText(__('Delivered removes the items from stock. Cancelled puts them back on sale.')),

                        TextEntry::make('number')->label(__('Order number')),
                    ]),

                /*
                 * Read-only on purpose. Line items are a record of what was
                 * bought and at what price; editing them here would leave the
                 * order total and the stock reservation disagreeing with each
                 * other, silently.
                 */
                Section::make(__('Items'))
                    ->description(__('What the customer ordered. Prices are as shown at the time of purchase.'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name')->label(__('Product')),
                                TextEntry::make('variant_label')->label(__('Variant'))->placeholder(__('—')),
                                TextEntry::make('quantity')->label(__('Qty')),
                                TextEntry::make('line_total')->label(__('Line total'))->money('USD'),
                            ])
                            ->columns(4),

                        TextEntry::make('total')->label(__('Order total'))->money('USD')->weight('bold'),
                    ]),

                Section::make(__('Stock trail'))
                    ->description(__('When this order reserved, released or consumed stock.'))
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('stock_reserved_at')->label(__('Reserved'))->dateTime()->placeholder(__('—')),
                        TextEntry::make('stock_fulfilled_at')->label(__('Deducted on delivery'))->dateTime()->placeholder(__('—')),
                        TextEntry::make('stock_released_at')->label(__('Released'))->dateTime()->placeholder(__('—')),
                    ]),
            ]);
    }
}
