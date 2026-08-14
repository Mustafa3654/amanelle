<?php

namespace App\Filament\Resources\Themes;

use App\Filament\Resources\Themes\Pages\CreateTheme;
use App\Filament\Resources\Themes\Pages\EditTheme;
use App\Filament\Resources\Themes\Pages\ListThemes;
use App\Filament\Resources\Themes\Schemas\ThemeForm;
use App\Filament\Resources\Themes\Tables\ThemesTable;
use App\Models\Theme;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    public static function getNavigationGroup(): ?string { return __('Settings'); }
    public static function getNavigationLabel(): string { return __('Themes'); }
    public static function getModelLabel(): string { return __('Theme'); }
    public static function getPluralModelLabel(): string { return __('Themes'); }
    public static function form(Schema $schema): Schema { return ThemeForm::configure($schema); }
    public static function table(Table $table): Table { return ThemesTable::configure($table); }
    public static function getPages(): array
    {
        return ['index' => ListThemes::route('/'), 'create' => CreateTheme::route('/create'), 'edit' => EditTheme::route('/{record}/edit')];
    }
}
