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
                ImageColumn::make('image_path')->label('')->height(60),

                TextColumn::make('caption')->wrap()->limit(60)->placeholder('—'),

                IconColumn::make('is_video')->label('Reel')->boolean(),

                TextColumn::make('posted_at')->label('Posted')->date('d M Y')->sortable(),

                IconColumn::make('is_active')->label('Shown')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No posts yet')
            ->emptyStateDescription('The Instagram section is hidden on the homepage until you add one.');
    }
}
