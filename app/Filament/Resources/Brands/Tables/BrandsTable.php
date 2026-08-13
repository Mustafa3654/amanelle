<?php

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->label(__('Slug'))
                    ->searchable(),
                TextColumn::make('logo_path')->label(__('Logo path'))
                    ->searchable(),
                TextColumn::make('origin_country')->label(__('Origin country'))
                    ->searchable(),
                IconColumn::make('is_authorised_stockist')->label(__('Is authorised stockist'))
                    ->boolean(),
                TextColumn::make('sort_order')->label(__('Sort order'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')->label(__('Is active'))
                    ->boolean(),
                TextColumn::make('created_at')->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label(__('Updated at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
