<?php

namespace App\Filament\Resources\IndustryResource\Pages;

use App\Filament\Resources\IndustryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Industry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditEntityProfile extends EditRecord
{
    protected static string $resource = IndustryResource::class;
}
