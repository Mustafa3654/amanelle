<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('username')
            ->label(__('Username'))
            ->required()
            ->autocomplete('username');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
