<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
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
                    ->afterStateHydrated(function ($state, $set) {
                        $accounts = \App\Models\Account::query()->where('currency', $state ?: 'USD')->where('is_active', true)->get();
                        $set('debit_account_id', $accounts->firstWhere('type', 'debit')?->id);
                        $set('credit_account_id', $accounts->firstWhere('type', 'credit')?->id);
                    })
                    ->afterStateUpdated(function ($state, $set) {
                        $accounts = \App\Models\Account::query()
                            ->where('currency', $state)
                            ->where('is_active', true)
                            ->get();

                        $set('debit_account_id', $accounts->firstWhere('type', 'debit')?->id);
                        $set('credit_account_id', $accounts->firstWhere('type', 'credit')?->id);
                    })
                    ->required(),
                Select::make('status')->options(['unpaid' => 'Unpaid', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled'])->default('unpaid')->required(),
                Select::make('debit_account_id')
                    ->label('Debit account')
                    ->options(fn ($get) => \App\Models\Account::query()
                        ->where('currency', $get('currency') ?: 'USD')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->account_number} · {$account->name}"]))
                    ->searchable()->preload()->required(),
                Select::make('credit_account_id')
                    ->label('Credit account')
                    ->options(fn ($get) => \App\Models\Account::query()
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
                                ->label('Type item name / code')
                                ->getSearchResultsUsing(function (string $search): array {
                                    return \App\Models\ProductVariant::query()
                                        ->with('product')
                                        ->where(function ($query) use ($search) {
                                            $query->where('item_code', 'like', "{$search}%")
                                                ->orWhere('sku', 'like', "{$search}%")
                                                ->orWhereHas('product', fn ($product) => $product->where('search_text', 'like', "{$search}%"));
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($variant) => [
                                            $variant->id => "{$variant->item_code} · {$variant->product?->name} · {$variant->label()}",
                                        ])
                                        ->all();
                                })
                                ->getOptionLabelUsing(fn ($value): ?string => ($variant = \App\Models\ProductVariant::with('product')->find($value))
                                    ? "{$variant->item_code} · {$variant->product?->name} · {$variant->label()}"
                                    : null)
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $variant = \App\Models\ProductVariant::find($state);
                                    $cost = (float) ($variant?->cost_price ?? 0);
                                    $set('unit_cost', $cost);
                                    $set('line_total', (int) ($get('quantity') ?: 1) * $cost);
                                })
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->numeric()->minValue(1)->default(1)->live()
                                ->afterStateUpdated(fn ($state, $get, $set) => $set('line_total', (int) $state * (float) $get('unit_cost')))
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('unit_cost')
                                ->label('Cost price')->numeric()->minValue(0)->prefix('$')->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $set('line_total', (int) $get('quantity') * (float) $state);

                                    if ($variantId = $get('product_variant_id')) {
                                        \App\Models\ProductVariant::whereKey($variantId)->update([
                                            'cost_price' => (float) $state,
                                        ]);
                                    }
                                })
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('line_total')
                                ->label('Amount')->numeric()->prefix('$')->readOnly()->required(),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->addActionLabel('Add purchased product')
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            $subtotal = collect($state ?? [])->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
                            $set('subtotal', $subtotal);
                            $set('total', $subtotal);
                            $set('invoice_total_display', $subtotal);
                            $set('debit', $subtotal);
                            $set('credit', $subtotal);
                        })
                        ->itemLabel(fn (array $state): ?string => $state['product_variant_id'] ?? null ? 'Purchased item' : 'New item'),

                    TextInput::make('invoice_total_display')
                        ->label('Invoice total')
                        ->prefix('$')
                        ->default(0)
                        ->readOnly()
                        ->dehydrated(false)
                        ->extraAttributes(['class' => 'text-lg font-semibold']),
                ]),
        ]);
    }
}
