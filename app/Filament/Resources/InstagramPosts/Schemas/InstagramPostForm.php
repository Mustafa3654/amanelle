<?php

namespace App\Filament\Resources\InstagramPosts\Schemas;

use App\Filament\Forms\Components\WebpUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class InstagramPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('permalink')
                        ->label(__('Post link'))
                        ->url()
                        ->required()
                        ->columnSpanFull()
                        ->placeholder(__('https://www.instagram.com/reel/…'))
                        ->helperText(__('Open the post on Instagram, tap Share, then Copy link.')),

                    WebpUpload::make('image_path')
                        ->label(__('Cover image'))
                        ->directory('instagram')
                        ->columnSpanFull()
                        // Instagram's CDN URLs expire, so the still is stored
                        // rather than hotlinked.
                        ->helperText(__('Screenshot or export the cover. Instagram\'s own image links expire, so it has to be uploaded.')),

                    Toggle::make('is_video')
                        ->label(__('It is a Reel'))
                        ->default(true)
                        ->helperText(__('Adds a play badge.')),

                    DateTimePicker::make('posted_at')->label(__('Posted on'))->default(now()),
                ]),

            Tabs::make('Caption')
                ->columnSpanFull()
                ->tabs(collect(config('amanelle.locales'))
                    ->map(fn (array $locale, string $code) => Tab::make($locale['name'])
                        ->schema([
                            Textarea::make("caption.{$code}")
                                ->label(__('Short caption'))
                                ->rows(2)
                                ->maxLength(160)
                                ->helperText(__('One line, shown over the image. Not the full post caption.')),
                        ]))
                    ->values()
                    ->all()),

            Section::make()
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')->label(__('Show on the homepage'))->default(true),
                    TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),
                ]),
        ]);
    }
}
