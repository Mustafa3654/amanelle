<?php

namespace App\Filament\Resources\PromoCodes\Tables;

use App\Models\PromoCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromoCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->state(fn (PromoCode $record) => $record->label())
                    ->description(fn (PromoCode $record) => $record->min_subtotal
                        ? 'Min spend $'.number_format((float) $record->min_subtotal, 2)
                        : null),

                TextColumn::make('used_count')
                    ->label('Used')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, PromoCode $record) => $record->max_uses
                        ? "{$state} / {$record->max_uses}"
                        : (string) $state),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    // A code that quietly expired is the usual reason for
                    // "the discount isn't working", so flag it in the list.
                    ->color(fn (PromoCode $record) => $record->expires_at?->isPast() ? 'danger' : null)
                    ->description(fn (PromoCode $record) => $record->expires_at?->isPast() ? 'Expired' : null)
                    ->sortable(),

                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
