<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Purchase invoice')->columnSpanFull()->columns(6)->schema([
                Select::make('supplier_id')->relationship('supplier', 'name')->searchable()->preload()->required()->columnSpan(2),
                DatePicker::make('invoice_date')->required()->default(now()),
                DatePicker::make('due_date')->label('Due date'),
                Select::make('currency')
                    ->options(['USD' => 'USD', 'LBP' => 'LBP'])
                    ->default('USD')
                    ->live()
                    ->afterStateHydrated(function ($state, Set $set) {
                        $accounts = \App\Models\Account::query()->where('currency', $state ?: 'USD')->where('is_active', true)->get();
                        $set('debit_account_id', $accounts->firstWhere('type', 'purchases')?->id);
                        $set('credit_account_id', $accounts->firstWhere('type', 'payable')?->id);
                    })
                    ->afterStateUpdated(function ($state, Set $set) {
                        $accounts = \App\Models\Account::query()
                            ->where('currency', $state)
                            ->where('is_active', true)
                            ->get();

                        $set('debit_account_id', $accounts->firstWhere('type', 'purchases')?->id);
                        $set('credit_account_id', $accounts->firstWhere('type', 'payable')?->id);
                    })
                    ->required(),
                Select::make('status')->options(['unpaid' => 'Unpaid', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled'])->default('unpaid')->required(),
                Select::make('debit_account_id')
                    ->label('Debit account')
                    ->options(fn (Get $get) => \App\Models\Account::query()
                        ->where('currency', $get('currency') ?: 'USD')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->account_number} · {$account->name}"]))
                    ->searchable()->preload()->required(),
                Select::make('credit_account_id')
                    ->label('Credit account')
                    ->options(fn (Get $get) => \App\Models\Account::query()
                        ->where('currency', $get('currency') ?: 'USD')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->account_number} · {$account->name}"]))
                    ->searchable()->preload()->required(),
                Textarea::make('notes')->columnSpan(6)->rows(2),
            ]),

            Section::make('Purchased items')
                ->description('Add every product received on this supplier invoice. Select by item code, enter quantity, and confirm the cost price.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('Invoice lines')
                        ->schema([
                            Select::make('product_variant_id')
                                ->label('Item code / product')
                                ->options(fn () => \App\Models\ProductVariant::with('product')->get()->mapWithKeys(fn ($variant) => [
                                    $variant->id => "{$variant->item_code} · {$variant->product?->name} · {$variant->label()}",
                                ]))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $variant = \App\Models\ProductVariant::find($state);
                                    $cost = (float) ($variant?->cost_price ?? 0);
                                    $set('unit_cost', $cost);
                                    $set('line_total', (int) ($get('quantity') ?: 1) * $cost);
                                })
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->numeric()->minValue(1)->default(1)->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('line_total', (int) $state * (float) $get('unit_cost')))
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('unit_cost')
                                ->label('Cost price')->numeric()->minValue(0)->prefix('$')->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('line_total', (int) $get('quantity') * (float) $state))
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('line_total')
                                ->label('Amount')->numeric()->prefix('$')->readOnly()->required(),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->addActionLabel('Add purchased product')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $subtotal = collect($state ?? [])->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
                            $set('subtotal', $subtotal);
                            $set('total', $subtotal);
                            $set('debit', $subtotal);
                            $set('credit', $subtotal);
                        })
                        ->itemLabel(fn (array $state): ?string => $state['product_variant_id'] ?? null ? 'Purchased item' : 'New item'),
                ]),
        ]);
    }
}
