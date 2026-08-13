<?php
namespace App\Filament\Resources\Suppliers\Schemas;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
class SupplierForm { public static function configure(Schema $schema): Schema { return $schema->components([
    TextInput::make('name')->required(), TextInput::make('account_number')->label('Account number')->required()->unique(ignoreRecord: true),
    TextInput::make('tax_number'), TextInput::make('contact_name'), TextInput::make('email')->email(), TextInput::make('phone'),
    Textarea::make('address'), Textarea::make('notes'), Toggle::make('is_active')->default(true),
]); } }
