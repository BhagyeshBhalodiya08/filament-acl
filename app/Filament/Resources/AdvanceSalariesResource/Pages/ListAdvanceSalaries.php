<?php

namespace App\Filament\Resources\AdvanceSalariesResource\Pages;

use App\Filament\Resources\AdvanceSalariesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvanceSalaries extends ListRecords
{
    protected static string $resource = AdvanceSalariesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
