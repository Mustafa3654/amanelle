<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Forms\Components\WebpUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),

                    Select::make('parent_id')
                        ->label('Sits under')
                        ->relationship('parent', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty for a top-level category.'),

                    WebpUpload::make('image_path')
                        ->label('Image')
                        ->directory('categories')
                        ->columnSpanFull(),
                ]),

            Tabs::make('Translations')
                ->columnSpanFull()
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            TextInput::make("name.{$code}")
                                ->label('Name')
                                ->required($code === 'ar'),

                            Textarea::make("description.{$code}")
                                ->label('Description')
                                ->rows(3),
                        ]))
                    ->values()
                    ->all()),

            Section::make()
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')->label('Active')->default(true),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
        ]);
    }
}
