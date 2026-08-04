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
                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')->required(),
                        TextInput::make('customer_phone')->tel()->required(),
                        TextInput::make('customer_email')->email(),
                        TextInput::make('city'),
                        Textarea::make('shipping_address')->rows(2)->columnSpanFull(),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Fulfilment')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->options(array_combine(Order::STATUSES, array_map('ucfirst', Order::STATUSES)))
                            ->required()
                            ->native(false)
                            // Stock follows this field through the model hook,
                            // so it is worth saying so where it is changed.
                            ->helperText('Delivered removes the items from stock. Cancelled puts them back on sale.'),

                        TextEntry::make('number')->label('Order number'),
                    ]),

                /*
                 * Read-only on purpose. Line items are a record of what was
                 * bought and at what price; editing them here would leave the
                 * order total and the stock reservation disagreeing with each
                 * other, silently.
                 */
                Section::make('Items')
                    ->description('What the customer ordered. Prices are as shown at the time of purchase.')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name')->label('Product'),
                                TextEntry::make('variant_label')->label('Variant')->placeholder('—'),
                                TextEntry::make('quantity')->label('Qty'),
                                TextEntry::make('line_total')->label('Line total')->money('USD'),
                            ])
                            ->columns(4),

                        TextEntry::make('total')->label('Order total')->money('USD')->weight('bold'),
                    ]),

                Section::make('Stock trail')
                    ->description('When this order reserved, released or consumed stock.')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('stock_reserved_at')->label('Reserved')->dateTime()->placeholder('—'),
                        TextEntry::make('stock_fulfilled_at')->label('Deducted on delivery')->dateTime()->placeholder('—'),
                        TextEntry::make('stock_released_at')->label('Released')->dateTime()->placeholder('—'),
                    ]),
            ]);
    }
}
