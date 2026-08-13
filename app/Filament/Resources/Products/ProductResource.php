<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }

    public static function getNavigationLabel(): string
    {
        return __('Products');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalogue');
    }

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slug';
    public static function getGloballySearchableAttributes(): array
    {
        // search_text, not name: the name lives in a JSON column and is not
        // directly searchable.
        return ['search_text', 'slug'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return (string) $record->name;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return ['Brand' => (string) ($record->brand?->name ?? '-')];
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
