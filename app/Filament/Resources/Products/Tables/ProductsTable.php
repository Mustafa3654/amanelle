<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
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
                    ->label(__('Product'))
                    ->description(fn ($record) => $record->brand?->name)
                    // The name lives in a JSON column, so searching it means
                    // going through the maintained search_text index rather
                    // than the attribute Filament would guess at.
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('search_text', 'like', "%{$search}%"))
                    ->wrap(),

                TextColumn::make('type')->label(__('Type'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'fragrance' => 'warning',
                        'skincare' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('category.slug')
                    ->label(__('Category'))
                    ->formatStateUsing(fn ($record) => $record->category?->name)
                    ->toggleable(),

                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label(__('Variants'))
                    ->alignEnd(),

                // Sums sellable units across markets. The number the shop can
                // actually offer, not the raw shelf count.
                TextColumn::make('available')
                    ->label(__('Available'))
                    ->alignEnd()
                    ->state(fn ($record) => $record->variants
                        ->flatMap->inventories
                        ->sum(fn ($i) => max(0, $i->quantity - $i->reserved)))
                    ->badge()
                    ->color(fn ($state) => $state === 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                IconColumn::make('is_active')->label(__('Published'))->boolean(),
                IconColumn::make('is_featured')->label(__('Featured'))->boolean(),

                TextColumn::make('published_at')->label(__('Published at'))->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('Type'))
                    ->options([
                        'fragrance' => __('Fragrance'),
                        'skincare' => __('Skincare'),
                        'makeup' => __('Makeup'),
                    ]),

                SelectFilter::make('brand')->label(__('Brand'))
                    ->relationship('brand', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')->label(__('Published')),
                TernaryFilter::make('is_featured')->label(__('Featured')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),

                /*
                 * Most of this catalogue is perfumes that differ only in name
                 * and notes, so re-entering brand, category, prices and
                 * variants each time is the slowest part of adding stock.
                 *
                 * The copy is unpublished and stock starts at zero: a
                 * half-filled duplicate must never appear in the shop, and
                 * inheriting the original's stock would invent units.
                 */
                Action::make('duplicate')
                    ->label(__('Duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('Creates an unpublished copy with the same variants and prices, and no stock.'))
                    ->action(function (Product $record) {
                        /*
                         * Reloaded clean before replicating: the row handed to
                         * this action carries the table's withCount and withSum
                         * aggregates as attributes, and replicate() would try
                         * to insert them as columns.
                         */
                        $source = Product::with('variants')->findOrFail($record->getKey());

                        $copy = $source->replicate(['search_text']);
                        $copy->slug = $source->slug.'-copy-'.Str::lower(Str::random(4));
                        $copy->is_active = false;
                        $copy->is_featured = false;
                        $copy->setTranslations('name', collect($source->getTranslations('name'))
                            ->map(fn ($n) => $n.' (copy)')
                            ->all());
                        $copy->save();

                        // Saving fires the created hook, which adds a default
                        // variant. The copy is about to receive the original's
                        // real variants, so that placeholder would be a
                        // phantom extra line.
                        $copy->variants()->delete();

                        foreach ($source->variants as $variant) {
                            $newVariant = $variant->replicate();
                            $newVariant->product_id = $copy->id;

                            // Both are unique columns, so a straight copy
                            // collides on the second one.
                            $newVariant->sku = $variant->sku.'-C'.Str::upper(Str::random(3));
                            $newVariant->item_code = 'ITEM-'.Str::upper(Str::random(8));

                            $newVariant->save();
                        }

                        Notification::make()
                            ->title(__('Duplicated'))
                            ->body(__('The copy is unpublished with no stock. Edit it, then publish.'))
                            ->success()
                            ->send();

                        return redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(__('Publish'))
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unpublish')
                        ->label(__('Unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('feature')
                        ->label(__('Feature on the homepage'))
                        ->icon('heroicon-o-star')
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unfeature')
                        ->label(__('Remove from the homepage'))
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_featured' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
