<?php
namespace App\Filament\Resources\Accounts\Schemas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
class AccountForm { public static function configure(Schema $schema): Schema { return $schema->components([
    TextInput::make('account_number')->label('Account number')->required()->unique(ignoreRecord: true),
    TextInput::make('name')->label('Account name')->required(),
    Select::make('type')->options(['debit' => 'Debit account', 'credit' => 'Credit account'])->required(),
    Select::make('currency')->options(['USD' => 'USD', 'LBP' => 'LBP'])->required()->default('USD'),
    TextInput::make('opening_balance')->numeric()->default(0), TextInput::make('contact_name'), TextInput::make('phone'),
    Textarea::make('address'), Textarea::make('notes'), Toggle::make('is_active')->default(true),
]); } }
