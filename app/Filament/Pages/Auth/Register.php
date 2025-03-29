<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms;
use Filament\Forms\Components\Select;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(User::class),

            Select::make('industry_id')
                ->label('Industry')
                ->options(Industry::pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->password()
                ->required()
                ->maxLength(255),
        ];
    }

    protected function handleRegistration(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'industry_id' => $data['industry_id'],
        ]);
    }
}
