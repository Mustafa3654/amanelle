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
            Section::make(__('Purchase invoice'))->columnSpanFull()->columns(6)->schema([
                DatePicker::make('invoice_date')->required()->default(now()),
                Select::make('currency')->label(__('Currency'))
                    ->options(['USD' => 'USD', 'LBP' => 'LBP'])
                    ->default('USD')
                    ->live()
                    ->required(),
                Textarea::make('notes')->label(__('Notes'))->columnSpan(6)->rows(2),
            ]),

            Section::make(__('Purchased items'))
                ->description(__('Add every product received on this purchase invoice. Select the product, enter quantity, and confirm the cost price.'))
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label(__('Invoice lines'))
                        ->schema([
                            Select::make('product_variant_id')
                                ->label(__('Product name'))
                                ->options(fn () => \App\Models\ProductVariant::query()
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(fn ($variant) => [
                                        $variant->id => (($variant->product?->getTranslation('name', 'en', false) ?: $variant->product?->getTranslation('name', 'ar', false) ?: 'Unnamed product').' · '.$variant->label()),
                                    ])
                                    ->all())
                                ->getSearchResultsUsing(function (string $search): array {
                                    return \App\Models\ProductVariant::query()
                                        ->with('product')
                                        ->whereHas('product', function ($product) use ($search) {
                                            $term = "%{$search}%";
                                            $product->where('search_text', 'like', $term)
                                                ->orWhere('name', 'like', $term);
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($variant) => [
                                            $variant->id => (($variant->product?->getTranslation('name', 'en', false) ?: $variant->product?->getTranslation('name', 'ar', false) ?: 'Unnamed product').' · '.$variant->label()),
                                        ])
                                        ->all();
                                })
                                ->getOptionLabelUsing(fn ($value): ?string => ($variant = \App\Models\ProductVariant::with('product')->find($value))
                                    ? (($variant->product?->getTranslation('name', 'en', false) ?: $variant->product?->getTranslation('name', 'ar', false) ?: 'Unnamed product').' · '.$variant->label())
                                    : null)
                                ->searchable()
                                ->preload()
                                ->searchDebounce(0)
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $variant = \App\Models\ProductVariant::find($state);
                                    $cost = (float) ($variant?->cost_price ?? 0);
                                    $set('unit_cost', $cost);
                                    $set('line_total', (int) ($get('quantity') ?: 1) * $cost);
                                })
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')->label(__('Quantity'))
                                ->numeric()->minValue(1)->default(1)->live()
                                ->afterStateUpdated(fn ($state, $get, $set) => $set('line_total', (int) $state * (float) $get('unit_cost')))
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('unit_cost')
                                ->label(__('Cost price'))->numeric()->minValue(0)->prefix('$')->live(onBlur: true)
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
                                ->label(__('Amount'))->numeric()->prefix('$')->readOnly()->required(),
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
                        ->label(__('Invoice total'))
                        ->prefix('$')
                        ->default(0)
                        ->readOnly()
                        ->dehydrated(false)
                        ->extraAttributes(['class' => 'text-lg font-semibold']),
                ]),
        ]);
    }
}
