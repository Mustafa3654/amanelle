<?php
namespace App\Filament\Resources\Suppliers\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class SuppliersTable { public static function configure(Table $table): Table { return $table->columns([
    TextColumn::make('name')->searchable(), TextColumn::make('account_number')->label('Account number')->searchable(), TextColumn::make('contact_name'), TextColumn::make('phone'), IconColumn::make('is_active')->boolean(),
])->recordActions([EditAction::make()]); } }
