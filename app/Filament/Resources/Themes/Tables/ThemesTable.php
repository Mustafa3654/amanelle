<?php

namespace App\Filament\Resources\Themes\Tables;

use App\Models\Theme;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->weight('bold'),
            TextColumn::make('effect')->badge(),
            IconColumn::make('is_active')->label(__('Active'))->boolean(),
            TextColumn::make('updated_at')->dateTime('d M Y')->sortable(),
        ])->recordActions([EditAction::make(), DeleteAction::make()->requiresConfirmation()]);
    }
}
