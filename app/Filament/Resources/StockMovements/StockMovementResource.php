<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\CreateStockMovement;
use App\Filament\Resources\StockMovements\Pages\EditStockMovement;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\Schemas\StockMovementForm;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    public static function getModelLabel(): string
    {
        return __('Stock movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock movements');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalogue');
    }


    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Stock history');
    }


    protected static ?string $recordTitleAttribute = 'id';

    // Append-only audit trail: viewing is the only thing that makes sense.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return StockMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
