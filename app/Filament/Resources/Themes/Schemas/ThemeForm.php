<?php

namespace App\Filament\Resources\Themes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label(__('Name'))->required()->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => blank($set('slug')) ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('effect')->options(['none' => __('None'), 'snow' => __('Snow'), 'lanterns' => __('Lanterns'), 'sheep' => __('Sheep'), 'stars' => __('Stars'), 'confetti' => __('Confetti')])->default('none')->native(false),
                Toggle::make('is_active')->label(__('Active theme'))->helperText(__('Activating this theme deactivates the previous theme.')),
                TextInput::make('greeting')->label(__('Seasonal greeting'))->columnSpanFull(),
                FileUpload::make('banner_image')->label(__('Banner image'))->image()->disk('public')->directory('themes')->columnSpanFull(),
            ]),
            Section::make(__('Colors'))->columns(5)->schema([
                ColorPicker::make('surface')->label(__('Surface'))->default('#faf7f2'),
                ColorPicker::make('surface_2')->label(__('Surface 2'))->default('#f2ece1'),
                ColorPicker::make('ink')->label(__('Text'))->default('#1a1612'),
                ColorPicker::make('accent')->label(__('Accent'))->default('#8c6a3a'),
                ColorPicker::make('accent_fill')->label(__('Accent fill'))->default('#c9a96e'),
                ColorPicker::make('accent_soft')->label(__('Accent soft'))->default('#e8d5a3'),
            ]),
        ]);
    }
}
