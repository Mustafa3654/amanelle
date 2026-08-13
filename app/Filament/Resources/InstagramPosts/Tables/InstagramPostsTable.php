<?php

namespace App\Filament\Resources\InstagramPosts\Tables;

use App\Models\InstagramPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstagramPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            // Order on the homepage is the whole point of this screen.
            ->reorderable('sort_order')
            ->columns([
                // Blank heading on purpose — the thumbnail speaks for itself.
                // Not __(''), which returns the whole translation array.
                ImageColumn::make('image_path')->label('')->height(60),

                TextColumn::make('caption')->label(__('Caption'))->wrap()->limit(60)->placeholder(__('—')),

                IconColumn::make('is_video')->label(__('Reel'))->boolean(),

                TextColumn::make('posted_at')->label(__('Posted'))->date('d M Y')->sortable(),

                IconColumn::make('is_active')->label(__('Shown'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No posts yet'))
            ->emptyStateDescription(__('The Instagram section is hidden on the homepage until you add one.'));
    }
}
