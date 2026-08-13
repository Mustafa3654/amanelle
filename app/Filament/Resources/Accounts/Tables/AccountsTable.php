<?php
namespace App\Filament\Resources\Accounts\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class AccountsTable { public static function configure(Table $table): Table { return $table->columns([
    TextColumn::make('account_number')->label('Account number')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('type')->badge(), TextColumn::make('currency'), TextColumn::make('balance')->alignEnd(), IconColumn::make('is_active')->boolean(),
])->defaultSort('account_number')->recordActions([EditAction::make()]); } }
