<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->description(fn ($record) => $record->brand?->name)
                    // The name lives in a JSON column, so searching it means
                    // going through the maintained search_text index rather
                    // than the attribute Filament would guess at.
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('search_text', 'like', "%{$search}%"))
                    ->wrap(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'fragrance' => 'warning',
                        'skincare' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('category.slug')
                    ->label('Category')
                    ->formatStateUsing(fn ($record) => $record->category?->name)
                    ->toggleable(),

                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->alignEnd(),

                // Sums sellable units across markets. The number the shop can
                // actually offer, not the raw shelf count.
                TextColumn::make('available')
                    ->label('Available')
                    ->alignEnd()
                    ->state(fn ($record) => $record->variants
                        ->flatMap->inventories
                        ->sum(fn ($i) => max(0, $i->quantity - $i->reserved)))
                    ->badge()
                    ->color(fn ($state) => $state === 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                IconColumn::make('is_active')->label('Published')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),

                TextColumn::make('published_at')->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'fragrance' => 'Fragrance',
                        'skincare' => 'Skincare',
                        'makeup' => 'Makeup',
                    ]),

                SelectFilter::make('brand')
                    ->relationship('brand', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')->label('Published'),
                TernaryFilter::make('is_featured')->label('Featured'),
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
