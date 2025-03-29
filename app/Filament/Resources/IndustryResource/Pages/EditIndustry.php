<?php

namespace App\Filament\Resources\IndustryResource\Pages;

use App\Filament\Resources\IndustryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

use App\Models\Industry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditIndustry extends EditTenantProfile
{
    protected static string $resource = IndustryResource::class;
  
    public static function getLabel(): string
    {
        return 'Edit your company';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
            ]);
    }
}
