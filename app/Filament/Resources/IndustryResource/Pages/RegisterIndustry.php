<?php

namespace App\Filament\Resources\IndustryResource\Pages;

use App\Filament\Resources\IndustryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Industry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;

class RegisterIndustry extends BaseRegisterTenant
{
    protected static string $resource = IndustryResource::class;

    public static function getLabel(): string
    {
        return 'Register Your Industry';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('number')
                    ->label('Contact Number')
                    ->maxLength(25),
                Textarea::make('address')
                    ->label('Address')
                    ->maxLength(255),
            ]);
    }

    protected function handleRegistration(array $data): Industry
    {
        /** @var Industry $entity */
        $entity = parent::handleRegistration($data);

        /** @var User $user */
        $user = auth()->user();

        // if ($user && $user->super_user === 'yes') {
            $user->industry()->attach($entity->id);
        // }

        return $entity;
    }
}
