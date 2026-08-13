<?php

namespace App\Filament\Resources\PurchaseInvoices\RelationManagers;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Invoice items and received stock';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_variant_id')
                ->label('Product / item code')
                ->options(fn () => ProductVariant::with('product')->get()->mapWithKeys(fn ($variant) => [$variant->id => "{$variant->item_code} · {$variant->product?->name} · {$variant->label()}"]))
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    $variant = ProductVariant::find($state);
                    $set('unit_cost', $variant?->cost_price ?? 0);
                    $set('line_total', (int) ($variant ? 1 : 0) * (float) ($variant?->cost_price ?? 0));
                })
                ->required(),
            TextInput::make('quantity')->numeric()->minValue(1)->default(1)->live()
                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('line_total', (int) $state * (float) $get('unit_cost')))->required(),
            TextInput::make('unit_cost')->numeric()->prefix('$')->minValue(0)->live()
                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('line_total', (int) $get('quantity') * (float) $state))->required(),
            TextInput::make('line_total')->numeric()->prefix('$')->readOnly()->required()->helperText('Quantity × cost price.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('variant.product.name')->label('Product'),
            TextColumn::make('variant.sku')->label('SKU'),
            TextColumn::make('quantity'),
            TextColumn::make('unit_cost')->money('USD'),
            TextColumn::make('line_total')->money('USD'),
        ])->headerActions([
            CreateAction::make()->after(function ($record) {
                $market = config('amanelle.default_market');
                $inventory = Inventory::firstOrNew(['product_variant_id' => $record->product_variant_id, 'market' => $market]);
                $inventory->quantity = ($inventory->quantity ?? 0) + $record->quantity;
                $inventory->reserved ??= 0;
                $inventory->low_stock_threshold ??= 5;
                $inventory->save();
                StockMovement::create([
                    'product_variant_id' => $record->product_variant_id,
                    'market' => $market,
                    'type' => 'purchase',
                    'quantity_delta' => $record->quantity,
                    'reserved_delta' => 0,
                    'user_id' => auth()->id(),
                    'note' => "Purchase invoice {$record->invoice?->invoice_number}",
                ]);
                Notification::make()->title('Stock received')->success()->send();
            }),
        ]);
    }
}
